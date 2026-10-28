# Warebase

Warebase adalah aplikasi web **Sistem Manajemen Inventaris Gudang** untuk gudang kantor, kampus, sekolah, atau instansi yang mengelola barang habis pakai (alat tulis, kertas, kebersihan, elektronik kecil). Aplikasi ini mencatat setiap barang masuk dan keluar sehingga stok selalu akurat, dan dirancang agar **sulit dicurangi**: stok tidak bisa diubah tanpa transaksi, barang keluar harus disetujui orang lain, dan semua aktivitas tercatat.

Aplikasi dibangun dengan arsitektur **Model-View-Controller (MVC)** menggunakan framework Laravel dan basis data relasional MySQL. Project ini dibuat untuk UAS mata kuliah WAD04 - Web Application Development.

## Fitur Utama

- **Autentikasi**: login, logout, session, dan pembatasan percobaan login. Tidak ada registrasi publik; akun hanya dibuat oleh Admin.
- **Hak akses berbasis peran**: **Admin** (kepala gudang) dan **Staff** (petugas gudang).
- **Dashboard**: jumlah produk, stok menipis, permintaan yang menunggu persetujuan, grafik barang masuk/keluar 7 hari, dan transaksi terbaru.
- **Data master**: kategori, rak (lokasi penyimpanan), supplier, dan divisi. Data yang masih dipakai tidak bisa dihapus.
- **Produk**: pencarian SKU/nama, filter kategori dan stok menipis, rak, satuan, dan stok minimum. Stok tidak bisa diedit manual.
- **Barang masuk**: banyak barang sekaligus dari satu supplier; stok langsung bertambah.
- **Permintaan barang keluar**: diajukan untuk divisi dan nama penerima, lalu **disetujui atau ditolak Admin** (alasan wajib). Pembuat permintaan tidak bisa menyetujui permintaannya sendiri.
- **Penyesuaian stok (stock opname)**: Admin memasukkan hasil hitung fisik; hanya selisihnya yang dicatat, dengan alasan wajib.
- **Kartu Stok**: riwayat pergerakan stok per produk dengan saldo berjalan, plus peringatan jika stok berubah di luar transaksi.
- **Log aktivitas**: login, login gagal, dan setiap perubahan data tercatat beserta pengguna, waktu, IP, dan nilai lama/baru. Log tidak bisa diubah atau dihapus.
- **Laporan**: pergerakan stok per periode (stok awal, masuk, keluar, penyesuaian, stok akhir) dengan unduh CSV dan cetak, serta jejak kerja per pengguna.
- **Kelola pengguna** (khusus Admin).
- Tampilan **mode gelap/terang**, form dalam **popup**, dan **konfirmasi** sebelum aksi penting. Dioptimalkan untuk desktop dan tablet.

## Hak Akses

| Fitur | Admin | Staff |
|---|:---:|:---:|
| Lihat dashboard, produk, data master, Kartu Stok | Ya | Ya |
| Catat barang masuk | Ya | Ya |
| Buat permintaan barang keluar | Ya | Ya |
| Lihat permintaan | Semua | Milik sendiri |
| Setujui / tolak permintaan | Ya, kecuali milik sendiri | Tidak |
| Tambah, ubah, hapus data master dan produk | Ya | Tidak |
| Penyesuaian stok | Ya | Tidak |
| Laporan pergerakan stok | Ya | Ya |
| Laporan jejak kerja | Semua pengguna | Diri sendiri |
| Log aktivitas | Ya | Tidak |
| Kelola pengguna | Ya | Tidak |

## Keamanan dan Anti-Kecurangan

- Stok hanya berubah lewat transaksi (barang masuk, permintaan yang disetujui, atau penyesuaian). Transaksi tidak bisa dihapus; koreksi dilakukan dengan transaksi baru.
- Pemisahan tugas: orang yang mengajukan barang keluar tidak bisa menyetujuinya.
- Setiap transaksi menyimpan stok sebelum dan sesudah, sehingga Kartu Stok bisa membuktikan stok sistem cocok dengan riwayatnya.
- Proteksi CSRF, XSS (output Blade di-escape), dan SQL injection (Eloquent/Query Builder dengan parameter binding). Validasi input di server dengan Form Request.
- Penguncian baris database saat memproses stok, sehingga dua orang yang bekerja bersamaan tidak membuat stok salah hitung.

## Teknologi

- PHP 8.3+ (versi yang sudah ditambal, lihat di bawah) dan Laravel 13
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

Perintah ini menjalankan MySQL 8 dan phpMyAdmin. Isi `.env` bawaan sudah sesuai untuk Docker, jadi tidak perlu diubah. Database `warebase` dan `warebase_test` (untuk test) dibuat otomatis.

- phpMyAdmin: http://localhost:8080 (user `app`, password `secret`)
- Port MySQL dan phpMyAdmin hanya terbuka untuk komputer itu sendiri (`127.0.0.1`), tidak bisa diakses dari jaringan lain. Password di atas hanya untuk pengembangan lokal.
- Menghentikan: `docker compose down` (data tetap tersimpan). Menghapus data juga: `docker compose down -v`.

### 5. Buat tabel dan isi data contoh

```bash
php artisan migrate --seed
```

Seeder membuat 2 akun contoh, data master, 11 produk, dan stok awal melalui transaksi barang masuk (beberapa produk sengaja di bawah stok minimum). Untuk mengulang dari awal: `php artisan migrate:fresh --seed`.

Sebagai alternatif, struktur dan data contoh akan tersedia dalam file `database/warebase.sql` yang bisa di-import lewat phpMyAdmin (menyusul).

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

Akun baru dibuat oleh Admin lewat menu **Pengguna**.

### Mencoba alur utama

1. Login sebagai **Staff**, buka **Permintaan Barang**, lalu buat permintaan beberapa barang untuk sebuah divisi.
2. Logout, login sebagai **Admin**, buka permintaan tersebut, lalu **Setujui** (stok berkurang) atau **Tolak** dengan alasan.
3. Buka **Produk**, klik nama produk untuk melihat **Kartu Stok** dan saldo berjalannya.
4. Sebagai Admin, buka **Log Aktivitas** dan **Laporan** untuk melihat jejak semua aktivitas di atas.

Admin tidak bisa menyetujui permintaan yang dibuatnya sendiri; ini disengaja (pemisahan tugas).

## Menjalankan Test

Test otomatis memakai database terpisah bernama `warebase_test`. Jika memakai Docker, database ini sudah dibuat otomatis. Jika memakai MySQL lokal, buat database `warebase_test` terlebih dahulu.

```bash
php artisan test
```

## Deploy ke Server (Dokploy)

Panduan lengkap langkah demi langkah ada di [docs/deploy-dokploy.md](docs/deploy-dokploy.md). Ringkasannya:

Aplikasi dideploy sebagai satu container dari `Dockerfile` (PHP 8.5.12 + Apache; CSS/JS di-build saat image dibuat, jadi server tidak butuh Node.js). Database MySQL berjalan sebagai service terpisah di Dokploy.

1. **Database**: di Dokploy buat *Database → MySQL* dengan image `mysql:8.4`, database `warebase`, user `warebase`, password hasil generate. Jangan isi *External Port* agar database tidak terbuka ke internet. Klik **Deploy**.
2. **Aplikasi**: buat *Application* dari repository GitHub ini, *Build Type* `Dockerfile`, port container `80`.
3. **Environment** aplikasi:

```env
APP_NAME=Warebase
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...        # buat dengan: php artisan key:generate --show
APP_URL=https://domain-anda
APP_LOCALE=id
LOG_CHANNEL=stderr
SESSION_SECURE_COOKIE=true
DB_CONNECTION=mysql
DB_HOST=nama-app-database  # "App Name" database di Dokploy
DB_PORT=3306
DB_DATABASE=warebase
DB_USERNAME=warebase
DB_PASSWORD=...           # password database dari Dokploy
```

4. **Domain**: tambahkan domain di tab *Domains* dengan HTTPS (Let's Encrypt), lalu **Deploy**. Setiap container dijalankan, migrasi database berjalan otomatis (`docker/entrypoint.sh`).
5. **Data contoh** (sekali saja): buka tab *Terminal/Advanced* aplikasi dan jalankan `artisan db:seed --force` untuk membuat akun contoh dan stok awal.

Pengaturan keamanan di image: `APP_DEBUG=false`, `expose_php=Off`, `session.upload_progress.enabled=0`, header keamanan Apache, dan proxy yang dipercaya hanya alamat jaringan privat (Traefik Dokploy), bisa diubah lewat `TRUSTED_PROXIES`.

## Aplikasi Online

- Live app: _menyusul_
- Repository: https://github.com/maulana-99/Warebase

## Struktur Folder Penting

| Folder | Isi |
|---|---|
| `app/Models/` | Model Eloquent (representasi tabel dan relasinya) |
| `app/Http/Controllers/` | Controller (logika aplikasi) |
| `app/Http/Requests/` | Validasi input server-side |
| `app/Services/` | Logika stok (`StockService`) dan log aktivitas (`ActivityLogger`) |
| `app/Enums/` | Peran, jenis, dan status transaksi |
| `resources/views/` | View Blade (tampilan) |
| `routes/web.php` | Daftar routing aplikasi |
| `database/migrations/` | Struktur tabel database |
| `database/seeders/` | Data contoh |
| `tests/` | Test otomatis |
