# Project Specification — Tes Teknis Web Developer PCR
> **Single Source of Truth (SSOT)** untuk implementasi, testing, AI/vibe coding, dan submission.

## 0. Metadata

| Item | Value |
|---|---|
| Tujuan | Tes Kemampuan Teknis Web Developer / Full Stack |
| Tema | Sistem KRS / Pengambilan Mata Kuliah |
| Bentuk | Single Page CRUD Akademik |
| Deadline | **20 September 2026, 23:59 WIB (Asia/Jakarta)** |
| Submission | https://forms.gle/8kauVAEKwpu84Vom6 |
| Prioritas | Requirement wajib > Acceptance Criteria > NFR > polish/bonus |

---

## 1. Objective

Membangun aplikasi modern frontend + backend untuk mengelola data KRS (`enrollments`) yang:

1. Memiliki CRUD lengkap.
2. Melakukan insert/update terkoordinasi pada 3 entitas yang saling berelasi.
3. Menjamin operasi create berjalan **atomic dalam 1 database transaction**.
4. Tetap operasional pada dataset minimal **5.000.000 baris**.
5. Mendukung server-side pagination, sorting, filtering, searching, dan export.
6. Memiliki validasi frontend + backend yang ketat.
7. Memiliki dokumentasi local setup dan deployment.
8. Dapat diuji melalui aplikasi online.

---

# 2. Scope & Business Rules

## 2.1 Entitas wajib

Minimal 3 tabel:

- `students`
- `courses`
- `enrollments`

### Relationship

```text
students 1 ────────< enrollments >──────── 1 courses

enrollments.student_id  → students.id
enrollments.course_id   → courses.id
```

Boleh menambahkan tabel lain jika diperlukan, tetapi 3 entitas di atas wajib ada.

---

## 2.2 Create / Create KRS

Form Create wajib menghasilkan keterlibatan pada:

- `students`
- `courses`
- `enrollments`

Diperbolehkan:

- insert data student baru + course baru + enrollment baru; atau
- memilih student/course yang sudah ada dengan opsi menambah baru; atau
- menggunakan upsert sesuai kebutuhan.

### Atomicity

Seluruh proses Create harus berada di **satu transaction**.

```text
BEGIN TRANSACTION

Create/Upsert Student
       ↓
Create/Upsert Course
       ↓
Create Enrollment
       ↓
COMMIT

Jika salah satu gagal:
ROLLBACK seluruh operasi
```

Tidak boleh ada kondisi data setengah tersimpan.

---

## 2.3 Read

Halaman utama menampilkan data `enrollments` dalam bentuk tabel.

Data list harus diambil dari backend secara server-side dan tidak boleh memuat seluruh dataset 5 juta baris ke browser.

---

## 2.4 Update

Minimal dapat mengubah:

- `status`
- `semester`
- `academic_year`

Boleh mengubah data student/course terkait jika desain aplikasi membutuhkan hal tersebut.

Perubahan tetap harus divalidasi.

---

## 2.5 Delete

Menghapus enrollment.

Pilihan:

- hard delete; atau
- soft delete.

Pilihan harus dijelaskan di README, termasuk dampaknya terhadap data student/course.

---

# 3. Technology Constraints

Teknologi bebas, tetapi wajib modern.

## Frontend

Harus menggunakan salah satu:

- React
- Vue
- Svelte
- Angular

Boleh memakai framework/tool pendukung seperti:

- Next
- Nuxt
- Vite
- dan sejenisnya.

## Backend

Harus menggunakan salah satu stack modern, misalnya:

- Node / Express / Fastify / Nest
- Laravel
- Django / FastAPI
- Spring Boot
- .NET
- dan sejenisnya.

## Database

Database relasional:

- PostgreSQL
- MySQL
- MariaDB

PostgreSQL direkomendasikan oleh soal.

## Dokumentasi

Wajib mendokumentasikan:

- local setup
- environment variables
- database setup
- migration
- seeder
- cara menjalankan aplikasi
- deployment / akses online

---

# 4. Final Locked Stack & Architecture
- **Backend**: Laravel 13 (PHP 8.3.22 via Laragon)
- **Frontend**: Vue 3 + Inertia.js + Bootstrap 5 + Vite
- **Database**: PostgreSQL (Managed via DBeaver / Laragon)
- **Data Strategy**:
  - Eloquent ORM: khusus Create, Update, Delete & Atomic Transaction 3 tabel.
  - Query Builder: khusus Read List Datatable (5M rows) & Streaming Export CSV.
- **Handoff Protocol**: File `HANDOFF.md` di root project digunakan sebagai sarana sinkronisasi antar AI.

---

# 5. Main Page / Single Page

Aplikasi berpusat pada satu halaman utama KRS.

Struktur UI yang direkomendasikan:

```text
┌─────────────────────────────────────────────────────┐
│ Header / Title                                      │
├─────────────────────────────────────────────────────┤
│ [Create KRS]                                        │
├─────────────────────────────────────────────────────┤
│ Quick Filters                                       │
│ Status | Semester                                   │
├─────────────────────────────────────────────────────┤
│ Live Search                                         │
│ NIM / Nama Mahasiswa / Kode MK                     │
├─────────────────────────────────────────────────────┤
│ Advanced Filter                                    │
│ + filter per kolom                                 │
├─────────────────────────────────────────────────────┤
│ Advanced Query / Filter Groups                     │
│ AND / OR                                            │
├─────────────────────────────────────────────────────┤
│ Advanced Multi-Column Sort                         │
├─────────────────────────────────────────────────────┤
│ Data Table                                          │
│ NIM | Mahasiswa | Kode | MK | Semester | Tahun... │
├─────────────────────────────────────────────────────┤
│ Pagination                                          │
├─────────────────────────────────────────────────────┤
│ Export                                              │
└─────────────────────────────────────────────────────┘
```

Create/Edit sebaiknya menggunakan modal/drawer agar tetap memenuhi konsep single-page.

---

# 6. Table Requirements

Minimal columns:

| Column | Source |
|---|---|
| NIM | `students.nim` |
| Nama Mahasiswa | `students.name` |
| Kode MK | `courses.code` |
| Nama MK | `courses.name` |
| Semester | `enrollments.semester` |
| Tahun Ajaran | `enrollments.academic_year` |
| Status | `enrollments.status` |

Boleh menambahkan:

- Credits
- Email
- Action
- Enrollment ID

---

# 7. Server-Side Query Requirements

## 7.1 Pagination

Backend wajib menerima parameter seperti:

```text
page
page_size
```

atau:

```text
offset
limit
```

Backend hanya mengambil record yang diperlukan untuk halaman aktif.

Target:

```text
GET /api/enrollments?page=1&page_size=25
```

Response minimal harus menyediakan data list dan informasi pagination.

---

## 7.2 Sorting

Setiap kolom tabel wajib dapat di-sort:

- ASC
- DESC

UI harus menampilkan indikator arah sort.

Sorting wajib dieksekusi di backend.

Contoh:

```text
GET /api/enrollments?sort=student_nim&direction=asc
```

### Security rule

**Jangan pernah menerima nama kolom database secara mentah lalu langsung memasukkannya ke query.**

Gunakan whitelist mapping:

```text
student_nim   → students.nim
student_name  → students.name
course_code   → courses.code
course_name   → courses.name
semester      → enrollments.semester
academic_year → enrollments.academic_year
status        → enrollments.status
```

---

# 8. Quick Filters

Minimal 2 quick filter.

Wajib tersedia:

### Filter Status

```text
DRAFT
SUBMITTED
APPROVED
REJECTED
```

### Filter Semester

Boleh menggunakan:

```text
GANJIL
GENAP
```

atau:

```text
1
2
```

Quick filter harus berjalan server-side dan dapat dikombinasikan.

---

# 9. Live Search

Search wajib:

- real-time
- server-side
- debounce sekitar 300–500 ms
- minimal mencakup 3 kolom utama

Minimal:

```text
students.nim
students.name
courses.code
```

Contoh:

```text
GET /api/enrollments?search=IF10
```

Untuk pencarian lintas beberapa kolom, gunakan grouping condition yang aman.

---

# 10. Advanced Filter

Setiap kolom yang ditampilkan harus dapat memiliki filter.

Contoh operator:

### String

- contains
- startsWith
- equal

### Academic Year

- equal
- between

### Semester

- in

### Status

- in

### Course Code

- contains

### Multi Filter

Beberapa filter dapat aktif sekaligus.

Default behavior:

```text
filter A AND filter B AND filter C
```

Harus tersedia:

- Apply
- Clear / Reset

Semua kondisi diproses server-side.

---

# 11. Advanced Multi-Column Sort

Sistem wajib mendukung urutan beberapa kolom.

Contoh:

```text
academic_year DESC
semester ASC
student_nim ASC
```

Prioritas sorting harus dipertahankan sesuai urutan yang dipilih user.

Contoh representasi API:

```text
sorts=[
  { field: "academic_year", direction: "desc" },
  { field: "semester", direction: "asc" },
  { field: "student_nim", direction: "asc" }
]
```

Gunakan whitelist field dan direction.

---

# 12. AND / OR Query Logic

Soal menyebut "Advanced Order AND/OR", tetapi catatan soal menjelaskan bahwa AND/OR lebih tepat diterapkan pada **kombinasi kondisi filter**.

Implementasi yang direkomendasikan:

```text
Group A:
  status = APPROVED
  AND
  semester = GANJIL

OR

Group B:
  course_code CONTAINS "IF"
```

Secara konsep:

```sql
WHERE
(
  status = 'APPROVED'
  AND semester = 'GANJIL'
)
OR
(
  course_code LIKE '%IF%'
)
```

### Aturan

- AND: semua kondisi dalam group harus cocok.
- OR: cukup salah satu group cocok.
- Parsing harus aman.
- Jangan membuat raw SQL dari string query user tanpa parameter binding.

Implementasi yang dipilih harus dijelaskan di README.

---

# 13. Export

## Wajib

Export harus mencakup **seluruh dataset**, bukan hanya halaman aktif.

Harus menghormati:

- search
- filter
- advanced filter
- sort/query context

### Format minimum

CSV.

XLSX opsional.

### Dataset

Harus mampu menangani skenario:

```text
5.000.000+ rows
```

### Strategi yang direkomendasikan

Gunakan salah satu:

- streaming response
- chunking / cursor
- queue/background job + download result

Jangan melakukan:

```text
SELECT * FROM enrollments
```

lalu memuat seluruh hasil ke memory aplikasi.

README wajib menjelaskan strategi:

- indexing
- batching/chunking
- streaming/queue
- memory management
- cara user memperoleh file

CSV direkomendasikan untuk tes ini karena XLSX memiliki batas jumlah row per worksheet.

---

# 14. Validation

Validasi wajib dilakukan di:

1. Frontend
2. Backend

Frontend memperbaiki UX.
Backend adalah sumber validasi yang tidak boleh dilewati.

---

## 14.1 Students

### `nim`

- required
- unique
- 8–12 digit angka
- tidak boleh mengandung spasi

### `name`

- required
- 3–100 karakter

### `email`

- required
- valid email
- unique

---

## 14.2 Courses

### `code`

- required
- unique
- pattern:

```regex
^[A-Z]{2,4}[0-9]{3}$
```

Contoh:

```text
IF101
TI201
RPL301
```

### `name`

- required
- 3–120 karakter

### `credits`

- required
- integer
- 1–6

---

## 14.3 Enrollments

### `academic_year`

- required
- format:

```text
YYYY/YYYY
```

Contoh:

```text
2025/2026
```

### `semester`

Enum:

```text
GANJIL
GENAP
```

atau desain `1/2`.

### `status`

Enum:

```text
DRAFT
SUBMITTED
APPROVED
REJECTED
```

### Unique constraint

Disarankan:

```text
(student_id, course_id, academic_year, semester)
```

Tidak boleh ada duplikasi kombinasi tersebut.

---

# 15. Error Handling

## Frontend

Harus menampilkan:

- error per field
- error bisnis / conflict
- error server
- loading state
- success notification

Contoh:

```text
NIM harus terdiri dari 8–12 digit angka.
```

## Backend/API

Gunakan HTTP status code sesuai kondisi.

Contoh:

```text
422 → validation error
404 → resource not found
409 → duplicate/conflict
500 → unexpected server error
```

Payload error harus konsisten dan mudah dipahami frontend.

Backend wajib:

- sanitasi/normalisasi input yang relevan
- validasi
- parameterized query / ORM
- menolak payload invalid

---

# 16. Seeder 5.000.000 Rows

Aplikasi wajib menyediakan mekanisme seeding minimal:

```text
5.000.000 enrollments
```

## Seeder Requirements

Harus:

- dapat dijalankan melalui command/script
- tidak menyebabkan out-of-memory
- menggunakan batching/bulk insert/COPY sesuai database
- dapat diparameterisasi

Contoh konsep:

```bash
seed --count=5000000
```

## README wajib berisi

1. Cara membuat database.
2. Cara menjalankan migration.
3. Cara menjalankan seeder 5 juta data.
4. Cara mengecek jumlah data.

Contoh verification:

```sql
SELECT COUNT(*) FROM enrollments;
```

Ekspektasi:

```text
>= 5,000,000
```

---

# 17. Database & Performance Strategy

Karena target utama adalah 5 juta rows, database design harus dipikirkan sejak awal.

## Minimal index strategy

Index harus dibuat berdasarkan pola:

- FK join
- quick filter
- search
- sorting
- unique constraint

Contoh area yang perlu dievaluasi:

```text
students.nim
students.email
courses.code
enrollments.student_id
enrollments.course_id
enrollments.semester
enrollments.status
enrollments.academic_year
```

Jangan asal membuat index pada semua kolom. Evaluasi kombinasi composite index berdasarkan query aktual.

## Performance validation

Gunakan query plan / database explain tooling untuk query utama:

- list
- search
- filter
- sort
- export

Target utamanya adalah menghindari full table scan yang tidak perlu untuk operasi UI.

---

# 18. Security / NFR

## Security

Wajib:

- server-side validation
- ORM / parameterized query
- SQL injection protection
- safe sort/filter field mapping
- CORS aman
- tidak mengekspos secret di frontend/public repository

## Code Quality

Wajib:

- struktur project rapi
- formatter
- linter
- error logging
- reusable service/component jika relevan

Observability dasar seperti request logging adalah nilai tambah.

---

# 19. API Contract (Recommended)

> Exact route naming bebas. Kontrak perlu konsisten sebelum frontend mulai dibangun.

Contoh:

```text
GET    /api/enrollments
POST   /api/enrollments
GET    /api/enrollments/{id}
PUT    /api/enrollments/{id}
DELETE /api/enrollments/{id}

GET    /api/students
GET    /api/courses

POST   /api/exports/enrollments
GET    /api/exports/{id}
```

Untuk single-page CRUD, `GET /api/enrollments` menjadi endpoint paling kritis.

---

# 20. Acceptance Criteria

## TS-01 — Setup & Seed

- Migration berhasil.
- Seeder berhasil.
- Data enrollments >= 5.000.000.
- `COUNT(*)` membuktikan jumlah data.
- Aplikasi tetap berjalan setelah seeding.

## TS-02 — Create / Atomic Transaction

- User membuat student baru.
- User membuat course baru.
- User membuat enrollment.
- Ketiga tabel berisi data.
- FK valid.
- Bila salah satu operasi gagal, seluruh transaction rollback.
- UI memberi notifikasi sukses.

## TS-03 — Frontend Validation

- NIM invalid ditolak.
- Course code invalid ditolak.
- Required field kosong ditolak.
- Error muncul per field.

## TS-04 — Backend Validation

- Invalid API payload menghasilkan HTTP 4xx.
- Duplicate NIM ditolak.
- Duplicate course code ditolak.
- Duplicate enrollment ditolak.
- Tidak ada data invalid yang masuk DB.

## TS-05 — Read / Pagination

- Page dapat berubah.
- Page size dapat berubah.
- Request membawa pagination parameter.
- Backend hanya mengambil data halaman aktif.
- Total record/total page tersedia.

## TS-06 — Sorting

- Semua header dapat di-sort.
- ASC dan DESC tersedia.
- Sorting terjadi di backend.
- UI menampilkan indikator sort.

## TS-07 — Quick Filter

- Status dapat difilter.
- Semester dapat difilter.
- Kedua filter dapat dikombinasikan.
- Filtering terjadi di backend.

## TS-08 — Live Search

- Search NIM.
- Search nama mahasiswa.
- Search kode mata kuliah.
- Debounce sekitar 300–500 ms.
- Search terjadi di backend.

## TS-09 — Advanced Filter

- Semua kolom tabel mempunyai filter.
- Multi-filter dapat digunakan.
- Default kombinasi filter menggunakan AND.
- Filter dapat di-reset.

## TS-10 — Advanced AND/OR Query

- User dapat membuat group filter.
- AND bekerja.
- OR bekerja.
- Hasil sesuai dokumentasi.
- Parsing query aman.

## TS-11 — Update

- Enrollment dapat diedit.
- Validation tetap berjalan.
- Data berubah di DB.
- UI menampilkan data terbaru.

## TS-12 — Delete

- Enrollment dapat dihapus.
- Setelah refresh, enrollment tidak tampil.
- Student/course tidak ikut rusak kecuali memang didesain cascade dan didokumentasikan.

## TS-13 — Export

- Export tanpa filter menghasilkan seluruh dataset.
- Export terfilter mengikuti kondisi query.
- Tidak terbatas pada page aktif.
- Stabil untuk 5 juta+ row.
- File dapat dibuka/diinspeksi.

---

# 21. Deliverables

## Git Repository

Public repository harus berisi:

- frontend source
- backend source
- README
- migrations/schema
- seeder
- environment example
- deployment instructions

Optional:

- Postman collection
- Insomnia collection
- automated tests
- performance benchmark

## Online Application

Wajib menyediakan:

- URL aplikasi terdeploy
- fitur utama dapat diakses
- export dapat diuji

Submission:

```text
Public Git Repo
Deployed App URL
Export test access/result
Optional short notes:
- stack
- performance strategy
- assumptions
- limitations
```

---

# 22. README Requirements

README final minimal menjelaskan:

## Project Overview

- tujuan
- fitur utama
- stack

## Architecture

- frontend
- backend
- database
- API flow

## Local Setup

- prerequisites
- clone
- install dependencies
- `.env`
- database
- migration

## Seed 5 Million

- command
- parameter
- estimasi kebutuhan
- verification count

## Run

- backend
- frontend

## API

- endpoint utama
- parameter
- response/error format

## Performance

- indexing
- pagination
- query optimization
- seeding strategy
- export strategy

## Deployment

- provider
- environment
- migration
- seed
- build
- public URL

## Assumptions / Limitations

Jelaskan keputusan teknis yang memang tidak ditentukan oleh soal.

---

# 23. Explicit Non-Goals

Jangan menambah scope yang tidak diperlukan sebelum seluruh requirement wajib selesai.

Contoh non-goal:

- authentication kompleks jika tidak diwajibkan
- role/permission kompleks
- dashboard statistik besar
- chat system
- notification system
- mobile app
- fitur akademik di luar KRS
- XLSX bila CSV sudah memenuhi requirement
- automated test suite sebelum acceptance criteria manual terpenuhi

---

# 24. Definition of Done

Project hanya dianggap selesai setelah:

### Function

- [ ] Create
- [ ] Read
- [ ] Update
- [ ] Delete
- [ ] Atomic transaction 3 tabel
- [ ] Frontend validation
- [ ] Backend validation
- [ ] Pagination
- [ ] Per-column sorting
- [ ] Quick filters
- [ ] Live search
- [ ] Advanced filter
- [ ] Multi-column sort
- [ ] AND/OR query
- [ ] Export

### Scale

- [ ] Seeder >= 5 juta rows
- [ ] COUNT(*) verified
- [ ] List endpoint diuji pada dataset besar
- [ ] Export diuji/dirancang untuk dataset besar
- [ ] Memory tidak jebol

### Quality

- [ ] Error handling
- [ ] Logging
- [ ] Lint
- [ ] Format
- [ ] Security basics
- [ ] README
- [ ] Deployment
- [ ] Public repo
- [ ] Online URL

---

# 25. AI / Vibe Coding Rules

Dokumen ini diperlakukan sebagai **SSOT** saat menggunakan AI.

## Rule 1 — Jangan mengubah requirement diam-diam

AI tidak boleh:

- menghapus fitur wajib
- menyederhanakan 5 juta menjadi dataset kecil
- mengganti server-side menjadi client-side
- menghapus transaction 3 tabel
- mengganti CSV menjadi export page-only
- menambahkan scope besar tanpa alasan

## Rule 2 — Setiap keputusan ambigu harus dicatat

Format:

```text
Decision:
Reason:
Impact:
```

## Rule 3 — Kerjakan per checkpoint

Jangan meminta AI membangun seluruh project dalam satu prompt besar.

Urutan:

```text
Specification
→ Architecture
→ Database
→ Backend contract
→ Query layer
→ Seeder
→ API testing
→ Frontend
→ Integration
→ Performance
→ Export
→ Deployment
→ Final audit
```

## Rule 4 — AI wajib membaca dokumen ini sebelum coding

Prompt dasar:

```text
Read PROJECT_SPEC_PCRO_Tes_Teknis_Web_Developer_2026.md first.

Treat it as the single source of truth.
Do not remove or reinterpret mandatory requirements without stating
the decision, reason, and impact.

Before changing code:
1. Identify the requirement being implemented.
2. State affected files/modules.
3. Implement only the requested scope.
4. Run/describe verification.
5. Report remaining risks.
```

---

# 26. Source Fidelity Notes

Dokumen ini adalah normalisasi struktur dari materi tes yang diberikan.

Requirement asli **tidak boleh dikurangi**.

Beberapa bagian soal memang memiliki wording yang kurang konsisten, terutama istilah:

```text
"advanced order AND/OR"
```

Soal sendiri memberi catatan bahwa AND/OR umumnya berkaitan dengan filter/query condition. Karena itu implementasi pada dokumen ini memperlakukan AND/OR sebagai **filter groups**, lalu menjelaskan interpretasinya di README.

---

# 27. Final Pre-Submission Audit

Sebelum submit, lakukan audit berdasarkan urutan:

```text
01. Migration clean setup
02. Seed 5M+
03. COUNT(*) proof
04. Create 3-table transaction
05. Rollback test
06. Frontend validation
07. Backend validation
08. Pagination
09. Per-column sorting
10. Quick filters
11. Live search
12. Advanced filters
13. AND/OR filters
14. Multi-column sorting
15. Update
16. Delete
17. Export all
18. Export with filters
19. Performance check
20. README
21. Deployment
22. Public repo
23. Final acceptance checklist
```

**Submission is complete only when all mandatory acceptance criteria are verifiably satisfied.**
