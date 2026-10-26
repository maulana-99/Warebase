# Warebase

Warebase adalah aplikasi web **Sistem Manajemen Inventaris Gudang**. Aplikasi ini membantu pengelola gudang mencatat data barang, kategori, dan supplier, serta mencatat setiap barang masuk dan barang keluar sehingga jumlah stok selalu akurat.

Aplikasi dibangun dengan arsitektur **Model-View-Controller (MVC)** menggunakan framework Laravel dan basis data relasional MySQL. Project ini dibuat untuk UAS mata kuliah WAD04 - Web Application Development.

## Fitur Utama

- Autentikasi pengguna: registrasi, login, logout, dan manajemen session.
- Hak akses berbasis peran (role): **Admin** dan **Staff**.
- Dashboard ringkasan: jumlah produk, peringatan stok menipis, dan transaksi terbaru.
- CRUD data master: kategori, supplier, dan produk.
- Pencatatan barang masuk dan barang keluar. Stok otomatis diperbarui, dan barang keluar ditolak jika stok tidak cukup.
- Laporan pergerakan stok dengan filter tanggal.
- Kelola pengguna (khusus Admin).

## Hak Akses

| Fitur | Admin | Staff |
|---|:---:|:---:|
| Lihat dashboard dan data produk | Ya | Ya |
| Catat barang masuk dan keluar | Ya | Ya |
| Tambah, ubah, hapus kategori, supplier, produk | Ya | Tidak |
| Lihat laporan | Ya | Ya |
| Kelola pengguna | Ya | Tidak |

## Teknologi

- PHP 8.3+ dan Laravel 13
- MySQL 8
- Blade, Tailwind CSS, dan Vite
- Docker Compose (opsional, untuk menjalankan MySQL dan phpMyAdmin)

## Kebutuhan Sistem

- PHP 8.3 atau lebih baru, dengan extension `pdo_mysql`, `mbstring`, `openssl`, `intl`, `bcmath`
- Composer 2
- Node.js 20+ dan npm
- MySQL 8, melalui Docker **atau** instalasi lokal (XAMPP, Laragon, MySQL Server)

## Cara Menjalankan di Komputer Lokal

### 1. Clone repository

```bash
git clone https://github.com/maulana-99/Warebase.git
cd Warebase
```

### 2. Install dependency

```bash
composer install
npm install
```

### 3. Siapkan file environment

```bash
cp .env.example .env
php artisan key:generate
```

Di Windows (Command Prompt), gunakan `copy .env.example .env`.

### 4. Siapkan database MySQL

Pilih **salah satu** cara berikut.

**Opsi A: Docker (disarankan)**

```bash
docker compose up -d
```

Perintah ini menjalankan MySQL 8 dan phpMyAdmin. Isi `.env` bawaan sudah sesuai, jadi tidak perlu diubah.

- phpMyAdmin: http://localhost:8080 (user `app`, password `secret`)

**Opsi B: MySQL lokal (XAMPP, Laragon, dan sejenisnya)**

1. Buat database baru bernama `warebase`.
2. Sesuaikan koneksi database di file `.env`, misalnya untuk XAMPP:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=warebase
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Buat tabel dan isi data contoh

```bash
php artisan migrate --seed
```

Sebagai alternatif, struktur dan data contoh juga tersedia dalam file `database/warebase.sql`. File ini bisa di-import lewat phpMyAdmin.

### 6. Build asset dan jalankan aplikasi

```bash
npm run build
php artisan serve
```

Buka http://localhost:8000 di browser.

## Akun Login Contoh

Akun berikut dibuat otomatis oleh seeder:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@warebase.test` | `password` |
| Staff | `staff@warebase.test` | `password` |

## Menjalankan Test

Test otomatis memakai database terpisah bernama `warebase_test`. Jika memakai Docker, database ini sudah dibuat otomatis. Jika memakai MySQL lokal, buat database `warebase_test` terlebih dahulu.

```bash
php artisan test
```

## Aplikasi Online

- Live app: _menyusul_
- Repository: https://github.com/maulana-99/Warebase

## Struktur Folder Penting

| Folder | Isi |
|---|---|
| `app/Models/` | Model Eloquent (representasi tabel dan relasinya) |
| `app/Http/Controllers/` | Controller (logika aplikasi) |
| `app/Http/Requests/` | Validasi input server-side |
| `resources/views/` | View Blade (tampilan) |
| `routes/web.php` | Daftar routing aplikasi |
| `database/migrations/` | Struktur tabel database |
| `database/seeders/` | Data contoh |
| `tests/` | Test otomatis |
