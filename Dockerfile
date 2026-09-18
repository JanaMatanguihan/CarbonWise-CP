FROM php:8.2-apache

# Install PostgreSQL system dependencies and PHP database drivers
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Copy application files into the default Apache web root
COPY . /var/www/html/

# Enable Apache mod_rewrite for custom routing
RUN a2enmod rewrite

# Configure Apache to listen on Railway's dynamic $PORT variable
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE 80

# Start Apache in the foreground (prevents MPM module crashes)
CMD ["apache2-foreground"]