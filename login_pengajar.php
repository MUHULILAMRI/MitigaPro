<?php
// login_pengajar.php — Login Pengajar MitigaPro
session_start();

if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'pengajar') {
    header('Location: index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/MitigaPro/include/autoload.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'pengajar' LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah, atau bukan akun Pengajar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengajar — MitigaPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{min-height:100vh;display:flex;font-family:'Poppins',sans-serif;background:#f0f4f8;overflow-x:hidden}

        .left-panel{
            width:45%;min-height:100vh;
            background:linear-gradient(160deg,#064e3b 0%,#065f46 40%,#047857 100%);
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:60px 40px;position:relative;overflow:hidden;
        }
        .left-panel::before{content:'';position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(34,197,94,0.15),transparent 70%);top:-100px;right:-100px;border-radius:50%}
        .left-panel::after{content:'';position:absolute;width:350px;height:350px;background:radial-gradient(circle,rgba(16,185,129,0.1),transparent 70%);bottom:-80px;left:-60px;border-radius:50%}
        .left-content{position:relative;z-index:1;text-align:center;max-width:340px}
        .left-content .logo{width:80px;height:80px;margin:0 auto 18px}
        .left-content .logo img{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 6px 20px rgba(0,0,0,0.4))}
        .left-content h1{font-size:32px;font-weight:800;color:#fff;margin-bottom:8px}
        .left-content .tagline{font-size:13px;color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:30px}
        .left-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);color:#86efac;padding:8px 20px;border-radius:20px;font-size:12px;font-weight:600}
        .left-badge i{font-size:14px}

        .right-panel{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 30px}
        .form-card{width:100%;max-width:420px;background:#fff;border-radius:20px;padding:40px 36px;box-shadow:0 4px 24px rgba(0,0,0,0.06)}
        .form-card h2{font-size:22px;font-weight:700;color:#1a2744;margin-bottom:4px}
        .form-card .sub{font-size:13px;color:#64748b;margin-bottom:28px}

        .role-tag{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#22c55e,#10b981);color:#fff;padding:5px 14px;border-radius:16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:24px}

        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:12.5px;font-weight:600;color:#475569;margin-bottom:6px}
        .input-wrap{position:relative}
        .input-wrap input{width:100%;padding:12px 14px 12px 42px;background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;font-size:13.5px;font-family:'Poppins',sans-serif;color:#1a2744;outline:none;transition:border-color .2s,background .2s}
        .input-wrap input:focus{border-color:#22c55e;background:#fff}
        .input-wrap input::placeholder{color:#94a3b8}
        .input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:14px;color:#94a3b8;pointer-events:none}
        .toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:14px;padding:0;transition:color .2s}
        .toggle-pw:hover{color:#475569}

        .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:10px;font-size:12.5px;margin-bottom:18px;display:flex;align-items:center;gap:8px}

        .btn-login{width:100%;background:linear-gradient(135deg,#22c55e,#10b981);color:#fff;border:none;padding:13px;border-radius:12px;font-size:14px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:opacity .2s,transform .2s;margin-top:6px;box-shadow:0 4px 16px rgba(34,197,94,0.25)}
        .btn-login:hover{opacity:.9;transform:translateY(-1px)}
        .btn-login .btn-text{display:inline}
        .btn-login .spinner{display:none;width:22px;height:22px;border:3px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto}
        @keyframes spin{to{transform:rotate(360deg)}}
        .btn-login.loading .btn-text{display:none}
        .btn-login.loading .spinner{display:block}

        .back-link{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:22px;color:#94a3b8;text-decoration:none;font-size:12.5px;font-weight:500;transition:color .2s}
        .back-link:hover{color:#22c55e}
        .login-footer{text-align:center;font-size:11px;color:#cbd5e1;margin-top:20px;line-height:1.6}

        .loading-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(255,255,255,0.92);align-items:center;justify-content:center;flex-direction:column;gap:16px}
        .loading-overlay.active{display:flex}
        .loading-wheel{width:44px;height:44px;border:4px solid #e2e8f0;border-top-color:#22c55e;border-radius:50%;animation:spin 1s linear infinite}
        .loading-label{font-size:13px;color:#64748b;font-weight:500}

        @media(max-width:900px){
            body{flex-direction:column}
            .left-panel{width:100%;min-height:auto;padding:40px 24px}
            .left-content h1{font-size:26px}
        }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-wheel"></div>
    <div class="loading-label">Sedang masuk...</div>
</div>

<div class="left-panel">
    <div class="left-content">
        <div class="logo"><img src="logo.png" alt="MitigaPro"></div>
        <h1>MitigaPro</h1>
        <p class="tagline">Sistem Informasi Balai Pengembangan Kompetensi PU<br>Wilayah VIII Makassar</p>
        <div class="left-badge"><i class="fas fa-chalkboard-teacher"></i> Panel Pengajar</div>
    </div>
</div>

<div class="right-panel">
    <div class="form-card">
        <span class="role-tag"><i class="fas fa-chalkboard-teacher"></i> Pengajar</span>
        <h2>Masuk sebagai Pengajar</h2>
        <p class="sub">Silakan masukkan username dan password Anda</p>

        <?php if ($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username"
                           placeholder="Username pengajar"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password"
                           placeholder="Password pengajar"
                           autocomplete="current-password" required>
                    <button type="button" class="toggle-pw" id="togglePw" title="Tampilkan/sembunyikan">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="btnLogin">
                <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Masuk</span>
                <div class="spinner"></div>
            </button>
        </form>

        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke halaman utama</a>
        <p class="login-footer">&copy; <?= date('Y') ?> Kementerian PU &mdash; Hak cipta dilindungi</p>
    </div>
</div>

<script>
document.getElementById('togglePw').addEventListener('click', function () {
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    const isHidden = pw.type === 'password';
    pw.type = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
});
document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = document.getElementById('btnLogin');
    btn.classList.add('loading');
    btn.disabled = true;
    document.getElementById('loadingOverlay').classList.add('active');
});
</script>
</body>
</html>
