<?php
// pages/pemilik/dashboard.php
require_once __DIR__ . '/../../includes/auth.php';
cekRole(['pemilik']);

$db      = getDB();
$msg_ok  = '';
$msg_err = '';

// ─── Proses tambah user ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah_user') {
    csrf_verify();
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    $roles_valid = ['admin', 'teknisi'];

    if ($nama === '' || $username === '' || $password === '' || !in_array($role, $roles_valid, true)) {
        $msg_err = 'Semua field wajib diisi dengan benar.';
    } elseif (strlen($password) < 8) {
        $msg_err = 'Password minimal 8 karakter.';
    } else {
        $cek = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $cek->bind_param('s', $username);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
            $msg_err = "Username \"$username\" sudah digunakan.";
        } else {
            $hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $owner_id = $_SESSION['user_id'];
            $ins = $db->prepare("INSERT INTO users (nama, username, password, role, aktif, dibuat_oleh) VALUES (?,?,?,?,1,?)");
            $ins->bind_param('ssssi', $nama, $username, $hash, $role, $owner_id);
            if ($ins->execute()) {
                log_aktivitas($owner_id, 'TAMBAH_USER', "Tambah user: $username ($role)");
                $msg_ok = "User <strong>$username</strong> berhasil dibuat.";
            } else {
                $msg_err = 'Gagal menyimpan user. Coba lagi.';
            }
            $ins->close();
        }
        $cek->close();
    }
}

// ─── Proses toggle aktif/nonaktif ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle_aktif') {
    csrf_verify();
    $uid   = (int)($_POST['uid'] ?? 0);
    $aktif = (int)($_POST['aktif'] ?? 0);
    $baru  = $aktif ? 0 : 1;
    $upd   = $db->prepare("UPDATE users SET aktif=? WHERE id=? AND role != 'pemilik'");
    $upd->bind_param('ii', $baru, $uid);
    $upd->execute();
    log_aktivitas($_SESSION['user_id'], 'TOGGLE_USER', "User ID $uid aktif=$baru");
    $upd->close();
    $msg_ok = 'Status user diperbarui.';
}

// ─── Proses hapus user ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus_user') {
    csrf_verify();
    $uid = (int)($_POST['uid'] ?? 0);

    $info = $db->prepare("SELECT username, role FROM users WHERE id=? AND role != 'pemilik' LIMIT 1");
    $info->bind_param('i', $uid);
    $info->execute();
    $user_info = $info->get_result()->fetch_assoc();
    $info->close();

    if ($user_info) {
        $del_log = $db->prepare("DELETE FROM log_aktivitas WHERE user_id=?");
        $del_log->bind_param('i', $uid);
        $del_log->execute();
        $del_log->close();

        $del = $db->prepare("DELETE FROM users WHERE id=? AND role != 'pemilik'");
        $del->bind_param('i', $uid);
        if ($del->execute()) {
            log_aktivitas($_SESSION['user_id'], 'HAPUS_USER', "Hapus user: {$user_info['username']} ({$user_info['role']})");
            $msg_ok = "User <strong>{$user_info['username']}</strong> berhasil dihapus.";
        } else {
            $msg_err = 'Gagal menghapus user.';
        }
        $del->close();
    } else {
        $msg_err = 'User tidak ditemukan atau tidak bisa dihapus.';
    }
}

// ─── Ambil daftar user ────────────────────────────────────────────────────────
$users = $db->query(
    "SELECT u.id, u.nama, u.username, u.role, u.aktif, u.created_at,
            p.nama AS dibuat_oleh_nama
     FROM users u
     LEFT JOIN users p ON p.id = u.dibuat_oleh
     ORDER BY u.role, u.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

// ─── Ambil log aktivitas (50 terakhir) ───────────────────────────────────────
$logs = $db->query(
    "SELECT l.aksi, l.keterangan, l.ip_address, l.created_at, u.nama, u.username
     FROM log_aktivitas l
     JOIN users u ON u.id = l.user_id
     ORDER BY l.created_at DESC
     LIMIT 50"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Pemilik — Billing ISP</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:       #0b0f1a;
            --surface:  #111827;
            --surface2: #1e293b;
            --border:   rgba(255,255,255,.08);
            --accent:   #3b82f6;
            --accent2:  #06b6d4;
            --text:     #f1f5f9;
            --muted:    #94a3b8;
            --success:  #22c55e;
            --danger:   #ef4444;
            --radius:   12px;
        }
        body { background: var(--bg); color: var(--text); font-family: 'Sora', sans-serif; min-height: 100vh; }
        nav { background: var(--surface); border-bottom: 1px solid var(--border);
              padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between;
              position: sticky; top: 0; z-index: 100; }
        .nav-brand { display: flex; align-items: center; gap: .6rem; font-weight: 700; font-size: 1rem; }
        .nav-brand span { background: linear-gradient(135deg, var(--accent), var(--accent2));
                          -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-role { font-family: 'JetBrains Mono', monospace; font-size: .72rem;
                    background: rgba(59,130,246,.15); border: 1px solid rgba(59,130,246,.3);
                    color: var(--accent2); padding: .25rem .75rem; border-radius: 20px; }
        .btn-logout { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3);
                      color: #fca5a5; padding: .4rem 1rem; border-radius: 8px;
                      font-family: 'Sora', sans-serif; font-size: .85rem; cursor: pointer; transition: background .2s; }
        .btn-logout:hover { background: rgba(239,68,68,.25); }
        main { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem; }
        .page-title { font-size: 1.5rem; font-weight: 700; margin-bottom: .25rem; }
        .page-sub   { font-size: .85rem; color: var(--muted); margin-bottom: 2rem; }
        .alert { padding: .85rem 1.25rem; border-radius: var(--radius); margin-bottom: 1.5rem;
                 font-size: .9rem; display: flex; align-items: center; gap: .5rem; }
        .alert-ok  { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert-err { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        .grid-2 { display: grid; grid-template-columns: 360px 1fr; gap: 1.5rem; align-items: start; margin-bottom: 1.5rem; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; }
        .card-title { font-size: 1rem; font-weight: 600; margin-bottom: 1.25rem;
                      padding-bottom: .75rem; border-bottom: 1px solid var(--border); }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: .75rem; font-weight: 600; color: var(--muted);
                text-transform: uppercase; letter-spacing: .05em; margin-bottom: .4rem; }
        input[type="text"], input[type="password"], select {
            width: 100%; padding: .65rem .9rem;
            background: rgba(255,255,255,.04); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text); font-family: 'Sora', sans-serif;
            font-size: .9rem; outline: none; transition: border-color .2s; }
        input:focus, select:focus { border-color: var(--accent); }
        select option { background: var(--surface2); }
        .btn-primary { width: 100%; padding: .75rem; background: linear-gradient(135deg, var(--accent), var(--accent2));
                       border: none; border-radius: 8px; color: #fff; font-family: 'Sora', sans-serif;
                       font-size: .95rem; font-weight: 600; cursor: pointer; margin-top: .5rem; transition: opacity .2s; }
        .btn-primary:hover { opacity: .88; }
        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th { text-align: left; font-size: .72rem; font-weight: 600; color: var(--muted);
             text-transform: uppercase; letter-spacing: .05em;
             padding: .6rem .9rem; border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: .75rem .9rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,.02); }
        .badge { display: inline-block; padding: .2rem .65rem; border-radius: 20px;
                 font-size: .7rem; font-weight: 600; font-family: 'JetBrains Mono', monospace; }
        .badge-pemilik { background: rgba(234,179,8,.15);  color: #fde047; border: 1px solid rgba(234,179,8,.3); }
        .badge-admin   { background: rgba(59,130,246,.15); color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
        .badge-teknisi { background: rgba(6,182,212,.15);  color: #67e8f9; border: 1px solid rgba(6,182,212,.3); }
        .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
        .dot-on  { background: var(--success); }
        .dot-off { background: var(--danger); }
        .aksi-wrap { display: flex; gap: .4rem; flex-wrap: wrap; }
        .btn-toggle { padding: .3rem .75rem; border-radius: 6px; font-size: .78rem; cursor: pointer;
                      font-family: 'Sora', sans-serif; border: 1px solid; transition: background .2s; }
        .btn-on  { background: rgba(239,68,68,.1); border-color: rgba(239,68,68,.3); color: #fca5a5; }
        .btn-off { background: rgba(34,197,94,.1); border-color: rgba(34,197,94,.3); color: #86efac; }
        .btn-hapus { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.4);
                     color: #fca5a5; padding: .3rem .75rem; border-radius: 6px; font-size: .78rem;
                     cursor: pointer; font-family: 'Sora', sans-serif; transition: background .2s; }
        .btn-hapus:hover { background: rgba(239,68,68,.3); }
        .log-wrap { max-height: 400px; overflow-y: auto; }
        .log-item { padding: .65rem .9rem; border-bottom: 1px solid var(--border); font-size: .82rem; }
        .log-item:last-child { border-bottom: none; }
        .log-aksi { font-family: 'JetBrains Mono', monospace; font-size: .72rem; font-weight: 600;
                    padding: .15rem .5rem; border-radius: 4px; margin-right: .5rem; }
        .log-LOGIN       { background: rgba(34,197,94,.15);   color: #86efac; }
        .log-LOGOUT      { background: rgba(148,163,184,.15); color: #94a3b8; }
        .log-TAMBAH_USER { background: rgba(59,130,246,.15);  color: #93c5fd; }
        .log-HAPUS_USER  { background: rgba(239,68,68,.15);   color: #fca5a5; }
        .log-TOGGLE_USER { background: rgba(234,179,8,.15);   color: #fde047; }
        .log-time { color: var(--muted); font-size: .75rem; font-family: 'JetBrains Mono', monospace; }
        .log-ip   { color: var(--muted); font-size: .72rem; }
    </style>
</head>
<body>
<nav>
    <div class="nav-brand">🌐 <span>Billing ISP</span></div>
    <div style="display:flex;align-items:center;gap:1rem">
        <span class="nav-role">👑 <?= htmlspecialchars($_SESSION['nama']) ?></span>
        <form method="POST" action="/billing-isp/logout.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button class="btn-logout" type="submit">Keluar</button>
        </form>
    </div>
</nav>

<main>
    <h1 class="page-title">Dashboard Pemilik</h1>
    <p class="page-sub">Kelola seluruh akun pengguna sistem billing ISP</p>

    <?php if ($msg_ok): ?>
    <div class="alert alert-ok">✓ <?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
    <div class="alert alert-err">⚠ <?= htmlspecialchars($msg_err) ?></div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- Form tambah user -->
        <div class="card">
            <p class="card-title">➕ Tambah User Baru</p>
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
                        <option value="admin">Admin (akses penuh)</option>
                        <option value="teknisi">Teknisi (kelola jaringan)</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Buat Akun</button>
            </form>
        </div>

        <!-- Daftar user -->
        <div class="card">
            <p class="card-title">👥 Daftar Pengguna (<?= count($users) ?>)</p>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nama']) ?></td>
                        <td style="font-family:'JetBrains Mono',monospace;font-size:.82rem">
                            <?= htmlspecialchars($u['username']) ?>
                        </td>
                        <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                        <td>
                            <span class="dot dot-<?= $u['aktif'] ? 'on' : 'off' ?>"></span>
                            <?= $u['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                        </td>
                        <td>
                            <?php if ($u['role'] !== 'pemilik'): ?>
                            <div class="aksi-wrap">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi" value="toggle_aktif">
                                    <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="aktif" value="<?= $u['aktif'] ?>">
                                    <button type="submit" class="btn-toggle <?= $u['aktif'] ? 'btn-on' : 'btn-off' ?>">
                                        <?= $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('Hapus user <?= htmlspecialchars($u['username']) ?>? Aksi ini tidak bisa dibatalkan.')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi" value="hapus_user">
                                    <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-hapus">Hapus</button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span style="color:var(--muted);font-size:.8rem">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Log Aktivitas -->
    <div class="card">
        <p class="card-title">📋 Log Aktivitas (50 terakhir)</p>
        <?php if (empty($logs)): ?>
            <p style="color:var(--muted);font-size:.9rem;text-align:center;padding:1rem">Belum ada aktivitas.</p>
        <?php else: ?>
        <div class="log-wrap">
            <?php foreach ($logs as $log): ?>
            <div class="log-item">
                <span class="log-aksi log-<?= $log['aksi'] ?>"><?= $log['aksi'] ?></span>
                <strong><?= htmlspecialchars($log['nama']) ?></strong>
                <span style="color:var(--muted)"> · </span>
                <?= htmlspecialchars($log['keterangan']) ?>
                <br>
                <span class="log-time"><?= $log['created_at'] ?></span>
                <span style="color:var(--muted)"> · </span>
                <span class="log-ip">IP: <?= htmlspecialchars($log['ip_address']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
