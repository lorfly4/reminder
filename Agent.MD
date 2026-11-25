# MASTER PROMPT — REMINDLY
Personal Reminder & Task Management System

## 0. PERAN & CARA KERJA
Bertindaklah sebagai Senior Full-Stack Developer, Software Architect, UI/UX Designer, Database Engineer, dan DevOps Engineer.

Bangun aplikasi REMINDLY yang BENAR-BENAR BERFUNGSI: frontend, backend, database, autentikasi, reminder engine, dan notifikasi. Bukan prototype, bukan UI statis, bukan data dummy.

Aturan kerja (wajib):
1. Analisis struktur project dan environment sebelum mengubah file. Jangan menimpa atau menghapus konfigurasi yang masih diperlukan.
2. Kerjakan bertahap per PHASE (bagian 9). Jangan berpindah phase sebelum Definition of Done phase tersebut terpenuhi.
3. Jangan pernah mengklaim fitur selesai jika belum dijalankan dan diuji. Sertakan output nyata dari perintah test/migrate/build.
4. Jika ada dependensi atau tool yang tidak tersedia, jelaskan dan berikan langkah instalasi. Jangan memalsukan hasilnya.
5. Jika ada keputusan teknis yang belum ditentukan, pilih opsi paling stabil dan umum, lalu catat di bagian "Asumsi & Keputusan" pada README.
6. Jika ada konflik antar instruksi atau informasi yang benar-benar menghalangi, berhenti dan tanyakan. Jangan menebak untuk hal yang berisiko (data, keamanan, biaya).
7. Jangan menghasilkan seluruh project dalam satu respons. Kerjakan per phase dengan commit kecil dan terarah.
8. Jangan meninggalkan tombol, menu, atau halaman yang tidak berfungsi. Fitur yang belum dikerjakan harus disembunyikan atau diberi label jelas.

## 1. OVERVIEW
- Nama: REMINDLY
- Tagline: "Your Life, Organized. Never Miss a Moment."
- Deskripsi: aplikasi produktivitas untuk mengatur tugas, jadwal, aktivitas, dan deadline dengan pengingat otomatis.
- Target pengguna: mahasiswa, karyawan, freelancer, pengguna umum.
- Tujuan: (1) mengingat tugas, (2) mengelola jadwal dan deadline, (3) notifikasi otomatis, (4) mengatur prioritas, (5) memantau produktivitas, (6) riwayat aktivitas.

## 2. TECH STACK (WAJIB)
Frontend: React 18+, TypeScript (strict), Vite, Tailwind CSS, React Router, Axios, Lucide React, FullCalendar, Chart.js (react-chartjs-2), TanStack Query untuk data fetching/caching.
Backend: Laravel 12, PHP 8.2+, Laravel Sanctum, Eloquent, Scheduler, Queue, Notification, Form Request, API Resource, Policy.
Database: MySQL 8, charset utf8mb4, collation utf8mb4_unicode_ci.
Background: Redis (queue, cache, lock) + Laravel Queue Worker.
Notifikasi: In-App, Email, Web Push (VAPID + Service Worker), Firebase Cloud Messaging.
Tools: Git, Composer, Node.js LTS, NPM, Postman/Pest/PHPUnit, Vitest + React Testing Library.
Deployment: Linux VPS, Nginx, PHP-FPM, MySQL, Redis, Supervisor (queue worker), cron (scheduler), SSL/HTTPS.
Arsitektur: frontend dan backend terpisah, komunikasi lewat REST API.

## 3. KEPUTUSAN TEKNIS YANG DIKUNCI
- Auth: Sanctum mode SPA (cookie-based session + CSRF) dengan endpoint /sanctum/csrf-cookie. Konfigurasikan SANCTUM_STATEFUL_DOMAINS, SESSION_DOMAIN, CORS (supports_credentials=true), dan axios `withCredentials`.
- Waktu: semua datetime disimpan dalam UTC. Setiap user punya kolom timezone (default Asia/Jakarta). Konversi dilakukan di API Resource / frontend. Perhitungan "hari ini", "overdue", dan reminder wajib memakai timezone user.
- Soft delete: tasks dan categories memakai SoftDeletes. Arsip = kolom `archived_at` (nullable), BUKAN status.
- Status task: todo, in_progress, completed. "Overdue" = status turunan (deadline < sekarang AND status != completed AND archived_at IS NULL), dihitung lewat query scope dan dijaga konsisten di seluruh API, dashboard, dan analytics. Tidak boleh ada dua definisi overdue.
- Prioritas: low, medium, high, urgent (enum PHP backed enum).
- Primary key: BIGINT auto-increment. ID tidak boleh menjadi satu-satunya proteksi akses, authorization wajib via Policy.
- Bahasa UI: Indonesia dan English (i18n dengan react-i18next). Default: Indonesia.
- Konfigurasi rahasia hanya lewat .env. Sediakan .env.example untuk frontend dan backend. Jangan commit credential apa pun.

## 4. DESIGN SYSTEM
Konsep: Modern Minimalist Productivity Dashboard dengan identitas visual REMINDLY yang konsisten (bukan template dashboard generik).
- Primary: Indigo/Purple. Secondary: White. Background: Light Gray. Text: Dark Navy. Accent: Soft Purple.
- Definisikan design tokens (warna, radius, spacing, shadow, tipografi) di tailwind.config dan CSS variables, termasuk varian Dark Mode.
- Referensi gaya: Linear, Notion, Todoist, ClickUp.
Ketentuan UI:
1. Sidebar pada desktop, bottom navigation / drawer pada mobile.
2. Card rounded, whitespace lega, tipografi mudah dibaca, tidak ramai.
3. Transisi halus (hormati prefers-reduced-motion).
4. Skeleton loading, empty state informatif, toast untuk feedback, error state yang jelas.
5. Seluruh halaman responsif (min 360px s.d. desktop lebar).
6. Aksesibilitas dasar: kontras WCAG AA, fokus terlihat, label form, navigasi keyboard, aria pada modal/dropdown.
7. Light Mode dan Dark Mode (ikuti preferensi sistem + pilihan manual tersimpan di settings).
Bangun komponen reusable: Button, Input, Select, Modal, Drawer, Badge, Card, Toast, Skeleton, EmptyState, ConfirmDialog, DatePicker/TimePicker.

## 5. MODUL & FITUR

### A. Authentication
Register, Login, Logout, Forgot Password, Reset Password (email token), Profile, Change Password.
- Validasi lengkap lewat Form Request, password di-hash (bcrypt/argon), rate limit login & forgot password.
- Semua endpoint selain auth wajib `auth:sanctum`.
- Pengguna hanya dapat mengakses data miliknya (Policy + scoping query ke user).

### B. Dashboard
Statistik: Total, Completed, Pending, Overdue.
Komponen: Welcome Message (nama user), Today's Tasks, Upcoming Reminders, Recent Activities, Productivity Chart, Completion Rate, Quick Add Task.
Semua data dari database (satu endpoint teragregasi, query efisien, tanpa N+1).

### C. Task Management
Field: id, user_id, category_id (nullable), title, description, start_at, deadline_at, priority, status, completed_at, archived_at, timestamps, deleted_at.
Fitur: Create, Edit, Delete (soft), Detail, Search (title/description), Filter (kategori, prioritas, status, arsip), Sort (deadline, prioritas, dibuat), Pagination, Mark as Completed (+undo), Archive/Unarchive.
Aturan bisnis:
- deadline_at >= start_at (jika keduanya ada), kecuali ada aturan eksplisit.
- Menandai completed mengisi completed_at, membatalkan reminder pending, dan mencatat activity log.
- Archive membatalkan/melewati reminder pending; unarchive tidak mengaktifkan kembali reminder yang sudah lewat waktu secara otomatis (catat sebagai keputusan di README).
- category_id harus milik user yang sama (validasi ownership).

### D. Category Management
Field: id, user_id, name, color (hex tervalidasi), timestamps, deleted_at. Unik per user (name).
Seed default per user saat register: Personal, Work, Education, Finance, Health, Meeting, Other.
CRUD + warna + filter task. Menghapus kategori yang punya task: task menjadi tanpa kategori (category_id = NULL), bukan terhapus. Konfirmasi di UI.

### E. Reminder Management (FITUR INTI)
Satu task bisa punya banyak reminder.
Field: id, task_id, remind_at (UTC), channels (JSON: in_app, email, web_push, fcm), status, is_enabled, snoozed_until (nullable), attempts, sent_at, last_error, dedupe_key (unik), timestamps.
Preset: Custom Date & Time, 1 hari sebelum, 3 jam sebelum, 1 jam sebelum, 30 menit sebelum, saat deadline. Preset dihitung dari deadline_at saat dibuat. Jika deadline task diubah, reminder preset dihitung ulang secara terdefinisi (dokumentasikan perilakunya). Reminder yang waktunya sudah lewat saat dibuat harus ditolak validasi atau ditandai jelas.
Fitur: Multiple reminders, Edit, Delete, Enable/Disable, Snooze (5/10/30/60 menit atau custom), Reminder History.

State machine status: pending → processing → sent | failed | cancelled | skipped. Transisi di luar aturan ini harus ditolak.

Reminder Engine:
1. Scheduler (`everyMinute`, `withoutOverlapping`, `onOneServer`) memilih reminder due: is_enabled=1, status=pending, remind_at (atau snoozed_until) <= now, task belum completed/archived/deleted.
2. Klaim atomik: `UPDATE ... SET status='processing' WHERE id=? AND status='pending'` dan hanya dispatch jika affected rows = 1. Tambahkan Cache Lock (Redis) per reminder sebagai lapisan kedua.
3. Dispatch job `SendReminderNotification` ke queue Redis. Job harus idempotent (cek status sebelum kirim), `tries`, `backoff` eksponensial, dan `failed()` handler yang menyimpan last_error.
4. Pengiriman per channel dicatat terpisah (tabel notification_deliveries: channel, status, error, attempts, sent_at) sehingga kegagalan satu channel tidak menggagalkan channel lain dan tidak menyebabkan pengiriman ulang channel yang sudah sukses.
5. Reminder stuck di `processing` terlalu lama (mis. > 10 menit) dikembalikan ke `pending` oleh job pemulihan terjadwal.
6. Reminder task yang sudah completed/archived/deleted → status `skipped`/`cancelled`, tidak dikirim.
7. Catch-up: jika scheduler/worker sempat mati, reminder terlambat tetap dikirim satu kali (bukan dibuang, bukan dikirim berulang) dengan batas toleransi yang dikonfigurasi.
8. Job terjadwal terpisah menandai task overdue dan membuat notifikasi "Overdue Task" tepat satu kali per task (gunakan dedupe_key).

### F. Notification Center
Jenis: Task Reminder, Upcoming Deadline, Overdue Task, Task Completed.
Fitur: daftar (paginasi), mark as read, mark all as read, delete, badge unread (polling ringan atau SSE sederhana), riwayat.
Channel:
1. In-App (tabel notifications).
2. Email (Laravel Mail/Notification via queue; template responsif; konfigurasi SMTP lewat .env; gunakan Mailpit/log driver untuk development).
3. Web Push (VAPID, service worker, tabel push_subscriptions; tangani permission denied/default, subscription kedaluwarsa (410/404 → hapus subscription), dan retry).
4. FCM (HTTP v1 API, token per perangkat; tangani token tidak valid dan hapus).
Jangan menganggap push selalu berhasil: tangani permission, subscription, kegagalan jaringan, retry, dan simpan status pengiriman.
Hormati Notification Preferences per user (channel on/off, quiet hours opsional).

### G. Calendar
FullCalendar: Daily, Weekly, Monthly, Agenda. Task tampil berdasarkan start_at dan deadline_at. Warna berdasarkan prioritas atau kategori (pilihan user). Klik event membuka detail task. Drag & drop / resize untuk reschedule lewat endpoint backend dengan validasi dan authorization; update optimistik dengan rollback jika gagal. Endpoint hanya mengembalikan event dalam rentang tanggal yang diminta (start, end).

### H. Recurring Tasks
Opsi: Daily, Weekly (pilih hari), Monthly (tanggal / hari ke-n), Yearly, Custom Interval. Opsional: tanggal berakhir atau jumlah kemunculan.
Tabel recurring_rules (satu rule per task): frequency, interval, by_weekday (JSON), until_at, count, next_run_at, last_generated_at.
Aturan:
- Occurrence berikutnya dibuat otomatis lewat scheduler (atau saat task completed, pilih satu pendekatan, dokumentasikan).
- Anti-duplikasi: unique constraint (recurring_rule_id, occurrence_at) dan pembuatan dalam transaksi.
- Task hasil generate menyalin judul, kategori, prioritas, dan reminder offset dari template.
- Tangani edge case: tanggal 31 pada bulan pendek, perubahan DST/timezone, dan rule yang dinonaktifkan.

### I. Analytics
Chart.js. Tampilkan: Completion Rate, Completed vs Pending, Productivity Overview, Weekly Activity, Monthly Activity, Tasks by Category, Overdue Statistics. Filter rentang tanggal. Semua dihitung dari database memakai timezone user, dengan index yang sesuai.

### J. Settings
Profile, Account, Password, Notification Preferences, Timezone, Language, Theme (Light/Dark/System), Data Privacy (ekspor data JSON, hapus akun dengan konfirmasi password).

## 6. DATABASE
Tabel (migration + model + factory + seeder):
users (termasuk timezone, language, theme), password_reset_tokens, sessions, categories, tasks, reminders, recurring_rules, notifications, notification_deliveries, activity_logs, push_subscriptions (endpoint, keys, type web|fcm, device info, last_used_at), user_settings (atau kolom JSON di users), jobs, failed_jobs, cache_locks (bila perlu).
Relasi:
- User hasMany Tasks, Categories, Notifications, ActivityLogs, PushSubscriptions.
- Category belongsTo User, hasMany Tasks.
- Task belongsTo User, belongsTo Category (nullable), hasMany Reminders, hasOne RecurringRule, hasMany ActivityLogs.
- Reminder belongsTo Task, hasMany NotificationDeliveries.
Wajib: foreign key dengan aksi on-delete yang eksplisit, index (user_id+status, user_id+deadline_at, remind_at+status, dedupe_key unique), tipe data tepat, timestamps, soft deletes sesuai bagian 3, utf8mb4_unicode_ci.
Hasilkan: ERD (Mermaid di docs/erd.md) dan dokumentasi struktur tabel.

## 7. API
Prefix /api/v1, JSON konsisten: `{ "data": ..., "meta": ..., "message": ... }`, error `{ "message": ..., "errors": { field: [..] } }` dengan HTTP status benar (401, 403, 404, 422, 429). Gunakan API Resource, Form Request, Policy, dan paginasi.
Auth: POST /register, /login, /logout, /forgot-password, /reset-password; GET /user; PUT /user/profile; PUT /user/password.
Dashboard: GET /dashboard.
Tasks: GET/POST /tasks; GET/PUT/DELETE /tasks/{id}; PATCH /tasks/{id}/complete; PATCH /tasks/{id}/reopen; PATCH /tasks/{id}/archive; PATCH /tasks/{id}/unarchive; PATCH /tasks/{id}/reschedule.
Categories: GET/POST /categories; PUT/DELETE /categories/{id}.
Reminders: GET /reminders; POST /reminders; PUT/DELETE /reminders/{id}; PATCH /reminders/{id}/toggle; POST /reminders/{id}/snooze.
Recurring: PUT/DELETE /tasks/{id}/recurring.
Calendar: GET /calendar/events?start=&end=.
Notifications: GET /notifications; PATCH /notifications/{id}/read; POST /notifications/read-all; DELETE /notifications/{id}; GET /notifications/unread-count.
Push: POST /push-subscriptions; DELETE /push-subscriptions/{id}.
Analytics: GET /analytics/overview; GET /analytics/productivity.
Settings: GET/PUT /settings.
Activity: GET /activity-logs.
Dokumentasikan seluruh endpoint (OpenAPI/Scribe atau Postman collection di docs/).

## 8. KEAMANAN
Authentication, Authorization (Policy pada setiap resource), validasi input, CSRF (sesuai mode SPA), rate limiting (auth ketat, API umum), hashing password, pencegahan SQL Injection (Eloquent/binding), pencegahan XSS (tidak ada dangerouslySetInnerHTML tanpa sanitasi; escape output email), CORS ketat per environment, validasi ownership di setiap relasi (mis. category_id, task_id), mass assignment protection ($fillable), security headers di Nginx, cookie Secure/HttpOnly/SameSite, log tanpa data sensitif. Tidak ada API key/password/credential di source code. Jalankan `composer audit` dan `npm audit`, laporkan temuannya.

## 9. WORKFLOW PER PHASE
PHASE 1 — Inisialisasi: cek environment (versi PHP, Composer, Node, NPM, MySQL, Redis); struktur monorepo; Laravel 12; React+TS+Vite; Tailwind + design tokens; koneksi MySQL (utf8mb4_unicode_ci); koneksi frontend↔backend (CSRF cookie + endpoint health nyata); layout shell (sidebar/mobile nav) dan halaman awal bergaya REMINDLY; .env.example; README awal.
PHASE 2 — Database & Auth: semua migration/model/relasi/factory/seeder; autentikasi lengkap (bagian 5A); halaman Login/Register/Forgot/Reset; guard route di frontend; test auth.
PHASE 3 — Core: Dashboard, Task CRUD, Category CRUD, search/filter/sort/paginasi, activity log; Policy + test authorization antar user.
PHASE 4 — Reminder Engine: reminder CRUD, scheduler, queue, state machine, locking/idempotensi, snooze, Notification Center in-app, job overdue, job pemulihan; test engine termasuk skenario duplikasi, worker mati, dan task selesai sebelum reminder jatuh tempo.
PHASE 5 — Calendar, Recurring, Analytics: FullCalendar + reschedule, recurring rules + anti-duplikasi, Chart.js + endpoint analytics.
PHASE 6 — Notifikasi Lanjutan: Email, Web Push (VAPID + service worker), FCM, preferensi notifikasi, penanganan subscription/token invalid, delivery log.
PHASE 7 — Finalisasi: Settings lengkap (tema, bahasa, ekspor/hapus data), hardening keamanan, optimasi query, test menyeluruh, konfigurasi produksi (Nginx, Supervisor, cron, SSL), panduan deployment VPS, README final.

## 10. STANDAR KODE
Clean code, SOLID bila relevan, tanpa duplikasi. Service Class untuk logika bisnis kompleks (ReminderService, RecurrenceService, DashboardService, AnalyticsService, NotificationDispatcher). Form Request untuk validasi, API Resource untuk response, Policy untuk otorisasi, Enum untuk status/prioritas. React: komponen reusable, custom hooks, pemisahan UI / logika / data fetching (services + TanStack Query), TypeScript strict tanpa `any` sembarangan, tipe API terpusat di src/types. File tidak terlalu besar tanpa alasan. Penamaan konsisten. Error handling jelas di backend dan frontend (error boundary, toast). Formatter/linter: Laravel Pint, ESLint, Prettier. Dokumentasikan konfigurasi penting.

## 11. TESTING
Backend (Pest/PHPUnit): register/login/logout, authorization antar user (user A tidak bisa membaca/mengubah/menghapus data user B untuk SEMUA resource), CRUD tasks & categories, validasi API, reminder scheduling & delivery (gunakan Queue::fake / Notification::fake dan test dengan waktu dibekukan), idempotensi (job dijalankan dua kali = satu pengiriman), snooze, recurring (tidak duplikat), notification history, statistik dashboard & analytics (termasuk batas hari berdasarkan timezone).
Frontend (Vitest + RTL): komponen kritis, form validation, guard route, state loading/empty/error.
Opsional: E2E smoke test (Playwright) untuk alur login → buat task → buat reminder.
Semua test harus lulus sebelum phase dianggap selesai.

## 12. DEFINITION OF DONE (per phase)
Sebuah phase selesai HANYA jika: (a) migration berjalan bersih dari database kosong (`migrate:fresh --seed`), (b) seluruh test phase tersebut lulus, (c) `npm run build` dan lint tanpa error, (d) aplikasi dijalankan dan alur utama phase dicek nyata (bukan mock), (e) tidak ada tombol/fitur yang tidak berfungsi, (f) tidak ada credential di kode, (g) README/docs diperbarui.

## 13. FORMAT LAPORAN (akhir setiap phase)
1. Ringkasan hasil.
2. File dibuat / diubah (daftar).
3. Penjelasan fungsi komponen penting.
4. Perintah instalasi dan menjalankan aplikasi (backend, frontend, queue worker, scheduler).
5. Output hasil test & build (nyata).
6. Asumsi & keputusan teknis yang diambil.
7. Masalah yang diketahui / pekerjaan tersisa.
8. Langkah untuk phase berikutnya.
Lalu BERHENTI dan tunggu perintah "lanjut" sebelum memulai phase berikutnya.

## TARGET AKHIR
Aplikasi REMINDLY yang benar-benar berjalan: UI/UX profesional, REST API terdokumentasi, database terstruktur, autentikasi aman, reminder otomatis yang andal tanpa duplikasi, notifikasi multi-channel, kalender, recurring task, analytics, dark mode, dan dokumentasi instalasi serta deployment lengkap.

MULAI SEKARANG dari PHASE 1.