<?php
// pages/pemilik/dashboard.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik']);

$db = getDB();

// ─── Statistik pelanggan ──────────────────────────────────────────────────────
$total_aktif    = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='aktif'")->fetch_row()[0] ?? 0;
$total_isolir   = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='isolir'")->fetch_row()[0] ?? 0;
$total_nonaktif = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='nonaktif'")->fetch_row()[0] ?? 0;
$total_pelanggan= $total_aktif + $total_isolir + $total_nonaktif;

// ─── Pendapatan ───────────────────────────────────────────────────────────────
$pendapatan_bulan = $db->query(
    "SELECT COALESCE(SUM(nominal),0) FROM pembayaran
     WHERE MONTH(tgl_bayar)=MONTH(NOW()) AND YEAR(tgl_bayar)=YEAR(NOW())"
)->fetch_row()[0] ?? 0;

$pendapatan_tahun = $db->query(
    "SELECT COALESCE(SUM(nominal),0) FROM pembayaran WHERE YEAR(tgl_bayar)=YEAR(NOW())"
)->fetch_row()[0] ?? 0;

$tagihan_unpaid = $db->query("SELECT COUNT(*) FROM tagihan WHERE status='unpaid'")->fetch_row()[0] ?? 0;

// ─── Pendapatan 6 bulan terakhir (untuk chart) ────────────────────────────────
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $row = $db->query(
        "SELECT COALESCE(SUM(nominal),0) as total,
         DATE_FORMAT(DATE_SUB(NOW(), INTERVAL $i MONTH), '%b %Y') as label
         FROM pembayaran
         WHERE MONTH(tgl_bayar)=MONTH(DATE_SUB(NOW(), INTERVAL $i MONTH))
         AND YEAR(tgl_bayar)=YEAR(DATE_SUB(NOW(), INTERVAL $i MONTH))"
    )->fetch_assoc();
    $chart_data[] = $row;
}

// ─── Distribusi paket ─────────────────────────────────────────────────────────
$paket_dist = $db->query(
    "SELECT p.nama, COUNT(pl.id) as total
     FROM paket p LEFT JOIN pelanggan pl ON pl.paket_id=p.id AND pl.status='aktif'
     GROUP BY p.id ORDER BY total DESC"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Billing ISP</title>
<link rel="stylesheet" href="/billing-isp/includes/sidebar.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.charts-grid { display:grid; grid-template-columns:1fr 320px; gap:1.25rem; margin-top:1.25rem; }
@media(max-width:900px){ .charts-grid { grid-template-columns:1fr; } }
.chart-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:1.25rem; }
.chart-title { font-size:.85rem; font-weight:600; color:var(--text); margin-bottom:1rem; }
.chart-wrap { position:relative; height:220px; }
</style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-title">Dashboard</div>
    <div class="page-sub">Ringkasan bisnis ISP — <?= date('d F Y') ?></div>

    <!-- Stat cards -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="sc-label">Total Pelanggan</div>
            <div class="sc-val"><?= $total_pelanggan ?></div>
            <div class="sc-sub"><?= $total_aktif ?> aktif</div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Pelanggan Aktif</div>
            <div class="sc-val"><?= $total_aktif ?></div>
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
        <div class="stat-card">
            <div class="sc-label">Pendapatan Bulan Ini</div>
            <div class="sc-val" style="font-size:1rem;margin-top:.2rem">
                Rp <?= number_format($pendapatan_bulan, 0, ',', '.') ?>
            </div>
            <div class="sc-sub"><?= date('F Y') ?></div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Pendapatan Tahun Ini</div>
            <div class="sc-val" style="font-size:1rem;margin-top:.2rem">
                Rp <?= number_format($pendapatan_tahun, 0, ',', '.') ?>
            </div>
            <div class="sc-sub"><?= date('Y') ?></div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Tagihan Belum Bayar</div>
            <div class="sc-val" style="<?= $tagihan_unpaid > 0 ? 'color:var(--warning)' : '' ?>">
                <?= $tagihan_unpaid ?>
            </div>
            <div class="sc-sub">perlu ditindaklanjuti</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <!-- Bar chart pendapatan -->
        <div class="chart-card">
            <div class="chart-title">Pendapatan 6 Bulan Terakhir</div>
            <div class="chart-wrap">
                <canvas id="chartPendapatan"></canvas>
            </div>
        </div>

        <!-- Donut chart status pelanggan -->
        <div class="chart-card">
            <div class="chart-title">Status Pelanggan</div>
            <div class="chart-wrap">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>
    </div>

    <!-- Distribusi paket -->
    <?php if (!empty($paket_dist)): ?>
    <div class="chart-card" style="margin-top:1.25rem">
        <div class="chart-title">Distribusi Paket (Pelanggan Aktif)</div>
        <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.5rem">
            <?php
            $max = max(array_column($paket_dist, 'total')) ?: 1;
            foreach ($paket_dist as $pk):
                $pct = $max > 0 ? round($pk['total'] / $max * 100) : 0;
            ?>
            <div style="display:flex;align-items:center;gap:.75rem;font-size:.825rem">
                <span style="width:160px;color:var(--muted);flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= htmlspecialchars($pk['nama']) ?>
                </span>
                <div style="flex:1;background:var(--surface2);border-radius:4px;height:8px;overflow:hidden">
                    <div style="width:<?= $pct ?>%;height:100%;background:var(--accent);border-radius:4px;transition:width .3s"></div>
                </div>
                <span style="width:30px;text-align:right;font-family:'JetBrains Mono',monospace;color:var(--text)">
                    <?= $pk['total'] ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Data dari PHP
const pendapatanLabels = <?= json_encode(array_column($chart_data, 'label')) ?>;
const pendapatanData   = <?= json_encode(array_map(fn($r) => (float)$r['total'], $chart_data)) ?>;

const statusLabels = ['Aktif', 'Diisolir', 'Nonaktif'];
const statusData   = [<?= $total_aktif ?>, <?= $total_isolir ?>, <?= $total_nonaktif ?>];

// Warna sesuai tema
const accent  = '#2563eb';
const success = '#16a34a';
const warning = '#d97706';
const danger  = '#dc2626';
const muted   = '#e2e5ea';

// Bar chart pendapatan
new Chart(document.getElementById('chartPendapatan'), {
    type: 'bar',
    data: {
        labels: pendapatanLabels,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: pendapatanData,
            backgroundColor: accent + '22',
            borderColor: accent,
            borderWidth: 1.5,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            x: { grid: { color: '#e2e5ea' }, ticks: { font: { size: 11 }, color: '#6b7280' } },
            y: {
                grid: { color: '#e2e5ea' },
                ticks: {
                    font: { size: 11 }, color: '#6b7280',
                    callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt'
                }
            }
        }
    }
});

// Donut chart status
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: [success + 'cc', warning + 'cc', '#9ca3af99'],
            borderColor: [success, warning, '#9ca3af'],
            borderWidth: 1.5,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 11 }, color: '#6b7280', padding: 12 }
            }
        },
        cutout: '65%',
    }
});
</script>
</body>
</html>
