FROM php:8.2-apache

# Install PostgreSQL system dependencies and PHP database drivers
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Resolve MPM conflicts by explicitly disabling event/worker and forcing mpm_prefork
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite

# Copy application files into Apache root directory
COPY . /var/www/html/

# Reconfigure Apache to listen on port 8080 (matching Railway domain settings)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]