<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'pengajar';

// Filter
$tahun_filter   = intval($_GET['tahun'] ?? 0);
$wilayah_filter = intval($_GET['wilayah'] ?? 0);
$search         = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
$types  = '';

if ($tahun_filter) {
    $where[]  = 'p.tahun = ?';
    $params[] = $tahun_filter;
    $types   .= 'i';
}
if ($wilayah_filter) {
    $where[]  = 'd.wilayah_id = ?';
    $params[] = $wilayah_filter;
    $types   .= 'i';
}
if ($search !== '') {
    $where[]  = '(p.jenis_pelatihan LIKE ? OR d.nama_dinas LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.*, d.nama_dinas, w.nama_wilayah
        FROM identifikasi_pelatihan p
        INNER JOIN dinas d ON p.dinas_id = d.id
        INNER JOIN wilayah w ON d.wilayah_id = w.id
        $where_sql
        ORDER BY p.created_at DESC";

if ($types) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $q_data = $stmt->get_result();
} else {
    $q_data = $conn->query($sql);
}

// Data untuk filter
$q_tahun   = $conn->query("SELECT DISTINCT tahun FROM identifikasi_pelatihan ORDER BY tahun DESC");
$q_wilayah = $conn->query("SELECT id, nama_wilayah FROM wilayah ORDER BY nama_wilayah ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Pelatihan | MitigaPro</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/footer.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--green:#22c55e;--red:#ef4444;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);margin:0}

.container{max-width:1100px;margin:0 auto;padding:30px 24px 60px}

.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.page-header h1{font-size:22px;font-weight:700;display:flex;align-items:center;gap:10px}
.page-header h1 i{color:var(--accent)}
.page-header p{font-size:13px;color:var(--muted);margin-top:4px}
.btn-add{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--accent);color:#fff;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;transition:opacity .2s}
.btn-add:hover{opacity:.85}

.filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:20px;background:var(--white);padding:14px 18px;border-radius:var(--radius);border:1px solid var(--border)}
.filter-bar select,.filter-bar input[type="text"]{padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;font-family:'Poppins',sans-serif;background:var(--white)}
.filter-bar input[type="text"]{min-width:180px}
.filter-bar .btn-filter{padding:8px 16px;border-radius:8px;background:var(--accent);color:#fff;border:none;font-size:12px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer}
.filter-bar .btn-reset{font-size:12px;color:var(--accent);text-decoration:none}

.card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);overflow:hidden}

.tbl{width:100%;border-collapse:collapse;font-size:12px}
.tbl th{background:var(--bg);padding:12px 14px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;color:var(--muted);letter-spacing:.5px}
.tbl td{padding:12px 14px;border-top:1px solid var(--border);vertical-align:middle}
.tbl tr:hover td{background:#f8fafc}

.badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:10px;font-weight:600}
.badge-blue{background:#eff6ff;color:var(--accent)}
.badge-green{background:#ecfdf5;color:#16a34a}

.btn-sm{padding:5px 12px;border-radius:6px;font-size:11px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px;border:none;cursor:pointer;font-family:'Poppins',sans-serif}
.btn-edit{background:#eff6ff;color:var(--accent)}
.btn-del{background:#fef2f2;color:var(--red)}
.btn-sm:hover{opacity:.8}

.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty i{font-size:40px;opacity:.3;display:block;margin-bottom:12px}

.toast{position:fixed;top:20px;right:20px;padding:12px 20px;background:var(--green);color:#fff;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;animation:slideIn .3s ease,fadeOut .3s ease 2.5s forwards}
@keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes fadeOut{to{opacity:0;transform:translateY(-10px)}}

.count-badge{background:var(--accent);color:#fff;font-size:11px;font-weight:700;padding:2px 10px;border-radius:12px;margin-left:8px}
</style>
</head>
<body>

<?php require INCLUDE_PATH . 'sidebar_pengajar.php'; ?>

<div id="mainContent" class="main-content">
<div class="container">
  <?= breadcrumb([['label' => 'Daftar Pelatihan']]) ?>

  <?php if (isset($_GET['success'])): ?>
  <div class="toast"><i class="fas fa-check-circle"></i>
    <?php
      echo match($_GET['success']) {
          'added'   => 'Pelatihan berhasil ditambahkan!',
          'deleted' => 'Pelatihan berhasil dihapus!',
          default   => 'Berhasil!',
      };
    ?>
  </div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <h1><i class="fas fa-graduation-cap"></i> Daftar Pelatihan <span class="count-badge"><?= $q_data->num_rows ?></span></h1>
      <p>Semua data identifikasi kebutuhan pelatihan</p>
    </div>
    <?php if ($role === 'admin'): ?>
    <a href="tambah_pelatihan_baru.php" class="btn-add"><i class="fas fa-plus"></i> Tambah Pelatihan</a>
    <?php endif; ?>
  </div>

  <form class="filter-bar" method="GET">
    <input type="text" name="q" placeholder="Cari pelatihan / dinas..." value="<?= htmlspecialchars($search) ?>">
    <select name="wilayah">
      <option value="0">Semua Wilayah</option>
      <?php while ($w = $q_wilayah->fetch_assoc()): ?>
        <option value="<?= $w['id'] ?>" <?= $wilayah_filter == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars(str_replace('Wilayah Kerja ', '', $w['nama_wilayah'])) ?></option>
      <?php endwhile; ?>
    </select>
    <select name="tahun">
      <option value="0">Semua Tahun</option>
      <?php while ($t = $q_tahun->fetch_assoc()): ?>
        <option value="<?= $t['tahun'] ?>" <?= $tahun_filter == $t['tahun'] ? 'selected' : '' ?>><?= $t['tahun'] ?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Cari</button>
    <?php if ($search || $tahun_filter || $wilayah_filter): ?>
      <a href="daftar_pelatihan.php" class="btn-reset"><i class="fas fa-times"></i> Reset</a>
    <?php endif; ?>
  </form>

  <?php if ($q_data->num_rows > 0): ?>
  <div class="card">
    <table class="tbl">
      <thead>
        <tr>
          <th>No</th>
          <th>Jenis Pelatihan</th>
          <th>Dinas</th>
          <th>Wilayah</th>
          <th>Tahun</th>
          <th>Kebutuhan</th>
          <?php if ($role === 'admin'): ?><th>Aksi</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; while ($row = $q_data->fetch_assoc()): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><strong><?= htmlspecialchars($row['jenis_pelatihan']) ?></strong></td>
          <td><?= htmlspecialchars($row['nama_dinas']) ?></td>
          <td><span class="badge badge-blue"><?= htmlspecialchars(str_replace('Wilayah Kerja ', '', $row['nama_wilayah'])) ?></span></td>
          <td><span class="badge badge-green"><?= $row['tahun'] ?></span></td>
          <td><?= htmlspecialchars(mb_strimwidth($row['kebutuhan'] ?? '-', 0, 50, '...')) ?></td>
          <?php if ($role === 'admin'): ?>
          <td>
            <a href="edit_pelatihan.php?id=<?= (int)$row['id'] ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i></a>
            <a href="hapus_pelatihan.php?id=<?= (int)$row['id'] ?>&from=list" class="btn-sm btn-del" onclick="return confirm('Hapus pelatihan ini?')"><i class="fas fa-trash"></i></a>
          </td>
          <?php endif; ?>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty">
    <i class="fas fa-graduation-cap"></i>
    <p>Belum ada data pelatihan<?= ($search || $tahun_filter || $wilayah_filter) ? ' dengan filter ini' : '' ?>.</p>
  </div>
  <?php endif; ?>
</div>

<?php require INCLUDE_PATH . 'footer.php'; ?>
</div>

</body>
</html>
