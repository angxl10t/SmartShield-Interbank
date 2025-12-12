#!/bin/bash

echo "🚀 INICIANDO DESPLIEGUE EN RENDER..."

# 1. Iniciar MySQL
echo "🗄️ Iniciando servicio MySQL..."
service mariadb start
sleep 5

# 2. Configurar Base de Datos y Usuarios
echo "⚙️ Configurando Base de Datos..."

# Crear la base de datos
mysql -e "CREATE DATABASE IF NOT EXISTS interbank;"

# --- CAMBIO IMPORTANTE: Crear un usuario que NO sea root ---
# Creamos el usuario 'admin_db' con contraseña '123456'
mysql -e "CREATE USER IF NOT EXISTS 'admin_db'@'%' IDENTIFIED BY '123456';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'admin_db'@'%' WITH GRANT OPTION;"
mysql -e "FLUSH PRIVILEGES;"
# -----------------------------------------------------------

# Importar tablas
if [ -f "/var/www/html/interbank.sql" ]; then
    echo "📥 Importando interbank.sql..."
    mysql interbank < /var/www/html/interbank.sql
    
    echo "🔑 Reseteando contraseña de usuario del login..."
    mysql interbank -e "UPDATE usuarios SET password_hash = '\$2y\$10\$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa' WHERE id_usuario = 1;"
else
    echo "⚠️ NO SE ENCONTRÓ interbank.sql en la raíz"
fi

# 3. Iniciar Apache
echo "🌐 Iniciando Apache..."
service apache2 start

# 4. Iniciar Python
echo "🧠 Entrenando modelos ML iniciales..."
cd /var/www/html/ml-system
echo "🤖 ARRANCANDO SERVIDOR PYTHON (ML)..."
python3 ml_api_server.py