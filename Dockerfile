FROM php:8.3-apache

# Extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli mbstring

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar archivos del proyecto
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Script de arranque: ajusta el puerto de Apache al $PORT que Railway asigna
COPY docker-start.sh /docker-start.sh
RUN chmod +x /docker-start.sh

CMD ["/docker-start.sh"]
