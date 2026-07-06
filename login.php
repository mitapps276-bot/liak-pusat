<?php
session_start();
require_once 'config/database.php';
require_once 'helpers.php';

set_security_headers();

// Secure cookie jika HTTPS
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if ($is_https) ini_set('session.cookie_secure', 1);

// Sudah login → redirect
if (isset($_SESSION['mothership_logged_in']) && $_SESSION['mothership_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// =====================================
// BRUTE FORCE PROTECTION
// =====================================
$attempts_dir = __DIR__ . '/config/attempts';
if (!is_dir($attempts_dir)) {
    @mkdir($attempts_dir, 0700, true);
    // Buat .htaccess di dalam folder attempts agar tidak diakses browser
    @file_put_contents($attempts_dir . '/.htaccess', "Deny from all\n");
}

$client_ip    = preg_replace('/[^a-zA-Z0-9_\-\.\:]/', '', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
$attempt_file = $attempts_dir . '/' . md5($client_ip) . '.json';
$max_attempts = 5;
$lockout_time = 900; // 15 menit

$attempt_data = ['count' => 0, 'last_attempt' => 0];
if (file_exists($attempt_file)) {
    $attempt_data = json_decode(file_get_contents($attempt_file), true) ?? $attempt_data;
}

// Reset otomatis jika lockout sudah lewat
if ($attempt_data['count'] >= $max_attempts && (time() - $attempt_data['last_attempt']) >= $lockout_time) {
    $attempt_data = ['count' => 0, 'last_attempt' => 0];
    @file_put_contents($attempt_file, json_encode($attempt_data));
}

$is_locked       = ($attempt_data['count'] >= $max_attempts);
$remaining_lock  = $is_locked ? max(0, $lockout_time - (time() - $attempt_data['last_attempt'])) : 0;
$error           = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === MOTHERSHIP_USER && password_verify($password, MOTHERSHIP_PASS_HASH)) {
        // ✅ Login berhasil — reset percobaan
        $attempt_data = ['count' => 0, 'last_attempt' => 0];
        @file_put_contents($attempt_file, json_encode($attempt_data));

        session_regenerate_id(true); // Cegah session fixation attack
        $_SESSION['mothership_logged_in'] = true;
        $_SESSION['login_time']           = time();
        $_SESSION['login_ip']             = $client_ip;

        header("Location: index.php");
        exit;

    } else {
        // ❌ Login gagal — tambah counter
        $attempt_data['count']++;
        $attempt_data['last_attempt'] = time();
        @file_put_contents($attempt_file, json_encode($attempt_data));

        $remaining = $max_attempts - $attempt_data['count'];
        if ($remaining > 0) {
            $error = "Username atau password salah. Sisa percobaan: <strong>{$remaining}x</strong>.";
        } else {
            $is_locked      = true;
            $remaining_lock = $lockout_time;
            $error          = "Akses dikunci 15 menit karena terlalu banyak percobaan gagal.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Mothership SI-LIAK</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #f8fafc;
            overflow: hidden;
        }

        /* Animated background */
        .blob {
            position: fixed;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.45;
            animation: float 10s ease-in-out infinite;
            pointer-events: none;
        }
        .blob-1 { top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, rgba(59,130,246,0.5) 0%, transparent 70%); }
        .blob-2 { bottom: -10%; right: -10%; width: 60vw; height: 60vw; background: radial-gradient(circle, rgba(139,92,246,0.4) 0%, transparent 70%); animation-delay: -5s; }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-20px) scale(1.05); }
        }

        /* Login card */
        .login-card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 48px 40px;
            border-radius: 24px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 420px;
            text-align: center;
            animation: cardIn 0.5s ease;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo-icon { font-size: 52px; margin-bottom: 14px; display: block; }

        h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 6px;
            background: linear-gradient(135deg, #60a5fa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle { color: #64748b; font-size: 14px; margin-bottom: 32px; }

        .form-group         { margin-bottom: 20px; text-align: left; }
        .form-group label   { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 8px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase; }
        .form-group input {
            width: 100%; padding: 13px 16px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #f8fafc;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .form-group input::placeholder { color: #334155; }
        .form-group input:disabled { opacity: 0.4; cursor: not-allowed; }

        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: white; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 600; font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.35);
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(59, 130, 246, 0.5);
        }
        .btn-submit:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }

        .alert {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 14px;
            line-height: 1.55;
            text-align: left;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }
        .alert-lock {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            color: #fcd34d;
        }

        .footer-note { margin-top: 28px; font-size: 12px; color: #1e293b; }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">
        <span class="logo-icon">🌐</span>
        <h2>Mothership Access</h2>
        <p class="subtitle">SI-LIAK Pusat — Panel Superadmin</p>

        <?php if ($is_locked): ?>
        <div class="alert alert-lock">
            🔒 Akses dikunci. Coba lagi dalam
            <strong><?= ceil($remaining_lock / 60) ?> menit</strong>.
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="superadmin"
                    required
                    autocomplete="username"
                    <?= $is_locked ? 'disabled' : '' ?>
                >
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••••••"
                    required
                    autocomplete="current-password"
                    <?= $is_locked ? 'disabled' : '' ?>
                >
            </div>
            <button type="submit" class="btn-submit" <?= $is_locked ? 'disabled' : '' ?>>
                <?= $is_locked ? '🔒 Akses Dikunci' : 'Masuk Dasbor →' ?>
            </button>
        </form>

        <p class="footer-note">SI-LIAK Pusat — Akses terbatas untuk superadmin yang berwenang</p>
    </div>
</body>
</html>
