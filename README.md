# Remindly

Remindly adalah aplikasi pengelola tugas, jadwal, dan pengingat.

## Phase 1

Fondasi proyek mencakup Laravel, React 18, Vite, Tailwind CSS, layout desktop/mobile,
tema terang/gelap, endpoint health API, dan konfigurasi CORS untuk Sanctum.

## Persyaratan

- PHP 8.2+
- Composer 2
- Node.js LTS dan npm
- MySQL 8 untuk deployment
- Redis untuk queue/cache pada deployment

Pengembangan lokal saat ini menggunakan SQLite bila MySQL belum tersedia.

## Instalasi

```powershell
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan serve
```

## Jalankan frontend

```powershell
npm run dev
```

Endpoint: `http://localhost:8000/up` dan `http://localhost:8000/api/v1/health`.

## Asumsi & Keputusan

- Environment menyediakan Laravel 13; fondasi mengikuti arsitektur Laravel 12+ yang diminta.
- SQLite dipakai untuk verifikasi lokal yang cepat jika MySQL/Redis belum tersedia.
- Waktu disimpan UTC; timezone pengguna diterapkan pada Phase 2.

## Phase 2

Phase 2 menyediakan migrasi user Remindly, kategori default, autentikasi Sanctum
berbasis cookie, register/login/logout, lupa/reset password, profile, dan perubahan
password. Semua endpoint dilindungi dengan `auth:sanctum` kecuali auth publik.

## Phase 3

Phase 3 menyediakan database task/activity log, dashboard teragregasi, CRUD task,
CRUD kategori, soft delete task/category, completed/reopen/archive/unarchive,
reschedule, pencarian/filter/sort/paginasi, dan perlindungan lintas-pengguna melalui
scope query plus Policy.

Definisi overdue tunggal adalah task aktif yang belum selesai, belum diarsipkan,
memiliki deadline, dan deadline-nya lebih awal dari waktu sekarang.

## Phase 4

Phase 4 menyediakan reminder CRUD/toggle/snooze, state machine pending→processing→
sent|failed|cancelled|skipped, claim atomik, delivery log per channel, cache lock,
recovery reminder stuck, scheduler berbasis Redis-ready queue, overdue dedupe harian,
dan Notification Center in-app.

## Phase 5

Phase 5 menyediakan Calendar API berbasis rentang start/end, FullCalendar UI,
recurring rule harian/mingguan/bulanan/tahunan/custom, anti-duplikasi occurrence,
penanganan bulan pendek, dan endpoint analytics overview/productivity.

## Phase 6

Phase 6 menyediakan email reminder melalui Laravel Notification, Web Push dengan
VAPID dan service worker, FCM HTTP v1 menggunakan service-account credentials,
subscription web/FCM, notification preferences, quiet hours, dan delivery status
per channel. Credential push tidak boleh disimpan di source code; gunakan env.

## Phase 7

Phase 7 menyediakan settings timezone/bahasa/theme, export JSON data pribadi,
penghapusan akun terkonfirmasi password, security headers, README/deployment,
dan migration/test/build validation.

## Menjalankan queue dan scheduler

```powershell
php artisan queue:work --queue=default
php artisan schedule:work
```

Untuk produksi, jalankan queue melalui Supervisor dan scheduler melalui cron:

```text
* * * * * cd /var/www/remindly && php artisan schedule:run
```

## Deploy ringkas

1. Setup Ubuntu 24.04 dengan PHP 8.3, PHP-FPM, MySQL 8, Redis, Nginx, dan Node.js LTS.
2. Salin `.env.example` menjadi `.env` dan isi secret/credential.
3. Jalankan `composer install --no-dev --optimize-autoloader`, `npm install`, dan `npm run build`.
4. Jalankan `php artisan migrate --force`, `php artisan config:cache`, dan `php artisan route:cache`.
5. Gunakan Supervisor untuk `php artisan queue:work --sleep=3 --tries=5`.
6. Aktifkan HTTPS dan cookie Secure/SameSite sesuai domain produksi.

## API Documentation

- OpenAPI 3: [docs/openapi.yaml](./docs/openapi.yaml)
- Postman: [docs/postman_collection.json](./docs/postman_collection.json)
- ERD: [docs/erd.md](./docs/erd.md)
- Deployment: [docs/deployment.md](./docs/deployment.md)

## Final frontend validation

- Frontend TypeScript strict: `npm run typecheck`
- Production build: `npm run build`
- Playwright smoke: `npm run test:e2e`

The calendar and productivity chart are lazy-loaded from the main frontend chunk.

## Blade frontend

- Frontend seluruhnya dirender oleh Laravel Blade.
- Tidak ada React, React Router, atau TypeScript runtime.
- Navigasi desktop/mobile menggunakan route Blade server-side.
- CSS diproses melalui Vite/Tailwind.

## PWA

- Manifest: `/manifest.webmanifest`
- Service worker: `/sw.js`
- Icon: `/icons/icon-192.png` dan `/icons/icon-512.png`
- Buka melalui HTTPS untuk instalasi PWA.

## Login and production notes

- Login/register redirects only after the session cookie is established; `/api/v1/user`
  is revalidated on application start.
- Quick Add uses optimistic UI so it remains responsive over slower database connections.
- Production session, cache, and queue should use Redis.
- Production cookies must use `SESSION_SECURE_COOKIE=true`, HTTPS, and the exact domain.
- Rotate the MySQL password before production deployment; never commit `.env`.
- Nginx and Supervisor templates are available in [deploy/remindly.conf](./deploy/remindly.conf)
  and [deploy/remindly-worker.conf](./deploy/remindly-worker.conf).

## Navigation

- Desktop sidebar and mobile bottom navigation use the same React Router paths:
  `/`, `/tasks`, `/calendar`, `/reminders`, dan `/settings`.
- Task, Calendar, Reminder, and Settings pages load their real data from `/api/v1`.
