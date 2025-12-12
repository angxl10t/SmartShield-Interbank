# Usamos PHP con Apache como base
FROM php:8.1-apache

# 1. Instalar Python, MySQL Server (MariaDB) y dependencias
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    mariadb-server \
    default-libmysqlclient-dev\
    git \
    unzip

# 2. Instalar extensiones PHP necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 3. Instalar librerías de Python
# (Copiamos requirements.txt si existe, si no instalamos manual)
COPY requirements.txt /tmp/
RUN pip3 install --break-system-packages -r /tmp/requirements.txt || \
    pip3 install --break-system-packages pandas scikit-learn mysql-connector-python flask flask-cors numpy joblib

# 4. Copiar todo el código al servidor
COPY . /var/www/html/

# 5. Configurar permisos y Apache
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 6. Copiar el script de arranque (lo crearemos en el paso 2)
COPY render-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 7. Exponer el puerto 80 (Render usa este por defecto)
EXPOSE 80

# 8. Ejecutar el script maestro al iniciar
CMD ["/usr/local/bin/entrypoint.sh"]