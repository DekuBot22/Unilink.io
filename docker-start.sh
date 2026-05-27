#!/bin/bash
# Railway inyecta $PORT dinamicamente; Apache debe escuchar en ese puerto
PORT=${PORT:-80}
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-enabled/000-default.conf
exec apache2-foreground
