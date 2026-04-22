<?php
// pages/pemilik/pelanggan.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik', 'admin']);

$db      = getDB();
$msg_ok  = '';
$msg_err = '';

// ─── Tambah ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    csrf_verify();
    $nama        = trim($_POST['nama'] ?? '');
    $no_hp       = trim($_POST['no_hp'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $alamat      = trim($_POST['alamat'] ?? '');
    $paket_id    = (int)($_POST['paket_id'] ?? 0);
    $tgl_pasang  = $_POST['tgl_pasang'] ?: null;
    $lat         = $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
    $lng         = $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
    $ont_merk    = trim($_POST['ont_merk'] ?? '');
    $router_merk = trim($_POST['router_merk'] ?? '');
    $pppoe_user  = trim($_POST['pppoe_user'] ?? '');
    $catatan     = trim($_POST['catatan'] ?? '');

    if ($nama === '' || $paket_id <= 0) {
        $msg_err = 'Nama dan paket wajib diisi.';
    } else {
        $ins = $db->prepare(
            "INSERT INTO pelanggan (nama, no_hp, email, alamat, paket_id, tgl_pasang,
             lat, lng, ont_merk, router_merk, pppoe_user, catatan, dibuat_oleh)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $ins->bind_param('ssssissddssssi',
            $nama, $no_hp, $email, $alamat, $paket_id, $tgl_pasang,
            $lat, $lng, $ont_merk, $router_merk, $pppoe_user,
            $catatan, $_SESSION['user_id']
        );
        if ($ins->execute()) {
            log_aktivitas($_SESSION['user_id'], 'TAMBAH_PELANGGAN', "Tambah pelanggan: $nama");
            $msg_ok = "Pelanggan <strong>$nama</strong> berhasil ditambahkan.";
        } else {
            $msg_err = 'Gagal: ' . $db->error;
        }
        $ins->close();
    }
}

// ─── Edit ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
    csrf_verify();
    $id          = (int)($_POST['id'] ?? 0);
    $nama        = trim($_POST['nama'] ?? '');
    $no_hp       = trim($_POST['no_hp'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $alamat      = trim($_POST['alamat'] ?? '');
    $paket_id    = (int)($_POST['paket_id'] ?? 0);
    $tgl_pasang  = $_POST['tgl_pasang'] ?: null;
    $lat         = $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
    $lng         = $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
    $ont_merk    = trim($_POST['ont_merk'] ?? '');
    $router_merk = trim($_POST['router_merk'] ?? '');
    $pppoe_user  = trim($_POST['pppoe_user'] ?? '');
    $catatan     = trim($_POST['catatan'] ?? '');

    if ($id <= 0 || $nama === '' || $paket_id <= 0) {
        $msg_err = 'Data tidak valid.';
    } else {
        $upd = $db->prepare(
            "UPDATE pelanggan SET nama=?, no_hp=?, email=?, alamat=?, paket_id=?,
             tgl_pasang=?, lat=?, lng=?, ont_merk=?, router_merk=?, pppoe_user=?, catatan=?
             WHERE id=?"
        );
        $upd->bind_param('ssssissddsssi',
            $nama, $no_hp, $email, $alamat, $paket_id, $tgl_pasang,
            $lat, $lng, $ont_merk, $router_merk, $pppoe_user, $catatan, $id
        );
        if ($upd->execute()) {
            log_aktivitas($_SESSION['user_id'], 'EDIT_PELANGGAN', "Edit pelanggan ID $id");
            $msg_ok = "Pelanggan <strong>$nama</strong> diperbarui.";
        } else {
            $msg_err = 'Gagal: ' . $db->error;
        }
        $upd->close();
    }
}

// ─── Toggle status ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'status') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id > 0 && in_array($status, ['aktif','nonaktif','isolir'])) {
        $tgl_isolir = $status === 'isolir' ? date('Y-m-d') : null;
        $upd = $db->prepare("UPDATE pelanggan SET status=?, tgl_isolir=? WHERE id=?");
        $upd->bind_param('ssi', $status, $tgl_isolir, $id);
        $upd->execute(); $upd->close();
        log_aktivitas($_SESSION['user_id'], 'STATUS_PELANGGAN', "ID $id status=$status");
        $msg_ok = 'Status diperbarui.';
    }
}

// ─── Hapus ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $info = $db->prepare("SELECT nama FROM pelanggan WHERE id=?");
    $info->bind_param('i', $id); $info->execute();
    $row = $info->get_result()->fetch_assoc(); $info->close();
    if ($row) {
        $del = $db->prepare("DELETE FROM pelanggan WHERE id=?");
        $del->bind_param('i', $id);
        if ($del->execute()) {
            log_aktivitas($_SESSION['user_id'], 'HAPUS_PELANGGAN', "Hapus: {$row['nama']}");
            $msg_ok = "Pelanggan <strong>{$row['nama']}</strong> dihapus.";
        } else { $msg_err = 'Gagal menghapus.'; }
        $del->close();
    }
}

// ─── Data ─────────────────────────────────────────────────────────────────────
$f_status = $_GET['status'] ?? '';
$f_paket  = (int)($_GET['paket_id'] ?? 0);
$f_q      = trim($_GET['q'] ?? '');

$where = ['1=1']; $params = []; $types = '';
if ($f_status !== '') { $where[] = 'pl.status=?'; $params[] = $f_status; $types .= 's'; }
if ($f_paket > 0)     { $where[] = 'pl.paket_id=?'; $params[] = $f_paket; $types .= 'i'; }
if ($f_q !== '') {
    $where[] = '(pl.nama LIKE ? OR pl.no_hp LIKE ? OR pl.kode LIKE ?)';
    $like = "%$f_q%"; $params = array_merge($params, [$like,$like,$like]); $types .= 'sss';
}

$sql = "SELECT pl.*, p.nama AS nama_paket, p.speed_down, p.speed_up
        FROM pelanggan pl JOIN paket p ON p.id=pl.paket_id
        WHERE ".implode(' AND ',$where)." ORDER BY pl.created_at DESC";
$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pakets = $db->query("SELECT id, nama, harga FROM paket WHERE aktif=1 ORDER BY harga ASC")->fetch_all(MYSQLI_ASSOC);

$edit_data = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $eq  = $db->prepare("SELECT * FROM pelanggan WHERE id=?");
    $eq->bind_param('i', $eid); $eq->execute();
    $edit_data = $eq->get_result()->fetch_assoc(); $eq->close();
}

$total_aktif    = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='aktif'")->fetch_row()[0] ?? 0;
$total_isolir   = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='isolir'")->fetch_row()[0] ?? 0;
$total_nonaktif = $db->query("SELECT COUNT(*) FROM pelanggan WHERE status='nonaktif'")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pelanggan — Billing ISP</title>
<link rel="stylesheet" href="/billing-isp/includes/sidebar.css">
<style>
.toolbar{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem}
.toolbar input[type="text"]{width:200px}
.toolbar select{width:auto}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:300;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1.5rem;width:100%;max-width:500px;max-height:90vh;overflow-y:auto}
.modal-title{font-size:.95rem;font-weight:600;margin-bottom:1.25rem;padding-bottom:.75rem;border-bottom:1px solid var(--border)}
.modal-footer{display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.25rem;padding-top:.75rem;border-top:1px solid var(--border)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.sec-label{font-size:.7rem;font-weight:600;color:var(--muted2);text-transform:uppercase;letter-spacing:.05em;margin:.875rem 0 .5rem}
</style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-title">Manajemen Pelanggan</div>
    <div class="page-sub">Kelola data pelanggan internet</div>

    <?php if ($msg_ok): ?><div class="alert alert-ok">✓ <?= $msg_ok ?></div><?php endif; ?>
    <?php if ($msg_err): ?><div class="alert alert-err">! <?= $msg_err ?></div><?php endif; ?>

    <div class="stat-grid" style="grid-template-columns:repeat(3,minmax(120px,160px));margin-bottom:1.25rem">
        <div class="stat-card"><div class="sc-label">Aktif</div><div class="sc-val"><?= $total_aktif ?></div></div>
        <div class="stat-card"><div class="sc-label">Diisolir</div><div class="sc-val"><?= $total_isolir ?></div></div>
        <div class="stat-card"><div class="sc-label">Nonaktif</div><div class="sc-val"><?= $total_nonaktif ?></div></div>
    </div>

    <div class="toolbar">
        <form method="GET" style="display:contents">
            <input type="text" name="q" placeholder="Cari nama / no HP / kode..." value="<?= htmlspecialchars($f_q) ?>">
            <select name="paket_id">
                <option value="">Semua Paket</option>
                <?php foreach ($pakets as $pk): ?>
                <option value="<?= $pk['id'] ?>" <?= $f_paket==$pk['id']?'selected':'' ?>><?= htmlspecialchars($pk['nama']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">Semua Status</option>
                <option value="aktif"    <?= $f_status==='aktif'   ?'selected':'' ?>>Aktif</option>
                <option value="isolir"   <?= $f_status==='isolir'  ?'selected':'' ?>>Isolir</option>
                <option value="nonaktif" <?= $f_status==='nonaktif'?'selected':'' ?>>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <a href="?" class="btn btn-secondary btn-sm">Reset</a>
        </form>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal-tambah').classList.add('open')">+ Tambah</button>
    </div>

    <div class="card" style="padding:0">
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr><th>Kode</th><th>Nama</th><th>No HP</th><th>Email</th><th>Paket</th><th>Status</th><th>Pasang</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem">Tidak ada data.</td></tr>
                    <?php else: ?>
                    <?php foreach ($list as $pl): ?>
                    <tr>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:.78rem"><?= htmlspecialchars($pl['kode']) ?></td>
                        <td><strong><?= htmlspecialchars($pl['nama']) ?></strong></td>
                        <td style="font-size:.82rem"><?= htmlspecialchars($pl['no_hp'] ?? '—') ?></td>
                        <td style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($pl['email'] ?? '—') ?></td>
                        <td>
                            <span style="font-size:.82rem"><?= htmlspecialchars($pl['nama_paket']) ?></span><br>
                            <span style="font-size:.7rem;color:var(--muted)"><?= $pl['speed_down'] ?>↓/<?= $pl['speed_up'] ?>↑ Mbps</span>
                        </td>
                        <td><span class="badge badge-<?= $pl['status'] ?>"><?= $pl['status'] ?></span></td>
                        <td style="font-size:.78rem;color:var(--muted)"><?= $pl['tgl_pasang'] ? date('d M Y', strtotime($pl['tgl_pasang'])) : '—' ?></td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?= $pl['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi" value="status">
                                    <input type="hidden" name="id" value="<?= $pl['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" class="btn btn-sm btn-secondary" style="cursor:pointer">
                                        <option value="aktif"    <?= $pl['status']==='aktif'   ?'selected':'' ?>>Aktif</option>
                                        <option value="isolir"   <?= $pl['status']==='isolir'  ?'selected':'' ?>>Isolir</option>
                                        <option value="nonaktif" <?= $pl['status']==='nonaktif'?'selected':'' ?>>Nonaktif</option>
                                    </select>
                                </form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus <?= htmlspecialchars($pl['nama']) ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= $pl['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
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

<!-- Modal Tambah -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal">
        <div class="modal-title">Tambah Pelanggan Baru</div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label>Nama Lengkap *</label>
                <input type="text" name="nama" placeholder="Nama pelanggan" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>No HP</label>
                    <input type="text" name="no_hp" placeholder="08xx">
                </div>
                <div class="form-group">
                    <label>Tanggal Pasang</label>
                    <input type="date" name="tgl_pasang">
                </div>
            </div>
            <div class="form-group">
                <label>Email (Gmail)</label>
                <input type="email" name="email" placeholder="contoh@gmail.com">
            </div>
            <div class="form-group">
                <label>Paket *</label>
                <select name="paket_id" required>
                    <option value="">— Pilih Paket —</option>
                    <?php foreach ($pakets as $pk): ?>
                    <option value="<?= $pk['id'] ?>"><?= htmlspecialchars($pk['nama']) ?> — Rp <?= number_format($pk['harga'],0,',','.') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" rows="2" placeholder="Alamat lengkap"></textarea>
            </div>

            <div class="sec-label">Lokasi (opsional)</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="text" name="lat" placeholder="-7.xxxxx">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="text" name="lng" placeholder="110.xxxxx">
                </div>
            </div>

            <div class="sec-label">Perangkat (opsional)</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Merk ONT</label>
                    <input type="text" name="ont_merk" placeholder="Huawei, ZTE...">
                </div>
                <div class="form-group">
                    <label>Merk Router</label>
                    <input type="text" name="router_merk" placeholder="TP-Link, Mikrotik...">
                </div>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <textarea name="catatan" rows="2" placeholder="Catatan tambahan..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-tambah').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<?php if ($edit_data): ?>
<div class="modal-overlay open">
    <div class="modal">
        <div class="modal-title">Edit Pelanggan — <?= htmlspecialchars($edit_data['kode']) ?></div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            <div class="form-group">
                <label>Nama Lengkap *</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($edit_data['nama']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>No HP</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($edit_data['no_hp'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email (Gmail)</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Paket *</label>
                <select name="paket_id" required>
                    <?php foreach ($pakets as $pk): ?>
                    <option value="<?= $pk['id'] ?>" <?= $edit_data['paket_id']==$pk['id']?'selected':'' ?>>
                        <?= htmlspecialchars($pk['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Pasang</label>
                    <input type="date" name="tgl_pasang" value="<?= $edit_data['tgl_pasang'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Username PPPoE</label>
                    <input type="text" name="pppoe_user" value="<?= htmlspecialchars($edit_data['pppoe_user'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" rows="2"><?= htmlspecialchars($edit_data['alamat'] ?? '') ?></textarea>
            </div>

            <div class="sec-label">Lokasi</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="text" name="lat" value="<?= $edit_data['lat'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="text" name="lng" value="<?= $edit_data['lng'] ?? '' ?>">
                </div>
            </div>

            <div class="sec-label">Perangkat</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Merk ONT</label>
                    <input type="text" name="ont_merk" value="<?= htmlspecialchars($edit_data['ont_merk'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Merk Router</label>
                    <input type="text" name="router_merk" value="<?= htmlspecialchars($edit_data['router_merk'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <textarea name="catatan" rows="2"><?= htmlspecialchars($edit_data['catatan'] ?? '') ?></textarea>
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
