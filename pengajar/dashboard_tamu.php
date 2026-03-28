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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Informasi MitigaPro — Bapekom PU VIII Makassar</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:   #1a2744;
  --blue:   #2c5282;
  --accent: #3b82f6;
  --bg:     #f0f4f8;
  --white:  #ffffff;
  --text:   #334155;
  --muted:  #94a3b8;
  --border: #e2e8f0;
  --radius: 14px;
}

body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
}

/* ── Topbar ── */
.topbar {
  background: linear-gradient(135deg, #1a2744, #2c5282);
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}
.topbar-left {
  display: flex;
  align-items: center;
  gap: 14px;
}
.topbar-logo {
  width: 36px; height: 36px;
}
.topbar-logo img {
  width: 100%; height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));
}
.topbar-title {
  color: #fff;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.topbar-sub {
  color: rgba(255,255,255,0.55);
  font-size: 11px;
  font-weight: 400;
}
.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}
.topbar-badge {
  background: rgba(255,255,255,0.12);
  color: rgba(255,255,255,0.7);
  font-size: 12px;
  font-weight: 500;
  padding: 5px 14px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.topbar-login {
  background: rgba(255,255,255,0.15);
  color: #fff;
  border: 1px solid rgba(255,255,255,0.2);
  padding: 7px 18px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.2s;
}
.topbar-login:hover { background: rgba(255,255,255,0.25); }

@media (max-width: 600px) {
  .topbar { padding: 0 16px; }
  .topbar-title { font-size: 14px; }
  .topbar-sub { display: none; }
}

/* ── Container ── */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 28px 24px 60px;
}

/* ── Hero ── */
.hero {
  background: linear-gradient(135deg, #1a2744, #2c5282 60%, #3b82f6);
  border-radius: 18px;
  padding: 40px 44px;
  color: #fff;
  margin-bottom: 28px;
  position: relative;
  overflow: hidden;
}
.hero::after {
  content: '';
  position: absolute;
  width: 300px; height: 300px;
  background: rgba(255,255,255,0.04);
  border-radius: 50%;
  top: -80px; right: -60px;
}
.hero-badge {
  background: rgba(255,255,255,0.15);
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 1px;
  text-transform: uppercase;
  margin-bottom: 16px;
}
.hero h1 {
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1.4;
  margin-bottom: 8px;
}
.hero p {
  font-size: 13.5px;
  color: rgba(255,255,255,0.65);
  line-height: 1.7;
  max-width: 700px;
}
@media (max-width: 600px) {
  .hero { padding: 28px 20px; }
  .hero h1 { font-size: 1.2rem; }
}

/* ── Stat Cards ── */
.stat-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}
.stat-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px 22px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition: box-shadow 0.2s, transform 0.2s;
}
.stat-card:hover {
  box-shadow: 0 6px 20px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}
.stat-icon {
  width: 50px; height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}
.stat-icon.blue   { background: #eff6ff; color: #3b82f6; }
.stat-icon.green  { background: #f0fdf4; color: #16a34a; }
.stat-icon.amber  { background: #fffbeb; color: #f59e0b; }
.stat-icon.purple { background: #faf5ff; color: #8b5cf6; }
.stat-num {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--navy);
  line-height: 1;
}
.stat-label {
  font-size: 12px;
  color: var(--muted);
  margin-top: 3px;
}

/* ── Section Header ── */
.sec-header {
  font-size: 16px;
  font-weight: 700;
  color: var(--navy);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.sec-header i { color: var(--accent); }

/* ── Wilayah Grid ── */
.wil-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}
.wil-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  text-decoration: none;
  color: inherit;
  transition: box-shadow 0.2s, transform 0.2s;
}
.wil-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.07);
  transform: translateY(-3px);
}
.wil-icon {
  width: 44px; height: 44px;
  border-radius: 10px;
  background: #eff6ff;
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}
.wil-info { flex: 1; }
.wil-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--navy);
  line-height: 1.3;
}
.wil-meta {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11.5px;
  color: var(--muted);
  margin-top: 4px;
}
.wil-arrow {
  color: var(--accent);
  font-size: 16px;
  flex-shrink: 0;
  opacity: 0;
  transition: opacity 0.2s;
}
.wil-card:hover .wil-arrow { opacity: 1; }

/* ── Berita Grid ── */
.berita-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}
.berita-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  transition: box-shadow 0.2s, transform 0.2s;
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
}
.berita-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.07);
  transform: translateY(-3px);
}
.berita-img {
  width: 100%;
  height: 140px;
  object-fit: cover;
  background: #e2e8f0;
}
.berita-img-placeholder {
  width: 100%;
  height: 140px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  color: #93c5fd;
}
.berita-body {
  padding: 16px 18px;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.berita-cat {
  display: inline-block;
  background: #eff6ff;
  color: var(--accent);
  font-size: 10px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 12px;
  margin-bottom: 8px;
  align-self: flex-start;
}
.berita-title {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--navy);
  line-height: 1.4;
  margin-bottom: 6px;
}
.berita-excerpt {
  font-size: 12px;
  color: var(--muted);
  line-height: 1.6;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.berita-date {
  font-size: 11px;
  color: #cbd5e1;
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 5px;
}

/* ── Empty state ── */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--muted);
}
.empty-state i { font-size: 36px; margin-bottom: 12px; color: #cbd5e1; }
.empty-state p { font-size: 13px; }

/* ── Quick Access Buttons ── */
.quick-access {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 32px;
}
.qa-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 22px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.qa-btn.blue    { background: var(--accent); color: #fff; }
.qa-btn.blue:hover { background: #2563eb; box-shadow: 0 4px 14px rgba(59,130,246,0.3); }
.qa-btn.green   { background: #16a34a; color: #fff; }
.qa-btn.green:hover { background: #15803d; box-shadow: 0 4px 14px rgba(22,163,74,0.3); }
.qa-btn.amber   { background: #f59e0b; color: #fff; }
.qa-btn.amber:hover { background: #d97706; box-shadow: 0 4px 14px rgba(245,158,11,0.3); }
.qa-btn.purple  { background: #8b5cf6; color: #fff; }
.qa-btn.purple:hover { background: #7c3aed; box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
.qa-btn i { font-size: 15px; }
@media (max-width: 600px) {
  .quick-access { flex-direction: column; }
  .qa-btn { justify-content: center; }
}

/* ── Footer ── */
.tamu-footer {
  background: var(--navy);
  color: rgba(255,255,255,0.5);
  text-align: center;
  padding: 20px 24px;
  font-size: 12px;
}
.tamu-footer strong { color: rgba(255,255,255,0.8); }
</style>
</head>
<body>

<!-- ═══ TOPBAR ═══ -->
<div class="topbar">
  <div class="topbar-left">
    <div class="topbar-logo">
      <img src="<?= BASE_URL ?>logo.png" alt="Logo">
    </div>
    <div>
      <div class="topbar-title">MitigaPro</div>
      <div class="topbar-sub">Sistem Informasi Pelatihan &amp; Wilayah Kerja</div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-badge">
      <i class="fas fa-eye"></i> Mode Pengunjung
    </div>
    <a href="<?= BASE_URL ?>login.php" class="topbar-login">
      <i class="fas fa-sign-in-alt"></i> Masuk
    </a>
  </div>
</div>

<!-- ═══ CONTENT ═══ -->
<div class="container">

  <!-- Hero Banner -->
  <div class="hero">
    <div class="hero-badge"><i class="fas fa-landmark"></i> Kementerian Pekerjaan Umum</div>
    <h1>Balai Pengembangan Kompetensi PU<br>Wilayah VIII Makassar</h1>
    <p>Sistem informasi pengelolaan data pengajar, identifikasi kebutuhan pelatihan, dan wilayah kerja yang mencakup Sulawesi, Gorontalo, dan Maluku Utara.</p>
  </div>

  <!-- Statistik -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div>
      <div>
        <div class="stat-num"><?= $total_pengajar ?></div>
        <div class="stat-label">Total Pengajar</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fas fa-building"></i></div>
      <div>
        <div class="stat-num"><?= $total_dinas ?></div>
        <div class="stat-label">Dinas Terdaftar</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon amber"><i class="fas fa-graduation-cap"></i></div>
      <div>
        <div class="stat-num"><?= $total_pelatihan ?></div>
        <div class="stat-label">Identifikasi Pelatihan</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon purple"><i class="fas fa-map-marked-alt"></i></div>
      <div>
        <div class="stat-num"><?= $total_wilayah ?></div>
        <div class="stat-label">Wilayah Kerja</div>
      </div>
    </div>
  </div>

  <!-- Akses Cepat -->
  <div class="sec-header"><i class="fas fa-th-large"></i> Akses Cepat</div>
  <div class="quick-access">
    <a href="<?= BASE_URL ?>pengajar/pengajar.php" class="qa-btn blue">
      <i class="fas fa-chalkboard-teacher"></i> Data Pengajar
    </a>
    <a href="<?= BASE_URL ?>pengajar/dinas.php" class="qa-btn green">
      <i class="fas fa-building"></i> Data Dinas
    </a>
    <a href="<?= BASE_URL ?>pengajar/daftar_pelatihan.php" class="qa-btn amber">
      <i class="fas fa-graduation-cap"></i> Daftar Pelatihan
    </a>
    <a href="<?= BASE_URL ?>pengajar/berita_pelatihan.php" class="qa-btn purple">
      <i class="fas fa-newspaper"></i> Berita Pelatihan
    </a>
  </div>

  <!-- Wilayah Kerja -->
  <div class="sec-header"><i class="fas fa-layer-group"></i> Wilayah Kerja</div>
  <div class="wil-grid">
    <?php foreach ($wil_stats as $w): ?>
    <a href="wilayah.php?id=<?= (int)$w['id'] ?>" class="wil-card">
      <div class="wil-icon"><i class="fas fa-location-dot"></i></div>
      <div class="wil-info">
        <div class="wil-name"><?= htmlspecialchars(str_replace('Wilayah Kerja ', '', $w['nama_wilayah'])) ?></div>
        <div class="wil-meta"><i class="fas fa-building"></i> <?= (int)$w['jml_dinas'] ?> dinas</div>
      </div>
      <i class="fas fa-chevron-right wil-arrow"></i>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Berita Pelatihan -->
  <div class="sec-header"><i class="fas fa-newspaper"></i> Berita Pelatihan Terbaru</div>
  <?php if (empty($berita_list)): ?>
  <div class="empty-state">
    <i class="fas fa-newspaper"></i>
    <p>Belum ada berita pelatihan yang dipublikasikan.</p>
  </div>
  <?php else: ?>
  <div class="berita-grid">
    <?php foreach ($berita_list as $b): ?>
    <a href="detail_berita.php?id=<?= (int)$b['id'] ?>" class="berita-card">
      <?php if (!empty($b['gambar'])): ?>
      <img class="berita-img" src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($b['gambar']) ?>" alt="">
      <?php else: ?>
      <div class="berita-img-placeholder"><i class="fas fa-image"></i></div>
      <?php endif; ?>
      <div class="berita-body">
        <?php if (!empty($b['kategori'])): ?>
        <span class="berita-cat"><?= htmlspecialchars($b['kategori']) ?></span>
        <?php endif; ?>
        <div class="berita-title"><?= htmlspecialchars($b['judul']) ?></div>
        <div class="berita-excerpt"><?= htmlspecialchars(strip_tags($b['isi'])) ?></div>
        <div class="berita-date"><i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($b['created_at'])) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<!-- ═══ FOOTER ═══ -->
<div class="tamu-footer">
  &copy; <?= date('Y') ?> <strong>Balai Pengembangan Kompetensi PU Wilayah VIII Makassar</strong>
  &mdash; Sistem Informasi MitigaPro
</div>

</body>
</html>
