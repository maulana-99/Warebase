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

Project ini bisa dijalankan di **Windows, macOS, maupun Linux**.

- PHP 8.3.36, 8.4.27, 8.5.12 atau lebih baru, dengan extension `pdo_mysql`, `mbstring`, `openssl`, `intl`, `zip`, `bcmath`
- Composer 2
- Node.js 22.23.4, 24.22.1, 26.12.1 atau lebih baru, dan npm
- MySQL 8, melalui Docker **atau** instalasi lokal

> **Keamanan:** versi minimum di atas adalah rilis yang menambal celah keamanan Oktober 2026: remote code execution di PHP (CVE-2026-109618, CVE-2026-109619) dan di dekoder Brotli Node.js (CVE-2026-111497), serta sertifikat eMudhra yang bocor. PHP 8.2 ke bawah dan Node.js 20 ke bawah tidak didukung. `composer.json` menolak instalasi di PHP yang belum ditambal. Perbarui juga CA bundle sistem operasi (paket `ca-certificates`).

Cek versi yang terpasang:

```bash
php -v
composer -V
node -v
```

### Rekomendasi instalasi per sistem operasi

| Sistem operasi | Cara termudah |
|---|---|
| Windows | [Laragon](https://laragon.org) (sudah berisi PHP, MySQL, Composer, Node.js) atau [Laravel Herd](https://herd.laravel.com) + MySQL |
| Windows dengan XAMPP | Bisa dipakai, **asalkan versi PHP-nya 8.3 atau lebih baru**. Lihat catatan XAMPP di bawah. |
| macOS | [Laravel Herd](https://herd.laravel.com) atau Homebrew (`brew install php composer node mysql`) |
| Linux | Package manager distro (`apt`, `dnf`, `pacman`) untuk PHP, Composer, dan Node.js; MySQL lewat Docker |

**Catatan XAMPP (Windows):**

1. Pastikan PHP bawaan XAMPP versi 8.3+. Cek dengan `php -v`. Jika versinya lebih lama, gunakan Laragon atau Herd.
2. Aktifkan extension yang dibutuhkan. Buka `C:\xampp\php\php.ini`, hapus tanda `;` di depan baris berikut, lalu restart Apache/terminal:

```ini
extension=intl
extension=zip
extension=pdo_mysql
```

3. Install [Composer for Windows](https://getcomposer.org/download/) dan [Node.js](https://nodejs.org) secara terpisah.
4. Jalankan MySQL dari XAMPP Control Panel. Apache tidak perlu dinyalakan, karena aplikasi dijalankan dengan `php artisan serve`.

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

macOS / Linux / Git Bash / PowerShell:

```bash
cp .env.example .env
php artisan key:generate
```

Windows Command Prompt (CMD):

```bat
copy .env.example .env
php artisan key:generate
```

### 4. Siapkan database MySQL

Pilih **salah satu** cara berikut.

**Opsi A: MySQL lokal (XAMPP, Laragon, Herd, MySQL Server)**

1. Nyalakan MySQL (misalnya lewat XAMPP Control Panel atau Laragon).
2. Buat database baru bernama `warebase`, misalnya lewat phpMyAdmin (http://localhost/phpmyadmin).
3. Sesuaikan koneksi database di file `.env`. Contoh untuk XAMPP/Laragon (user `root` tanpa password):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=warebase
DB_USERNAME=root
DB_PASSWORD=
```

**Opsi B: Docker (Windows, macOS, Linux)**

Butuh [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/macOS) atau Docker Engine (Linux). Pastikan port `3306` tidak sedang dipakai MySQL lain (misalnya MySQL XAMPP).

```bash
docker compose up -d
```

Perintah ini menjalankan MySQL 8 dan phpMyAdmin. Isi `.env` bawaan sudah sesuai untuk Docker, jadi tidak perlu diubah.

- phpMyAdmin: http://localhost:8080 (user `app`, password `secret`)

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

## Masalah yang Sering Terjadi

| Pesan error | Solusi |
|---|---|
| `could not find driver` | Extension `pdo_mysql` belum aktif. Aktifkan di `php.ini`. |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL belum menyala, atau `DB_HOST`/`DB_PORT` di `.env` salah. |
| `SQLSTATE[HY000] [1045] Access denied` | `DB_USERNAME`/`DB_PASSWORD` di `.env` tidak sesuai dengan MySQL yang dipakai. |
| `Unknown database 'warebase'` | Database `warebase` belum dibuat. |
| `Vite manifest not found` | Jalankan `npm install` lalu `npm run build`. |
| `Your requirements could not be resolved` saat `composer install` | Versi PHP di bawah 8.3, atau extension `intl`/`zip` belum aktif. |

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
