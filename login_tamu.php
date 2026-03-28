<?php
// login_tamu.php — Masuk sebagai Tamu (tanpa akun)
session_start();

// Set session tamu
$_SESSION['user_id']  = 0;
$_SESSION['username'] = 'Tamu';
$_SESSION['role']     = 'tamu';

header('Location: /MitigaPro/pengajar/dashboard_tamu.php');
exit;
