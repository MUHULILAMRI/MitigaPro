<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: berita_pelatihan.php'); exit; }

$stmt = $conn->prepare("SELECT b.*, u.username FROM berita_pelatihan b LEFT JOIN users u ON b.user_id = u.id WHERE b.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$berita = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$berita) { header('Location: berita_pelatihan.php'); exit; }

// Berita lainnya (max 3)
$q_lain = $conn->prepare("SELECT id, judul, gambar, kategori, created_at FROM berita_pelatihan WHERE id != ? ORDER BY created_at DESC LIMIT 3");
$q_lain->bind_param('i', $id);
$q_lain->execute();
$r_lain = $q_lain->get_result();
$q_lain->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($berita['judul']) ?> | MitigaPro</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php if (($_SESSION['role'] ?? '') !== 'tamu'): ?>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<?php endif; ?>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);margin:0}

.container{max-width:800px;margin:0 auto;padding:30px 24px 60px}

.article{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;margin-bottom:24px}
.article-img{width:100%;max-height:400px;object-fit:cover}
.article-body{padding:32px}
.article-meta{display:flex;gap:14px;font-size:11px;color:var(--muted);margin-bottom:16px;align-items:center;flex-wrap:wrap}
.article-meta i{margin-right:3px}
.badge{display:inline-block;padding:3px 10px;border-radius:10px;font-size:10px;font-weight:700}
.badge-info{background:#eff6ff;color:var(--accent)}
.badge-warn{background:#fffbeb;color:#d97706}
.badge-green{background:#ecfdf5;color:#16a34a}
.badge-gray{background:#f1f5f9;color:var(--muted)}

.article-title{font-size:24px;font-weight:700;line-height:1.4;margin-bottom:20px}
.article-content{font-size:14px;line-height:1.8;color:#334155;white-space:pre-line}

.sidebar-section{margin-top:30px}
.sidebar-section h3{font-size:15px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.sidebar-section h3 i{color:var(--accent);font-size:13px}

.related-list{display:flex;flex-direction:column;gap:12px}
.related-card{display:flex;gap:12px;background:var(--white);border-radius:10px;border:1px solid var(--border);padding:12px;text-decoration:none;color:var(--navy);transition:box-shadow .2s}
.related-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.06)}
.related-card img{width:80px;height:56px;object-fit:cover;border-radius:6px;flex-shrink:0}
.related-card .rc-placeholder{width:80px;height:56px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#cbd5e1;flex-shrink:0}
.related-card .rc-info{flex:1}
.related-card .rc-title{font-size:13px;font-weight:600;line-height:1.3;margin-bottom:4px}
.related-card .rc-date{font-size:10px;color:var(--muted)}

.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--accent);text-decoration:none;font-size:13px;font-weight:600;margin-top:8px}
.back-link:hover{text-decoration:underline}

.btn-edit-float{position:fixed;bottom:24px;right:24px;background:var(--accent);color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 16px rgba(59,130,246,.3);z-index:100}
.btn-edit-float:hover{opacity:.9}
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
  <?= breadcrumb([['label' => 'Berita Pelatihan', 'url' => 'berita_pelatihan.php'], ['label' => mb_strimwidth($berita['judul'], 0, 30, '...')]]) ?>

  <article class="article">
    <?php if ($berita['gambar']): ?>
      <img src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($berita['gambar']) ?>" alt="" class="article-img">
    <?php endif; ?>

    <div class="article-body">
      <div class="article-meta">
        <?php
          $badge_class = match($berita['kategori']) {
              'Informasi'  => 'badge-info',
              'Pengumuman' => 'badge-warn',
              'Jadwal'     => 'badge-green',
              default      => 'badge-gray',
          };
        ?>
        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($berita['kategori'] ?: 'Umum') ?></span>
        <span><i class="fas fa-calendar"></i> <?= date('d M Y, H:i', strtotime($berita['created_at'])) ?></span>
        <span><i class="fas fa-user"></i> <?= htmlspecialchars($berita['username'] ?? 'Admin') ?></span>
      </div>

      <h1 class="article-title"><?= htmlspecialchars($berita['judul']) ?></h1>
      <div class="article-content"><?= nl2br(htmlspecialchars($berita['isi'])) ?></div>
      <?php if (!empty($berita['link'])): ?>
      <div style="margin-top:20px;padding:14px 18px;background:#eff6ff;border-radius:8px;border:1px solid #bfdbfe;display:inline-flex;align-items:center;gap:8px">
        <i class="fas fa-external-link-alt" style="color:var(--accent)"></i>
        <a href="<?= htmlspecialchars($berita['link']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--accent);font-weight:600;font-size:13px;text-decoration:none"><?= htmlspecialchars($berita['link']) ?></a>
      </div>
      <?php endif; ?>
    </div>
  </article>

  <?php if ($r_lain->num_rows > 0): ?>
  <div class="sidebar-section">
    <h3><i class="fas fa-newspaper"></i> Berita Lainnya</h3>
    <div class="related-list">
      <?php while ($l = $r_lain->fetch_assoc()): ?>
      <a href="detail_berita.php?id=<?= (int)$l['id'] ?>" class="related-card">
        <?php if ($l['gambar']): ?>
          <img src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($l['gambar']) ?>" alt="">
        <?php else: ?>
          <div class="rc-placeholder"><i class="fas fa-newspaper"></i></div>
        <?php endif; ?>
        <div class="rc-info">
          <div class="rc-title"><?= htmlspecialchars(mb_strimwidth($l['judul'], 0, 60, '...')) ?></div>
          <div class="rc-date"><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($l['created_at'])) ?></div>
        </div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
  <?php endif; ?>

  <a href="berita_pelatihan.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke daftar berita</a>
</div>

<?php require INCLUDE_PATH . 'footer.php'; ?>
</div>

<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
<a href="edit_berita.php?id=<?= (int)$berita['id'] ?>" class="btn-edit-float"><i class="fas fa-edit"></i> Edit</a>
<?php endif; ?>

</body>
</html>
