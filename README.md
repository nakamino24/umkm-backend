# UMKM Backend (Laravel)

Backend API untuk manajemen produk, order, auth, dan dashboard UMKM.

## Prasyarat

- PHP 8.2+
- Composer
- SQLite (default) atau MySQL

## Setup cepat

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

API berjalan di `http://localhost:8000`.

## Endpoint penting

- `GET /api/health` → health check backend
- `POST /api/auth/register` → register user (primary)
- `POST /api/auth/login` → login user (primary)
- `GET /api/auth/me` → profil user login
- `POST /api/auth/logout` → logout

Endpoint yang membutuhkan autentikasi menggunakan `auth:sanctum` token.

Alias lama tetap tersedia untuk kompatibilitas: `/api/register`, `/api/login`, `/api/user`, `/api/logout`.

## Konfigurasi frontend + CORS

Sesuaikan nilai berikut di `.env` supaya frontend dan backend sinkron:

- `APP_URL=http://localhost:8000`
- `FRONTEND_URL=http://localhost:5173`
- `SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173`
- `CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173`

## Testing

```bash
php artisan test
```
