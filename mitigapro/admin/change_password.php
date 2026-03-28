<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

require INCLUDE_PATH . 'sidebar_mitigapro.php';

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { header('Location: ' . $_SERVER['REQUEST_URI']); exit; }

    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $msg = 'Semua field wajib diisi.'; $msg_type = 'err';
    } elseif (strlen($new) < 6) {
        $msg = 'Password baru minimal 6 karakter.'; $msg_type = 'err';
    } elseif ($new !== $confirm) {
        $msg = 'Konfirmasi password tidak cocok.'; $msg_type = 'err';
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($current, $user['password'])) {
            $msg = 'Password lama salah.'; $msg_type = 'err';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            $msg = 'Password berhasil diubah!'; $msg_type = 'ok';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ganti Password | MitigaPro</title>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_mitigapro.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root { --navy:#1a2744; --blue:#2c5282; --accent:#3b82f6; --bg:#f5f7fb; --white:#fff; --border:#e2e8f0; --muted:#64748b; --radius:12px; }
body { font-family:'Poppins',sans-serif; background:var(--bg); min-height:100vh; display:flex; color:var(--navy); }

.main-content { margin-left:260px; flex:1; padding:40px; transition:margin-left .3s; }
.main-content.expanded { margin-left:72px; }
@media(max-width:768px){ .main-content{margin-left:0;padding:20px} }

.page-title { font-size:22px; font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:10px; }
.page-title i { color:var(--accent); }
.page-sub { font-size:13px; color:var(--muted); margin-bottom:28px; }

.card {
  background:var(--white); border-radius:var(--radius); border:1px solid var(--border);
  padding:28px 32px; max-width:500px;
}

.fg { margin-bottom:18px; }
.fg label { display:block; font-size:12px; font-weight:600; margin-bottom:6px; color:var(--navy); }
.fg input {
  width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:8px;
  font-size:14px; font-family:'Poppins',sans-serif; transition:border-color .2s;
}
.fg input:focus { outline:none; border-color:var(--accent); }

.btn-submit {
  padding:10px 28px; background:var(--accent); color:#fff; border:none; border-radius:8px;
  font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer;
  transition:opacity .2s;
}
.btn-submit:hover { opacity:.85; }

.alert {
  padding:10px 16px; border-radius:8px; font-size:12px; font-weight:600; margin-bottom:18px;
}
.alert-ok  { background:#dcfce7; color:#15803d; }
.alert-err { background:#fef2f2; color:#dc2626; }
</style>
</head>
<body>

<div class="main-content" id="mainContent">
  <h1 class="page-title"><i class="fas fa-key"></i> Ganti Password</h1>
  <p class="page-sub">Ubah password akun Anda. Password lama diperlukan untuk verifikasi.</p>

  <div class="card">
    <?php if ($msg): ?>
      <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field() ?>

      <div class="fg">
        <label><i class="fas fa-lock"></i> Password Lama</label>
        <input type="password" name="current_password" required>
      </div>

      <div class="fg">
        <label><i class="fas fa-lock"></i> Password Baru</label>
        <input type="password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter">
      </div>

      <div class="fg">
        <label><i class="fas fa-lock"></i> Konfirmasi Password Baru</label>
        <input type="password" name="confirm_password" required>
      </div>

      <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan Password</button>
    </form>
  </div>
</div>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('collapsed');
  document.getElementById('mainContent').classList.toggle('expanded');
});
</script>
</body>
</html>
