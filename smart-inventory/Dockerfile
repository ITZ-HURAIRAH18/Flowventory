# EMERGENCY FRESH BUILD 
# Force complete cache bust by using explicit PHP version tag
FROM php:8.2.17-fpm-alpine3.19

LABEL version="3.0-final" description="Clean Flowventory Laravel deployment"

# Install required packages
RUN apk add --no-cache nginx postgresql-client libpq-dev oniguruma-dev openssl-dev curl git zip && \
    docker-php-ext-install pdo_pgsql mbstring && \
    rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy app from smart-inventory context (since context is root)
COPY smart-inventory/ /var/www/html/

# DEBUG: List what was copied
RUN echo "=== Files copied into container ===" && \
    pwd && \
    ls -la | head -20 && \
    echo "Looking for composer files..." && \
    ls -la composer.* 2>&1 || echo "composer.* not found"

# Install PHP packages - MUST SUCCEED
RUN echo "Installing Composer dependencies..." && \
    if [ ! -f composer.json ]; then \
        echo "ERROR: composer.json not found!"; \
        echo "Files in current directory:"; \
        ls -la; \
        exit 1; \
    fi && \
    composer install --no-dev --optimize-autoloader --no-interaction && \
    if [ ! -f vendor/autoload.php ]; then \
        echo "ERROR: vendor/autoload.php not created!"; \
        exit 1; \
    fi && \
    echo "✓ Composer dependencies installed successfully" && \
    ls -la vendor/autoload.php

# Create Laravel public/index.php explicitly (guarantee it exists)
RUN mkdir -p /var/www/html/public && \
    cat > /var/www/html/public/index.php << 'LARAVEL_INDEX_EOF'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
LARAVEL_INDEX_EOF

# Nginx config
RUN cat > /etc/nginx/nginx.conf << 'NGINX_EOF'
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    
    access_log /var/log/nginx/access.log main;
    
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;
    
    server {
        listen 8080 default_server;
        server_name _;
        root /var/www/html/public;
        
        index index.php index.html index.htm;
        charset utf-8;
        
        error_page 403 /index.php;
        error_page 404 /index.php;
        
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
        
        location ~ /health\.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index health.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
        
        location ~ \.php$ {
            try_files $uri =404;
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            include fastcgi_params;
        }
        
        location ~ /\. {
            deny all;
        }
    }
}
NGINX_EOF

# Set permissions - CRITICAL for 403 fix
RUN mkdir -p /var/www/html/public && \
    mkdir -p /var/www/html/storage && \
    mkdir -p /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/storage && \
    chmod -R 775 /var/www/html/bootstrap/cache && \
    find /var/www/html/public -type f -exec chmod 644 {} \; && \
    find /var/www/html/public -type d -exec chmod 755 {} \;

# Create a fallback index.php in case Laravel's doesn't exist
RUN echo '<?php if (file_exists("/var/www/html/public/index.php")) { include "/var/www/html/public/index.php"; } else { phpinfo(); } ?>' > /var/www/html/public/index.php.fallback

# Create a health check file in case Laravel doesn't start
RUN mkdir -p /var/www/html/public && \
    echo '<?php echo json_encode(["status" => "ok", "time" => date("Y-m-d H:i:s")]); ?>' > /var/www/html/public/health.php && \
    chmod 644 /var/www/html/public/health.php

# Startup script - with error checking and logging
RUN cat > /start.sh << 'BASH_EOF'
#!/bin/sh

echo "=== Starting Flowventory Container ==="
cd /var/www/html

# Create .env if missing
if [ ! -f /var/www/html/.env ]; then
    echo "Creating .env from .env.example..."
    if [ -f /var/www/html/.env.example ]; then
        cp /var/www/html/.env.example /var/www/html/.env
    fi
fi

# Ensure directories exist with correct permissions
mkdir -p /var/www/html/public /var/www/html/storage /var/www/html/bootstrap/cache

# Check actual Laravel index.php
if [ -f /var/www/html/public/index.php ]; then
    INDEX_SIZE=$(wc -c < /var/www/html/public/index.php)
    echo "✓ Laravel public/index.php exists (${INDEX_SIZE} bytes)"
else
    echo "ERROR: public/index.php still missing!"
fi

# Set permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod 775 /var/www/html/storage /var/www/html/bootstrap/cache
chmod 644 /var/www/html/public/*.php 2>/dev/null || true
find /var/www/html/public -type d -exec chmod 755 {} \; 2>/dev/null || true

# CRITICAL: Verify vendor exists
if [ ! -d /var/www/html/vendor ]; then
    echo "ERROR: vendor directory not found!"
    ls -la /var/www/html/ | head -20
    exit 1
fi
echo "✓ Vendor directory exists"

# Railway target port
PORT=4000
echo "Listening on PORT: $PORT"

# Update Nginx config with actual port
sed -i "s/listen 8080/listen ${PORT}/g" /etc/nginx/nginx.conf

echo "Starting PHP-FPM..."
php-fpm -D

# Give PHP-FPM time to start
sleep 2

echo "Starting Nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
BASH_EOF

RUN chmod +x /start.sh && \
    chmod +x /usr/local/sbin/php-fpm

EXPOSE 4000

ENV PORT=4000

CMD ["/start.sh"]
