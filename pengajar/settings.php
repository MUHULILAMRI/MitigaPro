<?php
require $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

require_role('admin', 'pengajar');

require INCLUDE_PATH . 'sidebar_pengajar.php';

// Ambil data user
$stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: ' . BASE_URL . 'logout.php');
    exit;
}

$msg = '';
$msg_type = '';

// Proses ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_verify()) { header('Location: ' . $_SERVER['REQUEST_URI']); exit; }

    if ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

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
            $pwd = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$pwd || !password_verify($current, $pwd['password'])) {
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

    if ($_POST['action'] === 'change_username') {
        $new_username = trim($_POST['new_username'] ?? '');

        if ($new_username === '') {
            $msg = 'Username tidak boleh kosong.'; $msg_type = 'err';
        } elseif (strlen($new_username) < 3) {
            $msg = 'Username minimal 3 karakter.'; $msg_type = 'err';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
            $msg = 'Username hanya boleh huruf, angka, dan underscore.'; $msg_type = 'err';
        } else {
            // Cek duplikat
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param('si', $new_username, $_SESSION['user_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $msg = 'Username sudah digunakan.'; $msg_type = 'err';
            } else {
                $stmt2 = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt2->bind_param('si', $new_username, $_SESSION['user_id']);
                $stmt2->execute();
                $stmt2->close();
                $_SESSION['username'] = $new_username;
                $user['username'] = $new_username;
                $msg = 'Username berhasil diubah!'; $msg_type = 'ok';
            }
            $stmt->close();
        }
    }
}

$role_label = match ($user['role']) {
    'admin' => 'Administrator',
    'pengajar' => 'Pengajar',
    default => 'Pengguna',
};
$joined = date('d F Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan Akun | MitigaPro</title>
<link rel="stylesheet" href="<?= BASE_URL ?>1_css/sidebar_pengajar.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#1a2744;--blue:#2c5282;--accent:#3b82f6;--green:#22c55e;--red:#ef4444;--bg:#f5f7fb;--white:#fff;--border:#e2e8f0;--muted:#64748b;--radius:12px}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--navy)}

.page-wrap{max-width:800px;margin:0 auto;padding:30px 24px 60px}

.page-header{background:linear-gradient(135deg,var(--navy),var(--blue));border-radius:var(--radius);padding:28px 32px;color:#fff;margin-bottom:28px}
.page-header h1{font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px}
.page-header p{font-size:12px;opacity:.7;margin-top:4px}

/* Profile Card */
.profile-card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;gap:24px}
.profile-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--blue));display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;flex-shrink:0}
.profile-info h2{font-size:18px;font-weight:700;margin-bottom:2px}
.profile-info .role-badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:600;background:#eff6ff;color:var(--accent)}
.profile-meta{display:flex;gap:20px;margin-top:8px;font-size:12px;color:var(--muted)}
.profile-meta i{margin-right:4px}

@media(max-width:500px){
    .profile-card{flex-direction:column;text-align:center}
    .profile-meta{justify-content:center;flex-wrap:wrap}
}

/* Cards */
.card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);padding:24px 28px;margin-bottom:20px}
.card-title{font-size:14px;font-weight:700;color:var(--navy);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.card-title i{color:var(--accent);font-size:14px}

/* Form */
.fg{margin-bottom:16px}
.fg label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--navy)}
.fg label i{margin-right:4px;color:var(--accent);font-size:11px}
.fg input{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:'Poppins',sans-serif;transition:border-color .2s}
.fg input:focus{outline:none;border-color:var(--accent)}
.fg input[readonly]{background:#f8fafc;color:var(--muted)}
.fg .hint{font-size:11px;color:var(--muted);margin-top:3px}

.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:500px){.form-row{grid-template-columns:1fr}}

.btn{padding:10px 24px;border-radius:8px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;text-decoration:none;border:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .2s}
.btn:hover{opacity:.85}
.btn-primary{background:var(--accent);color:#fff}
.btn-success{background:var(--green);color:#fff}

/* Alerts */
.alert{padding:12px 16px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-ok{background:#dcfce7;color:#15803d}
.alert-err{background:#fef2f2;color:#dc2626}
.alert i{font-size:14px}
</style>
</head>
<body>

<div id="mainContent" class="main-content">
<div class="page-wrap">
    <?= breadcrumb([['label' => 'Pengaturan']]) ?>

    <div class="page-header">
        <h1><i class="fas fa-cog"></i> Pengaturan Akun</h1>
        <p>Kelola profil dan keamanan akun Anda</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>">
        <i class="fas fa-<?= $msg_type === 'ok' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Profile Overview -->
    <div class="profile-card">
        <div class="profile-avatar"><i class="fas fa-user"></i></div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <span class="role-badge"><?= $role_label ?></span>
            <div class="profile-meta">
                <span><i class="fas fa-id-badge"></i> ID: <?= $user['id'] ?></span>
                <span><i class="fas fa-calendar"></i> Bergabung: <?= $joined ?></span>
            </div>
        </div>
    </div>

    <!-- Ubah Username -->
    <div class="card">
        <div class="card-title"><i class="fas fa-user-edit"></i> Ubah Username</div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_username">
            <div class="fg">
                <label><i class="fas fa-at"></i> Username Baru</label>
                <input type="text" name="new_username" value="<?= htmlspecialchars($user['username']) ?>" required minlength="3" pattern="[a-zA-Z0-9_]+">
                <div class="hint">Hanya huruf, angka, dan underscore. Minimal 3 karakter.</div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Username</button>
        </form>
    </div>

    <!-- Ganti Password -->
    <div class="card">
        <div class="card-title"><i class="fas fa-lock"></i> Ganti Password</div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="fg">
                <label><i class="fas fa-key"></i> Password Lama</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-row">
                <div class="fg">
                    <label><i class="fas fa-lock"></i> Password Baru</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter">
                </div>
                <div class="fg">
                    <label><i class="fas fa-lock"></i> Konfirmasi Password</label>
                    <input type="password" name="confirm_password" required minlength="6" placeholder="Ketik ulang password baru">
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="fas fa-shield-alt"></i> Ubah Password</button>
        </form>
    </div>

    <!-- Info Sistem -->
    <div class="card">
        <div class="card-title"><i class="fas fa-info-circle"></i> Informasi Sistem</div>
        <div class="form-row">
            <div class="fg">
                <label>Aplikasi</label>
                <input type="text" value="MitigaPro v1.0" readonly>
            </div>
            <div class="fg">
                <label>Role Akun</label>
                <input type="text" value="<?= $role_label ?>" readonly>
            </div>
        </div>
        <div class="form-row">
            <div class="fg">
                <label>Server</label>
                <input type="text" value="<?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?>" readonly>
            </div>
            <div class="fg">
                <label>PHP Version</label>
                <input type="text" value="<?= phpversion() ?>" readonly>
            </div>
        </div>
    </div>

</div>
</div>

</body>
</html>
