# Use official PHP image with Apache
FROM php:8.2-apache

# Set working directory FIRST before any COPY commands
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    git \
    curl \
    libonig-dev \
    libpq-dev \
    postgresql-client \
    && docker-php-ext-install pdo_pgsql mbstring zip \
    && docker-php-ext-enable pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Clean up Apache MPM modules - remove all and enable only prefork
RUN find /etc/apache2/mods-enabled -name "mpm_*" -delete && \
    find /etc/apache2/mods-enabled -name "mpm_*" -type l -delete && \
    a2dismod mpm_event 2>/dev/null || true && \
    a2dismod mpm_worker 2>/dev/null || true && \
    a2dismod mpm_itk 2>/dev/null || true && \
    a2enmod mpm_prefork

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy all project files (assumes build context is smart-inventory directory)
COPY . .

# Install PHP dependencies
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Configure Apache to serve Laravel public directory
RUN cat > /etc/apache2/sites-available/000-default.conf << 'EOF'
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Verify only one MPM is enabled and fix any conflicts
RUN ls /etc/apache2/mods-enabled/mpm_* 2>/dev/null | xargs -r rm && \
    a2enmod mpm_prefork

# Set Laravel permissions and create missing directories
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create entrypoint script
RUN cat > /usr/local/bin/docker-entrypoint.sh << 'EOF'
#!/bin/bash
set -e

# CRITICAL: Fix Apache MPM conflict before starting Apache
echo "Cleaning up Apache MPM modules..."
find /etc/apache2/mods-enabled -name "mpm_*" -type f -delete 2>/dev/null || true
find /etc/apache2/mods-enabled -name "mpm_*" -type l -delete 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_*.conf /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || true

# Disable all MPM modules
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true  
a2dismod mpm_itk 2>/dev/null || true

# Enable only prefork
echo "Enabling mpm_prefork..."
a2enmod mpm_prefork

# Verify configuration before starting
echo "Testing Apache configuration..."
apache2ctl configtest || (echo "Apache config test failed!" && exit 1)

# Handle PORT environment variable for Railway
PORT=${PORT:-8000}
if [ "$PORT" != "80" ]; then
    sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
fi

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
EOF

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 8080

# Set environment
ENV PORT=8080

# Start with entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]