FROM php:8.3-apache

# Extensiones necesarias: PDO para MySQL y sesiones.
RUN docker-php-ext-install pdo pdo_mysql

# Apache: reescritura de URLs (front controller) y variables de entorno.
RUN a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Toda la app apunta a index.php cuando la URL no es un archivo real.
RUN printf '%s\n' \
    '<Directory /var/www/html>' \
    '    Options -Indexes' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/render.conf \
    && a2enconf render

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
