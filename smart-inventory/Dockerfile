# FRESH BUILD - NO CACHE
# Simple PHP-FPM + Nginx for Laravel
FROM php:8.2-fpm-alpine

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
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
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

# Startup script - NO ARTISAN CALLS
RUN cat > /start.sh << 'BASH_EOF'
#!/bin/sh
cd /var/www/html
php-fpm -D
exec nginx -g 'daemon off;'
BASH_EOF

RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
