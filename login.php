<?php
// login.php — Halaman pilih role login MitigaPro
session_start();

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
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
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{min-height:100vh;display:flex;font-family:'Poppins',sans-serif;background:#f0f4f8;overflow-x:hidden}

        /* ── Left Panel ── */
        .left-panel{
            width:45%;min-height:100vh;
            background:linear-gradient(160deg,#0f172a 0%,#1e3a5f 50%,#1a2744 100%);
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:60px 40px;position:relative;overflow:hidden;
        }
        .left-panel::before{
            content:'';position:absolute;width:500px;height:500px;
            background:radial-gradient(circle,rgba(59,130,246,0.15),transparent 70%);
            top:-100px;right:-100px;border-radius:50%;
        }
        .left-panel::after{
            content:'';position:absolute;width:350px;height:350px;
            background:radial-gradient(circle,rgba(245,158,11,0.1),transparent 70%);
            bottom:-80px;left:-60px;border-radius:50%;
        }
        .left-content{position:relative;z-index:1;text-align:center;max-width:380px}
        .left-content .logo{width:90px;height:90px;margin:0 auto 20px}
        .left-content .logo img{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 6px 20px rgba(0,0,0,0.4))}
        .left-content h1{font-size:36px;font-weight:800;color:#fff;letter-spacing:1px;margin-bottom:8px}
        .left-content .tagline{font-size:13px;color:rgba(255,255,255,0.55);line-height:1.7;margin-bottom:32px}
        .features{text-align:left;display:flex;flex-direction:column;gap:14px}
        .features .feat{display:flex;align-items:flex-start;gap:12px;color:rgba(255,255,255,0.65);font-size:12.5px;line-height:1.5}
        .features .feat i{color:#3b82f6;font-size:16px;margin-top:2px;min-width:20px}

        /* ── Right Panel ── */
        .right-panel{
            flex:1;display:flex;align-items:center;justify-content:center;
            padding:40px 30px;
        }
        .right-inner{width:100%;max-width:520px}
        .right-inner .welcome{font-size:24px;font-weight:700;color:#1a2744;margin-bottom:6px}
        .right-inner .welcome-sub{font-size:13px;color:#64748b;margin-bottom:36px}

        .role-cards{display:flex;flex-direction:column;gap:16px}
        .role-card{
            display:flex;align-items:center;gap:18px;
            background:#fff;border:2px solid #e2e8f0;border-radius:16px;
            padding:22px 24px;text-decoration:none;color:#1a2744;
            transition:all .25s ease;position:relative;overflow:hidden;
        }
        .role-card:hover{
            border-color:transparent;
            transform:translateX(6px);
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
        }
        .role-card.admin:hover{border-color:#3b82f6;background:linear-gradient(135deg,#eff6ff,#fff)}
        .role-card.pengajar:hover{border-color:#22c55e;background:linear-gradient(135deg,#ecfdf5,#fff)}
        .role-card.tamu:hover{border-color:#f59e0b;background:linear-gradient(135deg,#fffbeb,#fff)}

        .role-icon{
            width:56px;height:56px;border-radius:14px;
            display:flex;align-items:center;justify-content:center;
            font-size:22px;color:#fff;flex-shrink:0;
        }
        .role-card.admin .role-icon{background:linear-gradient(135deg,#3b82f6,#6366f1);box-shadow:0 6px 18px rgba(59,130,246,0.25)}
        .role-card.pengajar .role-icon{background:linear-gradient(135deg,#22c55e,#10b981);box-shadow:0 6px 18px rgba(34,197,94,0.25)}
        .role-card.tamu .role-icon{background:linear-gradient(135deg,#f59e0b,#fbbf24);box-shadow:0 6px 18px rgba(245,158,11,0.25)}

        .role-info{flex:1}
        .role-info h2{font-size:16px;font-weight:700;margin-bottom:3px}
        .role-info p{font-size:12px;color:#64748b;line-height:1.5}
        .role-arrow{font-size:18px;color:#cbd5e1;transition:all .25s}
        .role-card:hover .role-arrow{color:#1a2744;transform:translateX(4px)}

        .landing-footer{margin-top:40px;font-size:11px;color:#94a3b8;line-height:1.6;text-align:center}

        @media(max-width:900px){
            body{flex-direction:column}
            .left-panel{width:100%;min-height:auto;padding:40px 24px}
            .left-content h1{font-size:28px}
            .features{display:none}
        }
    </style>
</head>
<body>

<div class="left-panel">
    <div class="left-content">
        <div class="logo"><img src="logo.png" alt="MitigaPro"></div>
        <h1>MitigaPro</h1>
        <p class="tagline">Sistem Informasi Balai Pengembangan Kompetensi PU<br>Wilayah VIII Makassar</p>
        <div class="features">
            <div class="feat"><i class="fas fa-users"></i><span>Kelola data pengajar & tenaga ahli secara terpusat</span></div>
            <div class="feat"><i class="fas fa-graduation-cap"></i><span>Identifikasi kebutuhan pelatihan per wilayah kerja</span></div>
            <div class="feat"><i class="fas fa-chart-line"></i><span>Dashboard analitik & laporan pelatihan real-time</span></div>
            <div class="feat"><i class="fas fa-newspaper"></i><span>Informasi berita pelatihan terkini</span></div>
        </div>
    </div>
</div>

<div class="right-panel">
    <div class="right-inner">
        <h2 class="welcome">Selamat Datang!</h2>
        <p class="welcome-sub">Silakan pilih peran untuk masuk ke sistem</p>

        <div class="role-cards">
            <a href="login_admin.php" class="role-card admin">
                <div class="role-icon"><i class="fas fa-shield-halved"></i></div>
                <div class="role-info">
                    <h2>Administrator</h2>
                    <p>Kelola data pengajar, dinas, pelatihan, dan berita informasi</p>
                </div>
                <i class="fas fa-chevron-right role-arrow"></i>
            </a>

            <a href="login_pengajar.php" class="role-card pengajar">
                <div class="role-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="role-info">
                    <h2>Pengajar</h2>
                    <p>Lihat data pelatihan, informasi dinas, dan berita terbaru</p>
                </div>
                <i class="fas fa-chevron-right role-arrow"></i>
            </a>

            <a href="login_tamu.php" class="role-card tamu">
                <div class="role-icon"><i class="fas fa-eye"></i></div>
                <div class="role-info">
                    <h2>Pengunjung</h2>
                    <p>Jelajahi informasi pelatihan dan berita tanpa perlu akun</p>
                </div>
                <i class="fas fa-chevron-right role-arrow"></i>
            </a>
        </div>

        <p class="landing-footer">
            &copy; <?= date('Y') ?> Kementerian Pekerjaan Umum<br>
            Hak cipta dilindungi undang-undang
        </p>
    </div>
</div>

</body>
</html>
