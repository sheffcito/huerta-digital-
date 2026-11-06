# 🌿 La Huerta Digital 2.0
> **Sistema Distribuido en Contenedores con Almacenamiento Persistente en Disco Secundario VDI y Monitoreo en Tiempo Real**  
> *Caso Práctico Tecnológico para Productores Agrícolas: Don Pedro, Doña Rosa, Don Manuel y Doña Carmen.*

---

## 📋 Tabla de Contenidos
1. [Descripción General](#-descripción-general)
2. [Arquitectura de los 5 Servicios](#-arquitectura-de-los-5-servicios)
3. [Requisitos Previos de la Máquina Virtual](#-requisitos-previos-de-la-máquina-virtual)
4. [Configuración Crítica del Segundo Disco VDI (Paso a Paso)](#-configuración-crítica-del-segundo-disco-vdi-paso-a-paso)
5. [Despliegue de los Servicios con Docker Compose](#-despliegue-de-los-servicios-con-docker-compose)
6. [Guía de Acceso a Servicios](#-guía-de-acceso-a-servicios)
7. [Configuración de Monitoreo en Uptime Kuma 2](#-configuración-de-monitoreo-en-uptime-kuma-2)
8. [Pruebas de Validación y Seguridad](#-pruebas-de-validación-y-seguridad)
9. [Resolución de Problemas Frecuentes (Troubleshooting)](#-resolución-de-problemas-frecuentes-troubleshooting)

---

## 📖 Descripción General

La Huerta Digital 2.0 es una evolución tecnológica orientada a resolver las limitaciones de escalabilidad, almacenamiento y disponibilidad de la versión inicial. La plataforma permite a los agricultores catalogar sus cosechas en tiempo real, actualizar precios y stock, subir fotografías remotamente mediante FTP a un volumen desacoplado y supervisar la salud de toda la infraestructura mediante sondas automáticas.

Todo el ecosistema se ejecuta sobre una única máquina virtual con **Ubuntu Server**, vinculada a la red física del aula mediante un adaptador en **Modo Puente (Bridged)** para permitir la interacción concurrente de múltiples clientes.

---

## 🏗️ Arquitectura de los 5 Servicios

| Servicio | Contenedor | Imagen Base | Puertos Mapeados | Propósito Técnico |
| :--- | :--- | :--- | :--- | :--- |
| **web** | `huerta_web` | Custom (`php:8.2-apache`) | `80:80` | Catálogo dinámico responsive en PHP con PDO MySQL. Monta `/mnt/huerta_fotos/imagenes` en modo solo lectura (`:ro`). |
| **db** | `huerta_db` | `mysql:8.0` | `3306:3306` | Base de datos relacional transaccional. Carga `init.sql` con usuarios `huerta_user` y el usuario de auditoría `huerta_lector`. |
| **phpmyadmin** | `huerta_pma` | `phpmyadmin:latest` | `8080:80` | Interfaz gráfica web para administración y verificación de tablas y esquemas MySQL. |
| **ftp** | `huerta_ftp` | `fauria/vsftpd` | `21:21`<br>`21100-21110:21100-21110` | Servidor FTP en modo pasivo con acceso de escritura directo a la partición del segundo disco (`/mnt/huerta_fotos/imagenes`). |
| **uptime-kuma** | `huerta_kuma` | `louislam/uptime-kuma:2` | `3001:3001` | Panel de monitoreo proactivo con sondas continuas HTTP (web) y TCP (MySQL). |

---

## 💻 Requisitos Previos de la Máquina Virtual

1. **Hipervisor**: VirtualBox 7.x (o superior) / VMware Workstation.
2. **Sistema Operativo**: Ubuntu Server 22.04 LTS o 24.04 LTS x86_64.
3. **Configuración de Red**:
   - Adaptador 1: **Modo Puente (Bridged Adapter)** conectado a la interfaz activa del host (Ethernet / Wi-Fi del aula).
   - Modo promiscuo: Permitir todo / Denegar según política de red.
4. **Almacenamiento**:
   - Disco 1 (SISTEMA): VDI primario de 20-25 GB (donde reside el SO y Docker).
   - Disco 2 (DATOS FOTOS): **Segundo disco virtual VDI de 8 GB** creado y conectado al controlador SATA de la VM en frío.

---

## 💽 Configuración Crítica del Segundo Disco VDI (Paso a Paso)

El requerimiento exige que el disco secundario sobreviva a reinicios (`sudo reboot`) y apagados totales (`sudo poweroff`) sin cambiar su punto de montaje. Por ello, se usa **UUID en `/etc/fstab`**.

### 1. Identificar el nuevo disco
Una vez añadido el disco en VirtualBox y encendida la VM, ejecutar:
```bash
lsblk
```
*(Generalmente aparecerá como `/dev/sdb` con tamaño ~8G sin particiones).*

### 2. Particionar el disco
Crear una partición primaria tipo Linux:
```bash
sudo fdisk /dev/sdb
```
Secuencia de comandos dentro de `fdisk`:
- `n` (nueva partición)
- `p` (partición primaria)
- `1` (número de partición)
- Presionar `ENTER` dos veces para usar todo el tamaño del disco.
- `w` (escribir cambios y salir).

### 3. Formatear la partición en ext4
```bash
sudo mkfs.ext4 -L "HUERTA_FOTOS" /dev/sdb1
```

### 4. Obtener el Identificador Único Universal (UUID)
```bash
sudo blkid /dev/sdb1
```
*Salida esperada (ejemplo):*
```text
/dev/sdb1: LABEL="HUERTA_FOTOS" UUID="3f4a8b2c-91e2-45a7-9bc4-123456789abc" BLOCK_SIZE="4096" TYPE="ext4" PARTUUID="..."
```
Copie el valor que está entre comillas en `UUID="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"`.

### 5. Crear el punto de montaje permanente
```bash
sudo mkdir -p /mnt/huerta_fotos
```

### 6. Configurar `/etc/fstab`
Edite el archivo de tabla de sistemas de archivos del sistema:
```bash
sudo nano /etc/fstab
```
Agregue la siguiente línea al final del archivo (reemplace con el UUID obtenido en el paso 4):
```text
UUID=3f4a8b2c-91e2-45a7-9bc4-123456789abc  /mnt/huerta_fotos  ext4  defaults  0  2
```
*Guardar con `Ctrl+O`, confirmar con `ENTER` y salir con `Ctrl+X`.*

### 7. Montar y verificar
Pruebe la sintaxis de `/etc/fstab` sin reiniciar:
```bash
sudo mount -a
```
Compruebe que el disco está efectivamente montado:
```bash
findmnt /mnt/huerta_fotos
df -h /mnt/huerta_fotos
```

### 8. Crear la carpeta compartida y conceder permisos
```bash
sudo mkdir -p /mnt/huerta_fotos/imagenes
sudo chmod -R 777 /mnt/huerta_fotos/imagenes
```

*(Opcional: puede ejecutar automáticamente estos pasos con el script incluido en `./scripts/setup_disk.sh`).*

---

## 🚀 Despliegue de los Servicios con Docker Compose

### 1. Instalación Oficial de Docker CE y Docker Compose Plugin (Guía Oficial)
Ejecute en la terminal de Ubuntu Server los siguientes comandos para agregar el repositorio oficial de Docker y sus dependencias:
```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

echo \
"deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release; echo $VERSION_CODENAME) stable" \
| sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
sudo systemctl enable --now docker
sudo usermod -aG docker $USER
newgrp docker
```

### 2. Configuración de Hardening SSH y Firewall UFW
```bash
# Modificar el puerto SSH por defecto a 43435 en /etc/ssh/sshd_config
sudo sed -i 's/#Port 22/Port 43435/' /etc/ssh/sshd_config
sudo sed -i 's/Port 22/Port 43435/' /etc/ssh/sshd_config
sudo systemctl restart ssh

# Configurar reglas del firewall UFW
sudo ufw allow 43435/tcp comment "SSH Hardened"
sudo ufw allow 80/tcp comment "Catálogo Web"
sudo ufw allow 8080/tcp comment "phpMyAdmin"
sudo ufw allow 3306/tcp comment "MySQL Aula"
sudo ufw allow 3001/tcp comment "Uptime Kuma"
sudo ufw allow 21/tcp comment "FTP Control"
sudo ufw allow 21100:21110/tcp comment "FTP Pasivo"
sudo ufw --force enable
```

### 3. Clonar o copiar el proyecto a la VM
Copie la carpeta del proyecto a la VM de Ubuntu Server (por ejemplo en `~/nube` o `~/huerta-digital`):
```bash
cd ~/nube
```

### 2. Configurar la dirección IP real de la VM en `docker-compose.yml`
Obtenga la dirección IP asignada por la red del aula a la VM:
```bash
hostname -I | awk '{print $1}'
```
Edite `docker-compose.yml` y actualice la directiva `PASV_ADDRESS` del servicio `ftp`:
```yaml
    environment:
      FTP_USER: productor
      FTP_PASS: clave_ftp_segura
      PASV_ADDRESS: 192.168.1.50 # <- Reemplace con su IP de la VM (ej. 192.168.100.25)
      PASV_MIN_PORT: 21100
      PASV_MAX_PORT: 21110
```

### 3. Levantar los 5 contenedores
```bash
docker compose up -d --build
```

### 4. Comprobar que los 5 contenedores están activos (`Up`)
```bash
docker compose ps
```
*Salida esperada:*
```text
NAME          IMAGE                       COMMAND                  SERVICE       CREATED         STATUS         PORTS
huerta_db     mysql:8.0                   "docker-entrypoint.s…"   db            1 minute ago    Up 1 minute    0.0.0.0:3306->3306/tcp
huerta_ftp    fauria/vsftpd               "/usr/sbin/run-vsftp…"   ftp           1 minute ago    Up 1 minute    0.0.0.0:21->21/tcp, 0.0.0.0:21100-21110->21100-21110/tcp
huerta_kuma   louislam/uptime-kuma:2      "/usr/bin/entry.sh"      uptime-kuma   1 minute ago    Up 1 minute    0.0.0.0:3001->3001/tcp
huerta_pma    phpmyadmin:latest           "/docker-entrypoint.…"   phpmyadmin    1 minute ago    Up 1 minute    0.0.0.0:8080->80/tcp
huerta_web    nube-web                    "docker-php-entrypoi…"   web           1 minute ago    Up 1 minute    0.0.0.0:80->80/tcp
```

---

## 🌐 Guía de Acceso a Servicios

Reemplace `<IP_VM>` por la IP real de su máquina virtual en modo puente:

1. **Catálogo Web**:
   - URL: `http://<IP_VM>/`
   - Función: Visualización dinámica de productos, filtrado por agricultor, consulta de stock y lectura de fotos desde el disco secundario.

2. **Panel phpMyAdmin**:
   - URL: `http://<IP_VM>:8080/`
   - Servidor: `db`
   - Usuario: `huerta_user` / Contraseña: `huerta_password` (o `root` / `root_password_segura`).

3. **Uptime Kuma 2 (Monitoreo)**:
   - URL: `http://<IP_VM>:3001/`
   - Primer acceso: Crear usuario administrador inicial (ej. `admin` / `Admin_Aula_2026`).

4. **Acceso FTP para Productores (Subida de Imágenes)**:
   - Host: `<IP_VM>`
   - Puerto: `21`
   - Modo de Transferencia: **Pasivo (Passive)**
   - Usuario: `productor`
   - Contraseña: `clave_ftp_segura`
   - Directorio remoto: `imagenes/`

5. **Acceso a MySQL con Usuario de Solo Lectura del Aula**:
   - Desde cualquier PC del aula con cliente MySQL:
     ```bash
     mysql -u huerta_lector -p -h <IP_VM> HUERTA_DIGITAL
     ```
   - Contraseña: `Lector_Aula_2026`

---

## 📊 Configuración de Monitoreo en Uptime Kuma 2

Una vez creada la cuenta de administración en `http://<IP_VM>:3001`:

### Monitor 1: Sonda HTTP al Catálogo Web
- **Tipo de Monitor**: `HTTP(s)`
- **Nombre**: `Huerta Web - Apache/PHP`
- **URL**: `http://huerta_web:80` *(o `http://192.168.1.X:80`)*
- **Intervalo de Latido**: `20 segundos`
- **Código de Estado Aceptado**: `200`

### Monitor 2: Sonda TCP a Base de Datos MySQL
- **Tipo de Monitor**: `Puerto TCP`
- **Nombre**: `Huerta DB - MySQL 8.0`
- **Hostname**: `db` *(o la IP de la VM)*
- **Puerto**: `3306`
- **Intervalo de Latido**: `20 segundos`

---

## 🧪 Pruebas de Validación y Seguridad

### 1. Prueba de Resiliencia a Reinicios (Prueba de Fuego)
```bash
sudo reboot
```
Al reiniciar la VM, compruebe:
1. `findmnt /mnt/huerta_fotos` (el disco debe estar montado automáticamente por UUID).
2. `docker compose ps` (los 5 contenedores deben estar arriba gracias a `restart: unless-stopped`).
3. El catálogo web debe seguir mostrando datos y fotos sin intervención manual.

### 2. Prueba de Seguridad: Usuario `huerta_lector`
Verificar que el usuario del aula solo puede consultar datos y no modificarlos ni borrarlos:
```bash
# Esta consulta DEBE funcionar:
SELECT * FROM productos;

# Esta consulta DEBE fallar con error de permisos:
DELETE FROM productos WHERE id = 1;
# Salida esperada: ERROR 1142 (42000): DELETE command denied to user 'huerta_lector'@'...'
```

### 3. Prueba de Simulación de Caída en Uptime Kuma
Detener intencionalmente el servicio de base de datos:
```bash
docker compose stop db
```
- Observe en Uptime Kuma cómo la sonda TCP del puerto 3306 cambia inmediatamente a **ROJO (DOWN)** y el catálogo web muestra la alerta de fallo de conexión.
- Reactivar el servicio:
```bash
docker compose start db
```
- En menos de 20 segundos, la sonda vuelve a **VERDE (UP)**.

---

## 🛠️ Resolución de Problemas Frecuentes (Troubleshooting)

1. **Error al conectar por FTP (Timeout en listado de directorios `MLSD` o `PASV`)**:
   - **Causa**: La variable `PASV_ADDRESS` en `docker-compose.yml` tiene una IP incorrecta.
   - **Solución**: Verifique la IP con `hostname -I`, modifique el valor y aplique con `docker compose up -d`.

2. **La base de datos no contiene los productos tras levantar**:
   - **Causa**: MySQL solo ejecuta `./mysql/init.sql` la primera vez que se crea el volumen `db_data`.
   - **Solución**: Reiniciar limpiando el volumen:
     ```bash
     docker compose down -v
     docker compose up -d --build
     ```

3. **Las imágenes subidas por FTP no se ven en la web**:
   - Verifique que se hayan subido con el nombre exacto (`papas.jpg`, `tomates.jpg`, etc.) respetando minúsculas.
   - Verifique permisos en el host: `sudo chmod -R 777 /mnt/huerta_fotos/imagenes`.
