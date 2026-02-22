# Use official PHP image with Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html

# Set Apache to listen on PORT environment variable
ENV APACHE_RUN_PORT 8080
RUN sed -i 's/Listen 80/Listen ${APACHE_RUN_PORT:-8080}/g' /etc/apache2/ports.conf

# Start Apache
CMD ["apache2-foreground"]
