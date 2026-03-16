# Use PHP-FPM with Alpine Linux (no Apache MPM conflicts!)
# Rebuilt: 2026-03-16 with debugging and explicit Nginx+FPM setup
FROM php:8.2-fpm-alpine

# Install Alpine dependencies
RUN apk add --no-cache \
    nginx \
    postgresql-client \
    libpq-dev \
    oniguruma-dev \
    openssl-dev \
    curl \
    git \
    zip \
    && docker-php-ext-install pdo_pgsql mbstring \
    && rm -rf /var/cache/apk/*

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy all project files
COPY . .

# Debug: Show what was copied
RUN echo "=== Build Context Debug ===" && \
    pwd && \
    echo "Files in current directory:" && \
    ls -la && \
    echo "" && \
    echo "=== End Debug ===" && \
    echo ""

# Install PHP dependencies with proper error handling
RUN if [ -f composer.json ]; then \
        echo "Found composer.json - installing dependencies..." && \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    else \
        echo "composer.json not found - this may be a build context issue" && \
        echo "Listing files in current directory:" && \
        ls -la; \
    fi

# Configure Nginx to serve Laravel
RUN cat > /etc/nginx/nginx.conf << 'EOF'
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
        listen 80 default_server;
        server_name _;
        root /var/www/html/public;
        
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
        
        index index.html index.php;
        
        charset utf-8;
        
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
        
        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }
        
        error_page 404 /index.php;
        
        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }
        
        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
EOF

# Set Laravel permissions and create missing directories
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod +x /var/www/html/artisan 2>/dev/null || echo "Note: artisan file may not exist yet"

# Create entrypoint script with both Nginx and PHP-FPM
RUN cat > /usr/local/bin/docker-entrypoint.sh << 'EOF'
#!/bin/sh

echo "======================================"
echo "Flowventory Container Starting"
echo "======================================"
echo "This is: PHP-FPM + Nginx"
echo ""

# Ensure we're in the correct working directory
cd /var/www/html
echo "Working directory: $(pwd)"
echo "Listing Laravel files:"
ls -la | head -20
echo ""

# Handle PORT environment variable for Railway
PORT=${PORT:-8080}
echo "Container PORT: $PORT"

# Update Nginx to listen on the specified port
echo "Configuring Nginx to listen on port $PORT..."
sed -i "s/listen 80/listen ${PORT}/g" /etc/nginx/nginx.conf

# Create .env if it doesn't exist
if [ ! -f .env ] && [ -f .env.example ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Wait for PHP-FPM to start
sleep 2

# Verify PHP-FPM is running
if pgrep -x "php-fpm" > /dev/null; then
    echo "✓ PHP-FPM is running"
else
    echo "✗ PHP-FPM failed to start - attempting direct start"
    php-fpm
fi

# Start Nginx in foreground
echo "Starting Nginx on port $PORT..."
echo "======================================"
echo "Application is ready"
echo "======================================"
exec nginx -g 'daemon off;'
EOF

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 8080

# Set environment
ENV PORT=8080

# Start with entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
