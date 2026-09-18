FROM php:8.2-apache

# Install PostgreSQL system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Copy application code into Apache web directory
COPY . /var/www/html/

# Enable Apache mod_rewrite module
RUN a2enmod rewrite

# Configure Apache to listen on 8080 by default
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

ENV PORT=8080
EXPOSE 8080

CMD ["apache2-foreground"]