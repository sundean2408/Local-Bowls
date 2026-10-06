# Local Bowls — Frontend

SPA Vue 3 + Vite untuk aplikasi Local Bowls. Lihat `README.md` di root
repository untuk penjelasan alur aplikasi dan cara menjalankan backend + frontend
bersamaan.

## Perintah

```bash
npm install       # install dependency
npm run dev       # jalankan dev server
npm run build     # build untuk produksi
npm run preview   # preview hasil build
```

## Konfigurasi

Salin `.env.example` ke `.env` dan atur `VITE_API_BASE_URL` sesuai alamat API
backend, termasuk suffix `/api`, contohnya `http://localhost:8000/api`.
Untuk hosting, atur variabel ini sebelum menjalankan `npm run build`; nilainya
akan disisipkan ke hasil build di `dist/`.
