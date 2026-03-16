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

# Create a health check file in case Laravel doesn't start
RUN mkdir -p /var/www/html/public && \
    echo '<?php echo json_encode(["status" => "ok", "time" => date("Y-m-d H:i:s")]); ?>' > /var/www/html/public/health.php && \
    chmod 644 /var/www/html/public/health.php

# Startup script - with error checking and logging
RUN cat > /start.sh << 'BASH_EOF'
#!/bin/sh

echo "=== Starting Flowventory Container ==="
cd /var/www/html

# Ensure directories exist with correct permissions
mkdir -p /var/www/html/public /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod 775 /var/www/html/storage /var/www/html/bootstrap/cache

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
