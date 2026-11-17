# Academic API

Backend REST API (Laravel 13, JWT) for authentication, student data, and lecturer data.

```sh
composer setup          # install, .env, key, migrate
php artisan jwt:secret  # generate JWT_SECRET
php artisan migrate:fresh --seed
php artisan serve
composer test
vendor/bin/pint --test   # code style (also run in CI)
```

API docs: `/docs` (Scribe), Postman collection: `/docs.postman`, OpenAPI spec in `docs/openapi.yaml`.
Full backend notes: [docs/README.md](docs/README.md).

Seeded accounts (password `password`): `admin@example.com`, `lecturer@example.com`, `student@example.com`.

---

## Panduan Pengujian dengan Postman (Setiap Endpoint)

Bagian ini menjelaskan langkah-langkah pengujian seluruh **38 endpoint** (`/api/v1`) menggunakan **Postman**.

### 1. Persiapan Awal di Postman

1. **Jalankan server dan data awal (seed):**
   ```sh
   php artisan migrate:fresh --seed
   php artisan serve
   ```
2. **Import Koleksi Otomatis (Opsional):**
   - Buka Postman → klik **Import** → masukkan URL `http://localhost:8000/docs.postman` (atau file `docs/openapi.yaml`).
3. **Buat Environment di Postman:**
   - Klik tab **Environments** → **Create Environment** (misal: `Academic API Local`).
   - Tambahkan variabel berikut:
     | Variable | Initial / Current Value | Keterangan |
     |----------|-------------------------|------------|
     | `base_url` | `http://localhost:8000/api/v1` | URL dasar API v1 |
     | `token` | *(kosongkan dulu)* | Akan diisi `access_token` JWT setelah login |
   - Aktifkan environment tersebut di pojok kanan atas Postman.
4. **Konfigurasi Header & Auth Standar:**
   - Pada setiap request, pastikan tab **Headers** memiliki:
     - `Accept`: `application/json`
     - `Content-Type`: `application/json` *(otomatis jika memilih Body → `raw` → `JSON`)*
   - Pada endpoint yang membutuhkan autentikasi, buka tab **Authorization** → pilih **Type: Bearer Token** → isi **Token** dengan `{{token}}`.
5. **Akun Demo (Password semua akun: `password`):**
   - **Admin:** `admin@example.com`
   - **Dosen (Lecturer):** `lecturer@example.com` *(mengampu course ID 1–4 pada data seed)*
   - **Mahasiswa (Student):** `student@example.com` *(student ID `1` pada data seed)*

---

### 2. Autentikasi & Password (`Auth`)

#### 2.1. `POST /auth/login` — Login & Dapatkan Token JWT
- **Role:** Publik (tanpa token)
- **Langkah di Postman:**
  1. Pilih method **POST** dan masukkan URL `{{base_url}}/auth/login`.
  2. Tab **Headers**: `Accept: application/json`.
  3. Tab **Body** → pilih **raw** → **JSON**:
     ```json
     {
       "email": "admin@example.com",
       "password": "password"
     }
     ```
     *(Ganti `email` dengan `lecturer@example.com` atau `student@example.com` saat menguji hak akses role lain).*
  4. *(Tips Otomatis)* Buka tab **Scripts** → **Post-response**, tambahkan:
     ```javascript
     const res = pm.response.json();
     if (res.access_token) {
         pm.environment.set("token", res.access_token);
     }
     ```
  5. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi `access_token`, `token_type` (`bearer`), `expires_in`, dan objek `user`.

#### 2.2. `GET /auth/me` — Lihat Profil User yang Sedang Login
- **Role:** Semua role terautentikasi (`admin`, `lecturer`, `student`)
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/auth/me`.
  2. Tab **Authorization**: pilih **Bearer Token** → `{{token}}`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi data user beserta relasi profil (`student` atau `lecturer`).

#### 2.3. `POST /auth/refresh` — Perbarui Token JWT
- **Role:** Semua role terautentikasi
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/auth/refresh`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Klik **Send** (tanpa body). Salin `access_token` baru ke variabel `{{token}}`.
- **Ekspektasi Respons:** Status `200 OK` dengan `access_token` baru.

#### 2.4. `POST /auth/logout` — Logout (Blacklist Token)
- **Role:** Semua role terautentikasi
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/auth/logout`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` (`{"message": "Logged out."}`). Token lama tidak bisa dipakai lagi (`401 Unauthorized`).

#### 2.5. `POST /auth/forgot-password` — Request Link Reset Password
- **Role:** Publik
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/auth/forgot-password`.
  2. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "email": "student@example.com"
     }
     ```
  3. Klik **Send**.
  4. Di environment lokal, buka `storage/logs/laravel.log` (jalankan `php artisan queue:work --once` jika `QUEUE_CONNECTION=database`) untuk menyalin `token` reset dari URL email.
- **Ekspektasi Respons:** Status `200 OK` (`{"message": "If an account exists for that email, a reset link has been sent."}`).

#### 2.6. `POST /auth/reset-password` — Reset Password dengan Token
- **Role:** Publik
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/auth/reset-password`.
  2. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "token": "<TOKEN_DARI_LOG_EMAIL>",
       "email": "student@example.com",
       "password": "newpassword123",
       "password_confirmation": "newpassword123"
     }
     ```
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` (`{"message": "Password has been reset."}`). Semua JWT lama untuk akun tersebut otomatis dicabut (`token_version` bertambah).

---

### 3. Referensi Wilayah Indonesia (`Regions`)

Semua endpoint wilayah membutuhkan header `Authorization: Bearer {{token}}` (role apa saja).

#### 3.1. `GET /regions/provinces` — Daftar Provinsi
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/regions/provinces`.
  2. Tab **Params** *(opsional)*: `search` = `aceh`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar provinsi (contoh: `{"code": "11", "name": "ACEH"}`).

#### 3.2. `GET /regions/provinces/{province}/regencies` — Daftar Kabupaten/Kota
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/regions/provinces/11/regencies`.
  2. Tab **Params** *(opsional)*: `search` = `lhokseumawe`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar kabupaten/kota di provinsi `11` (contoh: `{"code": "11.73", "name": "KOTA LHOKSEUMAWE"}`).

#### 3.3. `GET /regions/regencies/{regency}/districts` — Daftar Kecamatan
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/regions/regencies/11.73/districts`.
  2. Tab **Params** *(opsional)*: `search` = `banda`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar kecamatan di `11.73` (contoh: `{"code": "11.73.04", "name": "Banda Sakti"}`).

#### 3.4. `GET /regions/districts/{district}/villages` — Daftar Kelurahan/Desa
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/regions/districts/11.73.04/villages`.
  2. Tab **Params** *(opsional)*: `search` = `meuria`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar gampong/kelurahan di kecamatan `11.73.04` (contoh: `{"code": "11.73.04.2010", "name": "Meuria Paloh"}`).

---

### 4. Profil Mandiri Mahasiswa (`Student Profile`)

> **Catatan:** Login terlebih dahulu sebagai `student@example.com` pada `POST /auth/login` sebelum menguji bagian ini.

#### 4.1. `GET /profile` — Lihat Profil & Alamat Mahasiswa Sendiri
- **Role:** `student`
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/profile`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (token milik `student`).
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` menampilkan profil mahasiswa beserta objek `region` dan `full_address`.

#### 4.2. `PATCH /profile` — Perbarui Kontak & Alamat Mahasiswa Sendiri
- **Role:** `student`
- **Langkah di Postman:**
  1. Pilih method **PATCH** ke `{{base_url}}/profile`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (token milik `student`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "phone": "081234567890",
       "address": "Jl. Medan - Banda Aceh Buketrata",
       "province_code": "11",
       "regency_code": "11.73",
       "district_code": "11.73.04",
       "village_code": "11.73.04.2010",
       "postal_code": "24301"
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` dengan data alamat dan `full_address` yang sudah diperbarui.

---

### 5. Manajemen Mahasiswa (`Students`)

#### 5.1. `GET /students` — Daftar Mahasiswa
- **Role:** `admin`, `lecturer`
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/students`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin` atau `lecturer`).
  3. Tab **Params** *(opsional)*:
     - `search`: cari berdasarkan nama atau NIM
     - `study_program`: filter program studi (misal `Informatics`)
     - `entry_year`: filter tahun masuk (misal `2024`)
     - `per_page`: jumlah per halaman (`1`–`100`, default `15`)
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` dengan daftar mahasiswa terpaginasi (`data`, `links`, `meta`).

#### 5.2. `POST /students` — Tambah Mahasiswa Baru + Akun Login
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/students`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "name": "Budi Santoso",
       "email": "budi.santoso@example.com",
       "password": "password123",
       "nim": "2025573010099",
       "study_program": "Teknologi Rekayasa Komputer Jaringan",
       "entry_year": 2025,
       "phone": "081298765432",
       "advisor_id": 1,
       "address": "Jl. Merdeka No. 10",
       "province_code": "11",
       "regency_code": "11.73",
       "district_code": "11.73.04",
       "village_code": "11.73.04.2010",
       "postal_code": "24351"
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `201 Created` berisi data mahasiswa yang baru dibuat.

#### 5.3. `GET /students/{student}` — Detail Mahasiswa
- **Role:** `admin`, `lecturer`
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/students/1`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin` atau `lecturer`).
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi detail mahasiswa ID `1`.

#### 5.4. `PUT` / `PATCH /students/{student}` — Perbarui Data Mahasiswa
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **PATCH** (atau **PUT**) ke `{{base_url}}/students/1`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "name": "Mahasiswa Demo Terupdate",
       "study_program": "Teknologi Rekayasa Komputer Jaringan",
       "phone": "081122334455"
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi data mahasiswa yang telah diperbarui.

#### 5.5. `DELETE /students/{student}` — Hapus Mahasiswa & Akun User
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **DELETE** ke `{{base_url}}/students/{id}` (gunakan ID mahasiswa hasil uji `POST /students`, misalnya `12`).
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `204 No Content`.

#### 5.6. `GET /students/{student}/transcript` — Lihat Transkrip Nilai & IPK (GPA)
- **Role:** `admin`, `lecturer`, atau `student` pemilik ID tersebut
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/students/1/transcript`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi `student`, `total_credits`, `gpa`, dan daftar `courses` yang sudah dinilai.

---

### 6. Manajemen Dosen (`Lecturers`)

Semua endpoint di bawah ini hanya dapat diakses oleh role **`admin`**.

#### 6.1. `GET /lecturers` — Daftar Dosen
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/lecturers`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Params** *(opsional)*: `search` (nama/NIDN), `department`, `per_page`.
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar dosen beserta `students_count`.

#### 6.2. `POST /lecturers` — Tambah Dosen Baru + Akun Login
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/lecturers`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "name": "Dr. Hendra Wijaya",
       "email": "hendra.wijaya@example.com",
       "password": "password123",
       "nidn": "0011223344",
       "department": "Teknologi Informasi dan Komputer",
       "phone": "081311223344"
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `201 Created` berisi data dosen baru.

#### 6.3. `GET /lecturers/{lecturer}` — Detail Dosen
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/lecturers/1`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi detail dosen ID `1`.

#### 6.4. `PUT` / `PATCH /lecturers/{lecturer}` — Perbarui Data Dosen
- **Langkah di Postman:**
  1. Pilih method **PATCH** ke `{{base_url}}/lecturers/1`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "department": "Teknologi Rekayasa Komputer Jaringan",
       "phone": "081399887766"
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi data dosen yang diperbarui.

#### 6.5. `DELETE /lecturers/{lecturer}` — Hapus Dosen & Akun User
- **Langkah di Postman:**
  1. Pilih method **DELETE** ke `{{base_url}}/lecturers/{id}` (misalnya ID dosen yang baru dibuat).
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `204 No Content`.

---

### 7. Manajemen Mata Kuliah (`Courses`)

#### 7.1. `GET /courses` — Daftar Mata Kuliah
- **Role:** Semua role terautentikasi (`admin`, `lecturer`, `student`)
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/courses`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Tab **Params** *(opsional)*: `search` (kode/nama), `lecturer_id`, `per_page`.
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar mata kuliah beserta dosen pengampu dan `enrollments_count`.

#### 7.2. `POST /courses` — Tambah Mata Kuliah Baru
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/courses`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "code": "TIK-6555",
       "name": "Website dan Sistem Mobile",
       "credits": 3,
       "description": "Pengembangan aplikasi web modern dan layanan REST API.",
       "lecturer_id": 4
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `201 Created` berisi data mata kuliah baru.

#### 7.3. `GET /courses/{course}` — Detail Mata Kuliah
- **Role:** Semua role terautentikasi
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/courses/1`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi detail mata kuliah ID `1`.

#### 7.4. `PUT` / `PATCH /courses/{course}` — Perbarui Mata Kuliah
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **PATCH** ke `{{base_url}}/courses/1`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "name": "Pemrograman Web Lanjut",
       "credits": 4
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi data mata kuliah yang telah diperbarui.

#### 7.5. `DELETE /courses/{course}` — Hapus Mata Kuliah
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **DELETE** ke `{{base_url}}/courses/{id}` (gunakan ID mata kuliah hasil pembuatan baru).
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `204 No Content`.

---

### 8. KRS & Penilaian (`Enrollments & Grades`)

#### 8.1. `GET /enrollments` — Daftar Pengambilan Mata Kuliah (KRS)
- **Role:** Semua role terautentikasi (`admin`: semua data, `lecturer`: kelas yang diampu, `student`: milik sendiri)
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/enrollments`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Tab **Params** *(opsional)*: `course_id`, `student_id`, `academic_year` (misal `2025/2026`), `semester` (`odd` atau `even`), `per_page`.
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar enrollment beserta nilai jika sudah dinilai.

#### 8.2. `POST /enrollments` — Ambil Mata Kuliah (Enroll)
- **Role:** `admin`, `student`
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/enrollments`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin` atau `student`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "student_id": 1,
       "course_id": 1,
       "academic_year": "2026/2027",
       "semester": "odd"
     }
     ```
     *(Catatan: Jika login sebagai `student`, field `student_id` boleh dikosongkan karena otomatis menggunakan ID mahasiswa yang login).*
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `201 Created` berisi data enrollment baru.

#### 8.3. `PUT /enrollments/{enrollment}/grade` — Input / Update Nilai Mahasiswa
- **Role:** `admin`, atau `lecturer` pengampu mata kuliah tersebut
- **Langkah di Postman:**
  1. Pilih method **PUT** ke `{{base_url}}/enrollments/1/grade`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin` atau `lecturer@example.com`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "score": 88.5
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi enrollment dengan `score: 88.5` dan `grade: "A"` (otomatis dihitung: A ≥ 85, B ≥ 70, C ≥ 55, D ≥ 40, E < 40).

#### 8.4. `POST /courses/{course}/grades/import` — Import Nilai Massal via CSV
- **Role:** `admin`, atau `lecturer` pengampu mata kuliah tersebut
- **Langkah di Postman:**
  1. Buat file `grades.csv` di komputer Anda dengan isi contoh berikut (gunakan NIM mahasiswa yang terdaftar pada course & semester terkait):
     ```csv
     nim,score
     2025573010099,90
     ```
  2. Pilih method **POST** ke `{{base_url}}/courses/1/grades/import`.
  3. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin` atau `lecturer@example.com`).
  4. Tab **Body** → pilih **form-data** (bukan `raw`), lalu tambahkan key berikut:
     | Key | Type | Value |
     |-----|------|-------|
     | `file` | **File** | Pilih file `grades.csv` |
     | `academic_year` | **Text** | `2025/2026` |
     | `semester` | **Text** | `odd` |
  5. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi ringkasan `{"data": {"processed": ..., "graded": ..., "failed": ..., "errors": [...]}}`.

#### 8.5. `DELETE /enrollments/{enrollment}` — Batalkan / Drop KRS
- **Role:** `admin` (semua enrollment), `student` (hanya milik sendiri yang belum memiliki nilai)
- **Langkah di Postman:**
  1. Pilih method **DELETE** ke `{{base_url}}/enrollments/{id}` (gunakan ID enrollment yang belum dinilai).
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `204 No Content`.

---

### 9. Jadwal Kuliah (`Timetable & Schedules`)

#### 9.1. `POST /courses/{course}/schedules` — Tambah Jadwal Mata Kuliah
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **POST** ke `{{base_url}}/courses/1/schedules`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "academic_year": "2025/2026",
       "semester": "odd",
       "day_of_week": 1,
       "start_time": "08:00",
       "end_time": "10:30",
       "room": "Lab Jaringan 1"
     }
     ```
     *(Catatan: `day_of_week` bernilai `1` Senin s/d `7` Minggu. Jika ruangan bentrok di hari, jam, dan semester yang sama, API mengembalikan `422 Unprocessable Content`).*
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `201 Created` berisi slot jadwal baru.

#### 9.2. `GET /courses/{course}/schedules` — Lihat Jadwal pada Satu Mata Kuliah
- **Role:** Semua role terautentikasi
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/courses/1/schedules`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar slot jadwal untuk mata kuliah ID `1`.

#### 9.3. `GET /timetable` — Lihat Jadwal Kuliah (Sesuai Role)
- **Role:** Semua role terautentikasi (`admin`: semua jadwal, `lecturer`: jadwal mata kuliah yang diampu, `student`: jadwal mata kuliah yang diambil)
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/timetable`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Tab **Params** *(opsional)*: `academic_year` = `2025/2026`, `semester` = `odd`.
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi jadwal berurutan menurut hari dan jam mulai.

#### 9.4. `PUT` / `PATCH /schedules/{schedule}` — Perbarui Slot Jadwal
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **PATCH** (atau **PUT**) ke `{{base_url}}/schedules/1`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "start_time": "09:00",
       "end_time": "11:30",
       "room": "Lab Jaringan 2"
     }
     ```
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi slot jadwal yang telah diperbarui.

#### 9.5. `DELETE /schedules/{schedule}` — Hapus Slot Jadwal
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **DELETE** ke `{{base_url}}/schedules/1`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `204 No Content`.

---

### 10. Presensi / Kehadiran (`Attendance`)

#### 10.1. `POST /courses/{course}/attendance` — Catat / Perbarui Presensi Kelas
- **Role:** `admin`, atau `lecturer` pengampu mata kuliah tersebut
- **Langkah di Postman:**
  1. Pastikan Anda mengetahui `enrollment_id` yang terdaftar di `course_id = 1` (bisa dilihat dari `GET /enrollments?course_id=1`, pada data seed biasanya `enrollment_id: 1` dan `2`).
  2. Pilih method **POST** ke `{{base_url}}/courses/1/attendance`.
  3. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin` atau `lecturer@example.com`).
  4. Tab **Body** → **raw (JSON)**:
     ```json
     {
       "date": "2025-09-01",
       "records": [
         {
           "enrollment_id": 1,
           "status": "present",
           "note": "Hadir tepat waktu"
         },
         {
           "enrollment_id": 2,
           "status": "sick",
           "note": "Surat keterangan dokter"
         }
       ]
     }
     ```
     *(Pilihan `status`: `present`, `absent`, `excused`, `sick`; `date` tidak boleh melebihi tanggal hari ini).*
  5. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` (`{"success": true, "date": "2025-09-01", "recorded": 2}`). Bersifat idempoten jika dikirim ulang pada tanggal yang sama.

#### 10.2. `GET /courses/{course}/attendance` — Lihat Presensi Kelas per Tanggal
- **Role:** `admin`, atau `lecturer` pengampu mata kuliah tersebut
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/courses/1/attendance`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin` atau `lecturer@example.com`).
  3. Tab **Params** *(wajib)*: `date` = `2025-09-01`.
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar kehadiran mahasiswa pada tanggal tersebut.

#### 10.3. `GET /students/{student}/attendance` — Rekap Persentase Kehadiran Mahasiswa
- **Role:** `admin`, `lecturer`, atau `student` pemilik ID tersebut
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/students/1/attendance`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}`.
  3. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi rekap per mata kuliah (`sessions`, `present`, `absent`, `excused`, `sick`, dan `attendance_rate`).

---

### 11. Log Audit (`Audit Logs`)

#### 11.1. `GET /audit-logs` — Lihat Riwayat Aktivitas & Perubahan Data
- **Role:** `admin`
- **Langkah di Postman:**
  1. Pilih method **GET** ke `{{base_url}}/audit-logs`.
  2. Tab **Authorization**: **Bearer Token** → `{{token}}` (`admin`).
  3. Tab **Params** *(opsional)*:
     - `event`: `created`, `updated`, `deleted`, `login`, `login_failed`, `logout`, `password_reset`, `grades_imported`, `attendance_recorded`
     - `user_id`: filter berdasarkan ID user pelaku (misal `1`)
     - `auditable_type`: misal `App\Models\Student`
     - `auditable_id`: misal `1`
     - `per_page`: `1`–`100` (default `20`)
  4. Klik **Send**.
- **Ekspektasi Respons:** Status `200 OK` berisi daftar log audit terpaginasi lengkap dengan `old_values`, `new_values`, `ip_address`, dan `user_agent`.
