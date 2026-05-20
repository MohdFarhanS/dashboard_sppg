# Dashboard MBG — Monitoring Gizi & Biaya Produksi

Sistem monitoring berbasis web untuk program **Makan Bergizi Gratis (MBG)** yang dikelola oleh Satuan Pelayanan Pemenuhan Gizi (SPPG). Dashboard ini membantu pengelola memantau kandungan gizi menu harian dan efisiensi biaya produksi secara real-time.

---

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Tech Stack](#tech-stack)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Struktur Peran Pengguna](#struktur-peran-pengguna)
- [Panduan Penggunaan](#panduan-penggunaan)
- [Struktur Database](#struktur-database)
- [API Internal](#api-internal)
- [Struktur Direktori](#struktur-direktori-penting)

---

## Fitur Utama

### Halaman Publik (Landing Page)
- Halaman beranda publik tanpa perlu login (`/`)
- Statistik real-time: rata-rata kalori, biaya/porsi, total porsi bulan ini, dan % AKG
- Kartu "Menu Hari Ini" menampilkan foto menu yang sudah difinalisasi
- Formulir kontak untuk mengirim pesan ke pengelola SPPG (tersimpan di `pesan_masuks`)

### Manajemen Menu Harian
- Input menu harian via **Simulasi Menu** — tidak ada form input langsung
- Pilih **kelompok sasaran** (12 kelompok: TK/PAUD, SD 1–3, SD 4–6, SMP, SMA, Balita 1–3, Balita 4–6, Ibu Hamil T1/T2/T3, Ibu Menyusui)
- Status menu: **Draft** (dapat diedit) dan **Final** (terkunci & masuk laporan)
- **Wajib upload foto menu** sebelum finalisasi — tombol Finalisasi dinonaktifkan jika foto belum ada
- Constraint unik: satu menu per hari per `kelompok_sasaran` (satu SPPG bisa punya banyak menu per hari untuk kelompok berbeda)

### Data Bahan Pangan (TKPI)
- Database **Tabel Komposisi Pangan Indonesia** (845+ bahan pangan)
- Informasi proksimat, mineral, dan vitamin per 100g BDD
- Pencarian dan filter berdasarkan nama, kode, dan kategori
- Toggle aktif/nonaktif bahan pangan
- Import data massal via CSV (ketua_sppg)

### Simulasi Menu
- Rakit kombinasi bahan pangan sebelum menyimpan sebagai menu harian
- Pilih kelompok sasaran untuk target AKG yang akurat per kelompok (12 kelompok)
- Kalkulasi estimasi gizi dan biaya secara real-time via AJAX
- Perbandingan 8 nutrisi (Energi, Protein, Lemak, Karbohidrat, Serat, Kalsium, Besi, Vit C) vs. AKG Makan Siang per kelompok
- Status nutrisi: **Kurang** (<80% AKG), **Cukup** (80–120%), **Lebih** (>120%)
- Simpan langsung sebagai Menu Harian (draft) atau edit menu yang sudah ada

### Monitoring Gizi
- Pemenuhan gizi harian dibandingkan dengan AKG makan siang per kelompok sasaran
- Grafik tren energi harian dalam satu bulan
- Rata-rata bulanan dan perbandingan vs AKG dalam grafik batang
- Tabel daftar menu final bulan ini dengan status gizi masing-masing

### Monitoring Biaya Produksi
- Kalkulasi cost per porsi berdasarkan harga bahan aktif pada tanggal menu
- Perbandingan cost aktual vs. anggaran per porsi
- Grafik tren biaya vs. anggaran harian
- Manajemen harga bahan dengan sistem tarif time-based (berlaku mulai–sampai)
- Harga bahan dikunci sebagai snapshot saat menu difinalisasi

### Budget Alert
- Notifikasi otomatis menu yang melebihi anggaran (Over Budget: >100%)
- Peringatan menu mendekati batas anggaran (Warning: ≥85%)
- Badge notifikasi di navbar dengan jumlah alert aktif (hanya bulan berjalan, dapat di-dismiss per sesi)
- Kartu alert per menu dengan progress bar penyerapan anggaran

### Laporan
- Laporan gizi bulanan (perbandingan AKG)
- Laporan biaya produksi bulanan
- Export ke **Excel** (.xlsx via FastExcel) dan **PDF** (via DomPDF)
- Fitur cetak langsung dari browser

### Administrasi
- **Superadmin**: kelola pengguna (tambah/edit/reset password/hapus), semua role kecuali superadmin
- **Ketua SPPG**: kelola anggaran per porsi per kelompok dengan periode berlaku, import TKPI, inbox pesan masuk
- **Akuntan**: input dan hapus harga bahan

---

## Tech Stack

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Bootstrap 5.3, Vanilla JS |
| Ikon | Font Awesome 6.5 |
| Chart | Chart.js 4.4 |
| Font | Plus Jakarta Sans |
| Database | MySQL (dev: SQLite) |
| PDF Export | barryvdh/laravel-dompdf |
| Excel Export | rap2hpoutre/fast-excel |
| Autocomplete | Custom AJAX + Fetch API |

---

## Persyaratan Sistem

- **PHP** >= 8.2
- **Composer** >= 2.x
- **Node.js** >= 18.x & NPM
- **MySQL** >= 8.0 (atau SQLite untuk development)
- **PHP Extensions:** BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

---

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/MohdFarhanS/dashboard_mbg.git
cd dashboard_mbg
```

### 2. Install Dependensi PHP

```bash
composer install
```

### 3. Salin File Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Konfigurasi Database

Edit file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dashboard_mbg
DB_USERNAME=root
DB_PASSWORD=your_password

UNIT_SPPG="SPPG Utama"
```

### 5. Migrasi & Seed Database

```bash
php artisan migrate
php artisan db:seed
```

Seeder akan membuat 4 akun pengguna dan 845+ data bahan pangan TKPI:

| Email | Password | Role |
|---|---|---|
| `superadmin@mbg.id` | `password123` | superadmin |
| `ketua@mbg.id` | `password123` | ketua_sppg |
| `gizi@mbg.id` | `password123` | ahli_gizi |
| `akuntan@mbg.id` | `password123` | akuntan |

### 6. Buat Symlink Storage

```bash
php artisan storage:link
```

Diperlukan agar foto menu dapat diakses dari browser.

### 7. Build Frontend & Jalankan Server

```bash
npm install
npm run build
php artisan serve
```

Akses aplikasi di `http://localhost:8000`.

**Mode development (semua service sekaligus):**

```bash
composer dev
```

---

## Konfigurasi

### Nilai AKG per Kelompok

Edit `app/Constants/AKG.php`. Konstanta yang tersedia:

- `AKG::HARIAN` — Target harian referensi anak 7–12 tahun
- `AKG::MAKAN_SIANG` — Target makan siang (32,5% dari AKG harian)
- `AKG::KELOMPOK` — Target per kelompok sasaran (12 kelompok)
- `AKG::PCT_PAGI` (0.225) dan `AKG::PCT_SIANG` (0.325) — Proporsi waktu makan

Untuk mengubah target kelompok, edit nilai pada `AKG::KELOMPOK` di file tersebut.

### Unit SPPG

Set nama unit di `.env`:
```env
UNIT_SPPG="Nama SPPG Anda"
```

---

## Struktur Peran Pengguna

| Fitur | Superadmin | Ketua SPPG | Ahli Gizi | Akuntan |
|---|:---:|:---:|:---:|:---:|
| Kelola Pengguna | ✅ | ❌ | ❌ | ❌ |
| Dashboard | ❌ | ✅ | ✅ | ✅ |
| Lihat Menu Harian | ❌ | ✅ | ✅ | ❌ |
| Buat/Edit/Hapus/Finalisasi Menu | ❌ | ❌ | ✅ | ❌ |
| Upload Foto Menu | ❌ | ❌ | ✅ | ❌ |
| Simulasi Menu | ❌ | ❌ | ✅ | ❌ |
| Monitoring Gizi | ❌ | ✅ | ✅ | ❌ |
| Monitoring Biaya | ❌ | ✅ | ❌ | ✅ |
| Budget Alert | ❌ | ✅ | ❌ | ✅ |
| Lihat Harga Bahan | ❌ | ✅ | ❌ | ✅ |
| Tambah/Hapus Harga Bahan | ❌ | ❌ | ❌ | ✅ |
| Kelola Anggaran Porsi | ❌ | ✅ | ❌ | ❌ |
| Laporan & Export | ❌ | ✅ | ✅ | ✅ |
| Lihat Bahan Pangan (TKPI) | ❌ | ✅ | ✅ | ✅ |
| Kelola Bahan Pangan (CRUD) | ❌ | ✅ | ❌ | ❌ |
| Import TKPI CSV | ❌ | ✅ | ❌ | ❌ |
| Inbox Pesan Masuk | ❌ | ✅ | ❌ | ❌ |

> Superadmin diarahkan ke halaman **Manajemen Pengguna** setelah login, bukan Dashboard.

---

## Panduan Penggunaan

### Alur Kerja Lengkap

```
[Superadmin] — Setup awal
  └─ Tambah pengguna: Ketua SPPG, Ahli Gizi, Akuntan

[Ketua SPPG] — Setup operasional
  └─ Set anggaran per porsi (menu Anggaran)
  └─ (Delegasi ke Akuntan) Input harga bahan

[Akuntan] — Harga bahan
  └─ Monitoring Biaya → Kelola Harga Bahan → Tambah Tarif Baru

[Ahli Gizi] — Harian
  └─ Simulasi → Isi data menu + bahan → Hitung Estimasi → Simpan (Draft)
  └─ Menu Harian → Upload Foto → Finalisasi (Final)

[Ketua SPPG / Ahli Gizi / Akuntan] — Monitoring & Laporan
  └─ Dashboard → ringkasan bulanan
  └─ Monitoring Gizi (Ketua/Ahli Gizi) → pantau AKG per kelompok
  └─ Monitoring Biaya (Ketua/Akuntan) → pantau cost vs anggaran
  └─ Budget Alert → investigasi menu bermasalah
  └─ Laporan → export Excel/PDF

[Ketua SPPG] — Komunikasi
  └─ Pesan Masuk → baca pesan dari landing page
```

---

### Membuat Menu Harian

Alur pembuatan menu harian dilakukan melalui **Simulasi Menu**:

1. **Buka Simulasi** — klik Simulasi di sidebar
2. **Isi data dasar**: tanggal, kelompok sasaran, jumlah porsi, nama menu (opsional)
3. **Tambah bahan pangan** — ketik nama bahan (autocomplete TKPI), isi jumlah gram per porsi
4. **Klik Hitung Estimasi** — panel kanan menampilkan:
   - Progress bar 8 nutrisi vs AKG makan siang kelompok yang dipilih
   - Estimasi biaya total, cost per porsi, dan perbandingan vs anggaran
5. **Simpan ke Menu Harian** — tersimpan sebagai **Draft**
6. **Upload Foto** — buka Menu Harian, klik Upload Foto (JPG/PNG/WebP, maks 2 MB)
7. **Finalisasi** — klik Finalisasi setelah foto terupload

> Menu **Draft** tidak muncul di laporan, grafik monitoring, dan landing page. Hanya menu **Final** yang dihitung.

---

### Manajemen Harga Bahan (Akuntan)

1. Buka **Monitoring Biaya → Kelola Harga Bahan**
2. Klik **+ Tambah Tarif Baru**
3. Pilih bahan pangan, isi harga per 100g, tanggal mulai berlaku
4. Simpan

Sistem selalu mengambil harga aktif pada tanggal menu (`berlaku_mulai <= tanggal AND (berlaku_sampai IS NULL OR berlaku_sampai >= tanggal)`). Saat menu difinalisasi, harga dikunci sebagai snapshot di `menu_detail_bahans.harga_per_100g`.

---

### Kelola Anggaran Porsi (Ketua SPPG)

1. Buka menu **Anggaran** di sidebar
2. Klik **+ Tambah Anggaran**
3. Pilih kelompok (Balita s/d Kelas 3 SD / Kelas 4 SD s/d Ibu Menyusui)
4. Isi nominal anggaran per porsi (Rp) dan tanggal mulai berlaku

Anggaran juga dikunci sebagai snapshot saat menu difinalisasi.

---

### Import TKPI CSV (Ketua SPPG)

1. Buka **Import TKPI** di sidebar
2. Upload file CSV (koma atau titik koma sebagai delimiter)
3. Review 10 baris preview
4. Pilih mode: **Skip** (abaikan duplikat) atau **Update** (perbarui data yang ada)
5. Klik Konfirmasi Import

Format minimal:
```
nama_bahan,energi,protein,lemak,karbohidrat
Nasi Putih,175,3.2,0.3,39.8
```

---

## Struktur Database

```
users                — Pengguna sistem (4 role: superadmin, ketua_sppg, ahli_gizi, akuntan)
bahan_pangans        — Data TKPI 845+ bahan pangan dengan nilai gizi per 100g BDD
menu_harians         — Menu harian; unique (tanggal, kelompok_sasaran); status: draft|final
menu_detail_bahans   — Bahan-bahan dalam satu menu + snapshot harga saat finalisasi
harga_bahans         — Harga bahan per 100g (time-based: berlaku_mulai, berlaku_sampai)
anggaran_porsis      — Anggaran per porsi per kelompok (time-based, per kelompok)
import_logs          — Riwayat import CSV TKPI
pesan_masuks         — Pesan kontak dari landing page (is_read, nama, no_hp, pesan)
```

**Relasi:**

```
users          ──< menu_harians         (user_id)
menu_harians   ──< menu_detail_bahans   (menu_harian_id, cascade delete)
bahan_pangans  ──< menu_detail_bahans   (bahan_pangan_id)
bahan_pangans  ──< harga_bahans         (bahan_pangan_id)
users          ──< anggaran_porsis      (created_by)
users          ──< import_logs          (user_id)
```

---

## API Internal

Semua endpoint memerlukan autentikasi (session Laravel). Tidak ada autentikasi token.

### Autocomplete Bahan Pangan
```
GET /api/bahan-pangan/search?q={keyword}&limit={n}
```
Tersedia untuk semua role operasional. Response menyertakan `harga_per_100g` aktif.

### Kalkulasi Simulasi (AJAX)
```
POST /simulasi/kalkulasi
Content-Type: application/json

{
  "bahans": [{"id": 1, "gram": 150, "porsi": 100}],
  "jumlah_porsi": 100,
  "tanggal": "2026-05-20",
  "kelompok": "SD_4_6"
}
```
`kelompok` adalah salah satu dari 12 kunci `AKG::KELOMPOK`. Response menyertakan `akg_target`, `persen_akg`, dan `anggaran_per_kelompok`.

### Upload Foto Menu
```
POST /menu-harian/{id}/upload-foto
Content-Type: multipart/form-data

foto_menu: <file> (JPG/PNG/WebP, maks 2 MB)
```
Hanya bisa dilakukan pada menu berstatus `draft`. Ahli gizi only.

### Tren Gizi Bulanan
```
GET /gizi/api/trend?bulan=2026-05
```

### Estimasi Biaya
```
POST /biaya/api/estimasi
```

---

## Struktur Direktori Penting

```
app/
├── Constants/
│   └── AKG.php                        — AKG target per kelompok (12 kelompok), proporsi waktu makan
├── Http/
│   ├── Controllers/
│   │   ├── LandingController.php      — Halaman publik + form kontak
│   │   ├── DashboardController.php    — Dashboard terpadu semua role operasional
│   │   ├── MenuHarianController.php   — CRUD menu, uploadFoto, finalize
│   │   ├── SimulasiController.php     — Simulasi + kalkulasi AJAX + simpan ke menu
│   │   ├── BahanPanganController.php  — CRUD TKPI + apiSearch autocomplete
│   │   ├── GiziController.php         — Monitoring gizi + API tren
│   │   ├── BiayaController.php        — Monitoring biaya + harga bahan + API estimasi
│   │   ├── AnggaranController.php     — Kelola anggaran porsi per kelompok
│   │   ├── LaporanController.php      — Laporan + export Excel & PDF
│   │   ├── BudgetAlertController.php  — Alert monitoring anggaran
│   │   ├── ImportTkpiController.php   — Import CSV TKPI (preview + konfirmasi)
│   │   ├── PesanMasukController.php   — Inbox pesan dari landing page
│   │   └── UserController.php         — Kelola pengguna (superadmin)
│   └── Middleware/
│       └── RoleMiddleware.php          — Role-based access control
├── Models/
│   ├── User.php                        — Role constants + helper methods
│   ├── MenuHarian.php                  — totalGizi(), totalBiaya(), evaluasiGizi(), statusAnggaran()
│   ├── BahanPangan.php                 — TKPI model + scope cari() + scopeKategori()
│   ├── MenuDetailBahan.php             — Line items menu + snapshot harga
│   ├── HargaBahan.php                  — hargaAktif() static method
│   ├── AnggaranPorsi.php               — aktif() static method, KELOMPOK_LABELS
│   ├── ImportLog.php                   — Riwayat import CSV
│   └── PesanMasuk.php                  — Pesan masuk + scope unread()
└── Providers/
    └── AppServiceProvider.php          — View composer: navAlertCount, navAlerts, pesanMasukCount

resources/views/
├── landing.blade.php                   — Halaman publik (standalone, tanpa layouts/app)
├── layouts/app.blade.php               — Layout utama semua halaman authenticated
├── partials/
│   ├── sidebar.blade.php               — Navigasi sidebar role-based
│   └── navbar.blade.php                — Navbar + badge alert + badge pesan
├── dashboard/index.blade.php
├── menu-harian/                        — index, show, edit (create via simulasi)
├── simulasi/index.blade.php
├── gizi/
├── biaya/
├── anggaran/
├── laporan/
├── budget-alert/
├── bahan-pangan/
├── import-tkpi/
├── pesan-masuk/
└── users/

database/
├── migrations/                         — 24 migration files (urutan timestamp)
├── seeders/
│   ├── DatabaseSeeder.php
│   ├── UserSeeder.php                  — 4 akun default
│   ├── BahanPanganSeeder.php           — 845+ data TKPI dari JSON
│   └── MenuDummySeeder.php             — Data dummy untuk testing
└── seeders/data/tkpi_seeder.json       — Data 845+ bahan pangan TKPI
```

---

*Dashboard MBG — Sistem Monitoring Gizi & Biaya Produksi untuk Program Makan Bergizi Gratis (MBG)*
