<?php
// pages/pemilik/tagihan.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik', 'admin']);

$db      = getDB();
$msg_ok  = '';
$msg_err = '';

$bulan_list = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

// Filter — default bulan & tahun sekarang
$f_bulan  = (int)($_GET['bulan']  ?? date('n'));
$f_tahun  = (int)($_GET['tahun']  ?? date('Y'));
$f_status = $_GET['status'] ?? '';
$f_q      = trim($_GET['q'] ?? '');

// ─── Generate tagihan bulanan ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'generate') {
    csrf_verify();
    $bulan = (int)($_POST['bulan'] ?? date('n'));
    $tahun = (int)($_POST['tahun'] ?? date('Y'));
    $tgl_jatuh_tempo = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-20";

    $pelanggan = $db->query(
        "SELECT pl.id, pl.paket_id, p.harga
         FROM pelanggan pl JOIN paket p ON p.id=pl.paket_id
         WHERE pl.status='aktif'"
    )->fetch_all(MYSQLI_ASSOC);

    $berhasil = 0; $skip = 0;
    foreach ($pelanggan as $pl) {
        $cek = $db->prepare("SELECT id FROM tagihan WHERE pelanggan_id=? AND periode_bulan=? AND periode_tahun=?");
        $cek->bind_param('iii', $pl['id'], $bulan, $tahun);
        $cek->execute(); $cek->store_result();
        if ($cek->num_rows > 0) { $skip++; $cek->close(); continue; }
        $cek->close();

        $ins = $db->prepare(
            "INSERT INTO tagihan (pelanggan_id, paket_id, periode_bulan, periode_tahun, nominal, tgl_jatuh_tempo, dibuat_oleh)
             VALUES (?,?,?,?,?,?,?)"
        );
        $ins->bind_param('iiidisi', $pl['id'], $pl['paket_id'], $bulan, $tahun, $pl['harga'], $tgl_jatuh_tempo, $_SESSION['user_id']);
        if ($ins->execute()) $berhasil++;
        $ins->close();
    }
    log_aktivitas($_SESSION['user_id'], 'GENERATE_TAGIHAN', "Generate $bulan/$tahun: $berhasil dibuat, $skip dilewati");
    $msg_ok = "Generate selesai: <strong>$berhasil</strong> tagihan dibuat, <strong>$skip</strong> sudah ada.";
    $f_bulan = $bulan; $f_tahun = $tahun;
}

// ─── Tambah manual ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    csrf_verify();
    $pelanggan_id    = (int)($_POST['pelanggan_id'] ?? 0);
    $bulan           = (int)($_POST['bulan'] ?? 0);
    $tahun           = (int)($_POST['tahun'] ?? 0);
    $nominal         = (float)($_POST['nominal'] ?? 0);
    $tgl_jatuh_tempo = $_POST['tgl_jatuh_tempo'] ?? '';

    if ($pelanggan_id <= 0 || $bulan <= 0 || $tahun <= 0 || $nominal <= 0 || !$tgl_jatuh_tempo) {
        $msg_err = 'Semua field wajib diisi.';
    } else {
        $pkt = $db->prepare("SELECT paket_id FROM pelanggan WHERE id=?");
        $pkt->bind_param('i', $pelanggan_id); $pkt->execute();
        $paket_id = $pkt->get_result()->fetch_assoc()['paket_id'] ?? 0;
        $pkt->close();

        $ins = $db->prepare(
            "INSERT INTO tagihan (pelanggan_id, paket_id, periode_bulan, periode_tahun, nominal, tgl_jatuh_tempo, dibuat_oleh)
             VALUES (?,?,?,?,?,?,?)"
        );
        $ins->bind_param('iiidisi', $pelanggan_id, $paket_id, $bulan, $tahun, $nominal, $tgl_jatuh_tempo, $_SESSION['user_id']);
        if ($ins->execute()) {
            log_aktivitas($_SESSION['user_id'], 'TAMBAH_TAGIHAN', "Tambah tagihan manual pelanggan ID $pelanggan_id $bulan/$tahun");
            $msg_ok = 'Tagihan berhasil ditambahkan.';
        } else { $msg_err = 'Gagal: ' . $db->error; }
        $ins->close();
    }
}

// ─── Lunaskan manual ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'lunaskan') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $upd = $db->prepare("UPDATE tagihan SET status='paid' WHERE id=? AND status='unpaid'");
    $upd->bind_param('i', $id); $upd->execute(); $upd->close();
    log_aktivitas($_SESSION['user_id'], 'LUNASKAN_TAGIHAN', "Lunaskan tagihan ID $id");
    $msg_ok = 'Tagihan ditandai lunas.';
}

// ─── Batal lunas ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'batal_lunas') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $upd = $db->prepare("UPDATE tagihan SET status='unpaid' WHERE id=? AND status='paid'");
    $upd->bind_param('i', $id); $upd->execute(); $upd->close();
    log_aktivitas($_SESSION['user_id'], 'BATAL_LUNAS', "Batal lunas tagihan ID $id");
    $msg_ok = 'Pelunasan dibatalkan.';
}

// ─── Edit tagihan ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit_tagihan') {
    csrf_verify();
    $id              = (int)($_POST['id'] ?? 0);
    $nominal         = (float)($_POST['nominal'] ?? 0);
    $tgl_jatuh_tempo = $_POST['tgl_jatuh_tempo'] ?? '';
    $keterangan      = trim($_POST['keterangan'] ?? '');
    if ($id > 0 && $nominal > 0 && $tgl_jatuh_tempo) {
        $upd = $db->prepare("UPDATE tagihan SET nominal=?, tgl_jatuh_tempo=?, keterangan=? WHERE id=?");
        $upd->bind_param('dssi', $nominal, $tgl_jatuh_tempo, $keterangan, $id);
        $upd->execute(); $upd->close();
        log_aktivitas($_SESSION['user_id'], 'EDIT_TAGIHAN', "Edit tagihan ID $id");
        $msg_ok = 'Tagihan diperbarui.';
    }
}

// ─── Hapus tagihan ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $del = $db->prepare("DELETE FROM tagihan WHERE id=? AND status='unpaid'");
    $del->bind_param('i', $id);
    if ($del->execute()) {
        log_aktivitas($_SESSION['user_id'], 'HAPUS_TAGIHAN', "Hapus tagihan ID $id");
        $msg_ok = 'Tagihan dihapus.';
    } else { $msg_err = 'Gagal menghapus.'; }
    $del->close();
}

// ─── Data tagihan ─────────────────────────────────────────────────────────────
$where = ['t.periode_bulan=?', 't.periode_tahun=?'];
$params = [$f_bulan, $f_tahun]; $types = 'ii';
if ($f_status !== '') { $where[] = 't.status=?'; $params[] = $f_status; $types .= 's'; }
if ($f_q !== '') { $where[] = 'pl.nama LIKE ?'; $params[] = "%$f_q%"; $types .= 's'; }

$sql = "SELECT t.*, pl.nama AS nama_pelanggan, pl.kode, pk.nama AS nama_paket
        FROM tagihan t
        JOIN pelanggan pl ON pl.id=t.pelanggan_id
        JOIN paket pk ON pk.id=t.paket_id
        WHERE ".implode(' AND ',$where)."
        ORDER BY t.status ASC, pl.nama ASC";
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tagihan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Summary
$sum = $db->prepare(
    "SELECT COUNT(*) as total,
     SUM(status='unpaid') as unpaid,
     SUM(status='paid') as paid,
     SUM(CASE WHEN status='paid' THEN nominal ELSE 0 END) as terbayar,
     SUM(CASE WHEN status='unpaid' THEN nominal ELSE 0 END) as belum
     FROM tagihan WHERE periode_bulan=? AND periode_tahun=?"
);
$sum->bind_param('ii', $f_bulan, $f_tahun); $sum->execute();
$s = $sum->get_result()->fetch_assoc(); $sum->close();

// Data untuk modal
$pelanggan_list = $db->query("SELECT id, kode, nama FROM pelanggan WHERE status='aktif' ORDER BY nama")->fetch_all(MYSQLI_ASSOC);

$edit_tagihan = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $eq  = $db->prepare("SELECT t.*, pl.nama AS nama_pelanggan FROM tagihan t JOIN pelanggan pl ON pl.id=t.pelanggan_id WHERE t.id=? AND t.status='unpaid'");
    $eq->bind_param('i', $eid); $eq->execute();
    $edit_tagihan = $eq->get_result()->fetch_assoc(); $eq->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tagihan — Billing ISP</title>
<link rel="stylesheet" href="/billing-isp/includes/sidebar.css">
<style>
.toolbar{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem}
.toolbar select,.toolbar input{width:auto}
.toolbar input[type="text"]{width:180px}
.badge-unpaid{background:#fffbeb;border-color:#fde68a;color:var(--warning)}
.badge-paid{background:#f0fdf4;border-color:#bbf7d0;color:var(--success)}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:300;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1.5rem;width:100%;max-width:460px;max-height:90vh;overflow-y:auto}
.modal-title{font-size:.95rem;font-weight:600;margin-bottom:1.25rem;padding-bottom:.75rem;border-bottom:1px solid var(--border)}
.modal-footer{display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.25rem;padding-top:.75rem;border-top:1px solid var(--border)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
</style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-title">Manajemen Tagihan</div>
    <div class="page-sub">Tagihan bulanan — <?= $bulan_list[$f_bulan] ?> <?= $f_tahun ?></div>

    <?php if ($msg_ok): ?><div class="alert alert-ok">✓ <?= $msg_ok ?></div><?php endif; ?>
    <?php if ($msg_err): ?><div class="alert alert-err">! <?= htmlspecialchars($msg_err) ?></div><?php endif; ?>

    <!-- Summary -->
    <div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.25rem">
        <div class="stat-card">
            <div class="sc-label">Total Tagihan</div>
            <div class="sc-val"><?= $s['total'] ?></div>
            <div class="sc-sub"><?= $bulan_list[$f_bulan] ?> <?= $f_tahun ?></div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Belum Lunas</div>
            <div class="sc-val" style="<?= $s['unpaid']>0?'color:var(--warning)':'' ?>"><?= $s['unpaid'] ?></div>
            <div class="sc-sub">Rp <?= number_format($s['belum'],0,',','.') ?></div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Sudah Lunas</div>
            <div class="sc-val" style="color:var(--success)"><?= $s['paid'] ?></div>
            <div class="sc-sub">Rp <?= number_format($s['terbayar'],0,',','.') ?></div>
        </div>
        <div class="stat-card">
            <div class="sc-label">Sisa Tagihan</div>
            <div class="sc-val" style="font-size:1rem;margin-top:.2rem">Rp <?= number_format($s['belum'],0,',','.') ?></div>
            <div class="sc-sub">belum terkumpul</div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <form method="GET" style="display:contents">
            <select name="bulan" onchange="this.form.submit()">
                <?php foreach ($bulan_list as $n => $nm): ?>
                <option value="<?= $n ?>" <?= $f_bulan==$n?'selected':'' ?>><?= $nm ?></option>
                <?php endforeach; ?>
            </select>
            <select name="tahun" onchange="this.form.submit()">
                <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
                <option value="<?= $y ?>" <?= $f_tahun==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="unpaid" <?= $f_status==='unpaid'?'selected':'' ?>>Belum Lunas</option>
                <option value="paid"   <?= $f_status==='paid'  ?'selected':'' ?>>Sudah Lunas</option>
            </select>
            <input type="text" name="q" placeholder="Cari pelanggan..." value="<?= htmlspecialchars($f_q) ?>"
                onchange="this.form.submit()">

        </form>
        <button class="btn btn-secondary btn-sm" onclick="document.getElementById('modal-generate').classList.add('open')">
            ⚡ Generate
        </button>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal-tambah').classList.add('open')">
            + Tambah
        </button>
    </div>

    <!-- Tabel -->
    <div class="card" style="padding:0">
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Pelanggan</th><th>Paket</th><th>Periode</th>
                        <th>Nominal</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tagihan_list)): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">
                        Tidak ada tagihan untuk periode ini.
                        <?php if ($s['total']==0): ?>
                        <br><small>Klik <strong>Generate</strong> untuk membuat tagihan otomatis.</small>
                        <?php endif; ?>
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($tagihan_list as $t): ?>
                    <?php $terlambat = strtotime($t['tgl_jatuh_tempo']) < time() && $t['status']==='unpaid'; ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($t['nama_pelanggan']) ?></strong><br>
                            <span style="font-size:.72rem;color:var(--muted);font-family:'JetBrains Mono',monospace"><?= htmlspecialchars($t['kode']) ?></span>
                        </td>
                        <td style="font-size:.82rem"><?= htmlspecialchars($t['nama_paket']) ?></td>
                        <td style="font-size:.82rem"><?= $bulan_list[$t['periode_bulan']] ?> <?= $t['periode_tahun'] ?></td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:.82rem">
                            Rp <?= number_format($t['nominal'],0,',','.') ?>
                        </td>
                        <td style="font-size:.78rem;color:<?= $terlambat?'var(--danger)':'var(--muted)' ?>">
                            <?= date('d M Y', strtotime($t['tgl_jatuh_tempo'])) ?>
                            <?= $terlambat ? '<br><span style="font-size:.7rem">Terlambat</span>' : '' ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $t['status'] ?>">
                                <?= $t['status']==='paid' ? 'Lunas' : 'Belum Lunas' ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if ($t['status'] === 'unpaid'): ?>
                                <form method="POST" style="display:inline"
                                    onsubmit="return confirm('Tandai lunas?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi" value="lunaskan">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Lunaskan</button>
                                </form>
                                <a href="?edit=<?= $t['id'] ?>&bulan=<?= $f_bulan ?>&tahun=<?= $f_tahun ?>&status=<?= $f_status ?>&q=<?= urlencode($f_q) ?>"
                                    class="btn btn-sm btn-secondary">Edit</a>
                                <form method="POST" style="display:inline"
                                    onsubmit="return confirm('Hapus tagihan ini?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display:inline"
                                    onsubmit="return confirm('Batalkan pelunasan?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi" value="batal_lunas">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">Batal Lunas</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Generate -->
<div class="modal-overlay" id="modal-generate">
    <div class="modal">
        <div class="modal-title">⚡ Generate Tagihan Bulanan</div>
        <p style="font-size:.85rem;color:var(--muted);margin-bottom:1.25rem">
            Buat tagihan otomatis untuk semua pelanggan aktif. Pelanggan yang sudah ada tagihannya akan dilewati.
        </p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="generate">
            <div class="form-row">
                <div class="form-group">
                    <label>Bulan</label>
                    <select name="bulan" required>
                        <?php foreach ($bulan_list as $n => $nm): ?>
                        <option value="<?= $n ?>" <?= $n==$f_bulan?'selected':'' ?>><?= $nm ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tahun</label>
                    <select name="tahun" required>
                        <?php for ($y = date('Y')+1; $y >= date('Y')-1; $y--): ?>
                        <option value="<?= $y ?>" <?= $y==$f_tahun?'selected':'' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-generate').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Manual -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal">
        <div class="modal-title">Tambah Tagihan Manual</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label>Pelanggan</label>
                <select name="pelanggan_id" required>
                    <option value="">— Pilih Pelanggan —</option>
                    <?php foreach ($pelanggan_list as $pl): ?>
                    <option value="<?= $pl['id'] ?>"><?= htmlspecialchars($pl['kode'].' — '.$pl['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Bulan</label>
                    <select name="bulan" required>
                        <?php foreach ($bulan_list as $n => $nm): ?>
                        <option value="<?= $n ?>" <?= $n==$f_bulan?'selected':'' ?>><?= $nm ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tahun</label>
                    <select name="tahun" required>
                        <?php for ($y = date('Y')+1; $y >= date('Y')-1; $y--): ?>
                        <option value="<?= $y ?>" <?= $y==$f_tahun?'selected':'' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Nominal (Rp)</label>
                    <input type="number" name="nominal" placeholder="200000" required min="1">
                </div>
                <div class="form-group">
                    <label>Jatuh Tempo</label>
                    <input type="date" name="tgl_jatuh_tempo" value="<?= date('Y-m-20') ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-tambah').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<?php if ($edit_tagihan): ?>
<div class="modal-overlay open">
    <div class="modal">
        <div class="modal-title">Edit Tagihan — <?= htmlspecialchars($edit_tagihan['nama_pelanggan']) ?></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="edit_tagihan">
            <input type="hidden" name="id" value="<?= $edit_tagihan['id'] ?>">
            <div class="form-group">
                <label>Nominal (Rp)</label>
                <input type="number" name="nominal" value="<?= $edit_tagihan['nominal'] ?>" required min="1">
            </div>
            <div class="form-group">
                <label>Jatuh Tempo</label>
                <input type="date" name="tgl_jatuh_tempo" value="<?= $edit_tagihan['tgl_jatuh_tempo'] ?>" required>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <input type="text" name="keterangan" value="<?= htmlspecialchars($edit_tagihan['keterangan'] ?? '') ?>" placeholder="Opsional">
            </div>
            <div class="modal-footer">
                <a href="?bulan=<?= $f_bulan ?>&tahun=<?= $f_tahun ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
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
