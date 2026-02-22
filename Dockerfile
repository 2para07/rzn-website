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

# Configure Apache to listen on dynamic port
RUN echo "Listen ${PORT:-8080}" > /etc/apache2/ports.conf.d/railway.conf

# Basic health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8080}/ || exit 1

CMD ["apache2-foreground"]
