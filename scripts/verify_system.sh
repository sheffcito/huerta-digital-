#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE VERIFICACIÓN DE EVIDENCIAS: La Huerta Digital 2.0
# ==============================================================================

echo "========================================================"
echo "    VERIFICACIÓN DEL SISTEMA - LA HUERTA DIGITAL 2.0    "
echo "========================================================"
date
echo ""

echo ">>> 1. ESTADO DE DISCOS Y MONTAJE POR UUID (/etc/fstab)"
echo "--------------------------------------------------------"
lsblk -o NAME,SIZE,FSTYPE,UUID,MOUNTPOINT
echo ""
echo "Entrada en /etc/fstab para huerta_fotos:"
grep huerta_fotos /etc/fstab || echo "ADVERTENCIA: No se encontró huerta_fotos en /etc/fstab"
echo ""
echo "Espacio en disco del volumen montado:"
df -h /mnt/huerta_fotos

echo ""
echo ">>> 2. ESTADO DE LOS 5 CONTENEDORES DOCKER"
echo "--------------------------------------------------------"
docker compose ps

echo ""
echo ">>> 3. PRUEBA DE CONEXIÓN HTTP LOCAL AL CATÁLOGO WEB (:80)"
echo "--------------------------------------------------------"
curl -I -s http://localhost:80 | head -n 5

echo ""
echo ">>> 4. PRUEBA DE SCRIPT DE PRUEBA DE BASE DE DATOS"
echo "--------------------------------------------------------"
curl -s http://localhost:80/test_db.php
echo ""

echo ""
echo ">>> 5. PRUEBA DE USUARIO DE SOLO LECTURA (huerta_lector) EN MYSQL (:3306)"
echo "--------------------------------------------------------"
if command -v mysql &> /dev/null; then
    mysql -u huerta_lector -pLector_Aula_2026 -h 127.0.0.1 -e "SELECT id, nombre, productor, precio, stock FROM HUERTA_DIGITAL.productos;"
else
    docker exec -i huerta_db mysql -u huerta_lector -pLector_Aula_2026 HUERTA_DIGITAL -e "SELECT id, nombre, productor, precio, stock FROM productos;"
fi

echo ""
echo ">>> 6. PUERTOS EN ESCUCHA EN EL HOST"
echo "--------------------------------------------------------"
ss -tulpn | grep -E ':(80|3306|8080|21|3001|2110[0-9])'

echo ""
echo "========================================================"
echo "          FIN DE LA VERIFICACIÓN DE SISTEMA             "
echo "========================================================"
