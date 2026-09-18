FROM php:8.2-apache

# Install PostgreSQL drivers and system packages
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copy code and enable Apache mod_rewrite
COPY . /var/www/html/
RUN a2enmod rewrite

EXPOSE 80