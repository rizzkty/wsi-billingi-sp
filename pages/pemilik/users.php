<?php
// pages/pemilik/users.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik']);

$db      = getDB();
$msg_ok  = '';
$msg_err = '';

// ─── Tambah user ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah_user') {
    csrf_verify();
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    if ($nama === '' || $username === '' || $password === '' || !in_array($role, ['admin','teknisi'], true)) {
        $msg_err = 'Semua field wajib diisi dengan benar.';
    } elseif (strlen($password) < 8) {
        $msg_err = 'Password minimal 8 karakter.';
    } else {
        $cek = $db->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $cek->bind_param('s', $username); $cek->execute(); $cek->store_result();
        if ($cek->num_rows > 0) {
            $msg_err = "Username \"$username\" sudah digunakan.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
            $owner_id = $_SESSION['user_id'];
            $ins = $db->prepare("INSERT INTO users (nama, username, password, role, aktif, dibuat_oleh) VALUES (?,?,?,?,1,?)");
            $ins->bind_param('ssssi', $nama, $username, $hash, $role, $owner_id);
            if ($ins->execute()) {
                log_aktivitas($owner_id, 'TAMBAH_USER', "Tambah user: $username ($role)");
                $msg_ok = "User <strong>$username</strong> berhasil dibuat.";
            } else { $msg_err = 'Gagal menyimpan user.'; }
            $ins->close();
        }
        $cek->close();
    }
}

// ─── Toggle aktif ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle_aktif') {
    csrf_verify();
    $uid = (int)($_POST['uid'] ?? 0); $aktif = (int)($_POST['aktif'] ?? 0); $baru = $aktif ? 0 : 1;
    $upd = $db->prepare("UPDATE users SET aktif=? WHERE id=? AND role != 'pemilik'");
    $upd->bind_param('ii', $baru, $uid); $upd->execute(); $upd->close();
    log_aktivitas($_SESSION['user_id'], 'TOGGLE_USER', "User ID $uid aktif=$baru");
    $msg_ok = 'Status user diperbarui.';
}

// ─── Hapus user ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus_user') {
    csrf_verify();
    $uid = (int)($_POST['uid'] ?? 0);
    $info = $db->prepare("SELECT username, role FROM users WHERE id=? AND role != 'pemilik' LIMIT 1");
    $info->bind_param('i', $uid); $info->execute();
    $user_info = $info->get_result()->fetch_assoc(); $info->close();
    if ($user_info) {
        $dl = $db->prepare("DELETE FROM log_aktivitas WHERE user_id=?");
        $dl->bind_param('i', $uid); $dl->execute(); $dl->close();
        $del = $db->prepare("DELETE FROM users WHERE id=? AND role != 'pemilik'");
        $del->bind_param('i', $uid);
        if ($del->execute()) {
            log_aktivitas($_SESSION['user_id'], 'HAPUS_USER', "Hapus user: {$user_info['username']}");
            $msg_ok = "User <strong>{$user_info['username']}</strong> berhasil dihapus.";
        } else { $msg_err = 'Gagal menghapus user.'; }
        $del->close();
    } else { $msg_err = 'User tidak ditemukan.'; }
}

// ─── Data ─────────────────────────────────────────────────────────────────────
$users = $db->query(
    "SELECT u.id, u.nama, u.username, u.role, u.aktif, u.created_at,
     p.nama AS dibuat_oleh_nama
     FROM users u LEFT JOIN users p ON p.id = u.dibuat_oleh
     ORDER BY u.role, u.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

// ─── Log aktivitas terkait user ───────────────────────────────────────────────
$logs = $db->query(
    "SELECT l.aksi, l.keterangan, l.ip_address, l.created_at, u.nama
     FROM log_aktivitas l JOIN users u ON u.id = l.user_id
     WHERE l.aksi IN ('TAMBAH_USER','HAPUS_USER','TOGGLE_USER','LOGIN','LOGOUT')
     ORDER BY l.created_at DESC LIMIT 30"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manajemen User — Billing ISP</title>
<link rel="stylesheet" href="/billing-isp/includes/sidebar.css">
<style>
.log-LOGIN  { background:var(--accent-bg); border-color:#bfdbfe; color:var(--accent); }
.log-LOGOUT { background:var(--surface2); border-color:var(--border); color:var(--muted); }
</style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-title">Manajemen User</div>
    <div class="page-sub">Kelola akun admin dan teknisi</div>

    <?php if ($msg_ok): ?><div class="alert alert-ok">✓ <?= $msg_ok ?></div><?php endif; ?>
    <?php if ($msg_err): ?><div class="alert alert-err">! <?= htmlspecialchars($msg_err) ?></div><?php endif; ?>

    <div class="grid-2">
        <!-- Form tambah -->
        <div class="card">
            <div class="card-title">Tambah User Baru</div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="aksi" value="tambah_user">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Contoh: Budi Santoso" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Contoh: budi123" required>
                </div>
                <div class="form-group">
                    <label>Password (min. 8 karakter)</label>
                    <input type="password" name="password" placeholder="••••••••" required minlength="8">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">— Pilih Role —</option>
                        <option value="admin">Admin</option>
                        <option value="teknisi">Teknisi</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Buat Akun</button>
            </form>
        </div>

        <!-- Daftar user -->
        <div class="card">
            <div class="card-title">Daftar Pengguna (<?= count($users) ?>)</div>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nama']) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:.8rem"><?= htmlspecialchars($u['username']) ?></td>
                            <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                            <td><span class="dot dot-<?= $u['aktif']?'on':'off' ?>"></span><?= $u['aktif']?'Aktif':'Nonaktif' ?></td>
                            <td>
                                <?php if ($u['role'] !== 'pemilik'): ?>
                                <div class="actions">
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="aksi" value="toggle_aktif">
                                        <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="aktif" value="<?= $u['aktif'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $u['aktif']?'btn-warning':'btn-success' ?>">
                                            <?= $u['aktif']?'Nonaktifkan':'Aktifkan' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('Hapus user <?= htmlspecialchars($u['username']) ?>?')">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="aksi" value="hapus_user">
                                        <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span style="color:var(--muted2);font-size:.8rem">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Log aktivitas user -->
    <div class="card" style="margin-top:1.25rem">
        <div class="card-title">Log Aktivitas User (30 terakhir)</div>
        <?php if (empty($logs)): ?>
        <p style="color:var(--muted);font-size:.85rem;text-align:center;padding:1.5rem">Belum ada aktivitas.</p>
        <?php else: ?>
        <div class="log-wrap">
            <?php foreach ($logs as $log): ?>
            <div class="log-item">
                <span class="log-aksi log-<?= $log['aksi'] ?>"><?= $log['aksi'] ?></span>
                <strong><?= htmlspecialchars($log['nama']) ?></strong>
                · <?= htmlspecialchars($log['keterangan']) ?>
                <br>
                <span class="log-time"><?= $log['created_at'] ?> · IP: <?= htmlspecialchars($log['ip_address']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
