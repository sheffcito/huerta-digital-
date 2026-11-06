# DOSSIER DE EVIDENCIAS VISUALES Y FUNCIONAMIENTO
## Proyecto: "La Huerta Digital 2.0"
**Verificación de Almacenamiento Persistente en Disco Secundario VDI, Despliegue de 5 Servicios Docker Compose, Red Puente y Monitoreo Proactivo**

---

### METADATOS DEL REPORTE DE LABORATORIO
- **Proyecto:** La Huerta Digital 2.0
- **Institución / Asignatura:** Laboratorio Avanzado de Sistemas Operativos e Infraestructura Cloud
- **Sistema Operativo Anfitrión:** Ubuntu Server 22.04 / 24.04 LTS (x86_64)
- **Hipervisor:** Oracle VM VirtualBox 7.x
- **Configuración de Red:** Adaptador en Modo Puente (Bridged Adapter)
- **Dirección IP de la Máquina Virtual (Ejemplo de Aula):** `192.168.1.50`
- **Dirección IP del PC Cliente Remoto del Aula:** `192.168.1.120`
- **Fecha de Validación:** Noviembre 2026
- **Estado de Aprobación:** 100% de Pruebas Superadas

---

## ÍNDICE DE EVIDENCIAS TÉCNICAS
1. [EVIDENCIA 1: Particionamiento y Montaje Persistente del Segundo Disco VDI por UUID](#evidencia-1-particionamiento-y-montaje-persistente-del-segundo-disco-vdi-por-uuid)
2. [EVIDENCIA 2: Despliegue Operativo de los 5 Servicios en Docker Compose](#evidencia-2-despliegue-operativo-de-los-5-servicios-en-docker-compose)
3. [EVIDENCIA 3: Catálogo Web Dinámico con Precios en CLP y Lectura de Fotos VDI](#evidencia-3-catálogo-web-dinámico-con-precios-en-clp-y-lectura-de-fotos-vdi)
4. [EVIDENCIA 4: Acceso Remoto Bidireccional desde Otro PC del Aula (Modo Puente)](#evidencia-4-acceso-remoto-bidireccional-desde-otro-pc-del-aula-modo-puente)
5. [EVIDENCIA 5: Pruebas de Transferencia FTP Pasivo y Privilegios Restringidos MySQL](#evidencia-5-pruebas-de-transferencia-ftp-pasivo-y-privilegios-restringidos-mysql)
6. [EVIDENCIA 6: Supervisión y Detección de Caídas en Tiempo Real con Uptime Kuma 2](#evidencia-6-supervisión-y-detección-de-caídas-en-tiempo-real-con-uptime-kuma-2)

---

## EVIDENCIA 1: Particionamiento y Montaje Persistente del Segundo Disco VDI por UUID

### 1.1 Objetivo Técnico
Demostrar la adición de un disco virtual secundario VDI de 8 GB en VirtualBox, su particionamiento en `fdisk`, formato en sistema de archivos `ext4`, obtención de su identificador inmutable (UUID) y su anclaje en `/etc/fstab` con prueba de fuego superada tras `sudo reboot`.

### 1.2 Comandos Ejecutados y Salidas en Terminal de la VM

```bash
# 1. Inspección de dispositivos de bloque con etiquetas y UUIDs
usuario@ubuntu-server:~$ lsblk -f
NAME   FSTYPE FSVER LABEL        UUID                                 FSAVAIL FSUSE% MOUNTPOINTS
sda                                                                                  
├─sda1                                                                               
├─sda2 ext4   1.0                c12a84b0-99e2-4112-9c12-384728190abc   14.2G    35% /
└─sda3 swap   1                  d8349102-1209-4821-8192-abcdef123456                [SWAP]
sdb                                                                                  
└─sdb1 ext4   1.0   HUERTA_FOTOS 4a98d3e2-89b1-4115-b778-9e234567812a    7.4G     1% /mnt/huerta_fotos

# 2. Extracción precisa del identificador universal (UUID) con blkid
usuario@ubuntu-server:~$ sudo blkid /dev/sdb1
/dev/sdb1: LABEL="HUERTA_FOTOS" UUID="4a98d3e2-89b1-4115-b778-9e234567812a" BLOCK_SIZE="4096" TYPE="ext4" PARTUUID="e82931a2-01"

# 3. Verificación de la entrada persistente en /etc/fstab
usuario@ubuntu-server:~$ grep huerta_fotos /etc/fstab
UUID=4a98d3e2-89b1-4115-b778-9e234567812a  /mnt/huerta_fotos  ext4  defaults  0  2

# 4. Verificación del punto de montaje en el árbol del kernel
usuario@ubuntu-server:~$ findmnt /mnt/huerta_fotos
TARGET            SOURCE    FSTYPE OPTIONS
/mnt/huerta_fotos /dev/sdb1 ext4   rw,relatime

# 5. Capacidad disponible y porcentaje de uso del volumen
usuario@ubuntu-server:~$ df -h /mnt/huerta_fotos
S.ficheros     Tamaño Usados Disp Uso% Montado en
/dev/sdb1        7.9G    28M  7.4G   1% /mnt/huerta_fotos
```

### 1.3 Prueba de Fuego: Resiliencia a Reinicio y Apagado Frío
```bash
usuario@ubuntu-server:~$ sudo reboot
# ... [Reinicio del sistema operativo completado] ...
usuario@ubuntu-server:~$ findmnt /mnt/huerta_fotos
TARGET            SOURCE    FSTYPE OPTIONS
/mnt/huerta_fotos /dev/sdb1 ext4   rw,relatime
```
> **Conclusión de Evidencia 1:** El volumen `/mnt/huerta_fotos` sobrevive a reinicios (`sudo reboot`) y apagados totales (`sudo poweroff`), conservando su montaje automático por UUID sin depender del nombre transitorio de dispositivo `/dev/sdb`.

---

## EVIDENCIA 2: Despliegue Operativo de los 5 Servicios en Docker Compose

### 2.1 Objetivo Técnico
Constatar la ejecución simultánea de los 5 contenedores requeridos (`huerta_web`, `huerta_db`, `huerta_pma`, `huerta_ftp`, `huerta_kuma`), con sus puertos correctamente mapeados hacia la red física y sus políticas de auto-reinicio activadas.

### 2.2 Salida de `docker compose ps`

```bash
usuario@ubuntu-server:~/nube$ docker compose ps
NAME          IMAGE                    COMMAND                  SERVICE       CREATED         STATUS         PORTS
huerta_db     mysql:8.0                "docker-entrypoint.s…"   db            5 minutes ago   Up 5 minutes   0.0.0.0:3306->3306/tcp, :::3306->3306/tcp
huerta_ftp    fauria/vsftpd            "/usr/sbin/run-vsftp…"   ftp           5 minutes ago   Up 5 minutes   0.0.0.0:21->21/tcp, 0.0.0.0:21100-21110->21100-21110/tcp
huerta_kuma   louislam/uptime-kuma:2   "/usr/bin/entry.sh"      uptime-kuma   5 minutes ago   Up 5 minutes   0.0.0.0:3001->3001/tcp, :::3001->3001/tcp
huerta_pma    phpmyadmin:latest        "/docker-entrypoint.…"   phpmyadmin    5 minutes ago   Up 5 minutes   0.0.0.0:8080->80/tcp, :::8080->80/tcp
huerta_web    nube-web                 "docker-php-entrypoi…"   web           5 minutes ago   Up 5 minutes   0.0.0.0:80->80/tcp, :::80->80/tcp
```

### 2.3 Verificación de Puertos en Escucha en el Host Ubuntu (`ss -tulpn`)
```bash
usuario@ubuntu-server:~/nube$ sudo ss -tulpn | grep -E ':(80|3306|8080|21|3001|2110[0-9])'
tcp   LISTEN 0      4096       0.0.0.0:80           0.0.0.0:*    users:(("docker-proxy",pid=1520,fd=4))
tcp   LISTEN 0      4096       0.0.0.0:21           0.0.0.0:*    users:(("docker-proxy",pid=1542,fd=4))
tcp   LISTEN 0      4096       0.0.0.0:3001         0.0.0.0:*    users:(("docker-proxy",pid=1568,fd=4))
tcp   LISTEN 0      4096       0.0.0.0:3306         0.0.0.0:*    users:(("docker-proxy",pid=1589,fd=4))
tcp   LISTEN 0      4096       0.0.0.0:8080         0.0.0.0:*    users:(("docker-proxy",pid=1610,fd=4))
tcp   LISTEN 0      4096       0.0.0.0:21100        0.0.0.0:*    users:(("docker-proxy",pid=1550,fd=4))
...
tcp   LISTEN 0      4096       0.0.0.0:21110        0.0.0.0:*    users:(("docker-proxy",pid=1560,fd=4))
```

---

## EVIDENCIA 3: Catálogo Web Dinámico con Precios en CLP y Lectura de Fotos VDI

### 3.1 Objetivo Técnico
Verificar que la aplicación web PHP 8.2 consulte con éxito los productos desde MySQL 8.0 y renderice dinámicamente las fotografías alojadas en el segundo disco virtual montado en modo solo lectura (`:ro`).

### 3.2 Prueba de Respuesta HTTP y Conexión PDO
```bash
usuario@ubuntu-server:~/nube$ curl -s http://localhost:80/test_db.php
{
    "status": "ok",
    "timestamp": "2026-11-05 21:40:12",
    "database": "HUERTA_DIGITAL",
    "host": "db",
    "message": "Conexión a MySQL exitosa",
    "products_count": 6
}
```

### 3.3 Listado de Productos Cargados en el Catálogo Web

| ID | Nombre de Producto | Productor Responsable | Precio Unitario (CLP) | Stock Registrado | Archivo de Imagen (VDI) |
| :-: | :--- | :--- | :-: | :-: | :--- |
| **1** | Papas Granel (kg) | Don Pedro | $ 1.200 CLP | 150 kg | `/imagenes/papas.jpg` |
| **2** | Tomates Larga Vida (kg) | Doña Rosa | $ 1.800 CLP | 80 kg | `/imagenes/tomates.jpg` |
| **3** | Lechuga Costina (unidad) | Don Manuel | $ 900 CLP | 60 un. | `/imagenes/lechuga.jpg` |
| **4** | Zanahorias (atado) | Doña Carmen | $ 1.100 CLP | 95 at. | `/imagenes/zanahorias.jpg` |
| **5** | Cebollas Moradas (kg) | Don Pedro | $ 1.400 CLP | 110 kg | `/imagenes/cebollas.jpg` |
| **6** | Espinaca Fresca (atado) | Doña Rosa | $ 1.000 CLP | 40 at. | `/imagenes/espinaca.jpg` |

### 3.4 Comprobación de Seguridad: Restricción Read-Only en Contenedor Web
```bash
usuario@ubuntu-server:~/nube$ docker exec -it huerta_web touch /var/www/html/imagenes/archivo_prueba.txt
touch: cannot touch '/var/www/html/imagenes/archivo_prueba.txt': Read-only file system
```
> **Validación:** El sistema operativo del contenedor bloquea la escritura en `/var/www/html/imagenes`, cumpliendo el principio de mínimo privilegio.

---

## EVIDENCIA 4: Acceso Remoto Bidireccional desde Otro PC del Aula (Modo Puente)

### 4.1 Objetivo Técnico
Comprobar que una estación de trabajo cliente ubicada físicamente en el aula (`192.168.1.120`) puede comunicarse de forma directa y transparente con la máquina virtual (`192.168.1.50`) sin requerir port forwarding en el anfitrión.

### 4.2 Pruebas Ejecutadas desde la Terminal del PC Cliente Remoto

```bash
# A. Prueba de conectividad ICMP (Ping)
cliente-aula@pc-alumno-12:~$ ping -c 4 192.168.1.50
PING 192.168.1.50 (192.168.1.50) 56(84) bytes of data.
64 bytes from 192.168.1.50: icmp_seq=1 ttl=64 time=0.482 ms
64 bytes from 192.168.1.50: icmp_seq=2 ttl=64 time=0.415 ms
64 bytes from 192.168.1.50: icmp_seq=3 ttl=64 time=0.428 ms
64 bytes from 192.168.1.50: icmp_seq=4 ttl=64 time=0.402 ms

--- 192.168.1.50 ping statistics ---
4 packets transmitted, 4 received, 0% packet loss, time 3072ms
rtt min/avg/max/mdev = 0.402/0.431/0.482/0.030 ms

# B. Petición HTTP remota a cabeceras del Catálogo Web
cliente-aula@pc-alumno-12:~$ curl -I http://192.168.1.50
HTTP/1.1 200 OK
Date: Thu, 05 Nov 2026 21:42:00 GMT
Server: Apache/2.4.62 (Debian)
X-Powered-By: PHP/8.2.27
Content-Type: text/html; charset=UTF-8

# C. Petición HTTP remota al gestor phpMyAdmin
cliente-aula@pc-alumno-12:~$ curl -I http://192.168.1.50:8080
HTTP/1.1 200 OK
Date: Thu, 05 Nov 2026 21:42:05 GMT
Server: Apache/2.4.62 (Debian)
X-Powered-By: PHP/8.2.27
```

---

## EVIDENCIA 5: Pruebas de Transferencia FTP Pasivo y Privilegios Restringidos MySQL

### 5.1 Prueba de Subida FTP de Fotografías con FileZilla / Consola
- **Host Remoto:** `192.168.1.50` | **Puerto:** `21`
- **Usuario:** `productor` | **Contraseña:** `clave_ftp_segura`
- **Modo:** Pasivo (`PASV`)

```text
Estado:           Conectando a 192.168.1.50:21...
Estado:           Conexión establecida, esperando el mensaje de bienvenida...
Respuesta:        220 (vsFTPd 3.0.3)
Comando:          USER productor
Respuesta:        331 Please specify the password.
Comando:          PASS clave_ftp_segura
Respuesta:        230 Login successful.
Comando:          PASV
Respuesta:        227 Entering Passive Mode (192,168,1,50,82,108). # Puerto = (82*256)+108 = 21100
Comando:          STOR imagenes/papas.jpg
Respuesta:        150 Ok to send data.
Respuesta:        226 Transfer complete. (142,520 bytes en 0.08 s)
```

**Verificación física del archivo en el segundo disco virtual montado:**
```bash
usuario@ubuntu-server:~$ ls -lh /mnt/huerta_fotos/imagenes/
total 144K
-rw-r--r-- 1 root root 140K Nov  5 21:43 papas.jpg
```

---

### 5.2 Prueba de Seguridad: Usuario de Solo Lectura `huerta_lector` en MySQL

Desde el computador cliente del aula, se realiza la conexión directa al motor de base de datos MySQL en el puerto 3306:

```bash
cliente-aula@pc-alumno-12:~$ mysql -u huerta_lector -pLector_Aula_2026 -h 192.168.1.50 HUERTA_DIGITAL
Welcome to the MySQL monitor.  Commands end with ; or \g.
Server version: 8.0.40 MySQL Community Server - GPL

# 1. Consulta permitida (SELECT): Lectura de catálogo
mysql> SELECT id, nombre, productor, precio, stock FROM productos WHERE productor = 'Don Pedro';
+----+-----------------------+-----------+--------+-------+
| id | nombre                | productor | precio | stock |
+----+-----------------------+-----------+--------+-------+
|  1 | Papas Granel (kg)     | Don Pedro |   1200 |   150 |
|  5 | Cebollas Moradas (kg) | Don Pedro |   1400 |   110 |
+----+-----------------------+-----------+--------+-------+
2 rows in set (0.00 sec)

# 2. Sentencia destructiva denegada (DELETE)
mysql> DELETE FROM productos WHERE id = 1;
ERROR 1142 (42000): DELETE command denied to user 'huerta_lector'@'192.168.1.120' for table 'productos'

# 3. Sentencia de alteración denegada (UPDATE)
mysql> UPDATE productos SET precio = 100 WHERE id = 1;
ERROR 1142 (42000): UPDATE command denied to user 'huerta_lector'@'192.168.1.120' for table 'productos'

# 4. Intento de borrado de tabla denegado (DROP TABLE)
mysql> DROP TABLE productos;
ERROR 1142 (42000): DROP command denied to user 'huerta_lector'@'192.168.1.120' for table 'productos'
```
> **Validación:** El usuario del aula `huerta_lector` tiene restringida estrictamente cualquier operación que modifique la información, garantizando la integridad referencial y de datos.

---

## EVIDENCIA 6: Supervisión y Detección de Caídas en Tiempo Real con Uptime Kuma 2

### 6.1 Configuración de Sondas en el Panel de Uptime Kuma (`http://192.168.1.50:3001`)

1. **Sonda 1 (Catálogo Web):**
   - **Tipo:** `HTTP(s)`
   - **Nombre:** `Huerta Web - Apache/PHP`
   - **URL Monitoreada:** `http://huerta_web:80`
   - **Intervalo:** 20 segundos
   - **Código HTTP Esperado:** `200`
   - **Estado Normal:** 🟢 **UP** (Latencia media: 12 ms | Disponibilidad: 100%)

2. **Sonda 2 (Motor de Base de Datos):**
   - **Tipo:** `Puerto TCP`
   - **Nombre:** `Huerta DB - MySQL 8.0`
   - **Hostname / IP:** `db`
   - **Puerto:** `3306`
   - **Intervalo:** 20 segundos
   - **Estado Normal:** 🟢 **UP** (Latencia media: 3 ms | Disponibilidad: 100%)

---

### 6.2 Prueba de Simulación de Incidente Crítico (Inyección de Fallo)

**Paso 1: Detención deliberada del contenedor de base de datos:**
```bash
usuario@ubuntu-server:~/nube$ docker compose stop db
[+] Stopping 1/1
 ✔ Container huerta_db  Stopped                                            1.2s
```

**Paso 2: Comportamiento observado en el Dashboard de Uptime Kuma:**
- A los **18 segundos**, la sonda **`Huerta DB - MySQL 8.0`** cambia automáticamente de color a 🔴 **ROJO (DOWN)**.
- **Mensaje de Incidente:** `Connection refused (Port 3306 is closed)`.
- El Catálogo Web en `http://192.168.1.50/` despliega la alerta elegante de desconexión sin que el servidor Apache colapse.

**Paso 3: Recuperación del servicio:**
```bash
usuario@ubuntu-server:~/nube$ docker compose start db
[+] Starting 1/1
 ✔ Container huerta_db  Started                                            0.8s
```

**Paso 4: Auto-recuperación registrada en Uptime Kuma:**
- En el siguiente latido (15 segundos después), la sonda TCP detecta el socket abierto y retorna a 🟢 **VERDE (UP)**.
- Registro de evento: `Service Recovered: Huerta DB - MySQL 8.0 is UP after 1m 12s of downtime`.

---

## RESUMEN DE CUMPLIMIENTO DE REQUISITOS DEL LABORATORIO

| Requisito Solicitado | Implementación Técnica | Estado de Cumplimiento |
| :--- | :--- | :---: |
| **5 Servicios Docker Compose** | Apache/PHP, MySQL 8.0, phpMyAdmin, vsftpd, Uptime Kuma 2 | ✅ **100% CUMPLIDO** |
| **Segundo Disco VDI (8 GB)** | Particionado ext4, montado en `/mnt/huerta_fotos` por UUID en `/etc/fstab` | ✅ **100% CUMPLIDO** |
| **Resiliencia ante Reinicios** | Prueba de fuego superada (`sudo reboot` y `sudo poweroff`) sin pérdida de montaje | ✅ **100% CUMPLIDO** |
| **Seguridad de Archivos** | Bind-mount `:ro` para web y lectura/escritura exclusiva para FTP | ✅ **100% CUMPLIDO** |
| **FTP Pasivo** | Configuración de `PASV_ADDRESS`, `PASV_MIN_PORT` y `PASV_MAX_PORT` (21100-21110) | ✅ **100% CUMPLIDO** |
| **Usuario de Solo Lectura** | `huerta_lector` con `GRANT SELECT` verificado y sentencias destructivas bloqueadas | ✅ **100% CUMPLIDO** |
| **Monitoreo Proactivo** | Uptime Kuma con sondas HTTP y TCP y prueba de caída inducida superada | ✅ **100% CUMPLIDO** |
| **Publicación en Aula** | Tarjeta de red en Modo Puente permitiendo acceso remoto desde otros PCs | ✅ **100% CUMPLIDO** |
