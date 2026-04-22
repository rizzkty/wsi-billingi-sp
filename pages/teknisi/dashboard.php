<?php
// pages/teknisi/dashboard.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik', 'admin', 'teknisi']);

$db = getDB();

// ─── Statistik ────────────────────────────────────────────────────────────────
$total_pelanggan = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='aktif'")->fetch_row()[0] ?? 0;
$total_isolir    = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='isolir'")->fetch_row()[0] ?? 0;
$total_nonaktif  = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='nonaktif'")->fetch_row()[0] ?? 0;


?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Teknisi — Billing ISP</title>
<link rel="stylesheet" href="/billing-isp/includes/sidebar.css">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-title">Dashboard Teknisi</div>
    <div class="page-sub">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></div>

    <!-- Statistik -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="sc-label">Pelanggan Aktif</div>
            <div class="sc-val"><?= $total_pelanggan ?></div>
            <div class="sc-sub">terhubung ke jaringan</div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Diisolir</div>
            <div class="sc-val"><?= $total_isolir ?></div>
            <div class="sc-sub">perlu penanganan</div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Nonaktif</div>
            <div class="sc-val"><?= $total_nonaktif ?></div>
            <div class="sc-sub">tidak aktif</div>
        </div>
    </div>



</div>
</body>
</html>
