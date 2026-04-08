<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Tamu diarahkan ke dashboard khusus (full screen)
if (($_SESSION['role'] ?? '') === 'tamu') {
    header("Location: " . BASE_URL . "pengajar/dashboard_tamu.php");
    exit;
}

/* ── Simpan catatan cepat ── */
$save_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['catatan_wilayah_id']) && ($_SESSION['role'] ?? '') !== 'tamu') {
    $wid     = intval($_POST['catatan_wilayah_id']);
    $catatan = trim($_POST['catatan'] ?? '');
    $user_id = intval($_SESSION['user_id']);

    if ($catatan !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO catatan_wilayah (wilayah_id, user_id, catatan, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE catatan = VALUES(catatan), created_at = NOW()"
        );
        if ($stmt) {
            $stmt->bind_param('iis', $wid, $user_id, $catatan);
            $stmt->execute();
            $stmt->close();
            $save_msg = 'ok';
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?saved=" . ($save_msg === 'ok' ? '1' : '0'));
    exit;
}

require INCLUDE_PATH . 'sidebar_pengajar.php';

// Ambil berita terbaru untuk slider
$q_berita_slider = $conn->query("SELECT b.*, u.username FROM berita_pelatihan b LEFT JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC LIMIT 10");
$berita_list = [];
while ($b = $q_berita_slider->fetch_assoc()) $berita_list[] = $b;

// Ambil data wilayah + jumlah dinas per wilayah
$q_wilayah = $conn->query("
  SELECT w.id, w.nama_wilayah, COUNT(d.id) AS jml_dinas
  FROM wilayah w
  LEFT JOIN dinas d ON d.wilayah_id = w.id
  GROUP BY w.id
  ORDER BY w.id
");
$wilayah_list = [];
while ($w = $q_wilayah->fetch_assoc()) $wilayah_list[] = $w;

// Admin stats
$role = $_SESSION['role'] ?? '';
if ($role === 'admin') {
    $total_pengajar  = (int)$conn->query("SELECT COUNT(*) AS c FROM pengajar")->fetch_assoc()['c'];
    $total_dinas     = (int)$conn->query("SELECT COUNT(*) AS c FROM dinas")->fetch_assoc()['c'];
    $total_pelatihan = (int)$conn->query("SELECT COUNT(*) AS c FROM identifikasi_pelatihan")->fetch_assoc()['c'];
    $total_berita    = (int)$conn->query("SELECT COUNT(*) AS c FROM berita_pelatihan")->fetch_assoc()['c'];
    $total_users     = (int)$conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
<title>Dashboard | Wilayah Kerja Bapekom PU VIII Makassar</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:   #1a2744;
  --blue:   #2c5282;
  --accent: #3b82f6;
  --bg:     #f5f7fb;
  --white:  #ffffff;
  --text:   #334155;
  --muted:  #94a3b8;
  --border: #e2e8f0;
  --radius: 12px;
}

body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
}

/* ── Layout ── */
.main {
  max-width: 1100px;
  margin: 0 auto;
  padding: 36px 32px 60px;
}
@media (max-width: 768px) { .main { padding: 24px 16px 40px; } }

/* ═══ ADMIN DASHBOARD ═══ */
.admin-greeting {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 28px; flex-wrap: wrap; gap: 16px;
}
.admin-greeting h2 { font-size: 1.35rem; font-weight: 700; color: var(--navy); margin: 0; }
.admin-greeting p { font-size: 13px; color: var(--muted); margin: 4px 0 0; }
.ag-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--accent); color: #fff;
  padding: 10px 20px; border-radius: 10px;
  font-size: 13px; font-weight: 600; text-decoration: none;
  transition: background 0.2s;
}
.ag-btn:hover { background: #2563eb; }

.admin-stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}
.astat-card {
  background: var(--white);
  border-radius: 14px;
  padding: 22px 20px;
  display: flex; flex-direction: column; gap: 14px;
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}
.astat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
.astat-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
}
.astat-blue::before  { background: linear-gradient(90deg, #3b82f6, #6366f1); }
.astat-green::before { background: linear-gradient(90deg, #22c55e, #10b981); }
.astat-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.astat-purple::before{ background: linear-gradient(90deg, #8b5cf6, #7c3aed); }
.astat-sky::before   { background: linear-gradient(90deg, #06b6d4, #0ea5e9); }

.astat-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; color: #fff;
}
.astat-blue .astat-icon  { background: linear-gradient(135deg, #3b82f6, #6366f1); }
.astat-green .astat-icon { background: linear-gradient(135deg, #22c55e, #10b981); }
.astat-amber .astat-icon { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.astat-purple .astat-icon{ background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.astat-sky .astat-icon   { background: linear-gradient(135deg, #06b6d4, #0ea5e9); }

.astat-num { font-size: 1.6rem; font-weight: 800; color: var(--navy); }
.astat-label { font-size: 11.5px; color: var(--muted); font-weight: 500; }
.astat-link {
  font-size: 11.5px; color: var(--accent); font-weight: 600;
  text-decoration: none; display: flex; align-items: center; gap: 4px;
  margin-top: auto;
}
.astat-link:hover { color: #2563eb; }

.admin-section {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 24px;
  margin-bottom: 24px;
}
.as-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px;
}
.as-header h3 {
  font-size: 15px; font-weight: 700; color: var(--navy);
  display: flex; align-items: center; gap: 8px; margin: 0;
}
.as-more {
  font-size: 12px; color: var(--accent); font-weight: 600;
  text-decoration: none; display: flex; align-items: center; gap: 4px;
}

/* Admin Wilayah Grid */
.admin-wil-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 12px;
}
.awil-card {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 16px;
  border-radius: 10px;
  border: 1px solid var(--border);
  text-decoration: none; color: inherit;
  transition: all 0.2s;
}
.awil-card:hover { background: #f8fafc; border-color: var(--accent); }
.awil-marker {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 16px; flex-shrink: 0;
}
.awil-name { font-size: 13px; font-weight: 700; color: var(--navy); }
.awil-meta { font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 4px; margin-top: 2px; }
.awil-arrow { color: #cbd5e1; font-size: 12px; margin-left: auto; }

/* Admin Berita List */
.admin-berita-list { display: flex; flex-direction: column; gap: 0; }
.ab-item {
  display: flex; gap: 14px; align-items: center;
  padding: 14px 0;
  border-bottom: 1px solid #f1f5f9;
  text-decoration: none; color: inherit;
  transition: background 0.2s;
}
.ab-item:last-child { border-bottom: none; }
.ab-item:hover { background: #f8fafc; margin: 0 -24px; padding: 14px 24px; border-radius: 8px; }
.ab-thumb {
  width: 56px; height: 56px; border-radius: 10px;
  overflow: hidden; flex-shrink: 0;
  background: #f1f5f9;
  display: flex; align-items: center; justify-content: center;
  color: #94a3b8; font-size: 20px;
}
.ab-thumb img { width: 100%; height: 100%; object-fit: cover; }
.ab-title { font-size: 13.5px; font-weight: 600; color: var(--navy); margin-bottom: 4px; }
.ab-meta {
  display: flex; gap: 10px; font-size: 11px; color: var(--muted); align-items: center;
}
.ab-badge {
  background: #eff6ff; color: var(--accent);
  padding: 2px 8px; border-radius: 6px;
  font-size: 10px; font-weight: 700;
}

@media (max-width: 600px) {
  .admin-stats { grid-template-columns: repeat(2, 1fr); }
  .admin-wil-grid { grid-template-columns: 1fr; }
  .admin-greeting { flex-direction: column; text-align: center; }
}

/* ═══ Identity Banner (pengajar) ═══ */
.identity-banner {
  background: linear-gradient(135deg, #1a2744, #2c5282);
  border-radius: 16px;
  padding: 32px 36px;
  margin-bottom: 32px;
  display: flex;
  align-items: center;
  gap: 28px;
  color: #fff;
}
.id-logo {
  width: 52px; height: 52px;
  flex-shrink: 0;
}
.id-logo img {
  width: 100%; height: 100%;
  object-fit: contain;
}
.id-text { flex: 1; }
.id-text .id-subtitle {
  font-size: 11px; font-weight: 600;
  letter-spacing: 1px; text-transform: uppercase;
  color: rgba(255,255,255,0.6);
  margin-bottom: 6px;
  display: flex; align-items: center; gap: 6px;
}
.id-text h2 {
  font-size: 1.25rem; font-weight: 700;
  line-height: 1.4; margin: 0 0 4px;
}
.id-text .id-location {
  font-size: 12.5px; color: rgba(255,255,255,0.5);
  display: flex; align-items: center; gap: 5px;
}
.id-stats {
  display: flex; gap: 10px; flex-shrink: 0;
}
.id-stat-box {
  background: rgba(255,255,255,0.1);
  border-radius: 10px;
  padding: 14px 18px;
  text-align: center;
  min-width: 80px;
}
.id-stat-num { font-size: 1.5rem; font-weight: 800; }
.id-stat-label { font-size: 10px; color: rgba(255,255,255,0.55); margin-top: 2px; }
@media (max-width: 768px) {
  .identity-banner { flex-direction: column; text-align: center; padding: 24px 20px; gap: 16px; }
  .id-text .id-location { justify-content: center; }
  .id-stats { justify-content: center; }
}

/* ── Quick Nav ── */
.quick-nav {
  display: flex; gap: 10px; flex-wrap: wrap;
  margin-bottom: 32px;
}
.qnav-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 8px;
  font-size: 13px; font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer; border: none; text-decoration: none;
  transition: background 0.2s, box-shadow 0.2s;
}
.qnav-primary {
  background: var(--accent); color: #fff;
}
.qnav-primary:hover { background: #2563eb; }
.qnav-outline {
  background: var(--white); color: var(--blue);
  border: 1px solid var(--border);
}
.qnav-outline:hover { background: #f0f4ff; }
.qnav-danger {
  background: var(--white); color: #dc2626;
  border: 1px solid #fecaca;
}
.qnav-danger:hover { background: #fef2f2; }

/* ── Section Title ── */
.section-title {
  font-size: 16px; font-weight: 700; color: var(--navy);
  margin-bottom: 20px;
  display: flex; align-items: center; gap: 8px;
}

/* ── Peta Wilayah ── */
.map-container {
  background: var(--white);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  margin-bottom: 40px;
}
.map-header {
  padding: 18px 24px;
  background: linear-gradient(135deg, #1a2744, #2c5282);
  color: #fff;
  display: flex; align-items: center; justify-content: space-between;
}
.map-header h3 { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.map-header .map-legend {
  display: flex; gap: 14px; font-size: 11px; color: rgba(255,255,255,0.7);
}
.map-legend span { display: flex; align-items: center; gap: 5px; }
.map-legend .dot-leg {
  width: 10px; height: 10px; border-radius: 50%; display: inline-block;
}
#mapWilayah {
  width: 100%; height: 480px;
  z-index: 1;
}
.map-info-bar {
  padding: 14px 24px;
  background: #f8fafc;
  border-top: 1px solid var(--border);
  display: flex; gap: 12px; flex-wrap: wrap;
}
.map-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--white); border: 1px solid var(--border);
  border-radius: 8px; padding: 6px 14px;
  font-size: 11.5px; font-weight: 600; color: var(--navy);
  cursor: pointer; transition: all 0.2s;
  text-decoration: none;
}
.map-chip:hover { background: #eff6ff; border-color: var(--accent); color: var(--accent); }
.map-chip .chip-count {
  background: var(--accent); color: #fff;
  font-size: 10px; padding: 2px 7px; border-radius: 10px;
}
@media (max-width: 768px) {
  #mapWilayah { height: 350px; }
  .map-header { flex-direction: column; gap: 8px; }
  .map-info-bar { justify-content: center; }
}

/* ── Info Banner ── */
.info-banner {
  background: var(--white);
  border: 1px solid var(--border);
  border-left: 4px solid var(--accent);
  border-radius: var(--radius);
  padding: 20px 24px;
  display: flex; align-items: flex-start; gap: 14px;
}
.info-banner-icon { font-size: 20px; color: var(--accent); flex-shrink: 0; margin-top: 2px; }
.info-banner h4 { font-size: 14px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
.info-banner p  { font-size: 12.5px; color: var(--muted); line-height: 1.6; }

/* ── Toast ── */
.toast {
  position: fixed; bottom: 24px; right: 24px; z-index: 9999;
  background: #16a34a; color: #fff;
  padding: 12px 20px; border-radius: 10px;
  font-size: 13px; font-weight: 600;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  display: flex; align-items: center; gap: 8px;
  animation: toastIn 0.3s ease both;
}
.toast.hide { animation: toastOut 0.25s ease forwards; }
@keyframes toastIn  { from { opacity:0; transform: translateY(12px); } to { opacity:1; transform: none; } }
@keyframes toastOut { to   { opacity:0; transform: translateY(12px); } }

/* ── Modal ── */
.modal-overlay {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(0,0,0,0.4);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
  opacity: 0; pointer-events: none;
  transition: opacity 0.2s;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal {
  background: var(--white);
  border-radius: 16px;
  padding: 32px 28px;
  width: 100%; max-width: 440px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  transform: translateY(16px);
  transition: transform 0.25s ease;
}
.modal-overlay.open .modal { transform: none; }
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px;
}
.modal-title {
  font-size: 16px; font-weight: 700; color: var(--navy);
  display: flex; align-items: center; gap: 8px;
}
.modal-close {
  background: #f1f5f9; border: none; width: 32px; height: 32px;
  border-radius: 8px; cursor: pointer; font-size: 16px; color: var(--muted);
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s;
}
.modal-close:hover { background: #fee2e2; color: #dc2626; }
.modal-body label {
  display: block; font-size: 12px; font-weight: 600;
  color: #64748b; margin-bottom: 5px;
}
.modal-body input[type=text],
.modal-body textarea,
.modal-body select {
  width: 100%; padding: 10px 12px;
  border: 1px solid var(--border); border-radius: 8px;
  font-size: 13px; font-family: 'Poppins', sans-serif; color: var(--text);
  outline: none; transition: border-color 0.2s;
  margin-bottom: 16px; background: #fafbfc;
}
.modal-body input[type=text]:focus,
.modal-body textarea:focus {
  border-color: var(--accent);
  background: #fff;
}
.modal-body textarea { resize: vertical; min-height: 90px; }
.modal-footer { display: flex; gap: 8px; margin-top: 4px; }
.btn-modal {
  flex: 1; padding: 10px;
  border: none; border-radius: 8px;
  font-size: 13px; font-weight: 700; font-family: 'Poppins', sans-serif;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background 0.2s;
}
.btn-save { background: var(--accent); color: #fff; }
.btn-save:hover { background: #2563eb; }
.btn-cancel { background: #f1f5f9; color: var(--muted); }
.btn-cancel:hover { background: #e2e8f0; }

/* ── Loading Bar ── */
.loading-bar {
  position: fixed; top: 0; left: 0; z-index: 9999;
  width: 0; height: 3px;
  background: var(--accent);
  transition: width 0.3s ease;
}
.loading-bar.active { width: 70%; transition: width 1.5s ease; }
.loading-bar.done { width: 100%; transition: width 0.2s ease; }

/* ── Berita Slider ── */
.slider-wrap {
  position: relative;
  border-radius: 16px;
  overflow: hidden;
  background: var(--white);
  border: 1px solid var(--border);
  margin-bottom: 40px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.slider { position: relative; min-height: 340px; }
.slide {
  position: absolute; inset: 0;
  display: flex;
  opacity: 0;
  transform: translateX(60px);
  transition: opacity 0.5s ease, transform 0.5s ease;
  pointer-events: none;
}
.slide.active {
  position: relative;
  opacity: 1;
  transform: translateX(0);
  pointer-events: all;
}
.slide.exit-left {
  opacity: 0;
  transform: translateX(-60px);
}
.slide.exit-right {
  opacity: 0;
  transform: translateX(60px);
}
.slide-img {
  width: 45%;
  min-height: 340px;
  flex-shrink: 0;
  overflow: hidden;
  position: relative;
  background: #f1f5f9;
}
.slide-img img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}
.slide.active .slide-img img {
  animation: slideImgZoom 8s ease-out forwards;
}
@keyframes slideImgZoom {
  from { transform: scale(1); }
  to   { transform: scale(1.08); }
}
.slide-placeholder {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  font-size: 60px; color: #cbd5e1;
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
}
.slide-body {
  flex: 1;
  padding: 36px 32px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.slide-meta {
  display: flex; gap: 12px; align-items: center;
  margin-bottom: 14px; flex-wrap: wrap;
}
.slide-badge {
  padding: 4px 12px; border-radius: 12px;
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
}
.badge-info { background: #eff6ff; color: var(--accent); }
.badge-warn { background: #fffbeb; color: #d97706; }
.badge-green { background: #ecfdf5; color: #16a34a; }
.badge-gray { background: #f1f5f9; color: var(--muted); }
.slide-date, .slide-author {
  font-size: 11px; color: var(--muted);
  display: flex; align-items: center; gap: 4px;
}
.slide-title {
  font-size: 20px; font-weight: 700; color: var(--navy);
  line-height: 1.4; margin-bottom: 12px;
}
.slide-excerpt {
  font-size: 13px; color: #64748b; line-height: 1.7;
  margin-bottom: 20px;
}
.slide-read {
  display: inline-flex; align-items: center; gap: 6px;
  color: var(--accent); font-size: 13px; font-weight: 600;
  text-decoration: none; transition: gap 0.2s;
}
.slide-read:hover { gap: 10px; }

/* Slider Buttons */
.slider-btn {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 42px; height: 42px;
  border-radius: 50%; border: none;
  background: rgba(255,255,255,0.9);
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
  color: var(--navy); font-size: 16px;
  cursor: pointer; z-index: 10;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
  backdrop-filter: blur(8px);
}
.slider-btn:hover {
  background: var(--accent); color: #fff;
  transform: translateY(-50%) scale(1.1);
  box-shadow: 0 4px 16px rgba(59,130,246,0.3);
}
.slider-prev { left: 16px; }
.slider-next { right: 16px; }

/* Dots */
.slider-dots {
  display: flex; gap: 8px;
  justify-content: center;
  padding: 16px 0 20px;
  position: absolute; bottom: 0; left: 0; right: 0;
}
.dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: #cbd5e1;
  cursor: pointer;
  transition: all 0.3s;
}
.dot.active {
  background: var(--accent);
  width: 28px;
  border-radius: 6px;
}

/* Counter */
.slider-counter {
  position: absolute; top: 16px; right: 16px;
  background: rgba(0,0,0,0.5);
  color: #fff; padding: 4px 12px;
  border-radius: 16px; font-size: 11px; font-weight: 600;
  z-index: 10;
  backdrop-filter: blur(4px);
}

@media (max-width: 768px) {
  .slide { flex-direction: column; }
  .slide-img { width: 100%; min-height: 200px; height: 200px; }
  .slide-body { padding: 24px 20px; }
  .slide-title { font-size: 16px; }
  .slider { min-height: auto; }
  .slider-btn { width: 36px; height: 36px; font-size: 14px; }
  .slider-prev { left: 8px; }
  .slider-next { right: 8px; }
}
</style>
</head>
<body>

<div id="mainContent" class="main-content">
<!-- Loading Bar -->
<div class="loading-bar" id="loadingBar"></div>

<!-- Content -->
<div class="main">
<?= breadcrumb([['label' => 'Dashboard']]) ?>

<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
<!-- ═══════════ ADMIN DASHBOARD ═══════════ -->

  <!-- Greeting -->
  <div class="admin-greeting">
    <div>
      <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?> 👋</h2>
      <p>Ringkasan data sistem MitigaPro &mdash; <?= date('l, d F Y') ?></p>
    </div>
    <a href="<?= BASE_URL ?>pengajar/tambah_pelatihan_baru.php" class="ag-btn"><i class="fas fa-plus"></i> Tambah Pelatihan</a>
  </div>

  <!-- Stats Cards -->
  <div class="admin-stats">
    <div class="astat-card astat-blue">
      <div class="astat-icon"><i class="fas fa-user-tie"></i></div>
      <div class="astat-info">
        <div class="astat-num"><?= $total_pengajar ?></div>
        <div class="astat-label">Total Pengajar</div>
      </div>
      <a href="<?= BASE_URL ?>pengajar/pengajar.php" class="astat-link">Lihat <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="astat-card astat-green">
      <div class="astat-icon"><i class="fas fa-building"></i></div>
      <div class="astat-info">
        <div class="astat-num"><?= $total_dinas ?></div>
        <div class="astat-label">Total Dinas</div>
      </div>
      <a href="<?= BASE_URL ?>pengajar/dinas.php" class="astat-link">Lihat <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="astat-card astat-amber">
      <div class="astat-icon"><i class="fas fa-graduation-cap"></i></div>
      <div class="astat-info">
        <div class="astat-num"><?= $total_pelatihan ?></div>
        <div class="astat-label">Identifikasi Pelatihan</div>
      </div>
      <a href="<?= BASE_URL ?>pengajar/daftar_pelatihan.php" class="astat-link">Lihat <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="astat-card astat-purple">
      <div class="astat-icon"><i class="fas fa-newspaper"></i></div>
      <div class="astat-info">
        <div class="astat-num"><?= $total_berita ?></div>
        <div class="astat-label">Berita Pelatihan</div>
      </div>
      <a href="<?= BASE_URL ?>pengajar/kelola_berita.php" class="astat-link">Lihat <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="astat-card astat-sky">
      <div class="astat-icon"><i class="fas fa-users"></i></div>
      <div class="astat-info">
        <div class="astat-num"><?= $total_users ?></div>
        <div class="astat-label">Total Pengguna</div>
      </div>
      <a href="<?= BASE_URL ?>pengajar/settings.php" class="astat-link">Kelola <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>

  <!-- Wilayah Overview -->
  <div class="admin-section">
    <div class="as-header">
      <h3><i class="fas fa-map-marked-alt"></i> Wilayah Kerja</h3>
    </div>
    <div class="admin-wil-grid">
      <?php
      $wil_colors = ['#ef4444','#f59e0b','#22c55e','#3b82f6','#8b5cf6','#ec4899','#06b6d4'];
      foreach ($wilayah_list as $i => $w):
        $clr = $wil_colors[$i] ?? '#64748b';
      ?>
      <a href="wilayah.php?id=<?= (int)$w['id'] ?>" class="awil-card">
        <div class="awil-marker" style="background:<?= $clr ?>"><i class="fas fa-location-dot"></i></div>
        <div class="awil-body">
          <div class="awil-name"><?= htmlspecialchars(str_replace('Wilayah Kerja ', '', $w['nama_wilayah'])) ?></div>
          <div class="awil-meta"><i class="fas fa-building"></i> <?= (int)$w['jml_dinas'] ?> dinas</div>
        </div>
        <i class="fas fa-chevron-right awil-arrow"></i>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Berita Terbaru (compact list) -->
  <div class="admin-section">
    <div class="as-header">
      <h3><i class="fas fa-newspaper"></i> Berita Terbaru</h3>
      <a href="<?= BASE_URL ?>pengajar/kelola_berita.php" class="as-more">Kelola Berita <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php if (count($berita_list) > 0): ?>
    <div class="admin-berita-list">
      <?php foreach (array_slice($berita_list, 0, 5) as $b): ?>
      <a href="detail_berita.php?id=<?= (int)$b['id'] ?>" class="ab-item">
        <div class="ab-thumb">
          <?php if ($b['gambar']): ?>
            <img src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($b['gambar']) ?>" alt="">
          <?php else: ?>
            <i class="fas fa-newspaper"></i>
          <?php endif; ?>
        </div>
        <div class="ab-body">
          <div class="ab-title"><?= htmlspecialchars($b['judul']) ?></div>
          <div class="ab-meta">
            <span class="ab-badge"><?= htmlspecialchars($b['kategori'] ?: 'Umum') ?></span>
            <span><i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($b['created_at'])) ?></span>
            <?php if ($b['username']): ?>
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($b['username']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty"><i class="fas fa-newspaper"></i><p>Belum ada berita.</p></div>
    <?php endif; ?>
  </div>

<?php else: ?>
<!-- ═══════════ PENGAJAR DASHBOARD ═══════════ -->

  <!-- Identity Banner -->
  <div class="identity-banner">
    <div class="id-logo">
      <img src="<?= BASE_URL ?>logo.png" alt="Logo">
    </div>
    <div class="id-text">
      <div class="id-subtitle"><i class="fas fa-landmark"></i> Kementerian PU</div>
      <h2>Balai Pengembangan Kompetensi PU<br>Wilayah VIII Makassar</h2>
      <div class="id-location">
        <i class="fas fa-map-marker-alt"></i> Sulawesi &bull; Gorontalo &bull; Maluku Utara
      </div>
    </div>
    <div class="id-stats">
      <div class="id-stat-box">
        <div class="id-stat-num">7</div>
        <div class="id-stat-label">Wilayah Kerja</div>
      </div>
      <div class="id-stat-box">
        <div class="id-stat-num">VIII</div>
        <div class="id-stat-label">Balai Wilayah</div>
      </div>
    </div>
  </div>

  <!-- Quick Nav -->
  <?php if (($_SESSION['role'] ?? '') !== 'tamu'): ?>
  <div class="quick-nav">
    <a href="<?= BASE_URL ?>pengajar/pengajar.php" class="qnav-btn qnav-primary">
      <i class="fas fa-chalkboard-teacher"></i> Data Pengajar
    </a>
    <a href="<?= BASE_URL ?>pengajar/dashboard.php" class="qnav-btn qnav-outline">
      <i class="fas fa-home"></i> Beranda
    </a>
    <button onclick="history.back()" class="qnav-btn qnav-outline">
      <i class="fas fa-arrow-left"></i> Kembali
    </button>
    <a href="<?= BASE_URL ?>logout.php" class="qnav-btn qnav-danger" id="btnKeluar">
      <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
  </div>
  <?php endif; ?>

  <!-- Berita Slider -->
  <div class="section-title"><i class="fas fa-newspaper"></i> Berita & Informasi Terbaru</div>

  <?php if (count($berita_list) > 0): ?>
  <div class="slider-wrap">
    <div class="slider" id="beritaSlider">
      <?php foreach ($berita_list as $i => $b): ?>
      <div class="slide <?= $i === 0 ? 'active' : '' ?>">
        <div class="slide-img">
          <?php if ($b['gambar']): ?>
            <img src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($b['gambar']) ?>" alt="">
          <?php else: ?>
            <div class="slide-placeholder"><i class="fas fa-newspaper"></i></div>
          <?php endif; ?>
        </div>
        <div class="slide-body">
          <div class="slide-meta">
            <?php
              $badge_class = match($b['kategori'] ?? '') {
                  'Informasi'  => 'badge-info',
                  'Pengumuman' => 'badge-warn',
                  'Jadwal'     => 'badge-green',
                  default      => 'badge-gray',
              };
            ?>
            <span class="slide-badge <?= $badge_class ?>"><?= htmlspecialchars($b['kategori'] ?: 'Umum') ?></span>
            <span class="slide-date"><i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($b['created_at'])) ?></span>
            <?php if ($b['username']): ?>
            <span class="slide-author"><i class="fas fa-user"></i> <?= htmlspecialchars($b['username']) ?></span>
            <?php endif; ?>
          </div>
          <h3 class="slide-title"><?= htmlspecialchars($b['judul']) ?></h3>
          <p class="slide-excerpt"><?= htmlspecialchars(mb_strimwidth(strip_tags($b['isi']), 0, 200, '...')) ?></p>
          <a href="detail_berita.php?id=<?= (int)$b['id'] ?>" class="slide-read">Baca Selengkapnya <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Controls -->
    <button class="slider-btn slider-prev" id="sliderPrev"><i class="fas fa-chevron-left"></i></button>
    <button class="slider-btn slider-next" id="sliderNext"><i class="fas fa-chevron-right"></i></button>

    <!-- Dots -->
    <div class="slider-dots" id="sliderDots">
      <?php foreach ($berita_list as $i => $b): ?>
      <span class="dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
      <?php endforeach; ?>
    </div>

    <!-- Counter -->
    <div class="slider-counter">
      <span id="sliderCurrent">1</span> / <?= count($berita_list) ?>
    </div>
  </div>
  <?php else: ?>
  <div class="empty">
    <i class="fas fa-newspaper"></i>
    <p>Belum ada berita pelatihan.</p>
  </div>
  <?php endif; ?>

  <?php if (($_SESSION['role'] ?? '') === 'pengajar'): ?>
  <!-- Peta Wilayah Kerja -->
  <div id="wilayah" style="margin-top:40px">
    <div class="map-container">
      <div class="map-header">
        <h3><i class="fas fa-map-marked-alt"></i> Peta Wilayah Kerja</h3>
        <div class="map-legend">
          <span><span class="dot-leg" style="background:#ef4444"></span> Sulawesi Selatan</span>
          <span><span class="dot-leg" style="background:#f59e0b"></span> Sulawesi Barat</span>
          <span><span class="dot-leg" style="background:#22c55e"></span> Sulawesi Tengah</span>
          <span><span class="dot-leg" style="background:#3b82f6"></span> Sulawesi Utara</span>
          <span><span class="dot-leg" style="background:#8b5cf6"></span> Sulawesi Tenggara</span>
          <span><span class="dot-leg" style="background:#ec4899"></span> Gorontalo</span>
          <span><span class="dot-leg" style="background:#06b6d4"></span> Maluku Utara</span>
        </div>
      </div>
      <div id="mapWilayah"></div>
      <div class="map-info-bar">
        <?php foreach ($wilayah_list as $w): ?>
        <a href="wilayah.php?id=<?= (int)$w['id'] ?>" class="map-chip">
          <i class="fas fa-location-dot" style="color:var(--accent)"></i>
          <?= htmlspecialchars(str_replace('Wilayah Kerja ', '', $w['nama_wilayah'])) ?>
          <span class="chip-count"><?= (int)$w['jml_dinas'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

<?php endif; /* admin vs pengajar */ ?>

</div>

<!-- ═══ MODAL CATATAN ═══ -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">
        <i class="fas fa-pen-to-square" style="color:var(--blue)"></i>
        <span id="modalWilayahLabel">Tambah Catatan</span>
      </div>
      <button class="modal-close" onclick="closeModal()" title="Tutup">&times;</button>
    </div>
    <form method="POST" action="" id="catatanForm">
      <?= csrf_field() ?>
      <input type="hidden" name="catatan_wilayah_id" id="catatanWilayahId">
      <div class="modal-body">
        <label for="catatanText"><i class="fas fa-file-alt"></i> Catatan untuk wilayah ini</label>
        <textarea name="catatan" id="catatanText" placeholder="Tuliskan catatan, temuan lapangan, atau hal penting lainnya..." required></textarea>

        <label><i class="fas fa-user"></i> Dicatat oleh</label>
        <input type="text" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" disabled style="opacity:0.6; cursor:default;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal btn-cancel" onclick="closeModal()">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn-modal btn-save">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Toast -->
<?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i> Catatan berhasil disimpan!
</div>
<?php endif; ?>

<script>
const loadBar = document.getElementById('loadingBar');

/* ── Page navigation with loading bar ── */
function leavePage(e, href) {
  e.preventDefault();
  loadBar.classList.add('active');
  setTimeout(() => {
    loadBar.classList.add('done');
    setTimeout(() => { window.location.href = href; }, 200);
  }, 400);
}

document.querySelectorAll('a.qnav-btn:not(.qnav-danger)').forEach(a => {
  a.addEventListener('click', function(e) { leavePage(e, this.href); });
});

/* ── Modal ── */
const overlay = document.getElementById('modalOverlay');

function openModal(id, nama) {
  document.getElementById('catatanWilayahId').value = id;
  document.getElementById('modalWilayahLabel').textContent = 'Catatan — Wilayah ' + nama;
  document.getElementById('catatanText').value = '';
  overlay.classList.add('open');
}

function closeModal() {
  overlay.classList.remove('open');
}

overlay.addEventListener('click', function(e) {
  if (e.target === overlay) closeModal();
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});

/* ── Toast auto-hide ── */
const toast = document.getElementById('toast');
if (toast) {
  setTimeout(() => {
    toast.classList.add('hide');
    setTimeout(() => toast.remove(), 350);
  }, 3500);
}

/* ── Konfirmasi logout ── */
var btnKeluar = document.getElementById('btnKeluar');
if (btnKeluar) btnKeluar.addEventListener('click', function(e) {
  e.preventDefault();
  if (confirm('Yakin ingin keluar dari sistem?')) {
    loadBar.classList.add('active');
    setTimeout(() => {
      loadBar.classList.add('done');
      setTimeout(() => { window.location.href = this.href; }, 200);
    }, 400);
  }
});

/* ── Berita Slider ── */
(function() {
  const slides = document.querySelectorAll('.slide');
  const dots   = document.querySelectorAll('.dot');
  const counter = document.getElementById('sliderCurrent');
  const prevBtn = document.getElementById('sliderPrev');
  const nextBtn = document.getElementById('sliderNext');

  if (!slides.length) return;

  let current = 0;
  let autoTimer = null;
  let isAnimating = false;

  function goTo(index, direction) {
    if (isAnimating || index === current) return;
    isAnimating = true;

    const oldSlide = slides[current];
    const newSlide = slides[index];

    // Exit direction
    oldSlide.classList.remove('active');
    oldSlide.classList.add(direction === 'next' ? 'exit-left' : 'exit-right');

    // Enter
    newSlide.style.transform = direction === 'next' ? 'translateX(60px)' : 'translateX(-60px)';
    newSlide.classList.add('active');

    // Update dots
    dots.forEach(d => d.classList.remove('active'));
    if (dots[index]) dots[index].classList.add('active');

    // Update counter
    if (counter) counter.textContent = index + 1;

    current = index;

    setTimeout(() => {
      oldSlide.classList.remove('exit-left', 'exit-right');
      isAnimating = false;
    }, 550);
  }

  function next() {
    goTo((current + 1) % slides.length, 'next');
  }

  function prev() {
    goTo((current - 1 + slides.length) % slides.length, 'prev');
  }

  // Auto-play
  function startAuto() {
    stopAuto();
    autoTimer = setInterval(next, 5000);
  }

  function stopAuto() {
    if (autoTimer) clearInterval(autoTimer);
  }

  if (prevBtn) prevBtn.addEventListener('click', () => { stopAuto(); prev(); startAuto(); });
  if (nextBtn) nextBtn.addEventListener('click', () => { stopAuto(); next(); startAuto(); });

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const idx = parseInt(dot.dataset.index, 10);
      stopAuto();
      goTo(idx, idx > current ? 'next' : 'prev');
      startAuto();
    });
  });

  // Swipe support
  const slider = document.getElementById('beritaSlider');
  if (slider) {
    let startX = 0;
    slider.addEventListener('touchstart', e => { startX = e.touches[0].clientX; stopAuto(); }, {passive:true});
    slider.addEventListener('touchend', e => {
      const diff = startX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        diff > 0 ? next() : prev();
      }
      startAuto();
    }, {passive:true});
  }

  // Pause on hover
  const wrap = document.querySelector('.slider-wrap');
  if (wrap) {
    wrap.addEventListener('mouseenter', stopAuto);
    wrap.addEventListener('mouseleave', startAuto);
  }

  // Keyboard
  document.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft') { stopAuto(); prev(); startAuto(); }
    if (e.key === 'ArrowRight') { stopAuto(); next(); startAuto(); }
  });

  startAuto();
})();

/* ── Leaflet Map Wilayah ── */
(function() {
  const mapEl = document.getElementById('mapWilayah');
  if (!mapEl) return;

  const map = L.map('mapWilayah', {
    center: [-1.5, 123.5],
    zoom: 6,
    zoomControl: true,
    scrollWheelZoom: true
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 18
  }).addTo(map);

  // Data wilayah: id, nama, koordinat, warna, jumlah dinas
  const wilayahData = [
    { id: 1, nama: 'Sulawesi Selatan',   lat: -3.67,  lng: 119.97, color: '#ef4444', dinas: <?= (int)($wilayah_list[0]['jml_dinas'] ?? 0) ?> },
    { id: 2, nama: 'Sulawesi Barat',     lat: -2.84,  lng: 119.23, color: '#f59e0b', dinas: <?= (int)($wilayah_list[1]['jml_dinas'] ?? 0) ?> },
    { id: 3, nama: 'Sulawesi Tengah',    lat: -1.43,  lng: 121.45, color: '#22c55e', dinas: <?= (int)($wilayah_list[2]['jml_dinas'] ?? 0) ?> },
    { id: 4, nama: 'Sulawesi Utara',     lat:  1.49,  lng: 124.84, color: '#3b82f6', dinas: <?= (int)($wilayah_list[3]['jml_dinas'] ?? 0) ?> },
    { id: 5, nama: 'Sulawesi Tenggara',  lat: -3.97,  lng: 122.51, color: '#8b5cf6', dinas: <?= (int)($wilayah_list[4]['jml_dinas'] ?? 0) ?> },
    { id: 6, nama: 'Gorontalo',          lat:  0.54,  lng: 123.06, color: '#ec4899', dinas: <?= (int)($wilayah_list[5]['jml_dinas'] ?? 0) ?> },
    { id: 7, nama: 'Maluku Utara',       lat:  1.57,  lng: 127.81, color: '#06b6d4', dinas: <?= (int)($wilayah_list[6]['jml_dinas'] ?? 0) ?> }
  ];

  wilayahData.forEach(w => {
    const icon = L.divIcon({
      className: '',
      html: '<div style="background:' + w.color + ';width:32px;height:32px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700">' + w.id + '</div>',
      iconSize: [32, 32],
      iconAnchor: [16, 16]
    });

    const marker = L.marker([w.lat, w.lng], { icon: icon }).addTo(map);

    marker.bindPopup(
      '<div style="font-family:Poppins,sans-serif;min-width:180px">' +
        '<div style="font-size:14px;font-weight:700;color:#1a2744;margin-bottom:6px">' +
          '<i class="fas fa-location-dot" style="color:' + w.color + '"></i> ' + w.nama +
        '</div>' +
        '<div style="font-size:12px;color:#64748b;margin-bottom:10px">' +
          '<i class="fas fa-building"></i> ' + w.dinas + ' dinas terdaftar' +
        '</div>' +
        '<a href="wilayah.php?id=' + w.id + '" style="display:inline-flex;align-items:center;gap:5px;background:#3b82f6;color:#fff;padding:6px 14px;border-radius:8px;font-size:11px;font-weight:600;text-decoration:none">' +
          '<i class="fas fa-eye"></i> Lihat Detail' +
        '</a>' +
      '</div>',
      { closeButton: true, maxWidth: 250 }
    );

    marker.bindTooltip(w.nama, {
      permanent: true,
      direction: 'bottom',
      offset: [0, 14],
      className: 'wil-tooltip'
    });
  });

  // Style tooltip
  const tooltipStyle = document.createElement('style');
  tooltipStyle.textContent = '.wil-tooltip{background:#1a2744;color:#fff;border:none;border-radius:6px;padding:3px 10px;font-size:10px;font-weight:600;font-family:Poppins,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,0.2)}.wil-tooltip::before{border-bottom-color:#1a2744!important}';
  document.head.appendChild(tooltipStyle);

  // Fix map size setelah container terlihat
  setTimeout(() => map.invalidateSize(), 300);

  // Jika URL ada #wilayah, scroll ke sana
  if (window.location.hash === '#wilayah') {
    setTimeout(() => {
      document.getElementById('wilayah')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      setTimeout(() => map.invalidateSize(), 500);
    }, 400);
  }
})();
</script>
</div><!-- /main-content -->
</body>
</html>
