#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE AUTOMATIZACIÓN Y GUÍA: MONTAJE SEGUNDO DISCO VDI POR UUID
# Proyecto: La Huerta Digital 2.0
# ==============================================================================
set -e

echo "=== [1/6] Identificando dispositivos de almacenamiento disponibles ==="
lsblk -f

DISK="/dev/sdb"
PARTITION="/dev/sdb1"
MOUNT_POINT="/mnt/huerta_fotos"

if [ ! -b "$DISK" ]; then
    echo "ERROR: El disco $DISK no fue detectado. Asegúrese de agregar el segundo disco VDI en VirtualBox."
    exit 1
fi

echo "=== [2/6] Verificando si existe la partición $PARTITION ==="
if [ ! -b "$PARTITION" ]; then
    echo "Creando tabla de particiones MBR y partición primaria en $DISK..."
    echo -e "o\nn\np\n1\n\n\nw" | sudo fdisk "$DISK"
    sudo partprobe "$DISK"
    sleep 2
    echo "Formateando $PARTITION en sistema de archivos ext4..."
    sudo mkfs.ext4 -L "HUERTA_FOTOS" "$PARTITION"
else
    echo "La partición $PARTITION ya existe."
fi

echo "=== [3/6] Obteniendo UUID de la partición ==="
UUID=$(sudo blkid -s UUID -o value "$PARTITION")
echo "UUID detectado: $UUID"

echo "=== [4/6] Creando punto de montaje $MOUNT_POINT ==="
sudo mkdir -p "$MOUNT_POINT"

echo "=== [5/6] Configurando montaje permanente en /etc/fstab ==="
FSTAB_LINE="UUID=$UUID  $MOUNT_POINT  ext4  defaults  0  2"

if grep -q "$UUID" /etc/fstab; then
    echo "El UUID ya se encuentra registrado en /etc/fstab."
else
    echo "Agregando entrada a /etc/fstab:"
    echo "$FSTAB_LINE" | sudo tee -a /etc/fstab
fi

echo "Probando montaje con mount -a..."
sudo mount -a

echo "=== [6/6] Creando estructura de carpetas y permisos para FTP/Web ==="
sudo mkdir -p "$MOUNT_POINT/imagenes"
# Otorgar permisos para que el contenedor vsftpd pueda escribir y el web pueda leer
sudo chmod -R 777 "$MOUNT_POINT/imagenes"

echo "=== Verificación Final ==="
findmnt "$MOUNT_POINT"
df -h "$MOUNT_POINT"
echo "¡Configuración de disco secundario persistente completada con éxito!"
