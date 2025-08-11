
## Laravel Wallet
 
 I built a Dockerized Laravel 12 application with a React + Vite frontend.
 - I use React for Login and Register.
 - I use Blade for the Admin area.
 - The app follows an event‑driven architecture for scalability and flexibility.
 - Code style targets PHP 8.4.
 - Core functionality is covered by tests.
 
 ### Quick start
 ```bash
 chmod +x setup.sh
 ./setup.sh
 open http://localhost:8000
 ```
 This sets up everything with Docker. No local PHP/Node needed.
 
 ### What setup.sh does
 - Builds and starts containers via `docker compose up -d --build` from `compose.yaml`.
 - Creates `.env` (if missing) and normalizes: `APP_URL`, `DB_*`, `REDIS_HOST=redis`, `SESSION_DRIVER=database`.
 - Generates app key, ensures the sessions table exists.
 - Waits until MySQL is reachable, then runs migrations.
   - Seeds only if the database is empty (no users yet).
 - Creates the storage symlink and fixes permissions.
 - Installs frontend deps with `npm ci` (or `npm install`) inside the container.
 - Removes any stale `public/hot` and builds production assets with Vite.
 
 ### Frontend
 - Vite entrypoint: `resources/js/app.jsx`.
 - Blade root view: `resources/views/react.blade.php` with `<div id="root"></div>`.
 - Production assets are built into `public/build`.
 
 ### Tooling and code quality
 - Static analysis: PHPStan (see `phpstan.neon`). I have considered all warnings/issues.
 - Code style: Laravel Pint (see `pint.json`), PSR‑12 ruleset.
 - PHP style: PHP 8.4 features where appropriate.
 
 ### Wallet Task (reviewer commands)
 - Run static analysis
 ```bash
 docker compose exec php ./vendor/bin/phpstan analyse
 ```
 - Fix formatting issues
 ```bash
 docker compose exec php ./vendor/bin/pint
 ```
 - Check code formatting (recommended first step)
 ```bash
 docker compose exec php ./vendor/bin/pint --test
 ```
 - Run tests
 ```bash
 docker compose exec php php artisan test
 ```
 
  ## License
  
  The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
