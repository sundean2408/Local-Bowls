# Local Bowls

Local Bowls is a restaurant ordering and operations application. The project
contains a Vue 3/Vite customer and staff frontend and a Laravel 10 REST API.

## Requirements

- PHP 8.1 or newer with the Laravel-required extensions
- Composer
- MySQL
- Node.js and npm

## Run locally (Windows / PowerShell)

1. Configure the backend:

   ```powershell
   Set-Location backend
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   Update the database settings in `backend/.env`, then run:

   ```powershell
   php artisan migrate --seed
   php artisan serve
   ```

   Local seeding creates demo accounts. Do not use those credentials in a
   production deployment.

2. In a second terminal, configure and run the frontend:

   ```powershell
   Set-Location frontend
   Copy-Item .env.example .env
   npm install
   npm run dev
   ```

   The frontend reads `VITE_API_BASE_URL`; its local default is
   `http://localhost:8000/api`.

## Production deployment

1. Set backend environment variables on the server. Use `APP_ENV=production`,
   `APP_DEBUG=false`, the correct `APP_URL`, database credentials, and
   `DB_TIMEZONE=+00:00`. Set `CORS_ALLOWED_ORIGINS` to the exact frontend
   origin(s), without a trailing slash.
2. Provide a unique `ADMIN_USERNAME` and a strong `ADMIN_PASSWORD` of at least
   16 characters before running the production seeder. Production seeding
   creates only this administrator; the local demo accounts are not created.
3. Run backend deployment commands from `backend`:

   ```sh
   php artisan config:clear
   php artisan migrate --force
   php artisan db:seed --force
   php artisan config:cache
   ```

4. Set `VITE_API_BASE_URL` to the production API URL before building the
   frontend. Build and deploy the generated `frontend/dist` directory:

   ```sh
   npm ci
   npm run build
   ```

The backend and database store timestamps in UTC. The frontend formats user
facing dates in `Asia/Jakarta`, so customer and staff devices display the same
local time regardless of their own timezone setting.

## Main directories

- `frontend/src/views` — customer and staff pages
- `frontend/src/services` — frontend API client
- `backend/routes/api.php` — REST API routes
- `backend/app/Http/Controllers` — API controllers
- `backend/database/migrations` and `backend/database/seeders` — database schema
  and initial data

## Validation

```powershell
Set-Location frontend
npm run build

Set-Location ../backend
php artisan test
```
