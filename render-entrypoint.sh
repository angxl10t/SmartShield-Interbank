#!/bin/bash

echo "🚀 INICIANDO DESPLIEGUE EN RENDER..."

# --- PASO 1: Iniciar MySQL (MariaDB) ---
echo "🗄️ Iniciando servicio MySQL..."
service mariadb start

# Esperar unos segundos a que MySQL arranque bien
sleep 5

# --- PASO 2: Configurar Base de Datos ---
echo "⚙️ Configurando Base de Datos..."
# Crear usuario root y base de datos
mysql -e "CREATE DATABASE IF NOT EXISTS interbank;"
mysql -e "CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;"
mysql -e "FLUSH PRIVILEGES;"

# Importar tu archivo SQL (que está en la raíz)
if [ -f "/var/www/html/interbank.sql" ]; then
    echo "📥 Importando interbank.sql..."
    mysql interbank < /var/www/html/interbank.sql
    
    # --- TRUCO: Asegurar la contraseña '123456' ---
    # Esto sobreescribe el hash para garantizar que puedas entrar
    echo "🔑 Reseteando contraseña de usuario prueba..."
    mysql interbank -e "UPDATE usuarios SET password_hash = '\$2y\$10\$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa' WHERE id_usuario = 1;"
else
    echo "⚠️ NO SE ENCONTRÓ interbank.sql en la raíz"
fi

# --- PASO 3: Iniciar Apache (Backend PHP) ---
echo "🌐 Iniciando Apache..."
service apache2 start

# --- PASO 4: Entrenar e Iniciar Python (ML) ---
# Esto va al final como pediste.
echo "🧠 Entrenando modelos ML iniciales..."
# Vamos a la carpeta y ejecutamos el setup rápido (modificado para no interactuar)
cd /var/www/html/ml-system

# Ejecutamos el servidor ML en primer plano (esto mantiene el contenedor vivo)
echo "🤖 ARRANCANDO SERVIDOR PYTHON (ML)..."
python3 ml_api_server.py