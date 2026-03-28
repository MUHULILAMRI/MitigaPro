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
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #064e3b 0%, #065f46 40%, #047857 100%);
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(34,197,94,0.12), transparent 70%);
            top: -200px; right: -150px;
            border-radius: 50%;
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(16,185,129,0.1), transparent 70%);
            bottom: -100px; left: -80px;
            border-radius: 50%;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            z-index: 1;
        }

        .login-card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px 32px 32px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #22c55e, #10b981);
            color: #fff;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 auto 16px;
        }

        .login-logo { text-align: center; margin-bottom: 8px; }
        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px; height: 64px;
        }
        .logo-icon img { width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3)); }

        .login-card h1 {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin: 12px 0 4px;
        }
        .login-sub {
            text-align: center;
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            line-height: 1.5;
            margin-bottom: 28px;
        }

        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%;
            padding: 12px 40px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            font-size: 13.5px;
            font-family: 'Poppins', sans-serif;
            color: #fff;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .input-wrap input:focus {
            border-color: #22c55e;
            background: rgba(255,255,255,0.1);
        }
        .input-wrap input::placeholder { color: rgba(255,255,255,0.25); }
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: rgba(255,255,255,0.35);
            pointer-events: none;
        }
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: rgba(255,255,255,0.35);
            font-size: 14px;
            padding: 0;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: rgba(255,255,255,0.7); }

        .alert-error {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12.5px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #22c55e, #10b981);
            color: #fff;
            border: none;
            padding: 13px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s;
            margin-top: 6px;
            box-shadow: 0 4px 16px rgba(34,197,94,0.3);
        }
        .btn-login:hover { opacity: 0.9; transform: translateY(-1px); }

        .btn-login .btn-text { display: inline; }
        .btn-login .spinner {
            display: none;
            width: 22px; height: 22px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-login.loading .btn-text { display: none; }
        .btn-login.loading .spinner  { display: block; }

        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(6,78,59,0.9);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
        }
        .loading-overlay.active { display: flex; }
        .loading-wheel {
            width: 44px; height: 44px;
            border: 4px solid rgba(255,255,255,0.1);
            border-top-color: #22c55e;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .loading-label { font-size: 13px; color: rgba(255,255,255,0.5); font-weight: 500; }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            color: rgba(255,255,255,0.35);
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .back-link:hover { color: rgba(255,255,255,0.7); }
        .back-link i { font-size: 11px; }

        .login-footer {
            text-align: center;
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            margin-top: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-wheel"></div>
    <div class="loading-label">Sedang masuk...</div>
</div>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-icon"><img src="logo.png" alt="MitigaPro"></div>
        </div>
        <h1>MitigaPro</h1>
        <p class="login-sub">Balai Pengembangan Kompetensi PU<br>Wilayah VIII Makassar</p>
        <div style="text-align:center">
            <span class="role-badge"><i class="fas fa-chalkboard-teacher"></i> Pengajar</span>
        </div>

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
                <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Masuk sebagai Pengajar</span>
                <div class="spinner"></div>
            </button>
        </form>

        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke halaman utama</a>

        <p class="login-footer">
            &copy; <?= date('Y') ?> Kementerian PU<br>
            Hak cipta dilindungi undang-undang
        </p>
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
