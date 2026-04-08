<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
require_role('admin');

require INCLUDE_PATH . 'sidebar_pengajar.php';

$role = $_SESSION['role'] ?? 'pengajar';

$query = $conn->query("SELECT d.*, w.nama_wilayah FROM dinas d LEFT JOIN wilayah w ON d.wilayah_id = w.id ORDER BY w.nama_wilayah, d.nama_dinas");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Dinas | MitigaPro</title>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);margin:0}

.container{max-width:1000px;margin:30px auto;padding:0 24px}

.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.page-header h1{font-size:22px;font-weight:700;display:flex;align-items:center;gap:10px}
.page-header h1 i{color:var(--accent)}
.page-header p{font-size:13px;color:var(--muted);margin-top:4px}
.btn-add{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--accent);color:#fff;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;transition:opacity .2s}
.btn-add:hover{opacity:.85}

.card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px}
.dinas-card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);padding:20px;transition:box-shadow .2s;text-decoration:none;color:var(--navy);display:block}
.dinas-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06)}
.dc-top{display:flex;align-items:flex-start;gap:14px;margin-bottom:10px}
.dc-icon{width:40px;height:40px;border-radius:10px;background:#eff6ff;color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.dc-name{font-size:14px;font-weight:700}
.dc-wilayah{font-size:11px;color:var(--muted);margin-top:2px}
.dc-meta{display:flex;gap:16px;font-size:11px;color:var(--muted)}
.dc-meta i{margin-right:4px;font-size:10px}

.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty i{font-size:40px;opacity:.3;display:block;margin-bottom:12px}
</style>
</head>
<body>

<div id="mainContent" class="main-content">
<div class="container">
  <?= breadcrumb([['label' => 'Data Dinas']]) ?>
  <div class="page-header">
    <div>
      <h1><i class="fas fa-building"></i> Daftar Dinas</h1>
      <p>Semua dinas yang terdaftar dalam sistem MitigaPro</p>
    </div>
    <?php if ($role === 'admin'): ?>
    <a href="tambah_dinas.php" class="btn-add"><i class="fas fa-plus"></i> Tambah Dinas</a>
    <?php endif; ?>
  </div>

  <?php if ($query->num_rows > 0): ?>
  <div class="card-grid">
    <?php while ($row = $query->fetch_assoc()): ?>
    <a href="detail_dinas.php?id=<?= (int)$row['id'] ?>" class="dinas-card">
      <div class="dc-top">
        <div class="dc-icon"><i class="fas fa-building"></i></div>
        <div>
          <div class="dc-name"><?= htmlspecialchars($row['nama_dinas']) ?></div>
          <div class="dc-wilayah"><?= htmlspecialchars($row['nama_wilayah'] ?? 'Tanpa Wilayah') ?></div>
        </div>
      </div>
      <div class="dc-meta">
        <?php if (!empty($row['alamat'])): ?>
          <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(mb_strimwidth($row['alamat'], 0, 40, '...')) ?></span>
        <?php endif; ?>
        <?php if (!empty($row['kontak'])): ?>
          <span><i class="fas fa-phone"></i> <?= htmlspecialchars($row['kontak']) ?></span>
        <?php endif; ?>
      </div>
    </a>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
  <div class="empty">
    <i class="fas fa-building"></i>
    <p>Belum ada dinas terdaftar.</p>
  </div>
  <?php endif; ?>
</div>

<?php require INCLUDE_PATH . 'footer.php'; ?>
</div><!-- /main-content -->
</body>
</html>
