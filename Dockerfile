FROM php:8.2-cli

# Install PostgreSQL system dependencies and PHP database drivers
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Set working directory to the web root
WORKDIR /var/www/html

# Copy application files
COPY . .

EXPOSE 8080

# Start PHP built-in web server on port 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]