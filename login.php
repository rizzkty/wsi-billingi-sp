<?php
require_once __DIR__ . '/includes/auth.php';
if (!empty($_SESSION['user_id'])) { redirectDashboard(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $hasil = login($username, $password);
        if ($hasil['sukses']) { redirectDashboard(); }
        else { $error = $hasil['pesan']; }
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — Billing ISP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#f0f2f5;--surface:#fff;--border:#e2e5ea;--border2:#d0d4db;--text:#1a1d23;--muted:#6b7280;--muted2:#9ca3af;--accent:#2563eb;--danger:#dc2626;--r:8px}
body{min-height:100vh;background:var(--bg);font-family:'Inter',sans-serif;font-size:14px;display:flex;align-items:center;justify-content:center}
.wrap{width:100%;max-width:360px;margin:1rem}
.brand{display:flex;align-items:center;gap:.65rem;margin-bottom:1.75rem}
.brand-logo{width:32px;height:32px;border-radius:7px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;font-family:'JetBrains Mono',monospace}
.brand-name{font-size:.9rem;font-weight:600;color:var(--text)}
.brand-sub{font-size:.72rem;color:var(--muted);margin-top:.1rem}
.card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1.75rem}
.card-title{font-size:.95rem;font-weight:600;color:var(--text);margin-bottom:.2rem}
.card-sub{font-size:.8rem;color:var(--muted);margin-bottom:1.5rem}
.err{background:#fef2f2;border:1px solid #fecaca;color:var(--danger);border-radius:var(--r);padding:.6rem .85rem;font-size:.8rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.4rem}
.fg{margin-bottom:.9rem}
label{display:block;font-size:.75rem;font-weight:500;color:var(--muted);margin-bottom:.3rem}
input{width:100%;padding:.58rem .75rem;background:var(--surface);border:1px solid var(--border2);border-radius:var(--r);color:var(--text);font-family:'Inter',sans-serif;font-size:.875rem;outline:none;transition:border-color .15s,box-shadow .15s}
input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,.08)}
input::placeholder{color:var(--muted2)}
.pw{position:relative}
.pw input{padding-right:2.5rem}
.pw-btn{position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted2);font-size:.85rem;padding:0;line-height:1;transition:color .15s}
.pw-btn:hover{color:var(--muted)}
.btn{width:100%;padding:.62rem;background:var(--accent);border:none;border-radius:var(--r);color:#fff;font-family:'Inter',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;transition:background .15s;margin-top:.25rem}
.btn:hover{background:#1d4ed8}
.foot{text-align:center;margin-top:1.25rem;font-size:.72rem;color:var(--muted2)}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">
    <div class="brand-logo">ISP</div>
    <div><div class="brand-name">Billing ISP</div><div class="brand-sub">Sistem Manajemen Tagihan</div></div>
  </div>
  <div class="card">
    <div class="card-title">Masuk ke akun</div>
    <div class="card-sub">Masukkan kredensial untuk melanjutkan</div>
    <?php if($error):?><div class="err"><span>!</span><?=htmlspecialchars($error)?></div><?php endif;?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
      <div class="fg">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username" value="<?=htmlspecialchars($_POST['username']??'')?>" autocomplete="username" required autofocus>
      </div>
      <div class="fg">
        <label>Password</label>
        <div class="pw">
          <input type="password" id="pw" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
          <button type="button" class="pw-btn" onclick="var i=document.getElementById('pw');i.type=i.type==='password'?'text':'password'">👁</button>
        </div>
      </div>
      <button type="submit" class="btn">Masuk</button>
    </form>
  </div>
  <div class="foot">Hanya pemilik yang dapat membuat akun baru</div>
</div>
</body>
</html>
