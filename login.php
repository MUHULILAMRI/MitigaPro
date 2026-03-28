<?php
// login.php — Halaman pilih role login MitigaPro
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MitigaPro</title>
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
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #1a2744 100%);
            overflow: hidden;
            position: relative;
        }

        /* Animated background circles */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.07;
            animation: float 8s ease-in-out infinite;
        }
        body::before {
            width: 500px; height: 500px;
            background: #3b82f6;
            top: -150px; right: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: #f59e0b;
            bottom: -120px; left: -80px;
            animation-delay: 4s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }

        .landing-wrapper {
            text-align: center;
            z-index: 1;
            padding: 20px;
            max-width: 940px;
            width: 100%;
        }

        .landing-logo {
            width: 80px; height: 80px;
            margin: 0 auto 16px;
        }
        .landing-logo img {
            width: 100%; height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
        }

        .landing-title {
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .landing-sub {
            font-size: 13px;
            color: rgba(255,255,255,0.55);
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .landing-label {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .role-cards {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .role-card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px 36px 32px;
            width: 280px;
            text-decoration: none;
            color: #fff;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .role-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 20px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .role-card:hover {
            transform: translateY(-6px);
            border-color: rgba(255,255,255,0.25);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .role-card.admin::before { background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(99,102,241,0.1)); }
        .role-card.pengajar::before { background: linear-gradient(135deg, rgba(34,197,94,0.15), rgba(16,185,129,0.1)); }
        .role-card.tamu::before { background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(251,191,36,0.1)); }
        .role-card:hover::before { opacity: 1; }

        .role-icon {
            width: 72px; height: 72px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 18px;
            position: relative;
            z-index: 1;
        }
        .role-card.admin .role-icon {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            box-shadow: 0 8px 24px rgba(59,130,246,0.3);
        }
        .role-card.pengajar .role-icon {
            background: linear-gradient(135deg, #22c55e, #10b981);
            box-shadow: 0 8px 24px rgba(34,197,94,0.3);
        }
        .role-card.tamu .role-icon {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            box-shadow: 0 8px 24px rgba(245,158,11,0.3);
        }

        .role-card h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        .role-card p {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .role-arrow {
            margin-top: 18px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,0.4);
            transition: all 0.3s;
            position: relative;
            z-index: 1;
        }
        .role-card:hover .role-arrow {
            color: #fff;
            gap: 10px;
        }

        .landing-footer {
            margin-top: 48px;
            font-size: 11px;
            color: rgba(255,255,255,0.25);
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .role-cards { flex-wrap: wrap; justify-content: center; }
        }
        @media (max-width: 620px) {
            .role-cards { flex-direction: column; align-items: center; }
            .landing-title { font-size: 26px; }
        }
    </style>
</head>
<body>

<div class="landing-wrapper">
    <div class="landing-logo"><img src="logo.png" alt="MitigaPro"></div>
    <h1 class="landing-title">MitigaPro</h1>
    <p class="landing-sub">Sistem Informasi Balai Pengembangan Kompetensi PU<br>Wilayah VIII Makassar</p>

    <div class="landing-label">Masuk Sebagai</div>

    <div class="role-cards">
        <a href="login_admin.php" class="role-card admin">
            <div class="role-icon"><i class="fas fa-shield-halved"></i></div>
            <h2>Administrator</h2>
            <p>Kelola data pengajar, dinas, pelatihan, dan berita informasi</p>
            <div class="role-arrow">Masuk <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="login_pengajar.php" class="role-card pengajar">
            <div class="role-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <h2>Pengajar</h2>
            <p>Lihat data pelatihan, informasi dinas, dan berita terbaru</p>
            <div class="role-arrow">Masuk <i class="fas fa-arrow-right"></i></div>
        </a>

        <a href="login_tamu.php" class="role-card tamu">
            <div class="role-icon"><i class="fas fa-eye"></i></div>
            <h2>Pengunjung</h2>
            <p>Jelajahi informasi pelatihan dan berita tanpa perlu akun</p>
            <div class="role-arrow">Lihat Data <i class="fas fa-arrow-right"></i></div>
        </a>
    </div>

    <p class="landing-footer">
        &copy; <?= date('Y') ?> Kementerian Pekerjaan Umum<br>
        Hak cipta dilindungi undang-undang
    </p>
</div>

</body>
</html>
