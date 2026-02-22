# PHP 8.1 Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Copy application files to Apache root
COPY . /var/www/html/

# Enable mod_rewrite
RUN a2enmod rewrite

# Configure Apache for Railway - set port 8080
RUN echo "Listen 8080" > /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/g' /etc/apache2/sites-enabled/000-default.conf

# Use apache foreground
CMD ["apache2-foreground"]
