<?php
/**
 * setup_admin.php — Buat akun admin pertama kali
 * Jalankan halaman ini SEKALI di browser: http://localhost/MitigaPro/setup_admin.php
 * HAPUS file ini setelah dijalankan!
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'admin';

    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password=VALUES(password), role=VALUES(role)");
        $stmt->bind_param('sss', $username, $hash, $role);
        if ($stmt->execute()) {
            $message = "✅ Akun <strong>$username</strong> berhasil dibuat/diperbarui dengan role <strong>$role</strong>.";
            $message .= '<br><strong style="color:red">Segera hapus file setup_admin.php ini!</strong>';
        } else {
            $message = "❌ Gagal: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "⚠️ Username dan password tidak boleh kosong.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup Admin — MitigaPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins',sans-serif; background:#f4f7fb; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
        .card { background:#fff; padding:40px; border-radius:16px; box-shadow:0 8px 30px rgba(0,0,0,0.1); max-width:420px; width:100%; }
        h2 { color:#1e3c72; margin:0 0 6px; }
        p.sub { color:#888; font-size:13px; margin:0 0 24px; }
        label { font-size:13px; font-weight:600; color:#444; display:block; margin-bottom:5px; }
        input, select { width:100%; padding:10px 14px; border:1.5px solid #d0d8e8; border-radius:8px; font-size:14px; margin-bottom:16px; font-family:'Poppins',sans-serif; box-sizing:border-box; }
        button { width:100%; background:#1e3c72; color:#fff; border:none; padding:12px; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; }
        .msg { padding:12px; border-radius:8px; background:#f0fff4; border:1px solid #2ecc71; margin-bottom:16px; font-size:13px; }
        .warning { background:#fff8e1; border-color:#f39c12; color:#e67e22; padding:12px; border-radius:8px; font-size:12px; margin-bottom:20px; }
    </style>
</head>
<body>
<div class="card">
    <h2>🛡️ Setup Admin MitigaPro</h2>
    <p class="sub">Buat atau perbarui akun pengguna</p>

    <div class="warning">
        ⚠️ <strong>Keamanan:</strong> Hapus file <code>setup_admin.php</code> dari server setelah digunakan!
    </div>

    <?php if ($message): ?>
        <div class="msg"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" required placeholder="Contoh: admin">

        <label>Password</label>
        <input type="password" name="password" required placeholder="Min. 8 karakter">

        <label>Role</label>
        <select name="role">
            <option value="admin">Admin</option>
            <option value="pengajar">Pengajar</option>
            <option value="user">User</option>
        </select>

        <button type="submit">Buat Akun</button>
    </form>

    <p style="margin-top:20px; text-align:center; font-size:13px;">
        <a href="/MitigaPro/login.php">← Kembali ke Login</a>
    </p>
</div>
</body>
</html>
