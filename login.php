<?php

require_once __DIR__ . '/includes/auth.php';


if (!empty($_SESSION['user_id'])) {
    redirectDashboard();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $hasil = login($username, $password);
        if ($hasil['sukses']) {
            redirectDashboard();
        } else {
            $error = $hasil['pesan'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Billing ISP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0b0f1a;
            --surface:   #111827;
            --border:    rgba(255,255,255,.08);
            --accent:    #3b82f6;
            --accent2:   #06b6d4;
            --text:      #f1f5f9;
            --muted:     #94a3b8;
            --error-bg:  rgba(239,68,68,.12);
            --error-bdr: rgba(239,68,68,.4);
            --error-txt: #fca5a5;
            --radius:    14px;
        }

        body {
            min-height: 100vh;
            background: var(--bg);
            font-family: 'Sora', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Grid background */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,.04) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        /* Glow blobs */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .25;
            pointer-events: none;
        }
        .blob-1 { width: 400px; height: 400px; background: #3b82f6; top: -100px; left: -100px; }
        .blob-2 { width: 300px; height: 300px; background: #06b6d4; bottom: -80px; right: -80px; }

        /* Card */
        .card {
            position: relative;
            width: 100%;
            max-width: 420px;
            margin: 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(20px);
            animation: slideUp .5s cubic-bezier(.16,1,.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Logo area */
        .logo-wrap {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 2rem;
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .logo-text h1 { font-size: 1.1rem; font-weight: 700; color: var(--text); }
        .logo-text p  { font-size: .75rem; color: var(--muted); font-family: 'JetBrains Mono', monospace; }

        /* Form */
        .form-group { margin-bottom: 1.25rem; }

        label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: .5rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: .75rem 1rem;
            background: rgba(255,255,255,.04);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-family: 'Sora', sans-serif;
            font-size: .95rem;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
        }
        input::placeholder { color: var(--muted); opacity: .6; }

        /* Password toggle */
        .pass-wrap { position: relative; }
        .pass-wrap input { padding-right: 2.8rem; }
        .toggle-pass {
            position: absolute; right: .9rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--muted); font-size: 1rem; padding: 0;
            transition: color .15s;
        }
        .toggle-pass:hover { color: var(--text); }

        /* Error */
        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-bdr);
            color: var(--error-txt);
            border-radius: var(--radius);
            padding: .75rem 1rem;
            font-size: .88rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: .5rem;
            animation: shake .3s ease;
        }
        @keyframes shake {
            0%,100%{transform:translateX(0)}
            20%{transform:translateX(-5px)}
            60%{transform:translateX(5px)}
        }

        /* Submit button */
        .btn-login {
            width: 100%;
            padding: .85rem;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border: none;
            border-radius: var(--radius);
            color: #fff;
            font-family: 'Sora', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
            margin-top: .5rem;
        }
        .btn-login:hover  { opacity: .9; transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        .footer-note {
            text-align: center;
            margin-top: 1.75rem;
            font-size: .75rem;
            color: var(--muted);
            font-family: 'JetBrains Mono', monospace;
        }
        .footer-note span { color: var(--accent2); }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="card">
        <div class="logo-wrap">
            <div class="logo-icon">🌐</div>
            <div class="logo-text">
                <h1>Billing ISP</h1>
                <p>v1.0 · sistem manajemen</p>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert-error">
            <span>⚠</span> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Masukkan username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="pass-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-pass" onclick="togglePass()" title="Tampilkan password">
                        👁
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Masuk →</button>
        </form>

        <p class="footer-note">
            Hanya <span>pemilik</span> yang dapat membuat akun baru
        </p>
    </div>

    <script>
        function togglePass() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
