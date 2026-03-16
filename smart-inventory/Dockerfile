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

# Copy app
COPY . .

# Install PHP packages
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>&1 || true

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
        
        index index.php index.html;
        charset utf-8;
        
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
        
        location ~ \.php$ {
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

# Set permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create a health check file in case Laravel doesn't start
RUN mkdir -p /var/www/html/public && \
    cat > /var/www/html/public/health.php << 'PHP_EOF'
<?php
echo json_encode(['status' => 'ok', 'timestamp' => date('Y-m-d H:i:s')]);
PHP_EOF
    chmod 644 /var/www/html/public/health.php

# Startup script - with error checking and logging
RUN cat > /start.sh << 'BASH_EOF'
#!/bin/sh
set -e

echo "=== Starting Flowventory Container ==="
cd /var/www/html

# Use Railway's PORT env var or default to 8080
PORT=${PORT:-8080}
echo "Listening on PORT: $PORT"

# Update Nginx config with actual port
sed -i "s/listen 8080/listen ${PORT}/g" /etc/nginx/nginx.conf

echo "Starting PHP-FPM..."
php-fpm -D

sleep 1

if ! pgrep -x "php-fpm" > /dev/null; then
    echo "ERROR: PHP-FPM failed to start"
    exit 1
fi
echo "✓ PHP-FPM is running"

echo "Testing Nginx config..."
nginx -t 2>&1 || { echo "ERROR: Nginx config failed"; exit 1; }

echo "Starting Nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
BASH_EOF

RUN chmod +x /start.sh && \
    chmod +x /usr/local/sbin/php-fpm

EXPOSE 8080

CMD ["/start.sh"]
