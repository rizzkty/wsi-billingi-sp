<?php
// pages/admin/dashboard.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik', 'admin']);

$db = getDB();

// Statistik singkat
$total_teknisi = $db->query("SELECT COUNT(*) FROM users WHERE role='teknisi' AND aktif=1")->fetch_row()[0];
$total_admin   = $db->query("SELECT COUNT(*) FROM users WHERE role='admin' AND aktif=1")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin — Billing ISP</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0b0f1a; --surface: #111827; --border: rgba(255,255,255,.08);
            --accent: #3b82f6; --accent2: #06b6d4; --text: #f1f5f9; --muted: #94a3b8;
        }
        body { background: var(--bg); color: var(--text); font-family: 'Sora', sans-serif; min-height: 100vh; }
        nav {
            background: var(--surface); border-bottom: 1px solid var(--border);
            padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand { font-weight: 700; }
        .nav-brand span { background: linear-gradient(135deg, var(--accent), var(--accent2));
                          -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-role { font-family: 'JetBrains Mono', monospace; font-size: .72rem;
                    background: rgba(59,130,246,.15); border: 1px solid rgba(59,130,246,.3);
                    color: var(--accent2); padding: .25rem .75rem; border-radius: 20px; }
        .btn-logout { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3);
                      color: #fca5a5; padding: .4rem 1rem; border-radius: 8px;
                      font-family: 'Sora', sans-serif; cursor: pointer; }
        main { max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: .25rem; }
        .sub { font-size: .85rem; color: var(--muted); margin-bottom: 2rem; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
            padding: 1.25rem 1.5rem;
        }
        .stat-card .label { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
        .stat-card .value { font-size: 2rem; font-weight: 700; margin-top: .25rem;
                            background: linear-gradient(135deg, var(--accent), var(--accent2));
                            -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .info-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
            padding: 1.5rem;
        }
        .info-card h2 { font-size: 1rem; font-weight: 600; margin-bottom: 1rem; }
        .perm-list { list-style: none; }
        .perm-list li { padding: .5rem 0; border-bottom: 1px solid var(--border); font-size: .9rem;
                        display: flex; align-items: center; gap: .5rem; }
        .perm-list li:last-child { border-bottom: none; }
        .ok  { color: #86efac; }
        .no  { color: #fca5a5; }
    </style>
</head>
<body>
<nav>
    <div class="nav-brand">🌐 <span>Billing ISP</span></div>
    <div style="display:flex;align-items:center;gap:1rem">
        <span class="nav-role">🛡 <?= htmlspecialchars($_SESSION['nama']) ?></span>
        <form method="POST" action="/logout.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button class="btn-logout">Keluar</button>
        </form>
    </div>
</nav>

<main>
    <h1>Dashboard Admin</h1>
    <p class="sub">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></p>

    <div class="stats">
        <div class="stat-card">
            <div class="label">Total Teknisi Aktif</div>
            <div class="value"><?= $total_teknisi ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Admin Aktif</div>
            <div class="value"><?= $total_admin ?></div>
        </div>
    </div>

    <div class="info-card">
        <h2>Hak Akses Kamu sebagai Admin</h2>
        <ul class="perm-list">
            <li><span class="ok">✓</span> Lihat & kelola data pelanggan</li>
            <li><span class="ok">✓</span> Kelola paket internet</li>
            <li><span class="ok">✓</span> Kelola pembayaran & tagihan</li>
            <li><span class="ok">✓</span> Lihat laporan keuangan</li>
            <li><span class="ok">✓</span> Kelola perangkat jaringan</li>
            <li><span class="no">✗</span> Membuat / menghapus akun user (hanya pemilik)</li>
        </ul>
    </div>
</main>
</body>
</html>
