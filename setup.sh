#!/bin/bash
set -euo pipefail

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

# Note: We will check service readiness later right before running migrations

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

# Normalize environment defaults for this docker setup
echo "🧭 Normalizing .env values (APP_URL, REDIS_HOST, DB_*, SESSION_DRIVER)..."
# Use GNU sed inside the php container to avoid macOS/BSD sed -i differences
docker compose exec -T php bash -lc "\
  if [ -f .env ]; then \
    if grep -q '^APP_URL=' .env; then \
      sed -i 's|^APP_URL=.*|APP_URL=http://localhost:8000|' .env; \
    else \
      echo 'APP_URL=http://localhost:8000' >> .env; \
    fi; \
    if grep -q '^REDIS_HOST=' .env; then \
      sed -i 's|^REDIS_HOST=.*|REDIS_HOST=redis|' .env; \
    else \
      echo 'REDIS_HOST=redis' >> .env; \
    fi; \
    if grep -q '^DB_HOST=' .env; then \
      sed -i 's|^DB_HOST=.*|DB_HOST=mysql|' .env; \
    else \
      echo 'DB_HOST=mysql' >> .env; \
    fi; \
    if grep -q '^DB_PORT=' .env; then \
      sed -i 's|^DB_PORT=.*|DB_PORT=3306|' .env; \
    else \
      echo 'DB_PORT=3306' >> .env; \
    fi; \
    if grep -q '^DB_DATABASE=' .env; then \
      sed -i 's|^DB_DATABASE=.*|DB_DATABASE=laravel_wallet|' .env; \
    else \
      echo 'DB_DATABASE=laravel_wallet' >> .env; \
    fi; \
    if grep -q '^DB_USERNAME=' .env; then \
      sed -i 's|^DB_USERNAME=.*|DB_USERNAME=laravel|' .env; \
    else \
      echo 'DB_USERNAME=laravel' >> .env; \
    fi; \
    if grep -q '^DB_PASSWORD=' .env; then \
      sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=laravelpassword|' .env; \
    else \
      echo 'DB_PASSWORD=laravelpassword' >> .env; \
    fi; \
    if grep -q '^SESSION_DRIVER=' .env; then \
      sed -i 's|^SESSION_DRIVER=.*|SESSION_DRIVER=database|' .env; \
    else \
      echo 'SESSION_DRIVER=database' >> .env; \
    fi; \
  fi"

# Generate key and run migrations
echo "🔑 Generating application key..."
docker compose exec -T php php artisan key:generate

echo "🧾 Preparing sessions table (SESSION_DRIVER=database) if missing..."
# Check and possibly create inside the container for consistent tooling
docker compose exec -T php bash -lc '
  if ls database/migrations/*create_sessions_table*.php >/dev/null 2>&1; then
    echo "   Sessions table migration already exists. Skipping generation."
  else
    php artisan session:table || echo "   session:table reported it already exists; continuing."
  fi
'

echo "⏳ Waiting for MySQL to be ready (ping)..."
# Use mysqladmin ping against the mysql container; allow up to ~120s
ATTEMPTS=60
for i in $(seq 1 $ATTEMPTS); do
  if docker compose exec -T mysql mysqladmin ping -h 127.0.0.1 -prootpassword --silent; then
    echo "✅ MySQL is accepting connections."
    break
  fi
  echo "   Waiting... ($i/$ATTEMPTS)"
  sleep 2
done

if ! docker compose exec -T mysql mysqladmin ping -h 127.0.0.1 -prootpassword --silent; then
  echo "❌ MySQL is not reachable after waiting. Check logs: 'docker compose logs mysql' and verify ports/volume."
  exit 1
fi

echo "🔎 Verifying application DB credentials..."
if ! docker compose exec -T mysql sh -lc "mysql -u laravel -plaravelpassword -e 'SELECT 1' laravel_wallet" >/dev/null 2>&1; then
  echo "❌ App DB credentials failed. The named volume may contain old credentials/users."
  echo "   Fix: run 'docker compose down -v' to reset the DB volume, then re-run ./setup.sh"
  exit 1
fi

echo "🗄️  Running database migrations (with seed if database is empty)..."
# Determine if we should seed: check if any users already exist
SHOULD_SEED=$(docker compose exec -T php bash -lc "\
  php -r 'require \"vendor/autoload.php\"; \$app = require \"bootstrap/app.php\"; \$app->make(\\Illuminate\\Contracts\\Console\\Kernel::class); echo \\App\\Models\\User::query()->exists() ? \"0\" : \"1\";'" 2>/dev/null || echo "0")

if [ "$SHOULD_SEED" = "1" ]; then
  echo "   No users found. Seeding sample data..."
  docker compose exec -T php php artisan migrate --seed
else
  echo "   Data detected. Running migrations without seeding..."
  docker compose exec -T php php artisan migrate
fi

echo "🔗 Creating storage symlink (force to be idempotent)..."
docker compose exec -T php php artisan storage:link --force

# Fix permissions if necessary (create .npm directory if missing and chown)
docker compose exec -T php bash -lc "\
  mkdir -p /var/www/html/.npm || true && \
  chown -R www-data:www-data /var/www/html/.npm || true && \
  chown -R www-data:www-data storage bootstrap/cache || true"

# Install frontend dependencies
echo "📥 Installing frontend dependencies (npm ci if lockfile exists)..."
docker compose exec -T -e npm_config_cache=/var/www/html/.npm php bash -lc "if [ -f package-lock.json ]; then npm ci --no-audit --no-fund; else npm install --no-audit --no-fund; fi"

# Build frontend assets
echo "🧱 Building frontend assets..."
# Ensure we are not proxying to Vite dev server
docker compose exec -T php bash -lc "rm -f public/hot"
docker compose exec -T -e npm_config_cache=/var/www/html/.npm php npm run build

echo ""
echo "🎉 Setup complete!"
echo "🌐 Your Laravel 12 application is running at: http://localhost:8000"
