<?php
/**
 * topbar_tamu.php — Topbar navigasi sederhana untuk halaman Tamu (tanpa sidebar)
 */
?>
<style>
.tamu-topbar {
  background: linear-gradient(135deg, #1a2744, #2c5282);
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 12px rgba(0,0,0,0.15);
  font-family: 'Poppins', sans-serif;
}
.tamu-topbar-left {
  display: flex;
  align-items: center;
  gap: 14px;
}
.tamu-topbar-logo img {
  width: 36px; height: 36px;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));
}
.tamu-topbar-title {
  color: #fff;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.tamu-topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}
.tamu-topbar-badge {
  background: rgba(255,255,255,0.12);
  color: rgba(255,255,255,0.7);
  font-size: 12px;
  font-weight: 500;
  padding: 5px 14px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.tamu-topbar-home {
  background: rgba(255,255,255,0.12);
  color: #fff;
  border: none;
  padding: 7px 18px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.2s;
}
.tamu-topbar-home:hover { background: rgba(255,255,255,0.22); }
.tamu-topbar-login {
  background: rgba(255,255,255,0.15);
  color: #fff;
  border: 1px solid rgba(255,255,255,0.2);
  padding: 7px 18px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.2s;
}
.tamu-topbar-login:hover { background: rgba(255,255,255,0.25); }
@media (max-width: 600px) {
  .tamu-topbar { padding: 0 16px; }
  .tamu-topbar-title { font-size: 14px; }
  .tamu-topbar-badge { display: none; }
}
</style>

<div class="tamu-topbar">
  <div class="tamu-topbar-left">
    <div class="tamu-topbar-logo">
      <img src="<?= BASE_URL ?>logo.png" alt="Logo">
    </div>
    <div class="tamu-topbar-title">MitigaPro</div>
  </div>
  <div class="tamu-topbar-right">
    <div class="tamu-topbar-badge">
      <i class="fas fa-eye"></i> Pengunjung
    </div>
    <a href="<?= BASE_URL ?>pengajar/dashboard_tamu.php" class="tamu-topbar-home">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>login.php" class="tamu-topbar-login">
      <i class="fas fa-sign-in-alt"></i> Masuk
    </a>
  </div>
</div>
