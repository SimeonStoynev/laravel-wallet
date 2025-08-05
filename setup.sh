#!/bin/bash
set -e

echo "🚀 Laravel 12 + PHP 8.4 Docker Setup"
echo "====================================="

# Check dependencies
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "❌ Docker Compose is not installed or accessible."
    exit 1
fi

echo "✅ Docker and Docker Compose are available"

# Build and start containers
echo "🏗️  Building and starting containers..."
docker compose up -d --build

# Wait for containers
echo "⏳ Waiting for containers to be ready..."
sleep 15

# Install Laravel 12
if [ ! -f "composer.json" ]; then
    echo "📦 Installing Laravel 12..."
    docker compose exec -T php composer create-project laravel/laravel:^12.0 temp
    docker compose exec -T php bash -c "mv temp/* temp/.* . 2>/dev/null || true"
    docker compose exec -T php rm -rf temp
else
    echo "📦 Installing composer dependencies..."
    docker compose exec -T php composer install
fi

# Setup environment
echo "⚙️  Setting up environment..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Generate key and run migrations
echo "🔑 Generating application key..."
docker compose exec -T php php artisan key:generate

echo "🗄️  Running database migrations..."
docker compose exec -T php php artisan migrate

# Fix permissions if necessary (create .npm directory if missing and chown)
docker compose exec php bash -c "mkdir -p /var/www/html/.npm || true && chown -R www-data:www-data /var/www/html/.npm || true"

# Install frontend dependencies
docker compose exec php npm install

# Build frontend assets
docker compose exec php npm run build

echo ""
echo "🎉 Setup complete!"
echo "🌐 Your Laravel 12 application is running at: http://localhost:8000"
