<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

// Hanya untuk tamu
if (($_SESSION['role'] ?? '') !== 'tamu') {
    header("Location: " . BASE_URL . "pengajar/dashboard.php");
    exit;
}

// ── Ambil statistik ──
$total_pengajar  = 0;
$total_dinas     = 0;
$total_pelatihan = 0;
$total_wilayah   = 7;

$r = $conn->query("SELECT COUNT(*) AS c FROM pengajar");
if ($r) { $total_pengajar = (int)$r->fetch_assoc()['c']; $r->free(); }

$r = $conn->query("SELECT COUNT(*) AS c FROM dinas");
if ($r) { $total_dinas = (int)$r->fetch_assoc()['c']; $r->free(); }

$r = $conn->query("SELECT COUNT(*) AS c FROM identifikasi_pelatihan");
if ($r) { $total_pelatihan = (int)$r->fetch_assoc()['c']; $r->free(); }

// ── Ambil berita terbaru ──
$berita_list = [];
$r = $conn->query("SELECT id, judul, isi, kategori, gambar, created_at FROM berita_pelatihan ORDER BY created_at DESC LIMIT 4");
if ($r) { while ($row = $r->fetch_assoc()) $berita_list[] = $row; $r->free(); }

// ── Ambil jumlah dinas per wilayah ──
$wil_stats = [];
$r = $conn->query("SELECT w.id, w.nama_wilayah, COUNT(d.id) AS jml_dinas
                    FROM wilayah w LEFT JOIN dinas d ON d.wilayah_id = w.id
                    GROUP BY w.id ORDER BY w.id");
if ($r) { while ($row = $r->fetch_assoc()) $wil_stats[] = $row; $r->free(); }

// ── Ambil pengajar aktif untuk tampilan publik ──
$pengajar_list = [];
$r = $conn->query("SELECT nip, nama_pengajar, jabatan, unit_kerja, instansi, foto, pendidikan_terakhir FROM pengajar WHERE status = 'aktif' ORDER BY nama_pengajar ASC LIMIT 12");
if ($r) { while ($row = $r->fetch_assoc()) $pengajar_list[] = $row; $r->free(); }

// ── Ambil data halaman pengunjung dari DB ──
$sambutan = [];
$r = $conn->query("SELECT * FROM visitor_sambutan LIMIT 1");
if ($r) { $sambutan = $r->fetch_assoc() ?: []; $r->free(); }

$profil = ['visi' => [], 'misi' => [], 'tugas' => [], 'fungsi' => []];
$r = $conn->query("SELECT * FROM visitor_profil ORDER BY tipe, urutan");
if ($r) { while ($row = $r->fetch_assoc()) $profil[$row['tipe']][] = $row; $r->free(); }

$struktur_list = [];
$r = $conn->query("SELECT * FROM visitor_struktur ORDER BY level, urutan");
if ($r) { while ($row = $r->fetch_assoc()) $struktur_list[] = $row; $r->free(); }

$faq_list = [];
$r = $conn->query("SELECT * FROM visitor_faq WHERE aktif = 1 ORDER BY urutan");
if ($r) { while ($row = $r->fetch_assoc()) $faq_list[] = $row; $r->free(); }

$kontak_map = [];
$r = $conn->query("SELECT * FROM visitor_kontak ORDER BY urutan");
if ($r) { while ($row = $r->fetch_assoc()) $kontak_map[$row['kunci']] = $row; $r->free(); }

$sosmed_list = [];
$r = $conn->query("SELECT * FROM visitor_sosmed WHERE aktif = 1 ORDER BY urutan");
if ($r) { while ($row = $r->fetch_assoc()) $sosmed_list[] = $row; $r->free(); }

$tautan_list = [];
$r = $conn->query("SELECT * FROM visitor_tautan WHERE aktif = 1 ORDER BY urutan");
if ($r) { while ($row = $r->fetch_assoc()) $tautan_list[] = $row; $r->free(); }

$galeri_list = [];
$r = $conn->query("SELECT * FROM visitor_galeri WHERE aktif = 1 ORDER BY created_at DESC LIMIT 9");
if ($r) { while ($row = $r->fetch_assoc()) $galeri_list[] = $row; $r->free(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Informasi MitigaPro — Bapekom PU VIII Makassar</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:   #0f172a;
  --blue:   #2c5282;
  --accent: #3b82f6;
  --bg:     #f0f4f8;
  --white:  #ffffff;
  --text:   #334155;
  --muted:  #94a3b8;
  --border: #e2e8f0;
  --radius: 14px;
}

html { scroll-behavior: smooth; }
body {
  font-family: 'Poppins', sans-serif;
  background: var(--navy);
  color: var(--text);
  margin: 0;
  overflow-x: hidden;
}

/* ── Topbar (glassmorphism, fixed) ── */
.topbar {
  position: fixed; top: 0; left: 0; right: 0;
  z-index: 200;
  padding: 0 40px; height: 64px;
  display: flex; align-items: center; justify-content: space-between;
  background: rgba(15,23,42,0.7);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  transition: background 0.3s;
}
.topbar.scrolled { background: rgba(15,23,42,0.95); }
.topbar-left { display: flex; align-items: center; gap: 14px; }
.topbar-logo { display: flex; align-items: center; gap: 8px; }
.topbar-logo img { width: 36px; height: 36px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3)); }
.topbar-title { color: #fff; font-size: 17px; font-weight: 700; letter-spacing: 0.5px; }
.topbar-sub { color: rgba(255,255,255,0.45); font-size: 10px; }
.topbar-right { display: flex; align-items: center; gap: 14px; }
.topbar-badge { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 500; padding: 5px 14px; border-radius: 20px; display: flex; align-items: center; gap: 6px; }
.topbar-login { background: var(--accent); color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-size: 12px; font-weight: 600; font-family: 'Poppins', sans-serif; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s; box-shadow: 0 2px 12px rgba(59,130,246,0.3); }
.topbar-login:hover { background: #2563eb; transform: translateY(-1px); }
.topbar-nav-link:hover { color: rgba(255,255,255,0.95) !important; }

/* ── Hero (Full viewport) ── */
.hero-section {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  position: relative;
  background: url('<?= BASE_URL ?>uploads/bg/bapekom8_ok%20(1).jpg') center/cover no-repeat fixed;
  overflow: hidden;
  padding: 80px 40px 60px;
}
.hero-section::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(160deg, rgba(15,23,42,0.85) 0%, rgba(30,58,95,0.75) 40%, rgba(44,82,130,0.7) 70%, rgba(59,130,246,0.55) 100%);
}
.hero-section::after {
  content: ''; position: absolute;
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(245,158,11,0.06), transparent 70%);
  bottom: -150px; left: -100px;
  border-radius: 50%;
  animation: heroFloat 10s ease-in-out infinite 3s;
}
@keyframes heroFloat {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-40px) scale(1.05); }
}

/* Animated particles */
.particles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.particle {
  position: absolute;
  width: 4px; height: 4px;
  background: rgba(255,255,255,0.15);
  border-radius: 50%;
  animation: particleUp linear infinite;
}
@keyframes particleUp {
  0% { transform: translateY(100vh) scale(0); opacity: 0; }
  20% { opacity: 1; }
  100% { transform: translateY(-20vh) scale(1); opacity: 0; }
}

.hero-content {
  position: relative; z-index: 2;
  text-align: center;
  max-width: 800px;
}
.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.15);
  padding: 8px 20px; border-radius: 24px;
  font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.8);
  letter-spacing: 1px; text-transform: uppercase;
  margin-bottom: 28px;
  animation: fadeSlideUp 0.8s ease 0.2s both;
}
.hero-content h1 {
  font-size: 3rem; font-weight: 800;
  color: #fff; line-height: 1.3;
  margin-bottom: 16px;
  animation: fadeSlideUp 0.8s ease 0.4s both;
}
.hero-content h1 span { color: #60a5fa; }
.hero-content p {
  font-size: 15px; color: rgba(255,255,255,0.55);
  line-height: 1.8; max-width: 600px; margin: 0 auto 36px;
  animation: fadeSlideUp 0.8s ease 0.6s both;
}
@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.hero-stats {
  display: flex; gap: 32px; justify-content: center; flex-wrap: wrap;
  animation: fadeSlideUp 0.8s ease 0.8s both;
}
.hero-stat {
  text-align: center;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 16px;
  padding: 20px 28px;
  min-width: 130px;
  backdrop-filter: blur(8px);
  transition: transform 0.3s, background 0.3s;
}
.hero-stat:hover {
  transform: translateY(-4px);
  background: rgba(255,255,255,0.1);
}
.hero-stat-num { font-size: 2rem; font-weight: 800; color: #fff; line-height: 1; }
.hero-stat-label { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 6px; }

.scroll-hint {
  position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
  color: rgba(255,255,255,0.3); font-size: 12px;
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  animation: bounce 2s infinite;
  z-index: 2;
}
.scroll-hint i { font-size: 20px; }
@keyframes bounce {
  0%, 100% { transform: translateX(-50%) translateY(0); }
  50% { transform: translateX(-50%) translateY(10px); }
}

/* ── Sections ── */
.section {
  padding: 80px 40px;
  position: relative;
}
.section-light { background: var(--white); }
.section-gray { background: var(--bg); }
.section-dark {
  background: linear-gradient(160deg, var(--navy), #1e3a5f);
  color: #fff;
}
.section-inner { max-width: 1200px; margin: 0 auto; }

.sec-header {
  font-size: 28px; font-weight: 800; margin-bottom: 8px;
  display: flex; align-items: center; gap: 12px;
}
.sec-header i { color: var(--accent); font-size: 24px; }
.sec-sub { font-size: 14px; color: var(--muted); margin-bottom: 40px; }

/* ── Wilayah Map ── */
.wil-map-wrap {
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
#mapTamu {
  width: 100%; height: 420px;
  z-index: 1;
}
.wil-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
  margin-top: 24px;
}
.wil-info-card {
  display: flex; align-items: center; gap: 12px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  padding: 14px 16px;
  text-decoration: none; color: #fff;
  transition: all 0.3s;
}
.wil-info-card:hover { background: rgba(255,255,255,0.12); transform: translateY(-2px); }
.wil-info-dot {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 14px; flex-shrink: 0;
}
.wil-info-name { font-size: 12px; font-weight: 600; line-height: 1.3; }
.wil-info-meta { font-size: 10.5px; color: rgba(255,255,255,0.45); margin-top: 2px; display: flex; align-items: center; gap: 4px; }
@media (max-width: 600px) {
  #mapTamu { height: 300px; }
  .wil-info-grid { grid-template-columns: 1fr 1fr; }
}

/* ── Berita Auto-Scroll Ticker ── */
.ticker-wrap {
  overflow: hidden;
  border-radius: 16px;
  padding: 10px 0;
}
.ticker-track {
  display: flex;
  gap: 20px;
  width: max-content;
  animation: tickerScroll 20s linear infinite;
}
.ticker-wrap:hover .ticker-track { animation-play-state: paused; }
@keyframes tickerScroll {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
.ticker-card {
  flex-shrink: 0;
  width: 260px;
  background: var(--white);
  border-radius: 14px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  transition: transform 0.3s, box-shadow 0.3s;
}
.ticker-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}
.ticker-img {
  width: 100%; height: 140px;
  overflow: hidden;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
}
.ticker-img img { width: 100%; height: 100%; object-fit: cover; }
.ticker-ph {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  font-size: 36px; color: #93c5fd;
}
.ticker-body { padding: 14px 16px 16px; }
.ticker-cat {
  display: inline-block;
  background: #eff6ff; color: var(--accent);
  font-size: 9px; font-weight: 700; text-transform: uppercase;
  padding: 3px 10px; border-radius: 10px;
  margin-bottom: 8px;
}
.ticker-title {
  font-size: 13px; font-weight: 700; color: var(--navy);
  line-height: 1.4; margin-bottom: 6px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.ticker-excerpt {
  font-size: 11.5px; color: var(--muted); line-height: 1.6; margin-bottom: 8px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.ticker-date { font-size: 10.5px; color: #cbd5e1; display: flex; align-items: center; gap: 5px; }

/* ── Sambutan ── */
.sambutan-wrap {
  display: flex; align-items: stretch; gap: 40px;
  background: var(--white); border-radius: 20px;
  overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,0.06);
  border: 1px solid var(--border);
}
.sambutan-photo {
  flex: 0 0 280px; position: relative;
  background: linear-gradient(135deg, #1e3a5f, var(--blue));
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.sambutan-photo img {
  width: 100%; height: 100%; object-fit: cover;
}
.sambutan-photo-placeholder {
  width: 140px; height: 140px; border-radius: 50%;
  background: rgba(255,255,255,0.1); border: 3px solid rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 56px; color: rgba(255,255,255,0.4);
}
.sambutan-body { flex: 1; padding: 40px 44px; }
.sambutan-label {
  display: inline-flex; align-items: center; gap: 6px;
  background: #eff6ff; color: var(--accent); font-size: 11px;
  font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
  padding: 5px 14px; border-radius: 20px; margin-bottom: 16px;
}
.sambutan-body h3 {
  font-size: 22px; font-weight: 800; color: var(--navy); margin-bottom: 6px;
}
.sambutan-role {
  font-size: 13px; color: var(--accent); font-weight: 600; margin-bottom: 20px;
}
.sambutan-text {
  font-size: 14px; color: #475569; line-height: 1.9;
  border-left: 3px solid var(--accent); padding-left: 20px;
  position: relative; font-style: italic;
}
.sambutan-text::before {
  content: '\201C'; font-size: 60px; color: rgba(59,130,246,0.15);
  position: absolute; top: -20px; left: -5px; font-family: Georgia, serif;
}
.sambutan-sign {
  margin-top: 24px; display: flex; align-items: center; gap: 12px;
}
.sambutan-sign-line {
  width: 40px; height: 2px; background: var(--accent); border-radius: 2px;
}
.sambutan-sign-name {
  font-size: 13px; font-weight: 700; color: var(--navy);
}

/* ── Tentang Kami ── */
.about-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
}
.about-card {
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px; padding: 32px 28px;
  transition: transform 0.3s, background 0.3s;
}
.about-card:hover { transform: translateY(-4px); background: rgba(255,255,255,0.1); }
.about-card-icon {
  width: 52px; height: 52px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: #fff; margin-bottom: 18px;
}
.about-card h4 {
  font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 10px;
}
.about-card p {
  font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.8;
}
.about-card ul {
  list-style: none; padding: 0; margin: 0;
}
.about-card ul li {
  font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.8;
  padding-left: 18px; position: relative;
}
.about-card ul li::before {
  content: ''; position: absolute; left: 0; top: 10px;
  width: 6px; height: 6px; border-radius: 50%;
}
.about-visi li::before { background: #60a5fa; }
.about-misi li::before { background: #34d399; }
.about-tugas li::before { background: #fbbf24; }
.about-fungsi li::before { background: #f472b6; }

/* ── Struktur Organisasi ── */
.orgchart {
  display: flex; flex-direction: column; align-items: center; gap: 0;
}
.org-level {
  display: flex; justify-content: center; gap: 24px; flex-wrap: wrap;
  position: relative;
}
.org-level::before {
  content: ''; position: absolute; top: -20px; left: 50%;
  width: 2px; height: 20px; background: rgba(59,130,246,0.3);
}
.org-level:first-child::before { display: none; }
.org-connector {
  width: 2px; height: 28px; background: rgba(59,130,246,0.2);
  margin: 0 auto;
}
.org-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 14px; padding: 20px 24px;
  text-align: center; min-width: 200px; max-width: 260px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.05);
  transition: transform 0.3s, box-shadow 0.3s;
}
.org-card:hover { transform: translateY(-4px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); }
.org-card.head { border-top: 3px solid var(--accent); }
.org-card.sub { border-top: 3px solid #22c55e; }
.org-avatar {
  width: 60px; height: 60px; border-radius: 50%;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  display: flex; align-items: center; justify-content: center;
  font-size: 24px; color: var(--accent);
  margin: 0 auto 12px; border: 2px solid rgba(59,130,246,0.15);
}
.org-name { font-size: 14px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
.org-pos { font-size: 11px; color: var(--muted); font-weight: 500; }

/* ── Pengajar Publik ── */
.pengajar-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px;
}
.peng-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 16px; overflow: hidden;
  transition: transform 0.3s, box-shadow 0.3s;
  text-align: center;
}
.peng-card:hover { transform: translateY(-6px); box-shadow: 0 12px 36px rgba(0,0,0,0.1); }
.peng-card-top {
  background: linear-gradient(135deg, #1e3a5f, var(--blue));
  padding: 24px 16px 16px; position: relative;
}
.peng-card-top::after {
  content: ''; position: absolute; bottom: -20px; left: 0; right: 0;
  height: 40px; background: var(--white); border-radius: 50% 50% 0 0;
}
.peng-avatar {
  width: 80px; height: 80px; border-radius: 50%;
  border: 4px solid rgba(255,255,255,0.9);
  object-fit: cover; position: relative; z-index: 2;
  background: rgba(255,255,255,0.15);
}
.peng-avatar-ph {
  width: 80px; height: 80px; border-radius: 50%;
  border: 4px solid rgba(255,255,255,0.9);
  background: rgba(255,255,255,0.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; color: rgba(255,255,255,0.7);
  position: relative; z-index: 2;
}
.peng-card-body { padding: 8px 18px 20px; }
.peng-name { font-size: 14px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
.peng-jabatan { font-size: 11.5px; color: var(--muted); margin-bottom: 6px; }
.peng-unit {
  font-size: 10.5px; color: var(--accent); font-weight: 600;
  background: #eff6ff; display: inline-block; padding: 3px 10px;
  border-radius: 10px;
}
.peng-edu { font-size: 10.5px; color: #94a3b8; margin-top: 6px; }

/* ── Galeri ── */
.galeri-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}
.galeri-card {
  border-radius: 14px; overflow: hidden; position: relative;
  height: 220px; cursor: pointer;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  transition: transform 0.3s;
}
.galeri-card:hover { transform: scale(1.03); }
.galeri-card img { width: 100%; height: 100%; object-fit: cover; }
.galeri-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(15,23,42,0.8), transparent 60%);
  display: flex; flex-direction: column; justify-content: flex-end;
  padding: 18px; color: #fff;
}
.galeri-overlay span { font-size: 13px; font-weight: 600; }
.galeri-overlay small { font-size: 10.5px; color: rgba(255,255,255,0.5); margin-top: 2px; }
.galeri-placeholder {
  width: 100%; height: 100%;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  display: flex; align-items: center; justify-content: center;
  font-size: 48px; color: #93c5fd;
}

/* ── FAQ ── */
.faq-list { max-width: 800px; margin: 0 auto; }
.faq-item {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 14px; margin-bottom: 12px;
  overflow: hidden; transition: box-shadow 0.3s;
}
.faq-item:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.faq-q {
  padding: 18px 24px; cursor: pointer;
  display: flex; align-items: center; justify-content: space-between;
  font-size: 14px; font-weight: 600; color: var(--navy);
  user-select: none; transition: background 0.2s;
}
.faq-q:hover { background: #f8fafc; }
.faq-q i { color: var(--accent); font-size: 14px; transition: transform 0.3s; }
.faq-item.open .faq-q i { transform: rotate(180deg); }
.faq-a {
  max-height: 0; overflow: hidden; transition: max-height 0.4s ease, padding 0.3s;
  padding: 0 24px;
}
.faq-item.open .faq-a {
  max-height: 400px; padding: 0 24px 18px;
}
.faq-a p {
  font-size: 13px; color: #64748b; line-height: 1.8;
}

/* ── Kontak Admin ── */
.kontak-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 32px;
}
.kontak-info { display: flex; flex-direction: column; gap: 20px; }
.kontak-item {
  display: flex; align-items: flex-start; gap: 16px;
  background: var(--white); border: 1px solid var(--border);
  border-radius: 14px; padding: 18px 20px;
  transition: transform 0.2s, box-shadow 0.2s;
}
.kontak-item:hover { transform: translateX(4px); box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.kontak-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; color: #fff; flex-shrink: 0;
}
.kontak-item h5 { font-size: 13px; font-weight: 700; color: var(--navy); margin: 0 0 4px; }
.kontak-item p { font-size: 12px; color: #64748b; margin: 0; line-height: 1.6; }
.kontak-map {
  border-radius: 16px; overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  border: 1px solid var(--border);
}
.kontak-map iframe { width: 100%; height: 100%; min-height: 380px; border: none; }
.kontak-socials {
  display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px;
}
.kontak-social {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 10px;
  font-size: 12px; font-weight: 600; color: #fff;
  text-decoration: none; transition: transform 0.2s, opacity 0.2s;
}
.kontak-social:hover { transform: translateY(-2px); opacity: 0.9; }
.kontak-social.ig { background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }
.kontak-social.fb { background: #1877f2; }
.kontak-social.yt { background: #ff0000; }
.kontak-social.wa { background: #25d366; }

/* ── Tautan Terkait ── */
.tautan-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
}
.tautan-card {
  display: flex; align-items: center; gap: 16px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 14px; padding: 18px 20px;
  text-decoration: none; color: #fff;
  transition: all 0.3s;
}
.tautan-card:hover { background: rgba(255,255,255,0.12); transform: translateY(-3px); }
.tautan-logo {
  width: 48px; height: 48px; border-radius: 12px;
  background: rgba(255,255,255,0.1);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.tautan-logo img { width: 32px; height: 32px; object-fit: contain; }
.tautan-logo i { font-size: 24px; color: rgba(255,255,255,0.7); }
.tautan-name { font-size: 13px; font-weight: 600; }
.tautan-desc { font-size: 10.5px; color: rgba(255,255,255,0.45); margin-top: 2px; }

/* ── Footer Enhanced ── */
.tamu-footer {
  background: var(--navy);
  border-top: 1px solid rgba(255,255,255,0.06);
  padding: 48px 40px 28px;
}
.footer-inner {
  max-width: 1200px; margin: 0 auto;
  display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 40px;
  margin-bottom: 36px;
}
.footer-brand h4 { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 10px; }
.footer-brand p { font-size: 12px; color: rgba(255,255,255,0.4); line-height: 1.8; }
.footer-col h5 { font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.7); margin-bottom: 14px; }
.footer-col a {
  display: block; font-size: 12px; color: rgba(255,255,255,0.4);
  text-decoration: none; padding: 4px 0; transition: color 0.2s;
}
.footer-col a:hover { color: var(--accent); }
.footer-bottom {
  border-top: 1px solid rgba(255,255,255,0.06);
  padding-top: 20px; text-align: center;
  font-size: 11px; color: rgba(255,255,255,0.3);
}
.footer-bottom strong { color: rgba(255,255,255,0.6); }

/* ── Responsive ── */
@media (max-width: 900px) {
  .hero-content h1 { font-size: 2rem; }
  .hero-stats { gap: 16px; }
  .hero-stat { min-width: 100px; padding: 16px 20px; }
  .section { padding: 50px 20px; }
  .sambutan-wrap { flex-direction: column; }
  .sambutan-photo { flex: 0 0 auto; min-height: 200px; }
  .sambutan-body { padding: 28px 24px; }
  .about-grid { grid-template-columns: 1fr; }
  .kontak-grid { grid-template-columns: 1fr; }
  .footer-inner { grid-template-columns: 1fr; gap: 24px; }
}
@media (max-width: 600px) {
  .topbar { padding: 0 16px; }
  .topbar-title { font-size: 14px; }
  .topbar-sub, .topbar-badge { display: none; }
  .topbar-nav-link { display: none !important; }
  .hero-content h1 { font-size: 1.6rem; }
  .hero-stat-num { font-size: 1.5rem; }
  .sec-header { font-size: 20px; }
  .pengajar-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
  .tautan-grid { grid-template-columns: 1fr; }
  .galeri-grid { grid-template-columns: 1fr; }
}

/* ── Animation helpers ── */
.reveal {
  opacity: 0; transform: translateY(40px);
  transition: opacity 0.7s ease, transform 0.7s ease;
}
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-left { opacity: 0; transform: translateX(-40px); transition: opacity 0.7s ease, transform 0.7s ease; }
.reveal-left.visible { opacity: 1; transform: translateX(0); }

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
</style>
</head>
<body>

<!-- ═══ TOPBAR ═══ -->
<div class="topbar" id="topbar">
  <div class="topbar-left">
    <div class="topbar-logo">
      <img src="<?= BASE_URL ?>logo.png" alt="Logo PU">
      <img src="<?= BASE_URL ?>logo_bpsdm.png" alt="Logo BPSDM">
    </div>
    <div>
      <div class="topbar-title">MitigaPro</div>
      <div class="topbar-sub">Sistem Informasi Pelatihan &amp; Wilayah Kerja</div>
    </div>
  </div>
  <div class="topbar-right">
    <a href="#sambutan" class="topbar-nav-link" style="color:rgba(255,255,255,0.6);font-size:11.5px;font-weight:500;text-decoration:none;transition:color .2s">Sambutan</a>
    <a href="#tentang" class="topbar-nav-link" style="color:rgba(255,255,255,0.6);font-size:11.5px;font-weight:500;text-decoration:none;transition:color .2s">Tentang</a>
    <a href="#pengajar" class="topbar-nav-link" style="color:rgba(255,255,255,0.6);font-size:11.5px;font-weight:500;text-decoration:none;transition:color .2s">Pengajar</a>
    <a href="#kontak" class="topbar-nav-link" style="color:rgba(255,255,255,0.6);font-size:11.5px;font-weight:500;text-decoration:none;transition:color .2s">Kontak</a>
    <div class="topbar-badge"><i class="fas fa-eye"></i> Pengunjung</div>
    <a href="<?= BASE_URL ?>login.php" class="topbar-login"><i class="fas fa-sign-in-alt"></i> Masuk</a>
  </div>
</div>

<!-- ═══ HERO FULL SCREEN ═══ -->
<section class="hero-section">
  <div class="particles" id="particles"></div>
  <div class="hero-content">
    <div class="hero-logo" style="animation:fadeSlideUp 0.8s ease 0.1s both;display:flex;align-items:center;gap:16px;justify-content:center;margin-bottom:20px">
      <img src="<?= BASE_URL ?>logo.png" alt="Logo PU" style="width:80px;height:80px;object-fit:contain;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.4))">
      <img src="<?= BASE_URL ?>logo_bpsdm.png" alt="Logo BPSDM" style="width:80px;height:80px;object-fit:contain;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.4))">
    </div>
    <div class="hero-badge"><i class="fas fa-landmark"></i> Kementerian Pekerjaan Umum</div>
    <h1>Balai Pengembangan<br>Kompetensi <span>PU Wilayah VIII</span><br>Makassar</h1>
    <p>Sistem informasi pengelolaan data pengajar, identifikasi kebutuhan pelatihan, dan wilayah kerja yang mencakup Sulawesi, Gorontalo, dan Maluku Utara.</p>
    <div class="hero-stats">
      <div class="hero-stat">
        <div class="hero-stat-num" data-count="<?= $total_pengajar ?>"><?= $total_pengajar ?></div>
        <div class="hero-stat-label">Pengajar</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-num" data-count="<?= $total_dinas ?>"><?= $total_dinas ?></div>
        <div class="hero-stat-label">Dinas</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-num" data-count="<?= $total_pelatihan ?>"><?= $total_pelatihan ?></div>
        <div class="hero-stat-label">Pelatihan</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-num" data-count="<?= $total_wilayah ?>"><?= $total_wilayah ?></div>
        <div class="hero-stat-label">Wilayah</div>
      </div>
    </div>
  </div>
  <div class="scroll-hint">
    <span>Scroll ke bawah</span>
    <i class="fas fa-chevron-down"></i>
  </div>
</section>

<!-- ═══ WILAYAH KERJA (MAP) ═══ -->
<section class="section section-dark">
  <div class="section-inner">
    <div class="sec-header reveal" style="color:#fff"><i class="fas fa-map-marked-alt"></i> Peta Wilayah Kerja</div>
    <p class="sec-sub reveal" style="color:rgba(255,255,255,0.4)">7 wilayah kerja Balai Pengembangan Kompetensi PU VIII Makassar</p>
    <div class="wil-map-wrap reveal">
      <div id="mapTamu"></div>
    </div>
    <div class="wil-info-grid">
      <?php
      $wil_colors = ['#ef4444','#f59e0b','#22c55e','#3b82f6','#8b5cf6','#ec4899','#06b6d4'];
      foreach ($wil_stats as $i => $w):
        $clr = $wil_colors[$i] ?? '#64748b';
      ?>
      <a href="wilayah.php?id=<?= (int)$w['id'] ?>" class="wil-info-card reveal">
        <div class="wil-info-dot" style="background:<?= $clr ?>"><i class="fas fa-location-dot"></i></div>
        <div>
          <div class="wil-info-name"><?= htmlspecialchars(str_replace('Wilayah Kerja ', '', $w['nama_wilayah'])) ?></div>
          <div class="wil-info-meta"><i class="fas fa-building"></i> <?= (int)$w['jml_dinas'] ?> dinas terdaftar</div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ KATA SAMBUTAN ADMIN ═══ -->
<section class="section section-light" id="sambutan">
  <div class="section-inner">
    <div class="sec-header reveal"><i class="fas fa-quote-left"></i> Kata Sambutan</div>
    <p class="sec-sub reveal">Selamat datang di Sistem Informasi MitigaPro</p>

    <div class="sambutan-wrap reveal">
      <div class="sambutan-photo">
        <?php if (!empty($sambutan['foto'])): ?>
        <img src="<?= BASE_URL ?>uploads/visitor/<?= htmlspecialchars($sambutan['foto']) ?>" alt="Foto">
        <?php else: ?>
        <div class="sambutan-photo-placeholder">
          <i class="fas fa-user-tie"></i>
        </div>
        <?php endif; ?>
      </div>
      <div class="sambutan-body">
        <div class="sambutan-label"><i class="fas fa-shield-halved"></i> Pengelola Sistem</div>
        <h3><?= htmlspecialchars($sambutan['judul'] ?? 'Selamat Datang, Pengunjung!') ?></h3>
        <div class="sambutan-role"><?= htmlspecialchars($sambutan['jabatan'] ?? 'Admin & Pengelola Sistem Informasi MitigaPro') ?></div>
        <div class="sambutan-text">
          <?= nl2br(htmlspecialchars($sambutan['isi'] ?? 'Selamat datang di Sistem Informasi MitigaPro.')) ?>
        </div>
        <div class="sambutan-sign">
          <div class="sambutan-sign-line"></div>
          <div class="sambutan-sign-name"><?= htmlspecialchars($sambutan['nama'] ?? 'Tim Pengelola MitigaPro') ?></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ TENTANG KAMI / PROFIL BALAI ═══ -->
<section class="section section-dark" id="tentang">
  <div class="section-inner">
    <div class="sec-header reveal" style="color:#fff"><i class="fas fa-landmark"></i> Tentang Kami</div>
    <p class="sec-sub reveal" style="color:rgba(255,255,255,0.4)">Profil Balai Pengembangan Kompetensi PU Wilayah VIII Makassar</p>

    <?php
    $about_config = [
      'visi'   => ['label' => 'Visi',        'icon' => 'fa-eye',      'gradient' => 'linear-gradient(135deg,#3b82f6,#6366f1)', 'css' => 'about-visi'],
      'misi'   => ['label' => 'Misi',        'icon' => 'fa-bullseye', 'gradient' => 'linear-gradient(135deg,#22c55e,#10b981)', 'css' => 'about-misi'],
      'tugas'  => ['label' => 'Tugas Pokok', 'icon' => 'fa-tasks',    'gradient' => 'linear-gradient(135deg,#f59e0b,#fbbf24)', 'css' => 'about-tugas'],
      'fungsi' => ['label' => 'Fungsi',      'icon' => 'fa-cogs',     'gradient' => 'linear-gradient(135deg,#ec4899,#f472b6)', 'css' => 'about-fungsi'],
    ];
    ?>
    <div class="about-grid">
      <?php foreach ($about_config as $tipe => $cfg): if (!empty($profil[$tipe])): ?>
      <div class="about-card reveal">
        <div class="about-card-icon" style="background:<?= $cfg['gradient'] ?>">
          <i class="fas <?= $cfg['icon'] ?>"></i>
        </div>
        <h4><?= $cfg['label'] ?></h4>
        <ul class="<?= $cfg['css'] ?>">
          <?php foreach ($profil[$tipe] as $item): ?>
          <li><?= htmlspecialchars($item['isi']) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ STRUKTUR ORGANISASI ═══ -->
<section class="section section-light" id="struktur">
  <div class="section-inner">
    <div class="sec-header reveal"><i class="fas fa-sitemap"></i> Struktur Organisasi</div>
    <p class="sec-sub reveal">Bagan organisasi Balai Pengembangan Kompetensi PU Wilayah VIII</p>

    <?php if (!empty($struktur_list)):
      // Group by level
      $levels = [];
      foreach ($struktur_list as $s) $levels[(int)$s['level']][] = $s;
      ksort($levels);
    ?>
    <div class="orgchart">
      <?php $first = true; foreach ($levels as $lvl => $items): ?>
        <?php if (!$first): ?><div class="org-connector"></div><?php endif; ?>
        <div class="org-level reveal">
          <?php foreach ($items as $s): ?>
          <div class="org-card <?= $lvl === 1 ? 'head' : 'sub' ?>" style="border-top-color:<?= htmlspecialchars($s['warna']) ?>">
            <div class="org-avatar" style="background:<?= $lvl === 1 ? 'linear-gradient(135deg,#eff6ff,#dbeafe)' : 'linear-gradient(135deg,#f0fdf4,#dcfce7)' ?>">
              <i class="<?= htmlspecialchars($s['icon']) ?>" style="color:<?= htmlspecialchars($s['warna']) ?>"></i>
            </div>
            <div class="org-name"><?= htmlspecialchars($s['nama']) ?></div>
            <div class="org-pos"><?= htmlspecialchars($s['jabatan']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php $first = false; endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:50px;color:var(--muted)">
      <i class="fas fa-sitemap" style="font-size:48px;opacity:.3;display:block;margin-bottom:14px"></i>
      <p>Struktur organisasi belum diatur.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══ DAFTAR PENGAJAR PUBLIK ═══ -->
<section class="section section-gray" id="pengajar">
  <div class="section-inner">
    <div class="sec-header reveal"><i class="fas fa-chalkboard-teacher"></i> Daftar Pengajar</div>
    <p class="sec-sub reveal">Tenaga pengajar aktif Balai Pengembangan Kompetensi PU Wilayah VIII Makassar</p>

    <?php if (empty($pengajar_list)): ?>
    <div style="text-align:center;padding:60px;color:var(--muted)">
      <i class="fas fa-users" style="font-size:48px;opacity:.3;display:block;margin-bottom:16px"></i>
      <p>Belum ada data pengajar.</p>
    </div>
    <?php else: ?>
    <div class="pengajar-grid">
      <?php foreach ($pengajar_list as $peng): ?>
      <div class="peng-card reveal">
        <div class="peng-card-top">
          <?php if (!empty($peng['foto'])): ?>
          <img class="peng-avatar" src="<?= BASE_URL ?>uploads/pengajar/<?= htmlspecialchars($peng['foto']) ?>" alt="">
          <?php else: ?>
          <div class="peng-avatar-ph"><i class="fas fa-user"></i></div>
          <?php endif; ?>
        </div>
        <div class="peng-card-body">
          <div class="peng-name"><?= htmlspecialchars($peng['nama_pengajar']) ?></div>
          <div class="peng-jabatan"><?= htmlspecialchars($peng['jabatan']) ?></div>
          <div class="peng-unit"><?= htmlspecialchars($peng['unit_kerja']) ?></div>
          <div class="peng-edu"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($peng['pendidikan_terakhir']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══ BERITA AUTO-SCROLL ═══ -->
<section class="section section-gray">
  <div class="section-inner">
    <div class="sec-header reveal"><i class="fas fa-newspaper"></i> Berita Pelatihan Terbaru</div>
    <p class="sec-sub reveal">Informasi terkini seputar kegiatan pelatihan</p>

    <?php if (empty($berita_list)): ?>
    <div style="text-align:center;padding:60px;color:var(--muted)">
      <i class="fas fa-newspaper" style="font-size:48px;opacity:.3;display:block;margin-bottom:16px"></i>
      <p>Belum ada berita pelatihan.</p>
    </div>
    <?php else: ?>
    <div class="ticker-wrap reveal" id="tickerWrap">
      <div class="ticker-track" id="tickerTrack">
        <?php foreach ($berita_list as $b): ?>
        <a href="detail_berita.php?id=<?= (int)$b['id'] ?>" class="ticker-card">
          <div class="ticker-img">
            <?php if (!empty($b['gambar'])): ?>
              <img src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($b['gambar']) ?>" alt="">
            <?php else: ?>
              <div class="ticker-ph"><i class="fas fa-newspaper"></i></div>
            <?php endif; ?>
          </div>
          <div class="ticker-body">
            <?php if (!empty($b['kategori'])): ?>
            <span class="ticker-cat"><?= htmlspecialchars($b['kategori']) ?></span>
            <?php endif; ?>
            <div class="ticker-title"><?= htmlspecialchars(mb_strimwidth($b['judul'], 0, 60, '...')) ?></div>
            <div class="ticker-excerpt"><?= htmlspecialchars(mb_strimwidth(strip_tags($b['isi']), 0, 80, '...')) ?></div>
            <div class="ticker-date"><i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($b['created_at'])) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
        <!-- Duplikat untuk seamless loop -->
        <?php foreach ($berita_list as $b): ?>
        <a href="detail_berita.php?id=<?= (int)$b['id'] ?>" class="ticker-card">
          <div class="ticker-img">
            <?php if (!empty($b['gambar'])): ?>
              <img src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($b['gambar']) ?>" alt="">
            <?php else: ?>
              <div class="ticker-ph"><i class="fas fa-newspaper"></i></div>
            <?php endif; ?>
          </div>
          <div class="ticker-body">
            <?php if (!empty($b['kategori'])): ?>
            <span class="ticker-cat"><?= htmlspecialchars($b['kategori']) ?></span>
            <?php endif; ?>
            <div class="ticker-title"><?= htmlspecialchars(mb_strimwidth($b['judul'], 0, 60, '...')) ?></div>
            <div class="ticker-excerpt"><?= htmlspecialchars(mb_strimwidth(strip_tags($b['isi']), 0, 80, '...')) ?></div>
            <div class="ticker-date"><i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($b['created_at'])) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══ GALERI KEGIATAN ═══ -->
<section class="section section-light" id="galeri">
  <div class="section-inner">
    <div class="sec-header reveal"><i class="fas fa-images"></i> Galeri Kegiatan</div>
    <p class="sec-sub reveal">Dokumentasi kegiatan pelatihan dan acara</p>

    <div class="galeri-grid">
      <?php
      // Gabungkan galeri dari tabel visitor_galeri + berita yang punya gambar
      $galeri_combined = $galeri_list;
      if (empty($galeri_combined)) {
          // Fallback: ambil dari berita jika galeri kosong
          $rg = $conn->query("SELECT judul, gambar, kategori, created_at FROM berita_pelatihan WHERE gambar IS NOT NULL AND gambar != '' ORDER BY created_at DESC LIMIT 6");
          if ($rg) { while ($row = $rg->fetch_assoc()) $galeri_combined[] = $row; $rg->free(); }
      }

      if (empty($galeri_combined)):
      ?>
      <div style="text-align:center;padding:60px;color:var(--muted);grid-column:1/-1">
        <i class="fas fa-camera" style="font-size:48px;opacity:.3;display:block;margin-bottom:16px"></i>
        <p>Belum ada dokumentasi kegiatan.</p>
      </div>
      <?php else: ?>
        <?php foreach ($galeri_combined as $gi):
          // Tentukan path gambar (galeri vs berita)
          $img_src = isset($gi['id']) && isset($gi['aktif'])
            ? BASE_URL . 'uploads/galeri/' . htmlspecialchars($gi['gambar'])
            : BASE_URL . 'uploads/berita/' . htmlspecialchars($gi['gambar']);
        ?>
        <div class="galeri-card reveal">
          <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($gi['judul']) ?>">
          <div class="galeri-overlay">
            <span><?= htmlspecialchars(mb_strimwidth($gi['judul'], 0, 50, '...')) ?></span>
            <small><?= !empty($gi['kategori']) ? htmlspecialchars($gi['kategori']) . ' • ' : '' ?><?= date('d M Y', strtotime($gi['created_at'])) ?></small>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══ FAQ ═══ -->
<section class="section section-gray" id="faq">
  <div class="section-inner">
    <div class="sec-header reveal"><i class="fas fa-circle-question"></i> Pertanyaan Umum (FAQ)</div>
    <p class="sec-sub reveal">Informasi yang sering ditanyakan pengunjung</p>

    <?php if (!empty($faq_list)): ?>
    <div class="faq-list">
      <?php foreach ($faq_list as $f): ?>
      <div class="faq-item reveal">
        <div class="faq-q" onclick="this.parentElement.classList.toggle('open')">
          <span><?= htmlspecialchars($f['pertanyaan']) ?></span>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-a">
          <p><?= nl2br(htmlspecialchars($f['jawaban'])) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:50px;color:var(--muted)">
      <i class="fas fa-circle-question" style="font-size:48px;opacity:.3;display:block;margin-bottom:14px"></i>
      <p>Belum ada FAQ.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══ INFORMASI & KONTAK ADMIN ═══ -->
<section class="section section-light" id="kontak">
  <div class="section-inner">
    <div class="sec-header reveal"><i class="fas fa-address-card"></i> Informasi &amp; Kontak</div>
    <p class="sec-sub reveal">Hubungi kami untuk informasi lebih lanjut</p>

    <div class="kontak-grid">
      <div class="kontak-info">
        <?php
        $kontak_keys = ['kepala_balai','alamat','telepon','email','jam_kerja'];
        foreach ($kontak_keys as $kk):
          if (!isset($kontak_map[$kk])) continue;
          $k = $kontak_map[$kk];
        ?>
        <div class="kontak-item reveal">
          <div class="kontak-icon" style="background:<?= htmlspecialchars($k['warna']) ?>">
            <i class="<?= htmlspecialchars($k['icon']) ?>"></i>
          </div>
          <div>
            <h5><?= htmlspecialchars($k['label']) ?></h5>
            <p><?= nl2br(htmlspecialchars($k['nilai'])) ?></p>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($sosmed_list)): ?>
        <div class="kontak-socials reveal">
          <?php foreach ($sosmed_list as $sm): ?>
          <a href="<?= htmlspecialchars($sm['url']) ?>" class="kontak-social <?= htmlspecialchars($sm['warna_class']) ?>" target="_blank" rel="noopener">
            <i class="<?= htmlspecialchars($sm['icon']) ?>"></i> <?= htmlspecialchars($sm['platform']) ?>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Google Maps -->
      <?php if (!empty($kontak_map['google_maps']['nilai'])): ?>
      <div class="kontak-map reveal">
        <iframe 
          src="<?= htmlspecialchars($kontak_map['google_maps']['nilai']) ?>" 
          allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══ LINK TAUTAN TERKAIT ═══ -->
<section class="section section-dark" id="tautan">
  <div class="section-inner">
    <div class="sec-header reveal" style="color:#fff"><i class="fas fa-link"></i> Tautan Terkait</div>
    <p class="sec-sub reveal" style="color:rgba(255,255,255,0.4)">Link website instansi terkait</p>

    <?php if (!empty($tautan_list)): ?>
    <div class="tautan-grid">
      <?php foreach ($tautan_list as $t): ?>
      <a href="<?= htmlspecialchars($t['url']) ?>" class="tautan-card reveal" target="_blank" rel="noopener">
        <div class="tautan-logo"><i class="<?= htmlspecialchars($t['icon']) ?>"></i></div>
        <div>
          <div class="tautan-name"><?= htmlspecialchars($t['nama']) ?></div>
          <?php if (!empty($t['deskripsi'])): ?>
          <div class="tautan-desc"><?= htmlspecialchars($t['deskripsi']) ?></div>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:50px;color:rgba(255,255,255,0.4)">
      <p>Belum ada tautan terkait.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══ FOOTER ENHANCED ═══ -->
<div class="tamu-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
        <img src="<?= BASE_URL ?>logo.png" alt="Logo" style="width:36px;height:36px;object-fit:contain;filter:brightness(0) invert(1);opacity:0.6">
        <h4 style="margin:0">MitigaPro</h4>
      </div>
      <p>Sistem Informasi Pengelolaan Data Pengajar, Identifikasi Kebutuhan Pelatihan &amp; Wilayah Kerja — Balai Pengembangan Kompetensi Pekerjaan Umum Wilayah VIII Makassar.</p>
    </div>
    <div class="footer-col">
      <h5>Navigasi</h5>
      <a href="#sambutan"><i class="fas fa-quote-left" style="width:16px"></i> Kata Sambutan</a>
      <a href="#tentang"><i class="fas fa-landmark" style="width:16px"></i> Tentang Kami</a>
      <a href="#struktur"><i class="fas fa-sitemap" style="width:16px"></i> Struktur Organisasi</a>
      <a href="#pengajar"><i class="fas fa-chalkboard-teacher" style="width:16px"></i> Pengajar</a>
      <a href="#galeri"><i class="fas fa-images" style="width:16px"></i> Galeri</a>
    </div>
    <div class="footer-col">
      <h5>Lainnya</h5>
      <a href="#faq"><i class="fas fa-circle-question" style="width:16px"></i> FAQ</a>
      <a href="#kontak"><i class="fas fa-address-card" style="width:16px"></i> Kontak</a>
      <a href="#tautan"><i class="fas fa-link" style="width:16px"></i> Tautan Terkait</a>
      <a href="<?= BASE_URL ?>login.php"><i class="fas fa-sign-in-alt" style="width:16px"></i> Masuk Sistem</a>
    </div>
  </div>
  <div class="footer-bottom">
    &copy; <?= date('Y') ?> <strong>Balai Pengembangan Kompetensi PU Wilayah VIII Makassar</strong>
    &mdash; Sistem Informasi MitigaPro. Hak Cipta Dilindungi.
  </div>
</div>

<script>
(function(){
  'use strict';

  // ── Topbar scroll effect ──
  const topbar = document.getElementById('topbar');
  window.addEventListener('scroll', () => {
    topbar.classList.toggle('scrolled', window.scrollY > 50);
  }, {passive:true});

  // ── Particles ──
  const pc = document.getElementById('particles');
  if (pc) {
    for (let i = 0; i < 30; i++) {
      const p = document.createElement('span');
      p.className = 'particle';
      p.style.left = Math.random() * 100 + '%';
      p.style.animationDuration = (6 + Math.random() * 10) + 's';
      p.style.animationDelay = Math.random() * 8 + 's';
      p.style.width = p.style.height = (2 + Math.random() * 4) + 'px';
      pc.appendChild(p);
    }
  }

  // ── Counter animation ──
  document.querySelectorAll('.hero-stat-num').forEach(el => {
    const target = parseInt(el.dataset.count, 10);
    if (isNaN(target) || target < 1) return;
    el.textContent = '0';
    const dur = 1500, start = performance.now();
    function tick(now) {
      const p = Math.min((now - start) / dur, 1);
      const ease = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * ease);
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  });

  // ── Scroll reveal ──
  const reveals = document.querySelectorAll('.reveal, .reveal-left');
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });
  reveals.forEach(el => obs.observe(el));

  // ── Berita Auto-Scroll Ticker ──
  const track = document.getElementById('tickerTrack');
  if (track) {
    const cardCount = track.children.length / 2;
    const duration = Math.max(15, cardCount * 6);
    track.style.animationDuration = duration + 's';
  }

  // ── Leaflet Map Wilayah ──
  const mapEl = document.getElementById('mapTamu');
  if (mapEl) {
    const map = L.map('mapTamu', {
      center: [-1.5, 123.5],
      zoom: 6,
      zoomControl: true,
      scrollWheelZoom: false
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; OSM &amp; CARTO',
      maxZoom: 18
    }).addTo(map);

    const wilData = [
      { id:1, nama:'Sulawesi Selatan',  lat:-3.67, lng:119.97, color:'#ef4444', dinas:<?= (int)($wil_stats[0]['jml_dinas']??0) ?> },
      { id:2, nama:'Sulawesi Barat',    lat:-2.84, lng:119.23, color:'#f59e0b', dinas:<?= (int)($wil_stats[1]['jml_dinas']??0) ?> },
      { id:3, nama:'Sulawesi Tengah',   lat:-1.43, lng:121.45, color:'#22c55e', dinas:<?= (int)($wil_stats[2]['jml_dinas']??0) ?> },
      { id:4, nama:'Sulawesi Utara',    lat:1.49,  lng:124.84, color:'#3b82f6', dinas:<?= (int)($wil_stats[3]['jml_dinas']??0) ?> },
      { id:5, nama:'Sulawesi Tenggara', lat:-3.97, lng:122.51, color:'#8b5cf6', dinas:<?= (int)($wil_stats[4]['jml_dinas']??0) ?> },
      { id:6, nama:'Gorontalo',         lat:0.54,  lng:123.06, color:'#ec4899', dinas:<?= (int)($wil_stats[5]['jml_dinas']??0) ?> },
      { id:7, nama:'Maluku Utara',      lat:1.57,  lng:127.81, color:'#06b6d4', dinas:<?= (int)($wil_stats[6]['jml_dinas']??0) ?> }
    ];

    wilData.forEach(w => {
      const icon = L.divIcon({
        className: '',
        html: `<div style="background:${w.color};width:34px;height:34px;border-radius:50%;border:3px solid rgba(255,255,255,0.9);box-shadow:0 2px 12px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700">${w.id}</div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 17]
      });

      const marker = L.marker([w.lat, w.lng], { icon }).addTo(map);

      marker.bindPopup(
        `<div style="font-family:Poppins,sans-serif;min-width:180px">`+
          `<div style="font-size:14px;font-weight:700;color:#1a2744;margin-bottom:6px">`+
            `<i class="fas fa-location-dot" style="color:${w.color}"></i> ${w.nama}`+
          `</div>`+
          `<div style="font-size:12px;color:#64748b;margin-bottom:10px">`+
            `<i class="fas fa-building"></i> ${w.dinas} dinas terdaftar`+
          `</div>`+
          `<a href="wilayah.php?id=${w.id}" style="display:inline-flex;align-items:center;gap:5px;background:${w.color};color:#fff;padding:6px 14px;border-radius:8px;font-size:11px;font-weight:600;text-decoration:none">`+
            `<i class="fas fa-eye"></i> Lihat Detail`+
          `</a>`+
        `</div>`,
        { closeButton: true, maxWidth: 250 }
      );

      marker.bindTooltip(w.nama, {
        permanent: true,
        direction: 'bottom',
        offset: [0, 14],
        className: 'wil-map-tip'
      });
    });

    // Tooltip style
    const ts = document.createElement('style');
    ts.textContent = '.wil-map-tip{background:rgba(26,39,68,0.9);color:#fff;border:none;border-radius:6px;padding:3px 10px;font-size:10px;font-weight:600;font-family:Poppins,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,0.3);backdrop-filter:blur(4px)}.wil-map-tip::before{border-bottom-color:rgba(26,39,68,0.9)!important}';
    document.head.appendChild(ts);

    // Fix size after reveal animation
    const mapObs = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting) { map.invalidateSize(); mapObs.unobserve(mapEl); }
    }, { threshold: 0.1 });
    mapObs.observe(mapEl);
  }

  // ── Smooth scroll for nav links ──
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ── Active nav link highlight ──
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.topbar-nav-link');
  window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(sec => {
      if (window.scrollY >= sec.offsetTop - 100) current = sec.getAttribute('id');
    });
    navLinks.forEach(link => {
      link.style.color = link.getAttribute('href') === '#' + current
        ? 'rgba(255,255,255,0.95)' : 'rgba(255,255,255,0.6)';
    });
  }, { passive: true });
})();
</script>

</body>
</html>
