<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
require_role('admin');

// Hapus berita
if (isset($_GET['hapus'])) {
    $del_id = intval($_GET['hapus']);
    // Hapus gambar jika ada
    $q = $conn->prepare("SELECT gambar FROM berita_pelatihan WHERE id = ?");
    $q->bind_param('i', $del_id);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    if ($row && $row['gambar'] && file_exists(UPLOAD_PATH . 'berita/' . $row['gambar'])) {
        unlink(UPLOAD_PATH . 'berita/' . $row['gambar']);
    }
    $stmt = $conn->prepare("DELETE FROM berita_pelatihan WHERE id = ?");
    $stmt->bind_param('i', $del_id);
    $stmt->execute();
    $stmt->close();
    header('Location: kelola_berita.php?success=deleted');
    exit;
}

// Ambil semua berita
$q_berita = $conn->query("
    SELECT b.*, u.username
    FROM berita_pelatihan b
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Berita Pelatihan | MitigaPro</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--green:#22c55e;--red:#ef4444;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);margin:0}

.container{max-width:1000px;margin:0 auto;padding:30px 24px 60px}

.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.page-header h1{font-size:22px;font-weight:700;display:flex;align-items:center;gap:10px}
.page-header h1 i{color:var(--accent)}
.page-header p{font-size:13px;color:var(--muted);margin-top:4px}
.btn-add{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--accent);color:#fff;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;transition:opacity .2s}
.btn-add:hover{opacity:.85}

.card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;margin-bottom:16px}

.tbl{width:100%;border-collapse:collapse;font-size:12px}
.tbl th{background:var(--bg);padding:12px 14px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;color:var(--muted);letter-spacing:.5px}
.tbl td{padding:12px 14px;border-top:1px solid var(--border);vertical-align:top}
.tbl tr:hover td{background:#f8fafc}
.tbl img{width:60px;height:40px;object-fit:cover;border-radius:6px}

.badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:10px;font-weight:600}
.badge-info{background:#eff6ff;color:var(--accent)}
.badge-warn{background:#fffbeb;color:#d97706}
.badge-green{background:#ecfdf5;color:#16a34a}
.badge-gray{background:#f1f5f9;color:var(--muted)}

.btn-sm{padding:5px 12px;border-radius:6px;font-size:11px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px;border:none;cursor:pointer;font-family:'Poppins',sans-serif}
.btn-edit{background:#eff6ff;color:var(--accent)}
.btn-del{background:#fef2f2;color:var(--red)}
.btn-sm:hover{opacity:.8}

.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty i{font-size:40px;opacity:.3;display:block;margin-bottom:12px}

.toast{position:fixed;top:20px;right:20px;padding:12px 20px;background:var(--green);color:#fff;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;animation:slideIn .3s ease,fadeOut .3s ease 2.5s forwards}
@keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes fadeOut{to{opacity:0;transform:translateY(-10px)}}
</style>
</head>
<body>

<?php require INCLUDE_PATH . 'sidebar_pengajar.php'; ?>

<div id="mainContent" class="main-content">
<div class="container">
  <?= breadcrumb([['label' => 'Berita Pelatihan']]) ?>

  <?php if (isset($_GET['success'])): ?>
  <div class="toast"><i class="fas fa-check-circle"></i>
    <?= $_GET['success'] === 'deleted' ? 'Berita berhasil dihapus!' : 'Berita berhasil ditambahkan!' ?>
  </div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <h1><i class="fas fa-newspaper"></i> Kelola Berita Pelatihan</h1>
      <p>Tambah, edit, atau hapus berita & informasi pelatihan</p>
    </div>
    <a href="tambah_berita.php" class="btn-add"><i class="fas fa-plus"></i> Tambah Berita</a>
  </div>

  <?php if ($q_berita->num_rows > 0): ?>
  <div class="card">
    <table class="tbl">
      <thead>
        <tr>
          <th>Gambar</th>
          <th>Judul</th>
          <th>Kategori</th>
          <th>Penulis</th>
          <th>Tanggal</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($b = $q_berita->fetch_assoc()): ?>
        <tr>
          <td>
            <?php if ($b['gambar']): ?>
              <img src="<?= BASE_URL ?>uploads/berita/<?= htmlspecialchars($b['gambar']) ?>" alt="">
            <?php else: ?>
              <div style="width:60px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#cbd5e1"><i class="fas fa-image"></i></div>
            <?php endif; ?>
          </td>
          <td>
            <strong><?= htmlspecialchars(mb_strimwidth($b['judul'], 0, 50, '...')) ?></strong>
            <div style="font-size:11px;color:var(--muted);margin-top:2px"><?= htmlspecialchars(mb_strimwidth(strip_tags($b['isi']), 0, 80, '...')) ?></div>
          </td>
          <td>
            <?php
              $badge_class = match($b['kategori']) {
                  'Informasi'  => 'badge-info',
                  'Pengumuman' => 'badge-warn',
                  'Jadwal'     => 'badge-green',
                  default      => 'badge-gray',
              };
            ?>
            <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($b['kategori'] ?: '-') ?></span>
          </td>
          <td><?= htmlspecialchars($b['username'] ?? '-') ?></td>
          <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
          <td>
            <a href="edit_berita.php?id=<?= (int)$b['id'] ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Edit</a>
            <a href="kelola_berita.php?hapus=<?= (int)$b['id'] ?>" class="btn-sm btn-del" onclick="return confirm('Hapus berita ini?')"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty">
    <i class="fas fa-newspaper"></i>
    <p>Belum ada berita pelatihan. <a href="tambah_berita.php">Tambah sekarang</a></p>
  </div>
  <?php endif; ?>
</div>

<?php require INCLUDE_PATH . 'footer.php'; ?>
</div>

</body>
</html>
