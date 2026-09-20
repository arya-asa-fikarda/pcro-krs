# Sistem KRS Akademik — High-Volume Single Page Application (5M Rows Ready)

![Laravel 13](https://img.shields.io/badge/Backend-Laravel%2013-red?style=for-the-badge&logo=laravel)
![Vue 3](https://img.shields.io/badge/Frontend-Vue%203%20%2B%20Inertia.js-green?style=for-the-badge&logo=vuedotjs)
![PostgreSQL 16](https://img.shields.io/badge/Database-PostgreSQL%2016-blue?style=for-the-badge&logo=postgresql)
![Bootstrap 5](https://img.shields.io/badge/UI-Bootstrap%205-purple?style=for-the-badge&logo=bootstrap)

Project ini merupakan implementasi tes teknis Full Stack Web Developer untuk mengelola data Kartu Rencana Studi (KRS) Akademik secara **Single Page Application (SPA)**. Aplikasi dirancang khusus untuk menangani kueri performa tinggi pada skala dataset **5.000.000+ baris data**.

---

## 🚀 Fitur Utama & Kepatuhan Spesifikasi (TS-01 s.d. TS-13)

1. **Single Page CRUD Akademik**: Pengelolaan data KRS tanpa reload halaman menggunakan Inertia.js + Vue 3.
2. **Atomic 3-Table Transaction (TS-02)**: Pembuatan KRS baru yang meng-upsert data Mahasiswa (`students`), Mata Kuliah (`courses`), dan Pengambilan KRS (`enrollments`) sekaligus dalam satu `DB::transaction` atomic.
3. **Dual-Layer Strict Validation (TS-03 & TS-04)**: Validasi ketat di Frontend dan Backend:
   - `student_nim`: 8–12 digit angka murni (`regex:/^[0-9]{8,12}$/`).
   - `course_code`: Format kapital & angka (`regex:/^[A-Z]{2,4}[0-9]{3}$/`, contoh: `IF101`).
   - `academic_year`: Format `YYYY/YYYY` (`regex:/^[0-9]{4}\/[0-9]{4}$/`).
4. **Server-Side Query Engine (TS-05 s.d. TS-10)**:
   - Server-Side Pagination (10, 25, 50, 100 baris per halaman).
   - Header Sorting (ASC/DESC) pada seluruh kolom dengan indikator visual UI.
   - Quick Filter (Semester & Status KRS) yang dapat dikombinasikan.
   - Debounced Live Search (400ms) mencakup NIM, Nama Mahasiswa, Kode MK, dan Nama MK.
   - Dynamic Advanced Query Logic Filter Grouping (**AND** / **OR**).
5. **High-Volume Streaming CSV Export (TS-13)**: Mengalirkan data CSV berukuran **500 MB+ / 5 Juta Row** secara instan menggunakan `streamDownload()` dan Generator `cursor()` tanpa memicu *Out of Memory* (RAM Server mendekati 0 MB).

---

## 🛠️ Stack Teknologi & Arsitektur

- **Backend Framework**: Laravel 13.32 (PHP 8.3.22)
- **Frontend Framework**: Vue 3 + Inertia.js (Monolith SPA Hybrid)
- **Styling**: Bootstrap 5.3 CDN
- **Database**: PostgreSQL 16 (Managed via Laragon / DBeaver)
- **Asset Bundler**: Vite 8.3 dengan `@vitejs/plugin-vue`

---

## 💻 Panduan Instalasi Lokal (Local Setup)

### Prasyarat
- PHP >= 8.3 dengan ekstensi `pdo_pgsql` aktif
- Composer
- Node.js & NPM
- Database PostgreSQL server berjalan di port `5432`

### Langkah-Langkah Instalasi

1. **Clone Repository**:
   ```bash
   git clone [https://github.com/arya-asa-fikarda/pcro-krs.git](https://github.com/arya-asa-fikarda/pcro-krs.git)
   cd pcro-krs
   ```

2. **Install Dependensi Backend & Frontend**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment (`.env`)**:
   Salin file `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Pastikan konfigurasi database di file `.env` sudah sesuai dengan PostgreSQL lokal Anda:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=pcro_krs
   DB_USERNAME=postgres
   DB_PASSWORD=root
   ```

4. **Jalankan Migrasi Database & Indeks Performa**:
   ```bash
   php artisan migrate
   ```

5. **Jalankan Aplikasi**:
   Buka 2 terminal terpisah:
   - Terminal 1 (Laravel Server): `php artisan serve`
   - Terminal 2 (Vite Asset Bundler): `npm run dev`

   Akses aplikasi di browser: **`http://127.0.0.1:8000`**

---

## 🗄️ Seeding High-Volume 5 Juta Data (TS-01)

Aplikasi menyediakan Artisan Command khusus dengan teknik *chunked batch insert* untuk meng-generate 5.000.000 data KRS tanpa menghabiskan memori server.

### Cara Menjalankan Seeder:
```bash
php artisan app:seed-high-volume --count=5000000 --truncate
```

### 🔧 Troubleshooting PostgreSQL Sequence ID Desync
Jika terjadi error duplicate key `enrollments_pkey` saat melakukan penambahan data baru setelah bulk seeding, jalankan kueri berikut di DBeaver/PostgreSQL CLI untuk merekonfigurasi Auto-Increment Sequence:

```sql
SELECT setval('students_id_seq', (SELECT MAX(id) FROM students));
SELECT setval('courses_id_seq', (SELECT MAX(id) FROM courses));
SELECT setval('enrollments_id_seq', (SELECT MAX(id) FROM enrollments));
```

### Verifikasi Jumlah Data:
Buka database `pcro_krs` di DBeaver atau PostgreSQL CLI, lalu jalankan query berikut:
```sql
SELECT COUNT(*) FROM enrollments;
```
*Ekspektasi Output*: `>= 5031950`

---

## ⚡ Strategi Performa & Optimalisasi Database

Untuk mempertahankan waktu respon di bawah 1 detik pada skala 5 juta data:

1. **Database Indexing**:
   Tabel PostgreSQL diindeks pada kolom-kolom kritis kueri:
   - `students`: Index pada `nim` dan `name`.
   - `courses`: Index pada `code` dan `name`.
   - `enrollments`: Index pada `student_id`, `course_id`, `semester`, `status`, dan `academic_year`.
2. **Memory-Efficient CSV Streaming**:
   Menggunakan Query Builder `DB::table()` digabung dengan PHP Generator `cursor()` dan `ob_end_clean()`. Data dialirkan langsung dari PostgreSQL ke socket browser per *batch* 5.000 baris, menjamin RAM server tidak pernah jebol (*Zero Memory Leak*).
3. **Eager Loading & Whitelist Sorting**:
   Mencegah masalah *N+1 Query* pada tampilan datatable UI dan membatasi kolom sorting hanya pada kolom yang telah diizinkan (*whitelisted*).

---

## 🌐 Panduan Deployment Online

Aplikasi ini siap didedeploy ke layanan cloud modern seperti **Supabase + Render / Koyeb / Vercel**:

1. **Database Cloud**: Buat instance PostgreSQL di Supabase / Neon.tech.
2. **Environment Variables**: Masukkan `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` cloud pada dashboard deployment.
3. **Build Command**:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm run build
   php artisan migrate --force
   ```
4. **Start Command**:
   ```bash
   php artisan serve --host=0.0.0.0 --port=$PORT
   ```

---

## 📜 Lisensi & Hak Cipta
Hak Cipta (c) 2026 **Arya Asa Fikarda**.
Project ini dirilis di bawah lisensi [MIT License](LICENSE).
