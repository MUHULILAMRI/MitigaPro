<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';
require_role('admin');

// ── Upload helper ──
function upload_foto(string $field, string $subfolder): ?string {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return null;
    $name = 'visitor_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dir  = UPLOAD_PATH . $subfolder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $name);
    return $name;
}

// ── Hapus file ──
function hapus_file(?string $filename, string $subfolder): void {
    if ($filename && file_exists(UPLOAD_PATH . $subfolder . '/' . $filename)) {
        unlink(UPLOAD_PATH . $subfolder . '/' . $filename);
    }
}

$msg = ''; $msg_type = '';
$tab = $_GET['tab'] ?? 'sambutan';
$valid_tabs = ['sambutan','profil','struktur','faq','kontak','sosmed','tautan','galeri'];
if (!in_array($tab, $valid_tabs)) $tab = 'sambutan';

// ═══════════════════════════════════════════════════════════
// POST HANDLERS
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    // ── SAMBUTAN ──
    if ($action === 'save_sambutan') {
        $nama    = trim($_POST['nama'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? '');
        $judul   = trim($_POST['judul'] ?? '');
        $isi     = trim($_POST['isi'] ?? '');
        $foto    = upload_foto('foto', 'visitor');

        $row = $conn->query("SELECT id, foto FROM visitor_sambutan LIMIT 1")->fetch_assoc();
        if ($row) {
            if ($foto) hapus_file($row['foto'], 'visitor');
            $sql = $foto
                ? "UPDATE visitor_sambutan SET nama=?, jabatan=?, judul=?, isi=?, foto=? WHERE id=?"
                : "UPDATE visitor_sambutan SET nama=?, jabatan=?, judul=?, isi=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($foto) { $stmt->bind_param('sssssi', $nama, $jabatan, $judul, $isi, $foto, $row['id']); }
            else       { $stmt->bind_param('ssssi',  $nama, $jabatan, $judul, $isi, $row['id']); }
        } else {
            $stmt = $conn->prepare("INSERT INTO visitor_sambutan (nama, jabatan, judul, isi, foto) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssss', $nama, $jabatan, $judul, $isi, $foto);
        }
        $stmt->execute(); $stmt->close();
        $msg = 'Kata sambutan berhasil disimpan.'; $msg_type = 'success';
    }

    // ── PROFIL (visi/misi/tugas/fungsi) ──
    if ($action === 'save_profil') {
        $tipe  = $_POST['tipe'] ?? '';
        $items = $_POST['items'] ?? [];
        if (in_array($tipe, ['visi','misi','tugas','fungsi'])) {
            $conn->query("DELETE FROM visitor_profil WHERE tipe = '" . $conn->real_escape_string($tipe) . "'");
            $stmt = $conn->prepare("INSERT INTO visitor_profil (tipe, isi, urutan) VALUES (?,?,?)");
            foreach ($items as $i => $text) {
                $text = trim($text);
                if ($text === '') continue;
                $urutan = $i + 1;
                $stmt->bind_param('ssi', $tipe, $text, $urutan);
                $stmt->execute();
            }
            $stmt->close();
            $msg = ucfirst($tipe) . ' berhasil disimpan.'; $msg_type = 'success';
        }
    }

    // ── STRUKTUR ──
    if ($action === 'add_struktur') {
        $stmt = $conn->prepare("INSERT INTO visitor_struktur (nama, jabatan, level, urutan, icon, warna) VALUES (?,?,?,?,?,?)");
        $nama = trim($_POST['nama'] ?? ''); $jabatan = trim($_POST['jabatan'] ?? '');
        $level = (int)($_POST['level'] ?? 1); $urutan = (int)($_POST['urutan'] ?? 0);
        $icon = trim($_POST['icon'] ?? 'fas fa-user'); $warna = trim($_POST['warna'] ?? '#3b82f6');
        $stmt->bind_param('ssiiss', $nama, $jabatan, $level, $urutan, $icon, $warna);
        $stmt->execute(); $stmt->close();
        $msg = 'Jabatan berhasil ditambahkan.'; $msg_type = 'success';
    }
    if ($action === 'edit_struktur') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE visitor_struktur SET nama=?, jabatan=?, level=?, urutan=?, icon=?, warna=? WHERE id=?");
        $nama = trim($_POST['nama'] ?? ''); $jabatan = trim($_POST['jabatan'] ?? '');
        $level = (int)($_POST['level'] ?? 1); $urutan = (int)($_POST['urutan'] ?? 0);
        $icon = trim($_POST['icon'] ?? 'fas fa-user'); $warna = trim($_POST['warna'] ?? '#3b82f6');
        $stmt->bind_param('ssiissi', $nama, $jabatan, $level, $urutan, $icon, $warna, $id);
        $stmt->execute(); $stmt->close();
        $msg = 'Jabatan berhasil diperbarui.'; $msg_type = 'success';
    }
    if ($action === 'delete_struktur') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM visitor_struktur WHERE id = $id");
        $msg = 'Jabatan berhasil dihapus.'; $msg_type = 'success';
    }

    // ── FAQ ──
    if ($action === 'add_faq') {
        $stmt = $conn->prepare("INSERT INTO visitor_faq (pertanyaan, jawaban, urutan) VALUES (?,?,?)");
        $q = trim($_POST['pertanyaan'] ?? ''); $a = trim($_POST['jawaban'] ?? ''); $u = (int)($_POST['urutan'] ?? 0);
        $stmt->bind_param('ssi', $q, $a, $u);
        $stmt->execute(); $stmt->close();
        $msg = 'FAQ berhasil ditambahkan.'; $msg_type = 'success';
    }
    if ($action === 'edit_faq') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE visitor_faq SET pertanyaan=?, jawaban=?, urutan=?, aktif=? WHERE id=?");
        $q = trim($_POST['pertanyaan'] ?? ''); $a = trim($_POST['jawaban'] ?? '');
        $u = (int)($_POST['urutan'] ?? 0); $ak = (int)($_POST['aktif'] ?? 1);
        $stmt->bind_param('ssiii', $q, $a, $u, $ak, $id);
        $stmt->execute(); $stmt->close();
        $msg = 'FAQ berhasil diperbarui.'; $msg_type = 'success';
    }
    if ($action === 'delete_faq') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM visitor_faq WHERE id = $id");
        $msg = 'FAQ berhasil dihapus.'; $msg_type = 'success';
    }

    // ── KONTAK ──
    if ($action === 'save_kontak') {
        $kunci = $_POST['kunci'] ?? '';
        $label = trim($_POST['label'] ?? '');
        $nilai = trim($_POST['nilai'] ?? '');
        $icon  = trim($_POST['icon'] ?? 'fas fa-info-circle');
        $warna = trim($_POST['warna'] ?? 'linear-gradient(135deg,#3b82f6,#6366f1)');
        $stmt = $conn->prepare("UPDATE visitor_kontak SET label=?, nilai=?, icon=?, warna=? WHERE kunci=?");
        $stmt->bind_param('sssss', $label, $nilai, $icon, $warna, $kunci);
        $stmt->execute(); $stmt->close();
        $msg = 'Kontak berhasil diperbarui.'; $msg_type = 'success';
    }

    // ── SOSMED ──
    if ($action === 'save_sosmed') {
        $ids   = $_POST['sosmed_id'] ?? [];
        $urls  = $_POST['sosmed_url'] ?? [];
        $aktfs = $_POST['sosmed_aktif'] ?? [];
        $stmt = $conn->prepare("UPDATE visitor_sosmed SET url=?, aktif=? WHERE id=?");
        foreach ($ids as $i => $sid) {
            $sid = (int)$sid;
            $url = trim($urls[$i] ?? '#');
            $ak  = isset($aktfs[$sid]) ? 1 : 0;
            $stmt->bind_param('sii', $url, $ak, $sid);
            $stmt->execute();
        }
        $stmt->close();
        $msg = 'Media sosial berhasil disimpan.'; $msg_type = 'success';
    }

    // ── TAUTAN ──
    if ($action === 'add_tautan') {
        $stmt = $conn->prepare("INSERT INTO visitor_tautan (nama, deskripsi, url, icon, urutan) VALUES (?,?,?,?,?)");
        $n = trim($_POST['nama'] ?? ''); $d = trim($_POST['deskripsi'] ?? '');
        $u = trim($_POST['url'] ?? ''); $ic = trim($_POST['icon'] ?? 'fas fa-link'); $ur = (int)($_POST['urutan'] ?? 0);
        $stmt->bind_param('ssssi', $n, $d, $u, $ic, $ur);
        $stmt->execute(); $stmt->close();
        $msg = 'Tautan berhasil ditambahkan.'; $msg_type = 'success';
    }
    if ($action === 'edit_tautan') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE visitor_tautan SET nama=?, deskripsi=?, url=?, icon=?, urutan=?, aktif=? WHERE id=?");
        $n = trim($_POST['nama'] ?? ''); $d = trim($_POST['deskripsi'] ?? '');
        $u = trim($_POST['url'] ?? ''); $ic = trim($_POST['icon'] ?? 'fas fa-link');
        $ur = (int)($_POST['urutan'] ?? 0); $ak = (int)($_POST['aktif'] ?? 1);
        $stmt->bind_param('ssssiii', $n, $d, $u, $ic, $ur, $ak, $id);
        $stmt->execute(); $stmt->close();
        $msg = 'Tautan berhasil diperbarui.'; $msg_type = 'success';
    }
    if ($action === 'delete_tautan') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM visitor_tautan WHERE id = $id");
        $msg = 'Tautan berhasil dihapus.'; $msg_type = 'success';
    }

    // ── GALERI ──
    if ($action === 'add_galeri') {
        $judul    = trim($_POST['judul'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $gambar   = upload_foto('gambar', 'galeri');
        if ($gambar) {
            $stmt = $conn->prepare("INSERT INTO visitor_galeri (judul, gambar, kategori) VALUES (?,?,?)");
            $stmt->bind_param('sss', $judul, $gambar, $kategori);
            $stmt->execute(); $stmt->close();
            $msg = 'Foto galeri berhasil ditambahkan.'; $msg_type = 'success';
        } else {
            $msg = 'Gagal upload gambar.'; $msg_type = 'error';
        }
    }
    if ($action === 'delete_galeri') {
        $id = (int)($_POST['id'] ?? 0);
        $row = $conn->query("SELECT gambar FROM visitor_galeri WHERE id=$id")->fetch_assoc();
        if ($row) hapus_file($row['gambar'], 'galeri');
        $conn->query("DELETE FROM visitor_galeri WHERE id = $id");
        $msg = 'Foto galeri berhasil dihapus.'; $msg_type = 'success';
    }

    // Redirect to prevent re-submit
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=$tab&msg=" . urlencode($msg) . "&msg_type=$msg_type");
    exit;
}

// ── GET flash message ──
if (!empty($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = $_GET['msg_type'] ?? 'success'; }

// ═══════════════════════════════════════════════════════════
// FETCH DATA
// ═══════════════════════════════════════════════════════════
$sambutan = $conn->query("SELECT * FROM visitor_sambutan LIMIT 1")->fetch_assoc() ?: [];

$profil = ['visi' => [], 'misi' => [], 'tugas' => [], 'fungsi' => []];
$r = $conn->query("SELECT * FROM visitor_profil ORDER BY tipe, urutan");
if ($r) { while ($row = $r->fetch_assoc()) $profil[$row['tipe']][] = $row; $r->free(); }

$struktur_list = [];
$r = $conn->query("SELECT * FROM visitor_struktur ORDER BY level, urutan");
if ($r) { while ($row = $r->fetch_assoc()) $struktur_list[] = $row; $r->free(); }

$faq_list = [];
$r = $conn->query("SELECT * FROM visitor_faq ORDER BY urutan");
if ($r) { while ($row = $r->fetch_assoc()) $faq_list[] = $row; $r->free(); }

$kontak_list = [];
$r = $conn->query("SELECT * FROM visitor_kontak ORDER BY urutan");
if ($r) { while ($row = $r->fetch_assoc()) $kontak_list[$row['kunci']] = $row; $r->free(); }

$sosmed_list = [];
$r = $conn->query("SELECT * FROM visitor_sosmed ORDER BY urutan");
if ($r) { while ($row = $r->fetch_assoc()) $sosmed_list[] = $row; $r->free(); }

$tautan_list = [];
$r = $conn->query("SELECT * FROM visitor_tautan ORDER BY urutan");
if ($r) { while ($row = $r->fetch_assoc()) $tautan_list[] = $row; $r->free(); }

$galeri_list = [];
$r = $conn->query("SELECT * FROM visitor_galeri ORDER BY created_at DESC");
if ($r) { while ($row = $r->fetch_assoc()) $galeri_list[] = $row; $r->free(); }

require INCLUDE_PATH . 'sidebar_mitigapro.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Halaman Pengunjung | MitigaPro</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_mitigapro.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --navy: #1a2744; --blue: #2c5282; --accent: #3b82f6;
  --bg: #f5f7fb; --white: #fff; --text: #334155;
  --muted: #94a3b8; --border: #e2e8f0; --radius: 12px;
  --success: #22c55e; --error: #ef4444;
}
body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); }
.main { max-width: 1100px; margin: 0 auto; padding: 32px; }

/* ── Page Header ── */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 28px; flex-wrap: wrap; gap: 12px;
}
.page-header h2 { font-size: 1.3rem; font-weight: 700; color: var(--navy); display: flex; align-items: center; gap: 10px; }
.page-header h2 i { color: var(--accent); }
.preview-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--accent); color: #fff; padding: 9px 20px;
  border-radius: 10px; font-size: 12px; font-weight: 600;
  text-decoration: none; transition: background 0.2s;
}
.preview-btn:hover { background: #2563eb; }

/* ── Flash Message ── */
.flash {
  padding: 14px 20px; border-radius: 10px; font-size: 13px; font-weight: 500;
  margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
  animation: flashIn 0.3s ease;
}
@keyframes flashIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
.flash-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.flash-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* ── Tabs ── */
.tab-nav {
  display: flex; gap: 4px; flex-wrap: wrap;
  background: var(--white); border: 1px solid var(--border);
  border-radius: 14px; padding: 6px;
  margin-bottom: 24px; overflow-x: auto;
}
.tab-link {
  padding: 10px 18px; border-radius: 10px; font-size: 12px; font-weight: 600;
  text-decoration: none; color: var(--muted); white-space: nowrap;
  transition: all 0.2s; display: flex; align-items: center; gap: 6px;
}
.tab-link:hover { color: var(--text); background: #f1f5f9; }
.tab-link.active { background: var(--accent); color: #fff; }

/* ── Card ── */
.card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: 14px; padding: 28px; margin-bottom: 20px;
}
.card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px; flex-wrap: wrap; gap: 10px;
}
.card-header h3 {
  font-size: 15px; font-weight: 700; color: var(--navy);
  display: flex; align-items: center; gap: 8px;
}
.card-header h3 i { color: var(--accent); font-size: 16px; }

/* ── Form ── */
.form-group { margin-bottom: 18px; }
.form-group label {
  display: block; font-size: 12px; font-weight: 600; color: var(--navy);
  margin-bottom: 6px;
}
.form-group input, .form-group textarea, .form-group select {
  width: 100%; padding: 10px 14px; border: 1px solid var(--border);
  border-radius: 10px; font-size: 13px; font-family: 'Poppins', sans-serif;
  color: var(--text); transition: border-color 0.2s;
}
.form-group input:focus, .form-group textarea:focus, .form-group select:focus {
  outline: none; border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.form-group textarea { min-height: 100px; resize: vertical; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.form-hint { font-size: 10.5px; color: var(--muted); margin-top: 4px; }

/* ── Buttons ── */
.btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 20px; border-radius: 10px; font-size: 12px;
  font-weight: 600; border: none; cursor: pointer;
  font-family: 'Poppins', sans-serif; transition: all 0.2s;
}
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: #2563eb; }
.btn-success { background: var(--success); color: #fff; }
.btn-success:hover { background: #16a34a; }
.btn-danger { background: var(--error); color: #fff; }
.btn-danger:hover { background: #dc2626; }
.btn-outline {
  background: transparent; color: var(--accent);
  border: 1px solid var(--accent);
}
.btn-outline:hover { background: var(--accent); color: #fff; }
.btn-sm { padding: 6px 12px; font-size: 11px; border-radius: 8px; }

/* ── Table-like list ── */
.item-list { display: flex; flex-direction: column; gap: 10px; }
.item-row {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 18px; border-radius: 10px;
  background: #f8fafc; border: 1px solid var(--border);
  transition: box-shadow 0.2s;
}
.item-row:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
.item-row .item-body { flex: 1; min-width: 0; }
.item-row .item-title { font-size: 13px; font-weight: 600; color: var(--navy); }
.item-row .item-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }
.item-row .item-actions { display: flex; gap: 6px; flex-shrink: 0; }
.item-badge {
  display: inline-flex; padding: 3px 10px; border-radius: 6px;
  font-size: 10px; font-weight: 600;
}
.badge-active { background: #f0fdf4; color: #16a34a; }
.badge-inactive { background: #fef2f2; color: #ef4444; }
.item-icon {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 16px; flex-shrink: 0;
}

/* ── Profil items ── */
.profil-items { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
.profil-item-row { display: flex; gap: 8px; align-items: center; }
.profil-item-row input { flex: 1; }
.remove-item { background: #fee2e2; color: #ef4444; border: none; border-radius: 8px; width: 34px; height: 34px; cursor: pointer; font-size: 14px; transition: background 0.2s; }
.remove-item:hover { background: #fecaca; }
.add-item-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 14px; border-radius: 8px; font-size: 11px;
  font-weight: 600; cursor: pointer; border: 1px dashed var(--accent);
  background: #eff6ff; color: var(--accent); transition: all 0.2s;
  font-family: 'Poppins', sans-serif;
}
.add-item-btn:hover { background: var(--accent); color: #fff; border-style: solid; }

/* ── Galeri grid ── */
.galeri-admin-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 14px;
}
.galeri-admin-card {
  border-radius: 12px; overflow: hidden; position: relative;
  border: 1px solid var(--border);
}
.galeri-admin-card img { width: 100%; height: 140px; object-fit: cover; }
.galeri-admin-card .galeri-admin-info {
  padding: 10px 12px; font-size: 11px; color: var(--text);
}
.galeri-admin-card .galeri-del {
  position: absolute; top: 8px; right: 8px;
}

/* ── Edit modal (inline) ── */
.edit-panel {
  display: none; background: #f1f5f9; border: 1px solid var(--border);
  border-radius: 12px; padding: 20px; margin-top: 10px;
}
.edit-panel.open { display: block; animation: flashIn 0.2s ease; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .main { padding: 20px 14px; }
  .form-row, .form-row-3 { grid-template-columns: 1fr; }
  .tab-nav { gap: 2px; }
  .tab-link { padding: 8px 12px; font-size: 11px; }
}

/* ── Foto preview ── */
.foto-preview {
  width: 100px; height: 100px; border-radius: 12px;
  object-fit: cover; border: 2px solid var(--border);
  margin-bottom: 10px;
}
</style>
</head>

<body>
<div class="main" id="mainContent">

<!-- Page Header -->
<div class="page-header">
  <h2><i class="fas fa-desktop"></i> Kelola Halaman Pengunjung</h2>
  <a href="<?= BASE_URL ?>login_tamu.php" target="_blank" class="preview-btn">
    <i class="fas fa-external-link-alt"></i> Preview
  </a>
</div>

<!-- Flash -->
<?php if ($msg): ?>
<div class="flash flash-<?= $msg_type === 'error' ? 'error' : 'success' ?>">
  <i class="fas fa-<?= $msg_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Tab Navigation -->
<div class="tab-nav">
  <a href="?tab=sambutan" class="tab-link <?= $tab==='sambutan'?'active':'' ?>"><i class="fas fa-quote-left"></i> Sambutan</a>
  <a href="?tab=profil"   class="tab-link <?= $tab==='profil'?'active':'' ?>"><i class="fas fa-landmark"></i> Profil</a>
  <a href="?tab=struktur" class="tab-link <?= $tab==='struktur'?'active':'' ?>"><i class="fas fa-sitemap"></i> Struktur</a>
  <a href="?tab=faq"      class="tab-link <?= $tab==='faq'?'active':'' ?>"><i class="fas fa-circle-question"></i> FAQ</a>
  <a href="?tab=kontak"   class="tab-link <?= $tab==='kontak'?'active':'' ?>"><i class="fas fa-address-card"></i> Kontak</a>
  <a href="?tab=sosmed"   class="tab-link <?= $tab==='sosmed'?'active':'' ?>"><i class="fas fa-share-alt"></i> Sosmed</a>
  <a href="?tab=tautan"   class="tab-link <?= $tab==='tautan'?'active':'' ?>"><i class="fas fa-link"></i> Tautan</a>
  <a href="?tab=galeri"   class="tab-link <?= $tab==='galeri'?'active':'' ?>"><i class="fas fa-images"></i> Galeri</a>
</div>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TAB: SAMBUTAN -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if ($tab === 'sambutan'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-quote-left"></i> Kata Sambutan Admin</h3>
  </div>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_sambutan">

    <?php if (!empty($sambutan['foto'])): ?>
    <img src="<?= BASE_URL ?>uploads/visitor/<?= htmlspecialchars($sambutan['foto']) ?>" class="foto-preview" alt="Foto">
    <?php endif; ?>

    <div class="form-group">
      <label>Foto (opsional)</label>
      <input type="file" name="foto" accept="image/*">
      <div class="form-hint">Upload foto admin/pejabat. Kosongkan untuk mempertahankan foto sebelumnya.</div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Nama</label>
        <input type="text" name="nama" value="<?= htmlspecialchars($sambutan['nama'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Jabatan / Role</label>
        <input type="text" name="jabatan" value="<?= htmlspecialchars($sambutan['jabatan'] ?? '') ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label>Judul Sambutan</label>
      <input type="text" name="judul" value="<?= htmlspecialchars($sambutan['judul'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label>Isi Sambutan</label>
      <textarea name="isi" rows="6" required><?= htmlspecialchars($sambutan['isi'] ?? '') ?></textarea>
      <div class="form-hint">Gunakan Enter untuk paragraf baru. HTML tidak diizinkan.</div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
  </form>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TAB: PROFIL (Visi, Misi, Tugas, Fungsi) -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if ($tab === 'profil'): ?>
<?php
$profil_config = [
  'visi'   => ['label' => 'Visi',        'icon' => 'fa-eye',       'color' => '#3b82f6'],
  'misi'   => ['label' => 'Misi',        'icon' => 'fa-bullseye',  'color' => '#22c55e'],
  'tugas'  => ['label' => 'Tugas Pokok', 'icon' => 'fa-tasks',     'color' => '#f59e0b'],
  'fungsi' => ['label' => 'Fungsi',      'icon' => 'fa-cogs',      'color' => '#ec4899'],
];
foreach ($profil_config as $tipe => $cfg):
?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas <?= $cfg['icon'] ?>" style="color:<?= $cfg['color'] ?>"></i> <?= $cfg['label'] ?></h3>
  </div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_profil">
    <input type="hidden" name="tipe" value="<?= $tipe ?>">

    <div class="profil-items" id="profil-<?= $tipe ?>">
      <?php if (!empty($profil[$tipe])):
        foreach ($profil[$tipe] as $item): ?>
      <div class="profil-item-row">
        <input type="text" name="items[]" value="<?= htmlspecialchars($item['isi']) ?>" required>
        <button type="button" class="remove-item" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
      </div>
      <?php endforeach;
      else: ?>
      <div class="profil-item-row">
        <input type="text" name="items[]" placeholder="Masukkan <?= strtolower($cfg['label']) ?>..." required>
        <button type="button" class="remove-item" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
      </div>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
      <button type="button" class="add-item-btn" onclick="addProfilItem('profil-<?= $tipe ?>', '<?= strtolower($cfg['label']) ?>')">
        <i class="fas fa-plus"></i> Tambah <?= $cfg['label'] ?>
      </button>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan <?= $cfg['label'] ?></button>
    </div>
  </form>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TAB: STRUKTUR ORGANISASI -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if ($tab === 'struktur'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-sitemap"></i> Struktur Organisasi</h3>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addStruktur').classList.toggle('open')">
      <i class="fas fa-plus"></i> Tambah Jabatan
    </button>
  </div>

  <!-- Form Tambah -->
  <div class="edit-panel" id="addStruktur">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_struktur">
      <div class="form-row">
        <div class="form-group">
          <label>Nama Jabatan</label>
          <input type="text" name="nama" required placeholder="Contoh: Kepala Balai">
        </div>
        <div class="form-group">
          <label>Keterangan Jabatan</label>
          <input type="text" name="jabatan" required placeholder="Keterangan singkat">
        </div>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label>Level (1=Atas, 2=Bawah)</label>
          <select name="level"><option value="1">Level 1 (Pimpinan)</option><option value="2">Level 2 (Sub Bagian)</option><option value="3">Level 3</option></select>
        </div>
        <div class="form-group">
          <label>Urutan</label>
          <input type="number" name="urutan" value="0" min="0">
        </div>
        <div class="form-group">
          <label>Warna</label>
          <input type="color" name="warna" value="#3b82f6" style="height:40px;padding:4px">
        </div>
      </div>
      <div class="form-group">
        <label>Icon (Font Awesome class)</label>
        <input type="text" name="icon" value="fas fa-user-tie" placeholder="fas fa-user-tie">
        <div class="form-hint">Contoh: fas fa-user-tie, fas fa-folder-open, fas fa-chart-line</div>
      </div>
      <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Simpan</button>
    </form>
  </div>

  <!-- List -->
  <div class="item-list" style="margin-top:16px">
    <?php foreach ($struktur_list as $s): ?>
    <div class="item-row">
      <div class="item-icon" style="background:<?= htmlspecialchars($s['warna']) ?>">
        <i class="<?= htmlspecialchars($s['icon']) ?>"></i>
      </div>
      <div class="item-body">
        <div class="item-title"><?= htmlspecialchars($s['nama']) ?></div>
        <div class="item-sub"><?= htmlspecialchars($s['jabatan']) ?> • Level <?= (int)$s['level'] ?> • Urutan <?= (int)$s['urutan'] ?></div>
      </div>
      <div class="item-actions">
        <button class="btn btn-outline btn-sm" onclick="toggleEdit('edit-struktur-<?= $s['id'] ?>')"><i class="fas fa-pen"></i></button>
        <form method="post" style="display:inline" onsubmit="return confirm('Hapus jabatan ini?')">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_struktur">
          <input type="hidden" name="id" value="<?= $s['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
        </form>
      </div>
    </div>
    <!-- Edit panel -->
    <div class="edit-panel" id="edit-struktur-<?= $s['id'] ?>">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="edit_struktur">
        <input type="hidden" name="id" value="<?= $s['id'] ?>">
        <div class="form-row">
          <div class="form-group"><label>Nama</label><input type="text" name="nama" value="<?= htmlspecialchars($s['nama']) ?>" required></div>
          <div class="form-group"><label>Keterangan</label><input type="text" name="jabatan" value="<?= htmlspecialchars($s['jabatan']) ?>" required></div>
        </div>
        <div class="form-row-3">
          <div class="form-group"><label>Level</label><select name="level"><option value="1" <?= $s['level']==1?'selected':'' ?>>Level 1</option><option value="2" <?= $s['level']==2?'selected':'' ?>>Level 2</option><option value="3" <?= $s['level']==3?'selected':'' ?>>Level 3</option></select></div>
          <div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="<?= (int)$s['urutan'] ?>" min="0"></div>
          <div class="form-group"><label>Warna</label><input type="color" name="warna" value="<?= htmlspecialchars($s['warna']) ?>" style="height:40px;padding:4px"></div>
        </div>
        <div class="form-group"><label>Icon</label><input type="text" name="icon" value="<?= htmlspecialchars($s['icon']) ?>"></div>
        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Update</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TAB: FAQ -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if ($tab === 'faq'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-circle-question"></i> Pertanyaan Umum (FAQ)</h3>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addFaq').classList.toggle('open')">
      <i class="fas fa-plus"></i> Tambah FAQ
    </button>
  </div>

  <div class="edit-panel" id="addFaq">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_faq">
      <div class="form-group"><label>Pertanyaan</label><input type="text" name="pertanyaan" required></div>
      <div class="form-group"><label>Jawaban</label><textarea name="jawaban" required></textarea></div>
      <div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="0" min="0" style="max-width:120px"></div>
      <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Simpan</button>
    </form>
  </div>

  <div class="item-list" style="margin-top:16px">
    <?php foreach ($faq_list as $f): ?>
    <div class="item-row">
      <div class="item-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1)">
        <i class="fas fa-question"></i>
      </div>
      <div class="item-body">
        <div class="item-title"><?= htmlspecialchars($f['pertanyaan']) ?></div>
        <div class="item-sub"><?= htmlspecialchars(mb_strimwidth($f['jawaban'], 0, 100, '...')) ?></div>
        <span class="item-badge <?= $f['aktif'] ? 'badge-active' : 'badge-inactive' ?>"><?= $f['aktif'] ? 'Aktif' : 'Nonaktif' ?></span>
      </div>
      <div class="item-actions">
        <button class="btn btn-outline btn-sm" onclick="toggleEdit('edit-faq-<?= $f['id'] ?>')"><i class="fas fa-pen"></i></button>
        <form method="post" style="display:inline" onsubmit="return confirm('Hapus FAQ ini?')">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete_faq"><input type="hidden" name="id" value="<?= $f['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
        </form>
      </div>
    </div>
    <div class="edit-panel" id="edit-faq-<?= $f['id'] ?>">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="edit_faq"><input type="hidden" name="id" value="<?= $f['id'] ?>">
        <div class="form-group"><label>Pertanyaan</label><input type="text" name="pertanyaan" value="<?= htmlspecialchars($f['pertanyaan']) ?>" required></div>
        <div class="form-group"><label>Jawaban</label><textarea name="jawaban" required><?= htmlspecialchars($f['jawaban']) ?></textarea></div>
        <div class="form-row">
          <div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="<?= (int)$f['urutan'] ?>" min="0"></div>
          <div class="form-group"><label>Status</label><select name="aktif"><option value="1" <?= $f['aktif']?'selected':'' ?>>Aktif</option><option value="0" <?= !$f['aktif']?'selected':'' ?>>Nonaktif</option></select></div>
        </div>
        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Update</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TAB: KONTAK -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if ($tab === 'kontak'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-address-card"></i> Informasi Kontak</h3>
  </div>
  <div class="item-list">
    <?php foreach ($kontak_list as $kunci => $k): ?>
    <div class="item-row" style="flex-direction:column;align-items:stretch">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:10px">
        <div class="item-icon" style="background:<?= htmlspecialchars($k['warna']) ?>">
          <i class="<?= htmlspecialchars($k['icon']) ?>"></i>
        </div>
        <div class="item-body">
          <div class="item-title"><?= htmlspecialchars($k['label']) ?></div>
          <div class="item-sub"><?= nl2br(htmlspecialchars(mb_strimwidth($k['nilai'], 0, 120, '...'))) ?></div>
        </div>
        <button class="btn btn-outline btn-sm" onclick="toggleEdit('edit-kontak-<?= $kunci ?>')"><i class="fas fa-pen"></i> Edit</button>
      </div>
      <div class="edit-panel" id="edit-kontak-<?= $kunci ?>">
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="save_kontak"><input type="hidden" name="kunci" value="<?= $kunci ?>">
          <div class="form-row">
            <div class="form-group"><label>Label</label><input type="text" name="label" value="<?= htmlspecialchars($k['label']) ?>" required></div>
            <div class="form-group"><label>Icon</label><input type="text" name="icon" value="<?= htmlspecialchars($k['icon']) ?>"></div>
          </div>
          <div class="form-group">
            <label>Nilai / Isi</label>
            <textarea name="nilai" rows="3" required><?= htmlspecialchars($k['nilai']) ?></textarea>
            <div class="form-hint"><?= $kunci === 'google_maps' ? 'Paste URL embed Google Maps di sini' : 'Gunakan Enter untuk baris baru' ?></div>
          </div>
          <div class="form-group">
            <label>Warna Gradient</label>
            <input type="text" name="warna" value="<?= htmlspecialchars($k['warna']) ?>">
            <div class="form-hint">Contoh: linear-gradient(135deg,#3b82f6,#6366f1)</div>
          </div>
          <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Simpan</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TAB: SOSMED -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if ($tab === 'sosmed'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-share-alt"></i> Media Sosial</h3>
  </div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_sosmed">
    <div class="item-list">
      <?php foreach ($sosmed_list as $sm): ?>
      <div class="item-row">
        <input type="hidden" name="sosmed_id[]" value="<?= $sm['id'] ?>">
        <div class="item-icon" style="background:var(--accent)">
          <i class="<?= htmlspecialchars($sm['icon']) ?>"></i>
        </div>
        <div class="item-body" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <strong style="min-width:90px;font-size:13px"><?= htmlspecialchars($sm['platform']) ?></strong>
          <input type="text" name="sosmed_url[]" value="<?= htmlspecialchars($sm['url']) ?>" style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px">
          <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;white-space:nowrap">
            <input type="checkbox" name="sosmed_aktif[<?= $sm['id'] ?>]" <?= $sm['aktif'] ? 'checked' : '' ?>> Aktif
          </label>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:16px">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Semua</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TAB: TAUTAN -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if ($tab === 'tautan'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-link"></i> Tautan Terkait</h3>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addTautan').classList.toggle('open')">
      <i class="fas fa-plus"></i> Tambah Tautan
    </button>
  </div>

  <div class="edit-panel" id="addTautan">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="add_tautan">
      <div class="form-row">
        <div class="form-group"><label>Nama</label><input type="text" name="nama" required></div>
        <div class="form-group"><label>URL</label><input type="url" name="url" required placeholder="https://..."></div>
      </div>
      <div class="form-row-3">
        <div class="form-group"><label>Deskripsi</label><input type="text" name="deskripsi"></div>
        <div class="form-group"><label>Icon</label><input type="text" name="icon" value="fas fa-link"></div>
        <div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="0" min="0"></div>
      </div>
      <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Simpan</button>
    </form>
  </div>

  <div class="item-list" style="margin-top:16px">
    <?php foreach ($tautan_list as $t): ?>
    <div class="item-row">
      <div class="item-icon" style="background:linear-gradient(135deg,#2c5282,#3b82f6)">
        <i class="<?= htmlspecialchars($t['icon']) ?>"></i>
      </div>
      <div class="item-body">
        <div class="item-title"><?= htmlspecialchars($t['nama']) ?></div>
        <div class="item-sub"><?= htmlspecialchars($t['deskripsi'] ?? '') ?> • <a href="<?= htmlspecialchars($t['url']) ?>" target="_blank" style="color:var(--accent)"><?= htmlspecialchars(mb_strimwidth($t['url'], 0, 50, '...')) ?></a></div>
        <span class="item-badge <?= $t['aktif'] ? 'badge-active' : 'badge-inactive' ?>"><?= $t['aktif'] ? 'Aktif' : 'Nonaktif' ?></span>
      </div>
      <div class="item-actions">
        <button class="btn btn-outline btn-sm" onclick="toggleEdit('edit-tautan-<?= $t['id'] ?>')"><i class="fas fa-pen"></i></button>
        <form method="post" style="display:inline" onsubmit="return confirm('Hapus tautan ini?')">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete_tautan"><input type="hidden" name="id" value="<?= $t['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
        </form>
      </div>
    </div>
    <div class="edit-panel" id="edit-tautan-<?= $t['id'] ?>">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="edit_tautan"><input type="hidden" name="id" value="<?= $t['id'] ?>">
        <div class="form-row">
          <div class="form-group"><label>Nama</label><input type="text" name="nama" value="<?= htmlspecialchars($t['nama']) ?>" required></div>
          <div class="form-group"><label>URL</label><input type="url" name="url" value="<?= htmlspecialchars($t['url']) ?>" required></div>
        </div>
        <div class="form-row-3">
          <div class="form-group"><label>Deskripsi</label><input type="text" name="deskripsi" value="<?= htmlspecialchars($t['deskripsi'] ?? '') ?>"></div>
          <div class="form-group"><label>Icon</label><input type="text" name="icon" value="<?= htmlspecialchars($t['icon']) ?>"></div>
          <div class="form-group"><label>Urutan</label><input type="number" name="urutan" value="<?= (int)$t['urutan'] ?>" min="0"></div>
        </div>
        <div class="form-group"><label>Status</label><select name="aktif"><option value="1" <?= $t['aktif']?'selected':'' ?>>Aktif</option><option value="0" <?= !$t['aktif']?'selected':'' ?>>Nonaktif</option></select></div>
        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Update</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TAB: GALERI -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if ($tab === 'galeri'): ?>
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-images"></i> Galeri Kegiatan</h3>
  </div>

  <form method="post" enctype="multipart/form-data" style="margin-bottom:24px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_galeri">
    <div class="form-row-3">
      <div class="form-group"><label>Judul</label><input type="text" name="judul" required></div>
      <div class="form-group"><label>Kategori</label><input type="text" name="kategori" placeholder="Opsional"></div>
      <div class="form-group"><label>Gambar</label><input type="file" name="gambar" accept="image/*" required></div>
    </div>
    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-upload"></i> Upload</button>
  </form>

  <?php if (empty($galeri_list)): ?>
  <div style="text-align:center;padding:40px;color:var(--muted)">
    <i class="fas fa-images" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px"></i>
    <p>Belum ada foto galeri.</p>
  </div>
  <?php else: ?>
  <div class="galeri-admin-grid">
    <?php foreach ($galeri_list as $g): ?>
    <div class="galeri-admin-card">
      <img src="<?= BASE_URL ?>uploads/galeri/<?= htmlspecialchars($g['gambar']) ?>" alt="<?= htmlspecialchars($g['judul']) ?>">
      <div class="galeri-admin-info">
        <strong><?= htmlspecialchars(mb_strimwidth($g['judul'], 0, 30, '...')) ?></strong><br>
        <span style="color:var(--muted);font-size:10px"><?= htmlspecialchars($g['kategori'] ?? '—') ?> • <?= date('d/m/Y', strtotime($g['created_at'])) ?></span>
      </div>
      <form method="post" class="galeri-del" onsubmit="return confirm('Hapus foto ini?')">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete_galeri"><input type="hidden" name="id" value="<?= $g['id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

</div><!-- .main -->

<script>
function toggleEdit(id) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle('open');
}

function addProfilItem(containerId, label) {
  const container = document.getElementById(containerId);
  const row = document.createElement('div');
  row.className = 'profil-item-row';
  row.innerHTML = '<input type="text" name="items[]" placeholder="Masukkan ' + label + '..." required>'
    + '<button type="button" class="remove-item" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
  container.appendChild(row);
  row.querySelector('input').focus();
}
</script>
</body>
</html>
