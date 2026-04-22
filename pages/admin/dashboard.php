<?php
// pages/admin/dashboard.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik', 'admin']);

$db = getDB();

// ─── Statistik ────────────────────────────────────────────────────────────────
$total_teknisi  = $db->query("SELECT COUNT(*) FROM users WHERE role='teknisi' AND aktif=1")->fetch_row()[0] ?? 0;
$total_admin    = $db->query("SELECT COUNT(*) FROM users WHERE role='admin' AND aktif=1")->fetch_row()[0] ?? 0;
$total_pelanggan= $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='aktif'")->fetch_row()[0] ?? 0;
$tagihan_unpaid = $db->query("SELECT COUNT(*) FROM tagihan WHERE status='unpaid'")->fetch_row()[0] ?? 0;
$pendapatan_bln = $db->query("SELECT COALESCE(SUM(nominal),0) FROM pembayaran WHERE MONTH(tgl_bayar)=MONTH(NOW()) AND YEAR(tgl_bayar)=YEAR(NOW())")->fetch_row()[0] ?? 0;


?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Admin — Billing ISP</title>
<link rel="stylesheet" href="/billing-isp/includes/sidebar.css">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-title">Dashboard Admin</div>
    <div class="page-sub">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></div>

    <!-- Statistik -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="sc-label">Pelanggan Aktif</div>
            <div class="sc-val"><?= $total_pelanggan ?></div>
            <div class="sc-sub">total pelanggan aktif</div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Tagihan Belum Bayar</div>
            <div class="sc-val"><?= $tagihan_unpaid ?></div>
            <div class="sc-sub">perlu ditindaklanjuti</div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Pendapatan Bulan Ini</div>
            <div class="sc-val" style="font-size:1.1rem;margin-top:.2rem">Rp <?= number_format($pendapatan_bln, 0, ',', '.') ?></div>
            <div class="sc-sub"><?= date('F Y') ?></div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Teknisi Aktif</div>
            <div class="sc-val"><?= $total_teknisi ?></div>
            <div class="sc-sub"><?= $total_admin ?> admin aktif</div>
        </div>
    </div>



</div>
</body>
</html>
