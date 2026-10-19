# appKantin — CI/CD dengan Docker Swarm

Aplikasi kantin berbasis PHP dan MySQL/MariaDB yang dideploy pada AWS EC2 menggunakan Docker Swarm. Proyek ini digunakan untuk praktikum CI/CD dengan GitHub Actions dan Docker Hub.

---

## 1. Tujuan Proyek

- Menjalankan aplikasi kantin dalam container Docker.
- Mendistribusikan aplikasi menggunakan Docker Swarm dengan tiga replika.
- Mengotomatisasi pemeriksaan sintaks PHP, build image, push image ke Docker Hub, dan deployment melalui GitHub Actions.
- Menguji pembaruan aplikasi dan mekanisme rollback tanpa mengganggu layanan produksi.

---

## 2. Arsitektur Infrastruktur

Infrastruktur menggunakan AWS EC2 di region `us-east-1`.

### Komponen utama:

| Komponen | Fungsi |
| :--- | :--- |
| **Swarm Manager** | Mengelola cluster dan deployment aplikasi |
| **Worker 1** | Menjalankan replika aplikasi |
| **Worker 2** | Menjalankan replika aplikasi |
| **Database Node** | Menyediakan layanan database MariaDB |
| **Docker Hub** | Menyimpan image aplikasi |
| **GitHub Actions** | Menjalankan pipeline CI/CD |

Service aplikasi bernama `kantin` berjalan dengan tiga replika dan menggunakan port `8080`.

> **Catatan:** Tiga replika dikelola oleh Docker Swarm. Penempatan aktual container dapat berubah mengikuti kondisi cluster dan kebijakan scheduling Swarm.

---

## 3. Teknologi yang Digunakan

- PHP
- MySQL/MariaDB
- Docker dan Docker Swarm
- AWS EC2
- GitHub Actions
- Self-hosted GitHub Actions runner
- Docker Hub

---

## 4. Struktur Repository

```text
appKantin/
├── .github/
│   └── workflows/
│       └── deploy.yml
├── assets/
│   └── style.css
├── config/
│   └── db.php
├── Dockerfile
├── .dockerignore
├── .gitignore
├── README.md
├── index.php
├── header.php
├── footer.php
└── file PHP aplikasi lainnya
```

---

## 5. Menjalankan Aplikasi Secara Lokal

### Prasyarat
Pastikan perangkat sudah memiliki Git, Docker Desktop atau Docker Engine, serta akses ke source code repository.

### Clone repository:
```bash
git clone https://github.com/yasss31/appKantin.git
cd appKantin
```

### Opsi A — Menjalankan dengan Docker
Build image:
```bash
docker build -t appkantin:local .
```

Jalankan container:
```bash
docker run --rm -p 8080:8080 appkantin:local
```

Buka browser:
`http://localhost:8080/`

> **Catatan:** Perintah ini mengasumsikan image menyediakan layanan HTTP pada port 8080 (`trafex/php-nginx`). Koneksi database lokal harus disesuaikan dengan konfigurasi aplikasi dan lingkungan pengujian.

### Opsi B — PHP Built-in Server
Jika PHP dan database sudah terpasang, jalankan:
```bash
php -S 127.0.0.1:8080
```
Kemudian buka `http://127.0.0.1:8080/`.

> **Catatan:** Metode ini hanya untuk pengujian lokal. Pastikan database dan konfigurasi koneksi sudah tersedia.

---

## 6. Alur CI/CD

Workflow berada di `.github/workflows/deploy.yml` dan dipicu oleh push ke branch `main`.

### Urutan proses:
1. **Checkout**: mengambil source code terbaru.
2. **PHP lint**: memeriksa sintaks file PHP (`php -l`).
3. **Buildx setup**: menyiapkan Docker Buildx.
4. **Docker Hub login**: menggunakan GitHub Secrets.
5. **Build dan push**: membuat image dengan tag `latest` dan tag berdasarkan commit SHA.
6. **Deploy**: memperbarui service `kantin` dengan rolling update, atau membuat service jika belum tersedia.

Rolling update menggunakan:
- `--update-parallelism 1`
- `--update-delay 10s`

Pengaturan ini memperbarui satu task dalam satu waktu dengan jeda 10 detik.

---

## 7. Konfigurasi GitHub Actions

### GitHub Secrets
Buka repository GitHub, lalu masuk ke **Settings → Secrets and variables → Actions**.

Tambahkan:
| Secret | Isi |
| :--- | :--- |
| `DOCKERHUB_USERNAME` | Username Docker Hub |
| `DOCKERHUB_TOKEN` | Docker Hub access token |

> **Penting:** Jangan menyimpan token, password, private key, atau kredensial produksi langsung di source code maupun README.

### Self-hosted Runner
Workflow menggunakan label runner:
```yaml
runs-on: [self-hosted, swarm-manager]
```
Artinya, GitHub Actions membutuhkan self-hosted runner yang aktif dan memiliki kedua label tersebut.

Runner harus memiliki akses yang sesuai untuk menjalankan Docker, melakukan build image, dan mengelola Docker Swarm. Pastikan permission runner dibatasi sesuai kebutuhan praktikum.

---

## 8. Konfigurasi Database Produksi

Service produksi menggunakan Docker Config bernama `kantin_db_php`.

Config tersebut dipasang pada container di:
`/var/www/html/config/db.php`

File konfigurasi yang dipasang oleh Swarm menggantikan file pada lokasi tersebut di container. Dengan demikian, konfigurasi database produksi dipisahkan dari file konfigurasi default di repository.

### Penting:
- Jangan commit password database atau kredensial produksi.
- Jangan menghapus atau mengganti Docker Config produksi tanpa memastikan penggantinya sudah benar.
- Jika service dibuat ulang, pastikan `kantin_db_php` tersedia dan berisi konfigurasi yang valid.
- Konfigurasi database pada lingkungan lokal harus disiapkan secara terpisah.

---

## 9. Verifikasi Deployment

Jalankan perintah berikut pada node Manager.

### Memeriksa service
```bash
docker service ls
docker service ps kantin
docker service inspect kantin
```
Pastikan service `kantin` memiliki tiga replika yang berjalan.

### Memeriksa endpoint HTTP
Dari perangkat yang memiliki akses jaringan ke server, buka:
`http://<PUBLIC-IP-MANAGER>:8080/`

Atau gunakan:
```bash
curl -i http://<PUBLIC-IP-MANAGER>:8080/
```
Respons `HTTP 200` menunjukkan endpoint berhasil merespons permintaan. Namun, respons tersebut belum otomatis membuktikan seluruh fungsi aplikasi dan database berjalan benar.

### Memeriksa log
```bash
docker service logs --tail 100 kantin
```
Gunakan log untuk membantu menemukan masalah saat container dimulai atau ketika aplikasi mengakses database.

---

## 10. Pengujian Rolling Update dan Rollback

Pengujian rollback dilakukan menggunakan service pengujian terpisah bernama `kantin-test` agar service produksi tidak sengaja terganggu.

### Ringkasan pengujian:
1. Menjalankan service pengujian menggunakan image yang diketahui sehat.
2. Menguji image yang sengaja dibuat bermasalah.
3. Memverifikasi kegagalan respons HTTP pada service pengujian (HTTP 500).
4. Menjalankan rollback pada service pengujian (`docker service rollback kantin-test`).
5. Memastikan respons HTTP kembali normal (`HTTP 200`).
6. Memastikan service produksi `kantin` tetap berjalan.

> **Catatan:** Pengujian ini membuktikan mekanisme rollback pada service pengujian, bukan pengujian rollback langsung pada service produksi.

---

## 11. Catatan Pemindahan ke Laptop

Saat melanjutkan proyek pada perangkat lain:
1. Clone repository dari GitHub.
2. Pasang Git, Docker, dan PHP jika diperlukan untuk pengujian lokal.
3. Periksa workflow di `.github/workflows/deploy.yml`.
4. Pastikan GitHub Secrets tetap tersedia di repository.
5. Pastikan self-hosted runner pada Manager masih aktif.
6. Pastikan cluster Swarm dan Docker Config `kantin_db_php` tetap tersedia.
7. Uji aplikasi lokal sebelum melakukan perubahan yang memicu deployment produksi.

> **Penting:** Source code dapat dipindahkan melalui GitHub, tetapi konfigurasi EC2, cluster Swarm, Docker Config, dan self-hosted runner tidak otomatis ikut tersimpan dalam repository.

---

## 12. Status dan Batasan

Dokumentasi ini menjelaskan alur CI/CD dan verifikasi yang dilakukan pada lingkungan praktikum. Hasil pengujian harus dibedakan dari pengujian manual seluruh fitur aplikasi melalui browser.

Sebelum menyatakan proyek selesai, lakukan pengujian browser untuk halaman utama, fungsi CRUD, pemesanan, dan koneksi database.

Repository: [https://github.com/yasss31/appKantin](https://github.com/yasss31/appKantin)
