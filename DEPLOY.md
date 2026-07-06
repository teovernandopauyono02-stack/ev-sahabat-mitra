# EV Sahabat — Panduan Deployment

## 1. Prasyarat Hosting
- PHP >= 8.2
- MySQL / MariaDB
- Composer
- Node.js 18+ & npm
- Ekstensi PHP: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo_mysql, tokenxml, xml, curl, gd

## 2. Upload Project

```bash
# Opsi A — Clone dari git
git clone https://github.com/your-user/ev-sahabat.git
cd ev-sahabat

# Opsi B — Upload manual via FTP/cPanel
# Upload seluruh folder project (kecuali node_modules, vendor, .env)
```

## 3. Environment Setup

```bash
cp .env.example .env
# EDIT .env — isi kredensial berikut:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://domain-anda.com
#   DB_DATABASE=nama_database
#   DB_USERNAME=user_database
#   DB_PASSWORD=password_database
#   MAIL_* — konfigurasi email

# Generate APP_KEY
php artisan key:generate
```

## 4. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

## 5. Database

```bash
php artisan migrate --force
php artisan db:seed --force
```

> **Info Login Default:**
> - Email: `admin@ev-sahabat.com`
> - Password: `EvSahabat#Admin2026!`
> - **WAJIB GANTI PASSWORD** setelah pertama login!

## 6. Storage Link

```bash
php artisan storage:link
```

## 7. Optimasi Laravel

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 8. Cron Job (Scheduler)

Tambahkan ke crontab hosting:
```
* * * * * php /path/ke/project/artisan schedule:run >> /dev/null 2>&1
```

## 9. Queue Worker

Untuk shared hosting, jalankan di background:
```bash
php artisan queue:work --queue=high,default --sleep=3 --tries=3 > storage/logs/queue.log &
```

Untuk VPS, gunakan Supervisor:
```ini
[program:ev-sahabat-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=high,default --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
```

## 10. Verifikasi

1. Buka `https://domain-anda.com/login`
2. Login dengan akun admin
3. Cek halaman: Dashboard, Station, Report, Alert, Security, API Integration
4. Cek export PDF/Excel

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| 404 semua halaman | Set `DocumentRoot` ke folder `public/` |
| Error "APP_KEY missing" | `php artisan key:generate` |
| Error storage permission | `chmod -R 775 storage/ bootstrap/cache/` |
| White screen / 500 | Cek `storage/logs/laravel.log` |
| Vite build error | Pastikan Node.js 18+, hapus `node_modules` & `package-lock.json`, lalu `npm install` ulang |
