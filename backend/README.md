# Local Bowls — Backend

REST API Laravel 10 + Sanctum untuk aplikasi Local Bowls. Lihat `README.md`
di root repository untuk penjelasan alur aplikasi dan cara menjalankan
backend + frontend bersamaan.

## Perintah

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Struktur Utama

- `app/Http/Controllers` — logic per resource (menu, kategori, meja, pesanan,
  pembayaran, laporan, user, auth)
- `app/Models` — model Eloquent
- `database/migrations` & `database/seeders` — skema & data awal
- `routes/api.php` — daftar endpoint API
