<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Filter kategori
$kat_filter = trim($_GET['kategori'] ?? '');
$where = '';
$params = [];
$types = '';
if ($kat_filter !== '') {
    $where = ' WHERE b.kategori = ?';
    $params[] = $kat_filter;
    $types = 's';
}

$sql = "SELECT b.*, u.username FROM berita_pelatihan b LEFT JOIN users u ON b.user_id = u.id $where ORDER BY b.created_at DESC";
if ($types) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $q_berita = $stmt->get_result();
} else {
    $q_berita = $conn->query($sql);
}

// Daftar kategori untuk filter
$q_kat = $conn->query("SELECT DISTINCT kategori FROM berita_pelatihan WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Berita Pelatihan | MitigaPro</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php if (($_SESSION['role'] ?? '') !== 'tamu'): ?>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<?php endif; ?>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--green:#22c55e;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);margin:0}

.container{max-width:1000px;margin:0 auto;padding:30px 24px 60px}

.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.page-header h1{font-size:22px;font-weight:700;display:flex;align-items:center;gap:10px}
.page-header h1 i{color:var(--accent)}
.page-header p{font-size:13px;color:var(--muted);margin-top:4px}

.filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:20px}
.filter-bar select{padding:8px 14px;border:1px solid var(--border);border-radius:8px;font-size:12px;font-family:'Poppins',sans-serif;background:var(--white)}
.filter-bar .btn-filter{padding:8px 16px;border-radius:8px;background:var(--accent);color:#fff;border:none;font-size:12px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer}
.filter-bar a{font-size:12px;color:var(--accent);text-decoration:none}

.news-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}

.news-card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;transition:box-shadow .2s;text-decoration:none;color:var(--navy);display:flex;flex-direction:column}
.news-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08)}
.news-img{width:100%;height:180px;object-fit:cover;background:#f1f5f9;display:flex;align-items:center;justify-content:center}
.news-img img{width:100%;height:100%;object-fit:cover}
.news-img .placeholder{color:#cbd5e1;font-size:40px}
.news-body{padding:18px 20px;flex:1;display:flex;flex-direction:column}
.news-meta{display:flex;gap:10px;font-size:10px;color:var(--muted);margin-bottom:8px;align-items:center}
.news-meta .badge{padding:2px 8px;border-radius:10px;font-size:9px;font-weight:700}
.badge-info{background:#eff6ff;color:var(--accent)}
.badge-warn{background:#fffbeb;color:#d97706}
.badge-green{background:#ecfdf5;color:#16a34a}
.badge-gray{background:#f1f5f9;color:var(--muted)}
.news-title{font-size:15px;font-weight:700;margin-bottom:8px;line-height:1.4}
.news-excerpt{font-size:12px;color:var(--muted);line-height:1.6;flex:1}
.news-footer{display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:12px;border-top:1px solid var(--border);font-size:11px;color:var(--muted)}
.read-more{color:var(--accent);font-weight:600;text-decoration:none;font-size:12px;display:inline-flex;align-items:center;gap:4px}
.read-more:hover{text-decoration:underline}

.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty i{font-size:40px;opacity:.3;display:block;margin-bottom:12px}

.btn-admin{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--accent);color:#fff;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;transition:opacity .2s}
.btn-admin:hover{opacity:.85}
</style>
</head>
<body>

<?php if (($_SESSION['role'] ?? '') === 'tamu'): ?>
  <?php require INCLUDE_PATH . 'topbar_tamu.php'; ?>
  <div id="mainContent">
<?php else: ?>
  <?php require INCLUDE_PATH . 'sidebar_pengajar.php'; ?>
  <div id="mainContent" class="main-content">
<?php endif; ?>
<div class="container">
  <?= breadcrumb([['label' => 'Berita Pelatihan']]) ?>

  <div class="page-header">
    <div>
      <h1><i class="fas fa-newspaper"></i> Berita & Informasi Pelatihan</h1>
      <p>Informasi terbaru seputar kegiatan pelatihan MitigaPro</p>
    </div>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
    <a href="kelola_berita.php" class="btn-admin"><i class="fas fa-cog"></i> Kelola Berita</a>
    <?php endif; ?>
  </div>

  <form class="filter-bar" method="GET">
    <select name="kategori">
      <option value="">Semua Kategori</option>
      <?php while ($k = $q_kat->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($k['kategori']) ?>" <?= $kat_filter === $k['kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($k['kategori']) ?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
    <?php if ($kat_filter): ?>
      <a href="berita_pelatihan.php"><i class="fas fa-times"></i> Reset</a>
    <?php endif; ?>
  </form>

  <?php if ($q_berita->num_rows > 0): ?>
  <div class="news-grid">
    <?php while ($b = $q_berita->fetch_assoc()): ?>
    <div class="news-card">
      <div class="news-img">
        <?php if ($b['gambar']): ?>
          <img src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($b['gambar']) ?>" alt="">
        <?php else: ?>
          <div class="placeholder"><i class="fas fa-newspaper"></i></div>
        <?php endif; ?>
      </div>
      <div class="news-body">
        <div class="news-meta">
          <?php
            $badge_class = match($b['kategori']) {
                'Informasi'  => 'badge-info',
                'Pengumuman' => 'badge-warn',
                'Jadwal'     => 'badge-green',
                default      => 'badge-gray',
            };
          ?>
          <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($b['kategori'] ?: 'Umum') ?></span>
          <span><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($b['created_at'])) ?></span>
        </div>
        <div class="news-title"><?= htmlspecialchars($b['judul']) ?></div>
        <div class="news-excerpt"><?= htmlspecialchars(mb_strimwidth(strip_tags($b['isi']), 0, 150, '...')) ?></div>
        <div class="news-footer">
          <span><i class="fas fa-user"></i> <?= htmlspecialchars($b['username'] ?? 'Admin') ?></span>
          <a href="detail_berita.php?id=<?= (int)$b['id'] ?>" class="read-more">Baca selengkapnya <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
  <div class="empty">
    <i class="fas fa-newspaper"></i>
    <p>Belum ada berita pelatihan.</p>
  </div>
  <?php endif; ?>
</div>

<?php require INCLUDE_PATH . 'footer.php'; ?>
</div>

</body>
</html>
