<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

require INCLUDE_PATH . 'sidebar_mitigapro.php';

// ── Statistik utama ────────────────────────────────────────
$stat_users    = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0;
$stat_pengajar = $conn->query("SELECT COUNT(*) c FROM pengajar")->fetch_assoc()['c'] ?? 0;
$stat_aktif    = $conn->query("SELECT COUNT(*) c FROM pengajar WHERE status='aktif'")->fetch_assoc()['c'] ?? 0;
$stat_nonaktif = $conn->query("SELECT COUNT(*) c FROM pengajar WHERE status='nonaktif'")->fetch_assoc()['c'] ?? 0;
$stat_wilayah  = $conn->query("SELECT COUNT(*) c FROM wilayah")->fetch_assoc()['c'] ?? 0;
$stat_dinas    = $conn->query("SELECT COUNT(*) c FROM dinas")->fetch_assoc()['c'] ?? 0;
$stat_pelatihan= $conn->query("SELECT COUNT(*) c FROM identifikasi_pelatihan")->fetch_assoc()['c'] ?? 0;

// ── Distribusi role ────────────────────────────────────────
$role_data = [];
$role_q = $conn->query("SELECT role, COUNT(*) c FROM users GROUP BY role");
while ($r = $role_q->fetch_assoc()) $role_data[$r['role']] = (int)$r['c'];

// ── Dinas per wilayah ──────────────────────────────────────
$wil_labels = []; $wil_counts = [];
$wq = $conn->query("SELECT w.nama_wilayah, COUNT(d.id) cnt FROM wilayah w LEFT JOIN dinas d ON d.wilayah_id=w.id GROUP BY w.id ORDER BY w.id");
while ($wr = $wq->fetch_assoc()) {
    $short = str_replace('Wilayah Kerja ', '', $wr['nama_wilayah']);
    $wil_labels[] = $short;
    $wil_counts[] = (int)$wr['cnt'];
}

// ── User terbaru ───────────────────────────────────────────
$recent_users = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// ── Pengajar terbaru ───────────────────────────────────────
$recent_pengajar = $conn->query("SELECT nip, nama_pengajar, jabatan, unit_kerja, status, created_at FROM pengajar ORDER BY created_at DESC LIMIT 5");

// ── Aktivitas terbaru (gabungan) ───────────────────────────
$activities = [];
$aq1 = $conn->query("SELECT 'user' as tipe, username as judul, CONCAT('Role: ', role) as detail, created_at FROM users ORDER BY created_at DESC LIMIT 3");
while ($a = $aq1->fetch_assoc()) $activities[] = $a;
$aq2 = $conn->query("SELECT 'pengajar' as tipe, nama_pengajar as judul, jabatan as detail, created_at FROM pengajar ORDER BY created_at DESC LIMIT 3");
while ($a = $aq2->fetch_assoc()) $activities[] = $a;
$aq3 = $conn->query("SELECT 'pelatihan' as tipe, jenis_pelatihan as judul, CONCAT('Tahun ', tahun) as detail, created_at FROM identifikasi_pelatihan ORDER BY created_at DESC LIMIT 3");
while ($a = $aq3->fetch_assoc()) $activities[] = $a;
usort($activities, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$activities = array_slice($activities, 0, 6);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin | MitigaPro</title>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_mitigapro.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy: #1a2744;
  --blue: #2c5282;
  --accent: #3b82f6;
  --green: #22c55e;
  --orange: #f59e0b;
  --red: #ef4444;
  --purple: #8b5cf6;
  --cyan: #06b6d4;
  --bg: #f5f7fb;
  --white: #ffffff;
  --border: #e2e8f0;
  --muted: #64748b;
  --radius: 12px;
}

body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  min-height: 100vh;
  display: flex;
  color: var(--navy);
}

/* ═══ MAIN ═══ */
.main-content {
  margin-left: 260px;
  flex: 1;
  padding: 28px 32px 50px;
  transition: margin-left 0.3s ease;
  min-height: 100vh;
}
.main-content.expanded { margin-left: 72px; }
@media (max-width: 768px) { .main-content { margin-left: 0; padding: 16px; } }

/* ═══ WELCOME BANNER ═══ */
.welcome-banner {
  background: linear-gradient(135deg, var(--navy) 0%, var(--blue) 60%, var(--accent) 100%);
  border-radius: var(--radius);
  padding: 28px 32px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}
.welcome-banner::after {
  content: '';
  position: absolute;
  right: -40px; top: -40px;
  width: 200px; height: 200px;
  background: rgba(255,255,255,0.04);
  border-radius: 50%;
}
.wb-left { display: flex; align-items: center; gap: 20px; position: relative; z-index: 1; }
.wb-logo { width: 48px; height: 48px; object-fit: contain; flex-shrink: 0; }
.wb-text h1 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
.wb-text p { font-size: 13px; opacity: 0.8; font-weight: 400; }
.wb-right { display: flex; gap: 10px; position: relative; z-index: 1; }
.wb-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 8px;
  font-size: 12px; font-weight: 600; font-family: 'Poppins', sans-serif;
  cursor: pointer; text-decoration: none; border: none;
  transition: opacity 0.2s;
}
.wb-btn:hover { opacity: 0.85; }
.wb-btn-primary { background: rgba(255,255,255,0.2); color: #fff; }
.wb-btn-secondary { background: var(--white); color: var(--blue); }

/* ═══ STAT CARDS ═══ */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.stat-card {
  background: var(--white);
  border-radius: var(--radius);
  padding: 20px;
  display: flex; align-items: flex-start; gap: 14px;
  border: 1px solid var(--border);
  transition: box-shadow 0.2s;
}
.stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }

.stat-icon {
  width: 42px; height: 42px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
}
.si-blue   { background: #eff6ff; color: var(--accent); }
.si-green  { background: #f0fdf4; color: var(--green); }
.si-orange { background: #fffbeb; color: var(--orange); }
.si-purple { background: #f5f3ff; color: var(--purple); }
.si-cyan   { background: #ecfeff; color: var(--cyan); }
.si-red    { background: #fef2f2; color: var(--red); }

.stat-info { flex: 1; min-width: 0; }
.stat-num { font-size: 24px; font-weight: 800; line-height: 1.1; }
.stat-label { font-size: 11px; color: var(--muted); font-weight: 500; margin-top: 2px; }
.stat-sub { font-size: 10px; color: var(--muted); margin-top: 4px; display: flex; align-items: center; gap: 4px; }
.stat-sub .up { color: var(--green); }
.stat-sub .down { color: var(--red); }

/* ═══ SECTION TITLE ═══ */
.section-title {
  font-size: 11px; font-weight: 700; color: var(--muted);
  letter-spacing: 0.6px; text-transform: uppercase;
  margin-bottom: 12px;
}

/* ═══ QUICK ACTIONS ═══ */
.qa-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 24px;
}
.qa-card {
  background: var(--white);
  border-radius: 10px;
  padding: 16px 14px;
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  text-decoration: none; color: var(--navy);
  border: 1px solid var(--border);
  transition: box-shadow 0.2s, transform 0.2s;
  cursor: pointer;
}
.qa-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.qa-icon {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 17px;
  background: color-mix(in srgb, var(--qc, var(--blue)) 10%, white);
  color: var(--qc, var(--blue));
}
.qa-label { font-size: 11.5px; font-weight: 600; text-align: center; }

/* ═══ GRID LAYOUTS ═══ */
.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}
.grid-3 {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}
@media (max-width: 1100px) {
  .grid-2, .grid-3 { grid-template-columns: 1fr; }
}

/* ═══ PANEL ═══ */
.panel {
  background: var(--white);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  overflow: hidden;
}
.panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
}
.panel-title {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; font-weight: 700; color: var(--navy);
}
.panel-title i { font-size: 13px; color: var(--accent); }
.panel-link {
  font-size: 11px; color: var(--accent); font-weight: 600;
  text-decoration: none; display: flex; align-items: center; gap: 4px;
  padding: 4px 10px; border-radius: 6px; background: #eff6ff;
  transition: background 0.2s;
}
.panel-link:hover { background: #dbeafe; }
.panel-body { padding: 16px 20px; }

/* ═══ TABLE ═══ */
.data-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.data-table th {
  padding: 8px 14px; text-align: left;
  color: var(--muted); font-size: 10px; font-weight: 700;
  letter-spacing: 0.3px; text-transform: uppercase;
  border-bottom: 1px solid var(--border);
}
.data-table td {
  padding: 10px 14px; border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }

.badge {
  display: inline-flex; padding: 2px 8px; border-radius: 100px;
  font-size: 10px; font-weight: 700;
}
.badge-admin    { background: #ede9fe; color: #7c3aed; }
.badge-pengajar { background: #dbeafe; color: #2563eb; }
.badge-user     { background: #dcfce7; color: #15803d; }
.badge-aktif    { background: #dcfce7; color: #15803d; }
.badge-nonaktif { background: #fee2e2; color: #dc2626; }

.td-avatar {
  width: 30px; height: 30px; border-radius: 50%;
  background: linear-gradient(135deg, var(--blue), var(--accent));
  display: inline-flex; align-items: center; justify-content: center;
  color: #fff; font-size: 11px; font-weight: 700; margin-right: 8px; flex-shrink: 0;
}
.td-name { display: flex; align-items: center; }
.td-sub  { font-size: 10px; color: var(--muted); }

.tbl-btn {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 10px; border-radius: 6px;
  font-size: 11px; font-weight: 600; font-family: 'Poppins', sans-serif;
  border: none; cursor: pointer; text-decoration: none;
  transition: opacity 0.2s;
}
.tbl-btn:hover { opacity: 0.8; }
.tbl-edit   { background: #eff6ff; color: var(--accent); }
.tbl-delete { background: #fef2f2; color: var(--red); }

/* ═══ ACTIVITY TIMELINE ═══ */
.timeline { list-style: none; }
.timeline li {
  display: flex; gap: 12px; padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}
.timeline li:last-child { border-bottom: none; }
.tl-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; flex-shrink: 0;
}
.tl-user     .tl-icon { background: #eff6ff; color: var(--accent); }
.tl-pengajar .tl-icon { background: #f0fdf4; color: var(--green); }
.tl-pelatihan .tl-icon { background: #fffbeb; color: var(--orange); }
.tl-text { flex: 1; min-width: 0; }
.tl-title { font-size: 12px; font-weight: 600; }
.tl-detail { font-size: 10px; color: var(--muted); }
.tl-time { font-size: 10px; color: var(--muted); white-space: nowrap; align-self: center; }

/* ═══ CHART ═══ */
.chart-wrap { max-width: 220px; margin: 0 auto; }
.chart-wrap-bar { max-width: 100%; }

/* ═══ PROGRESS BAR ═══ */
.progress-group { margin-bottom: 12px; }
.progress-group:last-child { margin-bottom: 0; }
.pg-header { display: flex; justify-content: space-between; margin-bottom: 4px; }
.pg-label { font-size: 11px; font-weight: 600; }
.pg-val { font-size: 11px; color: var(--muted); font-weight: 600; }
.progress-bar {
  width: 100%; height: 6px; border-radius: 3px;
  background: #f1f5f9; overflow: hidden;
}
.progress-fill {
  height: 100%; border-radius: 3px;
  transition: width 0.6s ease;
}

/* ═══ EMPTY STATE ═══ */
.empty-state {
  text-align: center; padding: 32px 16px; color: var(--muted);
}
.empty-state i { font-size: 28px; opacity: 0.4; display: block; margin-bottom: 8px; }
.empty-state p { font-size: 12px; }

/* ═══ SYSTEM INFO ═══ */
.sys-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.sys-item {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 12px; border-radius: 8px; background: #f8fafc;
}
.sys-item i { font-size: 13px; color: var(--accent); width: 16px; text-align: center; }
.sys-label { font-size: 10px; color: var(--muted); }
.sys-val { font-size: 12px; font-weight: 600; }
</style>
</head>
<body>

<div class="main-content" id="mainContent">

  <!-- ── Welcome Banner ── -->
  <div class="welcome-banner">
    <div class="wb-left">
      <img src="<?= BASE_URL ?>logo.png" alt="Logo" class="wb-logo">
      <div class="wb-text">
        <h1>Dashboard Admin</h1>
        <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong> &mdash; <?= date('l, d F Y') ?></p>
      </div>
    </div>
    <div class="wb-right">
      <a href="manage_users.php" class="wb-btn wb-btn-primary"><i class="fas fa-user-plus"></i> Tambah User</a>
      <a href="<?= BASE_URL ?>pengajar/pengajar_add.php" class="wb-btn wb-btn-secondary"><i class="fas fa-chalkboard-teacher"></i> Tambah Pengajar</a>
    </div>
  </div>

  <!-- ── Stat Cards ── -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon si-blue"><i class="fas fa-users"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= $stat_users ?></div>
        <div class="stat-label">Total User</div>
        <div class="stat-sub">Semua role</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-green"><i class="fas fa-chalkboard-teacher"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= $stat_pengajar ?></div>
        <div class="stat-label">Total Pengajar</div>
        <div class="stat-sub"><span class="up"><i class="fas fa-circle" style="font-size:6px"></i> <?= $stat_aktif ?> aktif</span></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-orange"><i class="fas fa-map-marked-alt"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= $stat_wilayah ?></div>
        <div class="stat-label">Wilayah Kerja</div>
        <div class="stat-sub">Indonesia Timur</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-cyan"><i class="fas fa-building"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= $stat_dinas ?></div>
        <div class="stat-label">Total Dinas</div>
        <div class="stat-sub"><?= $stat_wilayah ?> provinsi</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-purple"><i class="fas fa-graduation-cap"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= $stat_pelatihan ?></div>
        <div class="stat-label">Pelatihan</div>
        <div class="stat-sub">Identifikasi</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-red"><i class="fas fa-user-shield"></i></div>
      <div class="stat-info">
        <div class="stat-num"><?= $role_data['admin'] ?? 0 ?></div>
        <div class="stat-label">Admin Aktif</div>
        <div class="stat-sub">Administrator</div>
      </div>
    </div>
  </div>

  <!-- ── Quick Actions ── -->
  <div class="section-title">Aksi Cepat</div>
  <div class="qa-grid">
    <a href="manage_users.php" class="qa-card" style="--qc:#3b82f6">
      <div class="qa-icon"><i class="fas fa-user-plus"></i></div>
      <div class="qa-label">Kelola User</div>
    </a>
    <a href="<?= BASE_URL ?>pengajar/pengajar_add.php" class="qa-card" style="--qc:#22c55e">
      <div class="qa-icon"><i class="fas fa-user-tie"></i></div>
      <div class="qa-label">Tambah Pengajar</div>
    </a>
    <a href="<?= BASE_URL ?>pengajar/pengajar.php" class="qa-card" style="--qc:#8b5cf6">
      <div class="qa-icon"><i class="fas fa-list"></i></div>
      <div class="qa-label">Data Pengajar</div>
    </a>
    <a href="belanja_modal.php" class="qa-card" style="--qc:#f59e0b">
      <div class="qa-icon"><i class="fas fa-coins"></i></div>
      <div class="qa-label">Belanja Modal</div>
    </a>
    <a href="<?= BASE_URL ?>pengajar/dashboard.php" class="qa-card" style="--qc:#06b6d4">
      <div class="qa-icon"><i class="fas fa-map-marked-alt"></i></div>
      <div class="qa-label">Peta Wilayah</div>
    </a>
    <a href="<?= BASE_URL ?>logout.php" class="qa-card" style="--qc:#ef4444" onclick="return confirm('Yakin ingin keluar?')">
      <div class="qa-icon"><i class="fas fa-sign-out-alt"></i></div>
      <div class="qa-label">Keluar</div>
    </a>
  </div>

  <!-- ── Pengajar Status + Dinas per Wilayah ── -->
  <div class="grid-2">
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title"><i class="fas fa-chart-bar"></i> Dinas per Wilayah</div>
      </div>
      <div class="panel-body">
        <div class="chart-wrap-bar">
          <canvas id="wilChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div class="panel-title"><i class="fas fa-chart-pie"></i> Distribusi Data</div>
      </div>
      <div class="panel-body">
        <div class="chart-wrap">
          <canvas id="roleChart"></canvas>
        </div>
        <!-- Pengajar Status Progress -->
        <div style="margin-top:20px">
          <div class="progress-group">
            <div class="pg-header">
              <span class="pg-label" style="color:var(--green)"><i class="fas fa-circle" style="font-size:7px"></i> Pengajar Aktif</span>
              <span class="pg-val"><?= $stat_aktif ?> / <?= $stat_pengajar ?></span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width:<?= $stat_pengajar > 0 ? round($stat_aktif/$stat_pengajar*100) : 0 ?>%;background:var(--green)"></div>
            </div>
          </div>
          <div class="progress-group">
            <div class="pg-header">
              <span class="pg-label" style="color:var(--red)"><i class="fas fa-circle" style="font-size:7px"></i> Pengajar Nonaktif</span>
              <span class="pg-val"><?= $stat_nonaktif ?> / <?= $stat_pengajar ?></span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width:<?= $stat_pengajar > 0 ? round($stat_nonaktif/$stat_pengajar*100) : 0 ?>%;background:var(--red)"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Tables + Activity ── -->
  <div class="grid-3">
    <div style="display:flex;flex-direction:column;gap:20px">
      <!-- User Terbaru -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-users"></i> User Terbaru</div>
          <a href="manage_users.php" class="panel-link"><i class="fas fa-arrow-right"></i> Semua</a>
        </div>
        <?php if ($recent_users->num_rows > 0): ?>
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Role</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php while ($u = $recent_users->fetch_assoc()): ?>
            <tr>
              <td>
                <div class="td-name">
                  <div class="td-avatar"><?= strtoupper(substr($u['username'],0,1)) ?></div>
                  <div>
                    <div><?= htmlspecialchars($u['username']) ?></div>
                    <div class="td-sub"><?= date('d M Y', strtotime($u['created_at'])) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
              <td>
                <a href="manage_users.php?edit=<?= $u['id'] ?>" class="tbl-btn tbl-edit"><i class="fas fa-pen"></i></a>
                <a href="manage_users.php?delete=<?= $u['id'] ?>" class="tbl-btn tbl-delete" onclick="return confirm('Hapus user ini?')"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="empty-state"><i class="fas fa-users"></i><p>Belum ada user.</p></div>
        <?php endif; ?>
      </div>

      <!-- Pengajar Terbaru -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-chalkboard-teacher"></i> Pengajar Terbaru</div>
          <a href="<?= BASE_URL ?>pengajar/pengajar.php" class="panel-link"><i class="fas fa-arrow-right"></i> Semua</a>
        </div>
        <?php if ($recent_pengajar->num_rows > 0): ?>
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Jabatan</th><th>Status</th></tr></thead>
          <tbody>
          <?php while ($p = $recent_pengajar->fetch_assoc()): ?>
            <tr>
              <td>
                <div class="td-name">
                  <div class="td-avatar" style="background:linear-gradient(135deg,#059669,#34d399)"><?= strtoupper(substr($p['nama_pengajar'],0,1)) ?></div>
                  <div>
                    <div><?= htmlspecialchars($p['nama_pengajar']) ?></div>
                    <div class="td-sub"><?= htmlspecialchars($p['unit_kerja']) ?></div>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($p['jabatan'] ?: '-') ?></td>
              <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="empty-state"><i class="fas fa-chalkboard-teacher"></i><p>Belum ada pengajar.</p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar: Activity + System Info -->
    <div style="display:flex;flex-direction:column;gap:20px">
      <!-- Aktivitas Terbaru -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-clock"></i> Aktivitas Terbaru</div>
        </div>
        <div class="panel-body" style="padding:8px 20px">
          <?php if (!empty($activities)): ?>
          <ul class="timeline">
            <?php foreach ($activities as $act):
              $type = $act['tipe'];
              $icon = match($type) {
                'user' => 'fa-user-plus',
                'pengajar' => 'fa-user-tie',
                'pelatihan' => 'fa-graduation-cap',
                default => 'fa-circle'
              };
              $ago = time() - strtotime($act['created_at']);
              if ($ago < 60) $time_str = 'Baru saja';
              elseif ($ago < 3600) $time_str = floor($ago/60) . 'm lalu';
              elseif ($ago < 86400) $time_str = floor($ago/3600) . 'j lalu';
              else $time_str = date('d M', strtotime($act['created_at']));
            ?>
            <li class="tl-<?= $type ?>">
              <div class="tl-icon"><i class="fas <?= $icon ?>"></i></div>
              <div class="tl-text">
                <div class="tl-title"><?= htmlspecialchars($act['judul']) ?></div>
                <div class="tl-detail"><?= htmlspecialchars($act['detail'] ?? '') ?></div>
              </div>
              <div class="tl-time"><?= $time_str ?></div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php else: ?>
            <div class="empty-state"><i class="fas fa-clock"></i><p>Belum ada aktivitas.</p></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- System Info -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-server"></i> Info Sistem</div>
        </div>
        <div class="panel-body">
          <div class="sys-grid">
            <div class="sys-item">
              <i class="fas fa-database"></i>
              <div>
                <div class="sys-label">Database</div>
                <div class="sys-val">mitigapro</div>
              </div>
            </div>
            <div class="sys-item">
              <i class="fas fa-code"></i>
              <div>
                <div class="sys-label">PHP</div>
                <div class="sys-val"><?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></div>
              </div>
            </div>
            <div class="sys-item">
              <i class="fas fa-table"></i>
              <div>
                <div class="sys-label">Total Data</div>
                <div class="sys-val"><?= $stat_users + $stat_pengajar + $stat_dinas ?></div>
              </div>
            </div>
            <div class="sys-item">
              <i class="fas fa-calendar"></i>
              <div>
                <div class="sys-label">Hari ini</div>
                <div class="sys-val"><?= date('d/m/Y') ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
/* ── Doughnut: Distribusi Role ── */
new Chart(document.getElementById('roleChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_map('ucfirst', array_keys($role_data))) ?>,
    datasets: [{ data: <?= json_encode(array_values($role_data)) ?>, backgroundColor: ['#8b5cf6','#3b82f6','#22c55e','#f59e0b'], borderWidth: 0, hoverOffset: 8 }]
  },
  options: {
    responsive: true,
    cutout: '60%',
    plugins: {
      legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 11 }, padding: 14, usePointStyle: true, pointStyleWidth: 8 } },
      tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} user` } }
    }
  }
});

/* ── Bar: Dinas per Wilayah ── */
new Chart(document.getElementById('wilChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($wil_labels) ?>,
    datasets: [{
      label: 'Jumlah Dinas',
      data: <?= json_encode($wil_counts) ?>,
      backgroundColor: ['#3b82f6','#22c55e','#f59e0b','#8b5cf6','#06b6d4','#ef4444','#ec4899'],
      borderRadius: 6,
      barThickness: 28
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Poppins', size: 11 } }, grid: { color: '#f1f5f9' } },
      x: { ticks: { font: { family: 'Poppins', size: 10 }, maxRotation: 45 }, grid: { display: false } }
    },
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.raw} dinas` } }
    }
  }
});

/* ── Sidebar toggle ── */
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('collapsed');
  document.getElementById('mainContent').classList.toggle('expanded');
});
</script>
</body>
</html>
