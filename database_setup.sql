-- ============================================================
--  database_setup.sql
--  MitigaPro — Balai Pengembangan Kompetensi PU VIII Makassar
--  Jalankan file ini di phpMyAdmin sekali untuk setup database
-- ============================================================

-- Buat & pilih database
CREATE DATABASE IF NOT EXISTS mitigapro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mitigapro;

-- ------------------------------------------------------------
-- 1. users (untuk login)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(80)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,   -- bcrypt hash
    role       ENUM('admin','pengajar') NOT NULL DEFAULT 'pengajar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Akun default:
--   admin    / admin123
--   pengajar / pengajar123
-- Password di-hash dengan password_hash($pass, PASSWORD_DEFAULT)
INSERT IGNORE INTO users (username, password, role) VALUES
    ('admin',    '$2y$10$HuB0OAMXE.wnQlz5WvMalejIBxJ.fFnLDAiVDXWXyFl7N9AFKTQ2y', 'admin'),
    ('pengajar', '$2y$10$KGHpfGPOvnj3eSr4cRKNBeH6J7/E6e1QcifLGAO5b4C2xQBIvMq1W', 'pengajar');
-- (admin123 dan pengajar123 — ubah segera setelah instalasi!)

-- ------------------------------------------------------------
-- 2. pengajar
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pengajar (
    nip                VARCHAR(30)  NOT NULL PRIMARY KEY,
    nama_pengajar      VARCHAR(150) NOT NULL,
    jenis_kelamin      ENUM('Laki-laki','Perempuan') NOT NULL,
    agama              VARCHAR(30)  NOT NULL,
    pendidikan_terakhir VARCHAR(30) NOT NULL,
    golongan           VARCHAR(20)  DEFAULT NULL,
    tempat_lahir       VARCHAR(80)  NOT NULL,
    tanggal_lahir      DATE         NOT NULL,
    no_hp              VARCHAR(20)  NOT NULL,
    email_pengajar     VARCHAR(120) NOT NULL,
    jabatan            VARCHAR(150) NOT NULL,
    unit_kerja         VARCHAR(150) NOT NULL,
    instansi           VARCHAR(150) NOT NULL,
    alamat_kantor      TEXT         NOT NULL,
    npwp               VARCHAR(30)  DEFAULT NULL,
    foto               VARCHAR(200) DEFAULT NULL,
    status             ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. wilayah
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wilayah (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_wilayah VARCHAR(200) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7 wilayah kerja Bapekom PU VIII Makassar
INSERT IGNORE INTO wilayah (id, nama_wilayah) VALUES
    (1, 'Wilayah Kerja Sulawesi Selatan'),
    (2, 'Wilayah Kerja Sulawesi Barat'),
    (3, 'Wilayah Kerja Sulawesi Tengah'),
    (4, 'Wilayah Kerja Sulawesi Utara'),
    (5, 'Wilayah Kerja Sulawesi Tenggara'),
    (6, 'Wilayah Kerja Gorontalo'),
    (7, 'Wilayah Kerja Maluku Utara');

-- ------------------------------------------------------------
-- 4. dinas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dinas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wilayah_id  INT UNSIGNED NOT NULL,
    nama_dinas  VARCHAR(200) NOT NULL,
    alamat      TEXT         DEFAULT NULL,
    kontak      VARCHAR(60)  DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wilayah_id) REFERENCES wilayah(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. identifikasi_pelatihan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS identifikasi_pelatihan (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dinas_id        INT UNSIGNED NOT NULL,
    jenis_pelatihan VARCHAR(200) NOT NULL,
    kebutuhan       TEXT         DEFAULT NULL,
    tahun           YEAR         NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dinas_id) REFERENCES dinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. mitigapro_menus
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mitigapro_menus (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug  VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO mitigapro_menus (slug, title) VALUES
    ('beranda',       'Beranda'),
    ('belanja-modal', 'Belanja Modal');

-- ------------------------------------------------------------
-- 7. mitigapro_handlers (narasumber / pengelola per menu)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mitigapro_handlers (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(150) NOT NULL,
    nip     VARCHAR(30)  DEFAULT NULL,
    jabatan VARCHAR(150) DEFAULT NULL,
    tugas   TEXT         DEFAULT NULL,
    photo   VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. mitigapro_handler_menu (relasi handler ↔ menu)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mitigapro_handler_menu (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    handler_id INT UNSIGNED NOT NULL,
    menu_id    INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_handler_menu (handler_id, menu_id),
    FOREIGN KEY (handler_id) REFERENCES mitigapro_handlers(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id)    REFERENCES mitigapro_menus(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. mitigapro_contents (konten tiap menu)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mitigapro_contents (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT         DEFAULT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    link        VARCHAR(255) DEFAULT NULL,
    link_label  VARCHAR(80)  DEFAULT NULL,
    priority    SMALLINT     NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES mitigapro_menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel catatan_wilayah (catatan cepat per wilayah dari dashboard)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catatan_wilayah (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wilayah_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    catatan    TEXT         NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_catatan (wilayah_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 10. berita_pelatihan (informasi / berita pelatihan oleh admin)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS berita_pelatihan (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul       VARCHAR(255) NOT NULL,
    isi         TEXT         NOT NULL,
    kategori    VARCHAR(100) DEFAULT NULL,
    gambar      VARCHAR(255) DEFAULT NULL,
    link        VARCHAR(500) DEFAULT NULL,
    user_id     INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

