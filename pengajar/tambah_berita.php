<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
require_role('admin');

if (isset($_POST['simpan'])) {
    if (!csrf_verify()) { header('Location: ' . $_SERVER['REQUEST_URI']); exit; }

    $judul    = trim($_POST['judul'] ?? '');
    $isi      = trim($_POST['isi'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $user_id  = (int) $_SESSION['user_id'];
    $gambar_name = null;

    // Upload gambar (opsional)
    if (!empty($_FILES['gambar']['name']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($_FILES['gambar']['tmp_name']);

        if (in_array($mime, $allowed, true) && $_FILES['gambar']['size'] <= 2 * 1024 * 1024) {
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
                default      => 'jpg',
            };
            $gambar_name = 'berita_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = UPLOAD_PATH . 'berita/';
            if (!is_dir($dest)) mkdir($dest, 0755, true);
            move_uploaded_file($_FILES['gambar']['tmp_name'], $dest . $gambar_name);
        }
    }

    $link = trim($_POST['link'] ?? '');
    if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) { $link = null; }

    $stmt = $conn->prepare("INSERT INTO berita_pelatihan (judul, isi, kategori, gambar, link, user_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssi', $judul, $isi, $kategori, $gambar_name, $link, $user_id);
    $stmt->execute();
    $stmt->close();

    header('Location: kelola_berita.php?success=added');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Berita Pelatihan | MitigaPro</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--green:#22c55e;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy);min-height:100vh}

.form-card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);width:100%;max-width:680px;overflow:hidden;margin:0 auto}
.form-header{background:linear-gradient(135deg,var(--navy),var(--blue));padding:24px 28px;color:#fff}
.form-header h2{font-size:18px;font-weight:700;display:flex;align-items:center;gap:10px}
.form-header p{font-size:12px;opacity:.7;margin-top:4px}
.form-body{padding:24px 28px}

.fg{margin-bottom:16px}
.fg label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--navy)}
.fg label i{margin-right:4px;color:var(--accent);font-size:11px}
.fg input,.fg textarea,.fg select{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:'Poppins',sans-serif;transition:border-color .2s}
.fg input:focus,.fg textarea:focus,.fg select:focus{outline:none;border-color:var(--accent)}
.fg input[type="file"]{padding:8px 12px;background:#f8fafc}

.form-actions{display:flex;gap:10px;margin-top:20px}
.btn{padding:10px 24px;border-radius:8px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;text-decoration:none;border:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s}
.btn:hover{opacity:.85}
.btn-primary{background:var(--accent);color:#fff}
.btn-secondary{background:#f1f5f9;color:var(--navy);border:1px solid var(--border)}
.hint{font-size:11px;color:var(--muted);margin-top:4px}
</style>
</head>
<body>

<?php require INCLUDE_PATH . 'sidebar_pengajar.php'; ?>

<div id="mainContent" class="main-content" style="padding:30px 20px">
<?= breadcrumb([['label' => 'Berita Pelatihan', 'url' => 'kelola_berita.php'], ['label' => 'Tambah Berita']]) ?>

<div class="form-card">
  <div class="form-header">
    <h2><i class="fas fa-newspaper"></i> Tambah Berita Pelatihan</h2>
    <p>Buat informasi atau berita pelatihan baru</p>
  </div>
  <div class="form-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="fg">
        <label><i class="fas fa-heading"></i> Judul Berita</label>
        <input type="text" name="judul" required placeholder="Masukkan judul berita" maxlength="255">
      </div>

      <div class="fg">
        <label><i class="fas fa-tag"></i> Kategori</label>
        <select name="kategori">
          <option value="">-- Pilih Kategori --</option>
          <option value="Informasi">Informasi</option>
          <option value="Pengumuman">Pengumuman</option>
          <option value="Jadwal">Jadwal Pelatihan</option>
          <option value="Hasil">Hasil Pelatihan</option>
          <option value="Lainnya">Lainnya</option>
        </select>
      </div>

      <div class="fg">
        <label><i class="fas fa-align-left"></i> Isi Berita</label>
        <textarea name="isi" rows="10" required placeholder="Tulis isi berita pelatihan..."></textarea>
      </div>

      <div class="fg">
        <label><i class="fas fa-link"></i> Link (opsional)</label>
        <input type="url" name="link" placeholder="https://contoh.com/informasi-pelatihan">
        <div class="hint">Masukkan URL lengkap jika ingin menautkan ke sumber eksternal.</div>
      </div>

      <div class="fg">
        <label><i class="fas fa-image"></i> Gambar (opsional)</label>
        <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp,image/gif">
        <div class="hint">Format: JPG, PNG, WebP, GIF. Maks 2MB.</div>
      </div>

      <div class="form-actions">
        <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        <a href="kelola_berita.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
      </div>
    </form>
  </div>
</div>
</div>

</body>
</html>
