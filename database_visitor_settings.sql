-- ============================================================
--  database_visitor_settings.sql
--  Tabel-tabel untuk pengaturan halaman pengunjung (tamu)
--  Jalankan file ini di phpMyAdmin setelah database_setup.sql
-- ============================================================

USE mitigapro;

-- ------------------------------------------------------------
-- 1. visitor_sambutan — Kata sambutan admin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_sambutan (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(150)  NOT NULL DEFAULT 'Tim Pengelola MitigaPro',
    jabatan     VARCHAR(200)  NOT NULL DEFAULT 'Admin & Pengelola Sistem Informasi MitigaPro',
    judul       VARCHAR(200)  NOT NULL DEFAULT 'Selamat Datang, Pengunjung!',
    isi         TEXT          NOT NULL,
    foto        VARCHAR(255)  DEFAULT NULL,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO visitor_sambutan (id, nama, jabatan, judul, isi) VALUES
(1,
 'Tim Pengelola MitigaPro',
 'Admin & Pengelola Sistem Informasi MitigaPro',
 'Selamat Datang, Pengunjung!',
 'Selamat datang di Sistem Informasi MitigaPro — Balai Pengembangan Kompetensi Pekerjaan Umum Wilayah VIII Makassar. Sistem ini dikembangkan untuk mendukung pengelolaan data pengajar, identifikasi kebutuhan pelatihan, serta pemetaan wilayah kerja yang mencakup Sulawesi, Gorontalo, dan Maluku Utara.\r\n\r\nKami berharap melalui platform ini, pengunjung dapat memperoleh informasi terkini mengenai kegiatan pelatihan, data pengajar yang kompeten, serta perkembangan wilayah kerja kami. Terima kasih atas kunjungan Anda dan semoga informasi yang tersedia bermanfaat.'
) ON DUPLICATE KEY UPDATE id=id;

-- ------------------------------------------------------------
-- 2. visitor_profil — Tentang Kami (Visi, Misi, Tugas, Fungsi)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_profil (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipe        ENUM('visi','misi','tugas','fungsi') NOT NULL,
    isi         TEXT NOT NULL,
    urutan      SMALLINT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO visitor_profil (tipe, isi, urutan) VALUES
('visi',   'Menjadi balai pengembangan kompetensi yang unggul dan terpercaya dalam penyelenggaraan pelatihan bidang pekerjaan umum di kawasan timur Indonesia', 1),
('misi',   'Menyelenggarakan pelatihan yang berkualitas dan relevan dengan kebutuhan daerah', 1),
('misi',   'Mengembangkan kompetensi SDM bidang pekerjaan umum secara berkelanjutan', 2),
('misi',   'Membangun kemitraan strategis dengan instansi terkait di wilayah kerja', 3),
('misi',   'Mengoptimalkan pemanfaatan teknologi informasi dalam pengelolaan pelatihan', 4),
('tugas',  'Melaksanakan pengembangan kompetensi di bidang pekerjaan umum dan perumahan rakyat', 1),
('tugas',  'Menyelenggarakan program pelatihan teknis dan manajerial', 2),
('tugas',  'Melakukan identifikasi kebutuhan pelatihan di wilayah kerja', 3),
('fungsi', 'Penyusunan rencana dan program pengembangan kompetensi', 1),
('fungsi', 'Penyelenggaraan pelatihan dan sertifikasi', 2),
('fungsi', 'Evaluasi dan pelaporan pelaksanaan program pelatihan', 3),
('fungsi', 'Pengelolaan data pengajar dan fasilitas pelatihan', 4);

-- ------------------------------------------------------------
-- 3. visitor_struktur — Struktur Organisasi
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_struktur (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(150) NOT NULL,
    jabatan     VARCHAR(200) NOT NULL,
    level       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    urutan      SMALLINT NOT NULL DEFAULT 0,
    icon        VARCHAR(50) DEFAULT 'fas fa-user-tie',
    warna       VARCHAR(30) DEFAULT '#3b82f6',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO visitor_struktur (nama, jabatan, level, urutan, icon, warna) VALUES
('Kepala Balai',            'Balai Pengembangan Kompetensi PU Wil. VIII',  1, 1, 'fas fa-user-tie',          '#3b82f6'),
('Kasubbag Tata Usaha',    'Sub Bagian Tata Usaha',                       2, 1, 'fas fa-folder-open',       '#22c55e'),
('Kasi Penyelenggaraan',   'Seksi Penyelenggaraan Pelatihan',             2, 2, 'fas fa-chalkboard-teacher', '#f59e0b'),
('Kasi Program & Evaluasi','Seksi Program dan Evaluasi',                  2, 3, 'fas fa-chart-line',         '#ec4899');

-- ------------------------------------------------------------
-- 4. visitor_faq — Pertanyaan Umum
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_faq (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pertanyaan  VARCHAR(500) NOT NULL,
    jawaban     TEXT         NOT NULL,
    urutan      SMALLINT     NOT NULL DEFAULT 0,
    aktif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO visitor_faq (pertanyaan, jawaban, urutan) VALUES
('Apa itu Bapekom PU Wilayah VIII Makassar?',
 'Balai Pengembangan Kompetensi (Bapekom) PU Wilayah VIII Makassar adalah unit pelaksana teknis di bawah Kementerian Pekerjaan Umum yang bertugas menyelenggarakan pelatihan dan pengembangan kompetensi SDM bidang pekerjaan umum di wilayah Sulawesi, Gorontalo, dan Maluku Utara.', 1),
('Wilayah mana saja yang menjadi cakupan Bapekom PU VIII?',
 'Bapekom PU Wilayah VIII Makassar mencakup 7 wilayah kerja yaitu: Sulawesi Selatan, Sulawesi Barat, Sulawesi Tengah, Sulawesi Utara, Sulawesi Tenggara, Gorontalo, dan Maluku Utara.', 2),
('Bagaimana cara mengakses informasi pelatihan?',
 'Pengunjung dapat melihat informasi pelatihan terbaru melalui halaman ini. Untuk informasi lebih detail mengenai pendaftaran dan jadwal pelatihan, silakan hubungi Admin melalui kontak yang tersedia di bagian bawah halaman ini.', 3),
('Apakah data di sistem ini dapat diakses oleh publik?',
 'Halaman pengunjung menampilkan informasi umum seperti statistik, peta wilayah, berita pelatihan, dan profil pengajar. Data detail dan fitur pengelolaan hanya dapat diakses oleh pengguna terdaftar (admin dan pengajar).', 4),
('Bagaimana cara menjadi pengajar di Bapekom PU VIII?',
 'Untuk informasi mengenai rekrutmen pengajar atau narasumber pelatihan, silakan menghubungi kantor Bapekom PU Wilayah VIII Makassar melalui email atau telepon yang tertera di bagian Kontak pada halaman ini.', 5);

-- ------------------------------------------------------------
-- 5. visitor_kontak — Informasi Kontak
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_kontak (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kunci       VARCHAR(50)  NOT NULL UNIQUE,
    label       VARCHAR(100) NOT NULL,
    nilai       TEXT         NOT NULL,
    icon        VARCHAR(50)  DEFAULT 'fas fa-info-circle',
    warna       VARCHAR(100) DEFAULT 'linear-gradient(135deg,#3b82f6,#6366f1)',
    urutan      SMALLINT     NOT NULL DEFAULT 0,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO visitor_kontak (kunci, label, nilai, icon, warna, urutan) VALUES
('kepala_balai', 'Kepala Balai',          'Balai Pengembangan Kompetensi PU Wilayah VIII Makassar',       'fas fa-user-tie',    'linear-gradient(135deg,#3b82f6,#6366f1)', 1),
('alamat',       'Alamat Kantor',         'Jl. A.P. Pettarani No.61, Tidung, Kec. Rappocini, Kota Makassar, Sulawesi Selatan 90222', 'fas fa-location-dot','linear-gradient(135deg,#22c55e,#10b981)', 2),
('telepon',      'Telepon / Faximile',    'Telp: (0411) 855-123\nFax: (0411) 855-124',                   'fas fa-phone',       'linear-gradient(135deg,#f59e0b,#fbbf24)', 3),
('email',        'Email Resmi',           'bapekom8makassar@pu.go.id',                                    'fas fa-envelope',    'linear-gradient(135deg,#8b5cf6,#7c3aed)', 4),
('jam_kerja',    'Jam Kerja Operasional', 'Senin – Jumat: 08.00 – 16.00 WITA\nSabtu, Minggu & Hari Libur: Tutup', 'fas fa-clock', 'linear-gradient(135deg,#ec4899,#f472b6)', 5),
('google_maps',  'Google Maps Embed',     'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3973.707!2d119.432!3d-5.152!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dbee329d96c4671%3A0x3030bfbcaf77020!2sJl.%20A.P.%20Pettarani%2C%20Makassar!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid', 'fas fa-map', 'linear-gradient(135deg,#06b6d4,#0ea5e9)', 6)
ON DUPLICATE KEY UPDATE label=VALUES(label);

-- ------------------------------------------------------------
-- 6. visitor_sosmed — Media Sosial
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_sosmed (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform    VARCHAR(50)  NOT NULL,
    url         VARCHAR(500) NOT NULL DEFAULT '#',
    icon        VARCHAR(50)  NOT NULL DEFAULT 'fas fa-link',
    warna_class VARCHAR(30)  NOT NULL DEFAULT '',
    urutan      SMALLINT     NOT NULL DEFAULT 0,
    aktif       TINYINT(1)   NOT NULL DEFAULT 1,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO visitor_sosmed (platform, url, icon, warna_class, urutan) VALUES
('Instagram', '#', 'fab fa-instagram',  'ig', 1),
('Facebook',  '#', 'fab fa-facebook-f', 'fb', 2),
('YouTube',   '#', 'fab fa-youtube',    'yt', 3),
('WhatsApp',  '#', 'fab fa-whatsapp',   'wa', 4);

-- ------------------------------------------------------------
-- 7. visitor_tautan — Tautan Terkait
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_tautan (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(150) NOT NULL,
    deskripsi   VARCHAR(300) DEFAULT NULL,
    url         VARCHAR(500) NOT NULL,
    icon        VARCHAR(50)  DEFAULT 'fas fa-link',
    urutan      SMALLINT     NOT NULL DEFAULT 0,
    aktif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO visitor_tautan (nama, deskripsi, url, icon, urutan) VALUES
('Kementerian PUPR',       'Kementerian Pekerjaan Umum dan Perumahan Rakyat', 'https://www.pu.go.id',       'fas fa-landmark',       1),
('BPSDM PUPR',             'Badan Pengembangan SDM Kementerian PUPR',         'https://bpsdm.pu.go.id',     'fas fa-graduation-cap',  2),
('Ditjen Sumber Daya Air',  'Direktorat Jenderal Sumber Daya Air',            'https://sda.pu.go.id',       'fas fa-water',           3),
('Ditjen Bina Marga',       'Direktorat Jenderal Bina Marga',                 'https://binamarga.pu.go.id', 'fas fa-road',            4),
('Ditjen Cipta Karya',      'Direktorat Jenderal Cipta Karya',                'https://ciptakarya.pu.go.id','fas fa-city',            5),
('BPS Sulawesi Selatan',    'Badan Pusat Statistik Prov. Sulawesi Selatan',   'https://sulsel.bps.go.id',   'fas fa-chart-pie',       6);

-- ------------------------------------------------------------
-- 8. visitor_galeri — Galeri Kegiatan (opsional, terpisah dari berita)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_galeri (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul       VARCHAR(200)  NOT NULL,
    gambar      VARCHAR(255)  NOT NULL,
    kategori    VARCHAR(100)  DEFAULT NULL,
    aktif       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
