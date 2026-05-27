FROM php:8.3-apache

# Dependencias del sistema requeridas por las extensiones PHP
RUN apt-get update && apt-get install -y \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli mbstring

# Habilitar mod_rewrite y permitir .htaccess
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Forzar solo mpm_prefork DESPUES de todos los a2enmod
# (elimina todos, luego crea solo prefork con ln -s forzado)
RUN cd /etc/apache2/mods-enabled \
    && rm -f mpm_event.conf mpm_event.load mpm_worker.conf mpm_worker.load mpm_prefork.conf mpm_prefork.load \
    && ln -s ../mods-available/mpm_prefork.load mpm_prefork.load \
    && ln -s ../mods-available/mpm_prefork.conf mpm_prefork.conf \
    && echo "=== MPM after fix ===" \
    && ls mpm_*.* 2>&1

# Copiar archivos del proyecto
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Script de arranque con diagnostico
RUN printf '#!/bin/bash\necho "=== MPM en arranque ==="\nls /etc/apache2/mods-enabled/mpm_* 2>&1\necho "======================"\nPORT=${PORT:-80}\nsed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf\nsed -i "s/<VirtualHost \\*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-enabled/000-default.conf\nexec apache2-foreground\n' > /start.sh \
    && chmod +x /start.sh

CMD ["/start.sh"]
