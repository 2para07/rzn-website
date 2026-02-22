#!/bin/bash
# Set Apache to listen on Railway's PORT or default to 8080
export APACHE_RUN_PORT=${PORT:-8080}
sed -i "s/Listen 8080/Listen ${APACHE_RUN_PORT}/g" /etc/apache2/ports.conf.d/railway.conf
apache2-foreground
