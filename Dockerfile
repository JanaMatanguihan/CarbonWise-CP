FROM php:8.2-apache

# Install PostgreSQL extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Use Apache's built-in ENV substitution for port configuration
ENV PORT=8080
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE 8080

CMD ["apache2-foreground"]