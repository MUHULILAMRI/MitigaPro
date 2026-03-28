<p align="center">
  <img src="logo.png" alt="MitigaPro Logo" width="80">
</p>

<h1 align="center">MitigaPro</h1>

<p align="center">
  <strong>Sistem Informasi Pengelolaan Pelatihan & Wilayah Kerja</strong><br>
  Balai Pengembangan Kompetensi PU Wilayah VIII Makassar
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Chart.js-FF6384?style=flat-square&logo=chartdotjs&logoColor=white" alt="Chart.js">
  <img src="https://img.shields.io/badge/FPDF-PDF_Export-red?style=flat-square" alt="FPDF">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

---

## 📋 Deskripsi

**MitigaPro** adalah sistem informasi berbasis web untuk **Balai Pengembangan Kompetensi (Bapekom) PU Wilayah VIII Makassar** yang mengelola:

- Data pengajar/instruktur pelatihan
- Identifikasi kebutuhan pelatihan per wilayah
- Data dinas instansi pemerintah
- Berita & informasi pelatihan
- Manajemen wilayah kerja (7 wilayah: Sulawesi Selatan, Sulawesi Barat, Sulawesi Tengah, Sulawesi Utara, Sulawesi Tenggara, Gorontalo, dan Maluku Utara)

---

## ✨ Fitur Utama

### 👨‍💼 Admin
| Fitur | Keterangan |
|-------|-----------|
| Dashboard Statistik | Ringkasan total user, pengajar, pelatihan, wilayah |
| Manajemen User | CRUD akun login (admin & pengajar) |
| Manajemen Pengajar | Tambah, edit, hapus data instruktur lengkap |
| Manajemen Dinas | Kelola instansi per wilayah |
| Manajemen Pelatihan | Identifikasi kebutuhan pelatihan tahunan |
| Kelola Berita | Buat, edit, hapus berita pelatihan |
| Belanja Modal | Kelola konten menu & narasumber |
| Pengaturan Akun | Ubah username & password |

### 👨‍🎓 Pengajar
| Fitur | Keterangan |
|-------|-----------|
| Dashboard Wilayah | 7 kartu wilayah dengan catatan cepat |
| Data Pengajar | Pencarian & paginasi database instruktur |
| Data Dinas | Daftar instansi per wilayah |
| Dashboard Pelatihan | Analitik: chart bar, line, horizontal bar per wilayah & tahun |
| Daftar Pelatihan | Tabel lengkap dengan filter wilayah & tahun |
| Berita Pelatihan | Baca berita & informasi terbaru |
| Export CSV | Export data pelatihan & pengajar ke CSV |
| Export PDF | Cetak biodata pengajar ke PDF |
| Pengaturan Akun | Ubah username & password |

### 👤 Tamu (Pengunjung)
| Fitur | Keterangan |
|-------|-----------|
| Dashboard Publik | Statistik umum (tanpa sidebar, layout full-width) |
| Berita Pelatihan | Baca berita & informasi pelatihan |
| Tanpa Login | Akses langsung tanpa perlu akun |

---

## 🛠️ Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 7.4+ (Native) |
| Database | MySQL / MariaDB (UTF-8 MB4) |
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Chart | Chart.js |
| Font | Google Fonts (Poppins) |
| Icon | Font Awesome 6.5.0 |
| PDF | FPDF Library |
| Keamanan | bcrypt hashing, CSRF token, session-based auth |

---

## 📁 Struktur Folder

```
MitigaPro/
├── index.php                  # Entry point (redirect berdasarkan role)
├── login.php                  # Halaman pilih role login
├── login_admin.php            # Form login admin
├── login_pengajar.php         # Form login pengajar
├── login_tamu.php             # Auto-login tamu
├── logout.php                 # Hapus session & logout
├── setup_admin.php            # Buat akun admin pertama (hapus setelah setup!)
├── database_setup.sql         # Script pembuatan database & tabel
│
├── 1_css/                     # File stylesheet
│   ├── sidebar_mitigapro.css  # Style sidebar admin
│   ├── sidebar_pengajar.css   # Style sidebar pengajar
│   ├── topbar.css             # Style topbar
│   ├── pengajar_view.css      # Style halaman view pengajar
│   └── footer.css             # Style footer
│
├── include/                   # Komponen & library
│   ├── autoload.php           # Konfigurasi DB, session, helper
│   ├── sidebar_pengajar.php   # Sidebar navigasi pengajar
│   ├── sidebar_mitigapro.php  # Sidebar navigasi admin
│   ├── topbar_tamu.php        # Topbar navigasi tamu
│   ├── topbar_pengajar.php    # Topbar pengajar
│   ├── footer.php             # Komponen footer
│   ├── fpdf.php               # Library FPDF
│   └── font/                  # File font FPDF
│
├── mitigapro/                 # Modul admin
│   ├── all_user.php           # Tampilan konten/menu
│   └── admin/
│       ├── db_mitigapro.php   # Dashboard admin
│       ├── manage_users.php   # CRUD user
│       ├── belanja_modal.php  # Kelola konten & narasumber
│       ├── change_password.php
│       └── delete.php         # Handler hapus data
│
├── pengajar/                  # Modul pengajar & tamu
│   ├── dashboard.php          # Dashboard utama pengajar
│   ├── dashboard_tamu.php     # Dashboard tamu (full-width)
│   ├── pengajar.php           # Daftar pengajar
│   ├── pengajar_add.php       # Tambah pengajar
│   ├── pengajar_edit.php      # Edit pengajar
│   ├── pengajar_view.php      # Detail pengajar
│   ├── pengajar_hapus.php     # Hapus pengajar
│   ├── dinas.php              # Daftar dinas
│   ├── tambah_dinas.php       # Tambah dinas
│   ├── edit_dinas.php         # Edit dinas
│   ├── hapus_dinas.php        # Hapus dinas
│   ├── detail_dinas.php       # Detail dinas
│   ├── wilayah.php            # Detail wilayah
│   ├── pelatihan_dashboard.php # Dashboard analitik pelatihan
│   ├── daftar_pelatihan.php   # Tabel daftar pelatihan
│   ├── tambah_pelatihan.php   # Tambah pelatihan
│   ├── tambah_pelatihan_baru.php
│   ├── edit_pelatihan.php     # Edit pelatihan
│   ├── hapus_pelatihan.php    # Hapus pelatihan
│   ├── berita_pelatihan.php   # Daftar berita
│   ├── detail_berita.php      # Detail berita
│   ├── kelola_berita.php      # Kelola berita (admin)
│   ├── tambah_berita.php      # Tambah berita
│   ├── edit_berita.php        # Edit berita
│   ├── download_pdf.php       # Export biodata PDF
│   ├── export_pelatihan.php   # Export pelatihan CSV
│   ├── export_pengajar.php    # Export pengajar CSV
│   ├── settings.php           # Pengaturan akun
│   └── cek_nip.php            # Validasi NIP
│
└── uploads/                   # File upload
    ├── berita/                # Gambar berita
    └── pengajar/              # Foto pengajar
```

---

## 🚀 Instalasi

### Prasyarat

- **PHP** >= 7.4
- **MySQL** / MariaDB
- **Web Server** (Apache/Nginx) — disarankan menggunakan [XAMPP](https://www.apachefriends.org/) atau [Laragon](https://laragon.org/)

### Langkah Instalasi

1. **Clone repository**
   ```bash
   git clone https://github.com/MUHULILAMRI/MitigaPro.git
   ```

2. **Pindahkan ke folder web server**
   ```bash
   # XAMPP
   cp -r MitigaPro /xampp/htdocs/

   # Laragon
   cp -r MitigaPro /laragon/www/
   ```

3. **Buat database**
   - Buka **phpMyAdmin** (`http://localhost/phpmyadmin`)
   - Buat database baru dengan nama `mitigapro`
   - Import file `database_setup.sql`

   Atau via terminal:
   ```bash
   mysql -u root -p -e "CREATE DATABASE mitigapro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p mitigapro < database_setup.sql
   ```

4. **Konfigurasi database** (jika perlu)

   Edit file `include/autoload.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');       // Sesuaikan password
   define('DB_NAME', 'mitigapro');
   ```

5. **Buat akun admin**

   Buka browser dan akses:
   ```
   http://localhost/MitigaPro/setup_admin.php
   ```
   > ⚠️ **Hapus file `setup_admin.php` setelah akun admin berhasil dibuat!**

6. **Akses aplikasi**
   ```
   http://localhost/MitigaPro/
   ```

---

## 🔐 Sistem Login

Aplikasi memiliki 3 role dengan halaman login terpisah:

| Role | Akses | Keterangan |
|------|-------|-----------|
| **Admin** | `login_admin.php` | Akses penuh ke semua fitur |
| **Pengajar** | `login_pengajar.php` | Kelola data & lihat analitik |
| **Tamu** | `login_tamu.php` | Akses langsung tanpa akun (view only) |

### Keamanan
- Password di-hash menggunakan **bcrypt** (`password_hash()`)
- Proteksi **CSRF token** pada semua form
- **Session-based authentication** dengan validasi role
- Fungsi helper `require_role()` untuk pembatasan akses

---

## 🗄️ Struktur Database

| Tabel | Fungsi |
|-------|--------|
| `users` | Akun login (admin & pengajar) |
| `pengajar` | Profil lengkap instruktur/pengajar |
| `wilayah` | 7 wilayah kerja Bapekom PU VIII |
| `dinas` | Instansi pemerintah per wilayah |
| `identifikasi_pelatihan` | Kebutuhan pelatihan per dinas per tahun |
| `berita_pelatihan` | Berita & informasi pelatihan |
| `catatan_wilayah` | Catatan cepat per wilayah per user |
| `mitigapro_menus` | Menu konten (admin) |
| `mitigapro_handlers` | Narasumber/instruktur unggulan |
| `mitigapro_handler_menu` | Relasi narasumber ↔ menu |
| `mitigapro_contents` | Konten per menu |

---

## 📊 Fitur Export

### CSV Export
- **Data Pengajar** — Export seluruh database instruktur dengan filter pencarian
- **Data Pelatihan** — Export kebutuhan pelatihan dengan filter tahun
- Encoding **UTF-8 BOM** untuk kompatibilitas Excel

### PDF Export
- **Biodata Pengajar** — Cetak biodata lengkap instruktur ke format PDF
- Mencakup: NIP, nama, jenis kelamin, pendidikan, jabatan, unit kerja, foto, dll.
- Area tanda tangan dengan tanggal kosong

---

## 📸 Screenshot

> _Tambahkan screenshot halaman utama aplikasi di sini_

---

## 👥 Kontributor

- **MUHULILAMRI** — Developer

---

## 📄 Lisensi

Project ini dilisensikan di bawah [MIT License](LICENSE).

---

<p align="center">
  <strong>MitigaPro</strong> &copy; 2026 — Balai Pengembangan Kompetensi PU Wilayah VIII Makassar
</p>
