#!/bin/bash

echo "🚀 INICIANDO DESPLIEGUE EN RENDER..."

# --- PASO 1: Iniciar MySQL (MariaDB) ---
echo "🗄️ Iniciando servicio MySQL..."
service mariadb start

# Esperar a que arranque
sleep 5

# --- PASO 2: Configurar Base de Datos ---
echo "⚙️ Configurando Base de Datos..."

# 1. Crear la base de datos (ESTO FALTABA)
mysql -e "CREATE DATABASE IF NOT EXISTS interbank;"

# 2. Configurar usuario root para localhost y 127.0.0.1
mysql -e "CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;"

mysql -e "CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;"

mysql -e "FLUSH PRIVILEGES;"

# Importar tu archivo SQL
if [ -f "/var/www/html/interbank.sql" ]; then
    echo "📥 Importando interbank.sql..."
    # Importamos usando la base de datos que acabamos de crear
    mysql interbank < /var/www/html/interbank.sql
    
    echo "🔑 Reseteando contraseña de usuario prueba..."
    mysql interbank -e "UPDATE usuarios SET password_hash = '\$2y\$10\$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa' WHERE id_usuario = 1;"
else
    echo "⚠️ NO SE ENCONTRÓ interbank.sql en la raíz"
fi

# --- PASO 3: Iniciar Apache ---
echo "🌐 Iniciando Apache..."
service apache2 start

# --- PASO 4: Iniciar Python (ML) ---
echo "🧠 Entrenando modelos ML iniciales..."
cd /var/www/html/ml-system

echo "🤖 ARRANCANDO SERVIDOR PYTHON (ML)..."
python3 ml_api_server.py