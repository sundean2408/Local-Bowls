# Local Bowls — Backend

REST API Laravel 10 + Sanctum untuk aplikasi Local Bowls. Lihat `README.md`
di root repository untuk penjelasan alur aplikasi dan cara menjalankan
backend + frontend bersamaan.

## Menjalankan lokal (Windows / PowerShell)

```powershell
cd backend
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Perbarui pengaturan koneksi database pada `.env` sebelum menjalankan migrasi.
Seeder lokal membuat akun demo untuk pengembangan saja.

## Produksi

Atur `APP_ENV=production`, `APP_DEBUG=false`, `DB_TIMEZONE=+00:00`, domain
`APP_URL`, dan `CORS_ALLOWED_ORIGINS` pada environment server. Sebelum
menjalankan `php artisan db:seed --force`, isi `ADMIN_USERNAME` dan
`ADMIN_PASSWORD` yang unik (minimal 16 karakter). Seeder produksi hanya
membuat akun administrator tersebut, bukan akun demo lokal.

```sh
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
```

Timestamp disimpan di UTC; zona waktu tampilan pengguna diatur frontend ke
`Asia/Jakarta`.

## Struktur Utama

- `app/Http/Controllers` — logic per resource (menu, kategori, meja, pesanan,
  pembayaran, laporan, user, auth)
- `app/Models` — model Eloquent
- `database/migrations` & `database/seeders` — skema & data awal
- `routes/api.php` — daftar endpoint API
