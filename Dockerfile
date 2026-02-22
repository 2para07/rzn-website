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

# Configure Apache to listen on port
RUN mkdir -p /etc/apache2/ports.conf.d/ && \
    echo "Listen 8080" > /etc/apache2/ports.conf.d/railway.conf

CMD ["apache2-foreground"]
