<?php
// pages/pemilik/paket.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik']);

$db      = getDB();
$msg_ok  = '';
$msg_err = '';

// ─── Tambah ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    csrf_verify();
    $nama       = trim($_POST['nama'] ?? '');
    $harga      = (int)($_POST['harga'] ?? 0);
    $speed_down = (int)($_POST['speed_down'] ?? 0);
    $speed_up   = (int)($_POST['speed_up'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    if ($nama === '' || $harga <= 0 || $speed_down <= 0 || $speed_up <= 0) {
        $msg_err = 'Nama, harga, dan speed wajib diisi.';
    } else {
        $ins = $db->prepare("INSERT INTO paket (nama, harga, speed_down, speed_up, keterangan) VALUES (?,?,?,?,?)");
        $ins->bind_param('sdiss', $nama, $harga, $speed_down, $speed_up, $keterangan);
        if ($ins->execute()) {
            log_aktivitas($_SESSION['user_id'], 'TAMBAH_PAKET', "Tambah paket: $nama");
            $msg_ok = "Paket <strong>$nama</strong> berhasil ditambahkan.";
        } else { $msg_err = 'Gagal menyimpan paket.'; }
        $ins->close();
    }
}

// ─── Edit ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
    csrf_verify();
    $id         = (int)($_POST['id'] ?? 0);
    $nama       = trim($_POST['nama'] ?? '');
    $harga      = (int)($_POST['harga'] ?? 0);
    $speed_down = (int)($_POST['speed_down'] ?? 0);
    $speed_up   = (int)($_POST['speed_up'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    if ($id <= 0 || $nama === '' || $harga <= 0) { $msg_err = 'Data tidak valid.'; }
    else {
        $upd = $db->prepare("UPDATE paket SET nama=?, harga=?, speed_down=?, speed_up=?, keterangan=? WHERE id=?");
        $upd->bind_param('sdissi', $nama, $harga, $speed_down, $speed_up, $keterangan, $id);
        if ($upd->execute()) {
            log_aktivitas($_SESSION['user_id'], 'EDIT_PAKET', "Edit paket ID $id: $nama");
            $msg_ok = "Paket <strong>$nama</strong> berhasil diperbarui.";
        } else { $msg_err = 'Gagal memperbarui paket.'; }
        $upd->close();
    }
}

// ─── Toggle ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0); $aktif = (int)($_POST['aktif'] ?? 0); $baru = $aktif ? 0 : 1;
    $upd = $db->prepare("UPDATE paket SET aktif=? WHERE id=?");
    $upd->bind_param('ii', $baru, $id); $upd->execute(); $upd->close();
    log_aktivitas($_SESSION['user_id'], 'TOGGLE_PAKET', "Paket ID $id aktif=$baru");
    $msg_ok = 'Status paket diperbarui.';
}

// ─── Hapus ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $cek = $db->prepare("SELECT COUNT(*) FROM pelanggan WHERE paket_id=?");
    $cek->bind_param('i', $id); $cek->execute();
    $n = $cek->get_result()->fetch_row()[0]; $cek->close();
    if ($n > 0) {
        $msg_err = "Paket tidak bisa dihapus, digunakan oleh <strong>$n pelanggan</strong>.";
    } else {
        $info = $db->prepare("SELECT nama FROM paket WHERE id=?");
        $info->bind_param('i', $id); $info->execute();
        $nm = $info->get_result()->fetch_assoc()['nama'] ?? ''; $info->close();
        $del = $db->prepare("DELETE FROM paket WHERE id=?");
        $del->bind_param('i', $id);
        if ($del->execute()) { log_aktivitas($_SESSION['user_id'], 'HAPUS_PAKET', "Hapus paket: $nm"); $msg_ok = "Paket <strong>$nm</strong> dihapus."; }
        else { $msg_err = 'Gagal menghapus.'; }
        $del->close();
    }
}

// ─── Data ─────────────────────────────────────────────────────────────────────
$pakets = $db->query(
    "SELECT p.*, COUNT(pl.id) as jml_pelanggan
     FROM paket p LEFT JOIN pelanggan pl ON pl.paket_id=p.id
     GROUP BY p.id ORDER BY p.harga ASC"
)->fetch_all(MYSQLI_ASSOC);

$edit_paket = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $eq  = $db->prepare("SELECT * FROM paket WHERE id=?");
    $eq->bind_param('i', $eid); $eq->execute();
    $edit_paket = $eq->get_result()->fetch_assoc(); $eq->close();
}

$total_aktif   = count(array_filter($pakets, fn($p) => $p['aktif']));
$total_nonaktif= count($pakets) - $total_aktif;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Paket Internet — Billing ISP</title>
<link rel="stylesheet" href="/billing-isp/includes/sidebar.css">
<style>
.paket-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1rem; }

.paket-card {
    background:var(--surface); border:1px solid var(--border);
    border-radius:var(--radius); overflow:hidden;
    transition:box-shadow .15s;
}
.paket-card:hover { box-shadow:0 2px 12px rgba(0,0,0,.06); }
.paket-card.nonaktif { opacity:.5; }

.paket-card-header {
    padding:1rem 1rem .75rem;
    border-bottom:1px solid var(--border);
    display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem;
}
.paket-nama { font-size:.875rem; font-weight:600; color:var(--text); line-height:1.3; }
.paket-harga { font-size:1.15rem; font-weight:600; color:var(--accent); margin-top:.2rem; }
.paket-harga small { font-size:.72rem; color:var(--muted); font-weight:400; }

.paket-card-body { padding:.875rem 1rem; }
.paket-speed {
    display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin-bottom:.75rem;
}
.speed-box {
    background:var(--surface2); border:1px solid var(--border);
    border-radius:6px; padding:.5rem; text-align:center;
}
.speed-val   { font-size:1rem; font-weight:600; color:var(--text); font-family:'JetBrains Mono',monospace; display:block; line-height:1; }
.speed-unit  { font-size:.6rem; color:var(--muted); display:block; margin-top:.15rem; text-transform:uppercase; }
.paket-ket   { font-size:.775rem; color:var(--muted); line-height:1.4; min-height:1.2rem; }
.paket-meta  {
    display:flex; align-items:center; justify-content:space-between;
    padding:.625rem 1rem; border-top:1px solid var(--border);
    font-size:.75rem; color:var(--muted);
}
.paket-actions { display:flex; gap:.35rem; }
.paket-actions .btn { flex:1; }

.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.25);
    z-index:300; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal { background:var(--surface); border:1px solid var(--border); border-radius:10px;
    padding:1.5rem; width:100%; max-width:420px; max-height:90vh; overflow-y:auto; }
.modal-title { font-size:.95rem; font-weight:600; margin-bottom:1.25rem;
    padding-bottom:.75rem; border-bottom:1px solid var(--border); }
.modal-footer { display:flex; gap:.5rem; justify-content:flex-end;
    margin-top:1.25rem; padding-top:.75rem; border-top:1px solid var(--border); }
.speed-inputs { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
.input-sfx { position:relative; }
.input-sfx input { padding-right:3rem; }
.input-sfx span { position:absolute; right:.75rem; top:50%; transform:translateY(-50%);
    font-size:.7rem; color:var(--muted); pointer-events:none; }
</style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-title">Paket Internet</div>
    <div class="page-sub">Kelola paket yang tersedia untuk pelanggan</div>

    <?php if ($msg_ok): ?><div class="alert alert-ok">✓ <?= $msg_ok ?></div><?php endif; ?>
    <?php if ($msg_err): ?><div class="alert alert-err">! <?= $msg_err ?></div><?php endif; ?>

    <!-- Stat -->
    <div class="stat-grid" style="grid-template-columns:repeat(3,minmax(120px,160px));margin-bottom:1.25rem">
        <div class="stat-card">
            <div class="sc-label">Total Paket</div>
            <div class="sc-val"><?= count($pakets) ?></div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Aktif</div>
            <div class="sc-val"><?= $total_aktif ?></div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Nonaktif</div>
            <div class="sc-val"><?= $total_nonaktif ?></div>
        </div>
    </div>

    <!-- Toolbar -->
    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal-tambah').classList.add('open')">
            + Tambah Paket
        </button>
    </div>

    <!-- Paket grid -->
    <?php if (empty($pakets)): ?>
    <div class="card" style="text-align:center;padding:3rem;color:var(--muted)">
        Belum ada paket. Tambahkan paket pertama.
    </div>
    <?php else: ?>
    <div class="paket-grid">
        <?php foreach ($pakets as $p): ?>
        <div class="paket-card <?= $p['aktif'] ? '' : 'nonaktif' ?>">
            <div class="paket-card-header">
                <div>
                    <div class="paket-nama"><?= htmlspecialchars($p['nama']) ?></div>
                    <div class="paket-harga">
                        Rp <?= number_format($p['harga'],0,',','.') ?>
                        <small>/bulan</small>
                    </div>
                </div>
                <span class="badge <?= $p['aktif'] ? 'badge-aktif' : 'badge-nonaktif' ?>" style="flex-shrink:0">
                    <?= $p['aktif'] ? 'Aktif' : 'Off' ?>
                </span>
            </div>

            <div class="paket-card-body">
                <div class="paket-speed">
                    <div class="speed-box">
                        <span class="speed-val"><?= $p['speed_down'] ?></span>
                        <span class="speed-unit">↓ Mbps</span>
                    </div>
                    <div class="speed-box">
                        <span class="speed-val"><?= $p['speed_up'] ?></span>
                        <span class="speed-unit">↑ Mbps</span>
                    </div>
                </div>
                <div class="paket-ket"><?= htmlspecialchars($p['keterangan'] ?? '') ?: '<span style="color:var(--border2)">—</span>' ?></div>
            </div>

            <div class="paket-meta">
                <span><?= $p['jml_pelanggan'] ?> pelanggan</span>
                <div class="paket-actions">
                    <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                    <form method="POST" style="display:contents">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="toggle">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="aktif" value="<?= $p['aktif'] ?>">
                        <button type="submit" class="btn btn-sm <?= $p['aktif'] ? 'btn-warning' : 'btn-success' ?>">
                            <?= $p['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </button>
                    </form>
                    <?php if ($p['jml_pelanggan'] == 0): ?>
                    <form method="POST" style="display:contents"
                        onsubmit="return confirm('Hapus paket <?= htmlspecialchars($p['nama']) ?>?')">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal">
        <div class="modal-title">Tambah Paket Baru</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label>Nama Paket</label>
                <input type="text" name="nama" placeholder="Paket 10 Mbps" required>
            </div>
            <div class="form-group">
                <label>Harga / Bulan (Rp)</label>
                <input type="number" name="harga" placeholder="200000" min="1" required>
            </div>
            <div class="form-group">
                <label>Speed</label>
                <div class="speed-inputs">
                    <div class="input-sfx">
                        <input type="number" name="speed_down" placeholder="10" min="1" required>
                        <span>↓ Mbps</span>
                    </div>
                    <div class="input-sfx">
                        <input type="number" name="speed_up" placeholder="5" min="1" required>
                        <span>↑ Mbps</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Keterangan (opsional)</label>
                <input type="text" name="keterangan" placeholder="Cocok untuk...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-tambah').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<?php if ($edit_paket): ?>
<div class="modal-overlay open">
    <div class="modal">
        <div class="modal-title">Edit Paket</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" value="<?= $edit_paket['id'] ?>">
            <div class="form-group">
                <label>Nama Paket</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($edit_paket['nama']) ?>" required>
            </div>
            <div class="form-group">
                <label>Harga / Bulan (Rp)</label>
                <input type="number" name="harga" value="<?= $edit_paket['harga'] ?>" min="1" required>
            </div>
            <div class="form-group">
                <label>Speed</label>
                <div class="speed-inputs">
                    <div class="input-sfx">
                        <input type="number" name="speed_down" value="<?= $edit_paket['speed_down'] ?>" min="1" required>
                        <span>↓ Mbps</span>
                    </div>
                    <div class="input-sfx">
                        <input type="number" name="speed_up" value="<?= $edit_paket['speed_up'] ?>" min="1" required>
                        <span>↑ Mbps</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <input type="text" name="keterangan" value="<?= htmlspecialchars($edit_paket['keterangan'] ?? '') ?>">
            </div>
            <div class="modal-footer">
                <a href="?" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
});
</script>
</body>
</html>
