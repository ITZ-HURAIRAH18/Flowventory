#!/bin/bash
set -e

# Get the port from environment, default to 8000
PORT=${PORT:-8000}

# Update Apache configuration to listen on the specified port
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf

# Run Laravel migrations if needed
php artisan migrate --force

# Start Apache
exec apache2-foreground
