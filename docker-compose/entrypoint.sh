#!/bin/bash
set -e

echo "🚀 Starting AIRobot CRM..."

# Wait for database to be ready
echo "⏳ Waiting for database..."
until php artisan db:show 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "✅ Database is up!"

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force --isolated

# Clear and cache configurations
echo "🔧 Optimizing application..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

# Create storage link if it doesn't exist
echo "🔗 Creating storage link..."
php artisan storage:link || true

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "✨ Application ready! Starting services..."

# Start supervisor
exec supervisord -c /etc/supervisor.d/supervisord.ini
