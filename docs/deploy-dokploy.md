# Panduan Deploy Warebase ke Dokploy

Panduan ini menjelaskan cara menjalankan Warebase di server sendiri memakai [Dokploy](https://dokploy.com). Hasil akhirnya: aplikasi bisa diakses lewat `https://domain-anda`, database MySQL berjalan di server yang sama dan tidak terbuka ke internet, dan setiap push ke branch `main` bisa ter-deploy otomatis.

Nama menu di Dokploy bisa sedikit berbeda antar versi, tetapi alurnya sama.

## Gambaran

```
Internet ──HTTPS──> Traefik (bawaan Dokploy) ──> container "warebase-app" (PHP 8.5 + Apache, port 80)
                                                        │
                                                        └── jaringan internal ──> container "warebase-mysql" (MySQL 8.4)
```

- **Aplikasi** dibangun dari `Dockerfile` di repository ini. CSS/JS di-build saat image dibuat, jadi server tidak perlu Node.js.
- **Database** adalah service MySQL terpisah di Dokploy.
- Setiap kali container aplikasi dijalankan, migrasi database berjalan otomatis (`docker/entrypoint.sh`).

## 0. Persiapan

1. **Server dengan Dokploy terpasang** (VPS Linux, minimal 2 GB RAM). Panel Dokploy bisa dibuka di `http://IP-SERVER:3000`.
2. **Domain atau subdomain**, misalnya `warebase.domain-anda.com`. Buat **DNS record A** yang mengarah ke IP server. Tunggu sampai `ping warebase.domain-anda.com` menampilkan IP server.
3. **Akses GitHub** ke repository `maulana-99/Warebase`. Di Dokploy buka **Settings → Git → GitHub**, lalu hubungkan akun GitHub (Dokploy memasang GitHub App).
4. Di komputer lokal, buat `APP_KEY` khusus produksi:

   ```bash
   php artisan key:generate --show
   ```

   Simpan hasilnya (diawali `base64:`). **Jangan** memakai `APP_KEY` dari `.env` lokal.

## 1. Buat project

1. Buka panel Dokploy, menu **Projects → Create Project**.
2. Nama: `Warebase`. Klik **Create**.

## 2. Buat database MySQL

1. Di dalam project, klik **Create Service → Database → MySQL**.
2. Isi form:

   | Field | Isi |
   |---|---|
   | Name | `Warebase MySQL` |
   | App Name | `warebase-mysql` (ini menjadi **host** database untuk aplikasi) |
   | Database Name | `warebase` |
   | Database User | `warebase` |
   | Database Password | biarkan hasil generate, **salin dan simpan** |
   | Database Root password | biarkan hasil generate, simpan |
   | Docker image | `mysql:8.4` |

3. Klik **Create**, lalu di halaman database klik **Deploy**. Tunggu status menjadi hijau/running.
4. **Jangan isi External Port.** Tanpa external port, database hanya bisa diakses oleh aplikasi di jaringan internal Dokploy.

> Catatan: jika Dokploy menambahkan akhiran acak pada App Name (misalnya `warebase-mysql-x1y2z3`), pakai nama lengkap itu sebagai `DB_HOST`. Nama lengkapnya terlihat di halaman database (bagian *Internal Credentials* / *Internal Host*).

## 3. Buat aplikasi

1. Di project yang sama, klik **Create Service → Application**.
2. Name: `Warebase App`, App Name: `warebase-app`. Klik **Create**.
3. Tab **General → Provider**: pilih **GitHub**, repository `maulana-99/Warebase`, branch `main`. Klik **Save**.
4. **Build Type**: pilih **Dockerfile**. Dockerfile Path: `Dockerfile` (di root). Klik **Save**.

## 4. Isi environment

Tab **Environment**, tempel isi berikut lalu sesuaikan nilai yang bertanda `...`:

```env
APP_NAME=Warebase
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://warebase.domain-anda.com
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=warebase-mysql
DB_PORT=3306
DB_DATABASE=warebase
DB_USERNAME=warebase
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Penjelasan singkat:

- `APP_DEBUG=false` wajib di server agar detail error tidak terlihat oleh pengunjung.
- `APP_URL` harus `https://` dan sama persis dengan domain, agar link dan form memakai HTTPS.
- `SESSION_SECURE_COOKIE=true` membuat cookie login hanya dikirim lewat HTTPS.
- `LOG_CHANNEL=stderr` membuat log Laravel tampil di tab **Logs** Dokploy.
- `TRUSTED_PROXIES` tidak perlu diisi: bawaannya sudah mempercayai jaringan privat tempat Traefik berjalan.

Klik **Save**.

## 5. Pasang domain dan HTTPS

1. Tab **Domains → Add Domain**.
2. Isi:

   | Field | Isi |
   |---|---|
   | Host | `warebase.domain-anda.com` |
   | Path | `/` |
   | Container Port | `80` |
   | HTTPS | aktif |
   | Certificate | Let's Encrypt |

3. Klik **Create/Save**.

## 6. Deploy

1. Klik **Deploy** di halaman aplikasi.
2. Buka tab **Deployments** untuk melihat proses build. Build pertama butuh beberapa menit (mengunduh image PHP dan Node, memasang extension, build CSS/JS).
3. Setelah selesai, buka tab **Logs**. Log yang sehat berisi baris migrasi `DONE` lalu Apache `resuming normal operations`.

## 7. Isi data contoh (sekali saja)

Akun login contoh dibuat oleh seeder. Jalankan sekali setelah deploy pertama:

1. Di halaman aplikasi, buka **Terminal** (tombol *Open Terminal* / tab *Advanced → Terminal*), pilih container aplikasi.
2. Jalankan:

   ```bash
   php artisan db:seed --force
   ```

Seeder aman dijalankan lagi: jika data contoh sudah ada, seeder akan dilewati.

## 8. Cek hasil

- [ ] `https://warebase.domain-anda.com` membuka halaman login dengan gembok HTTPS.
- [ ] `https://warebase.domain-anda.com/up` menampilkan halaman status hijau.
- [ ] Login `admin@warebase.test` / `password` berhasil masuk ke dashboard dan tampilan (CSS) rapi.
- [ ] Login sebagai staff, buat permintaan barang; login sebagai admin, setujui; stok berkurang di Kartu Stok.
- [ ] Menu **Log Aktivitas** mencatat login tadi dengan IP asli Anda (bukan IP internal Docker).

Setelah aplikasi siap dinilai, **ganti password akun contoh** lewat menu Pengguna, atau berikan password-nya hanya kepada penguji.

## 9. Update aplikasi

- **Otomatis**: di tab **General**, aktifkan **Autodeploy**. Setiap merge/push ke `main` akan memicu build dan deploy baru. Migrasi baru berjalan otomatis saat container baru menyala.
- **Manual**: klik **Deploy** (atau **Redeploy**) kapan saja.
- **Kembali ke versi sebelumnya**: di tab **Deployments**, pilih deployment lama lalu **Rollback** (jika tersedia), atau `git revert` commit bermasalah lalu push ke `main`.

## 10. Backup database

Di halaman database MySQL, tab **Backups**: hubungkan tujuan penyimpanan (S3/MinIO/R2) di **Settings → Destinations**, lalu jadwalkan backup harian. Transaksi stok tidak pernah dihapus, jadi backup inilah pengaman utama data.

## Masalah yang sering terjadi

| Gejala | Penyebab dan solusi |
|---|---|
| **Bad Gateway / 502** | Container Port di domain bukan `80`, atau container belum selesai menyala. Cek tab **Logs**. |
| Log: `Database not reachable, giving up.` | `DB_HOST`, `DB_USERNAME`, atau `DB_PASSWORD` salah, atau database belum di-deploy. Samakan dengan data di halaman database, lalu **Redeploy**. |
| **500 Server Error** | `APP_KEY` kosong/salah. Isi dengan hasil `php artisan key:generate --show` lalu **Redeploy**. Detail error ada di tab **Logs** (karena `LOG_CHANNEL=stderr`). |
| Halaman tanpa CSS atau form gagal kirim (419) | `APP_URL` tidak memakai `https://` atau beda dengan domain. Perbaiki lalu **Redeploy**. |
| Login gagal terus dengan akun contoh | Seeder belum dijalankan. Lakukan langkah 7. |
| Sertifikat HTTPS tidak muncul | DNS domain belum mengarah ke IP server, atau port 80/443 server tertutup firewall. |
| Perubahan env tidak berpengaruh | Konfigurasi di-cache saat container menyala. Setelah mengubah env, selalu klik **Redeploy**. |

## Keamanan yang sudah diterapkan di image

- PHP 8.5.12 dan Node.js 22.23.4: versi yang menambal celah keamanan Oktober 2026.
- `APP_DEBUG=false`, `expose_php=Off`, versi Apache disembunyikan, header `X-Content-Type-Options`, `X-Frame-Options`, dan `Referrer-Policy`.
- `session.upload_progress.enabled=0` sebagai mitigasi CVE-2026-115560.
- Hanya proxy di jaringan privat (Traefik) yang dipercaya, sehingga IP di log aktivitas tidak bisa dipalsukan lewat header `X-Forwarded-For`.
- File `.env`, `.git`, dan folder test tidak ikut masuk image (`.dockerignore`).
