FROM php:8.3-cli

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli mbstring

# Copiar archivos del proyecto
COPY . /var/www/html/

WORKDIR /var/www/html

# Iniciar el servidor web integrado de PHP en el puerto $PORT
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-80} -t /var/www/html"]
