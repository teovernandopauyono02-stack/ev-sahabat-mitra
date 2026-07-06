<?php

/**
 * EV Sahabat — Deployment Checklist
 *
 * Cara pakai:
 *   1. Upload semua file ke hosting (via git clone / FTP / cPanel)
 *   2. Jalankan: composer install --optimize-autoloader --no-dev
 *   3. Copy .env & isi kredensial hosting:
 *        cp .env.example .env
 *        # lalu edit .env: DB, APP_URL, MAIL, dll.
 *   4. Generate APP_KEY (jika belum): php artisan key:generate
 *   5. Jalankan: php artisan migrate --force
 *   6. Isi data awal: php artisan db:seed --force
 *   7. Build frontend: npm install && npm run build
 *   8. Optimize: php artisan optimize
 *   9. Set storage link: php artisan storage:link
 *  10. Setup cron job untuk scheduler:
 *        * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
 *  11. Setup queue worker (disarankan supervisor):
 *        php artisan queue:work --queue=high,default --sleep=3 --tries=3
 *
 * Lihat juga DEPLOY.md untuk panduan lengkap.
 */

echo "✓ EV Sahabat Deployment Checklist siap!\n";
