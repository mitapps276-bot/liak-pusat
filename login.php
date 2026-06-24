<?php
session_start();
require_once 'config/database.php';

$error = '';

// Cek jika sudah login
if (isset($_SESSION['mothership_logged_in']) && $_SESSION['mothership_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if ($password === MOTHERSHIP_PASS) {
        $_SESSION['mothership_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Mothership SI-LIAK</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #2c3e50; margin: 0; display: flex; align-items: center; justify-content: center; height: 100vh; color: #333; }
        .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 400px; text-align: center; }
        .login-box h2 { margin: 0 0 20px 0; color: #2c3e50; }
        .login-box input[type="password"] { width: 90%; padding: 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
        .login-box button { background: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 5px; font-size: 16px; cursor: pointer; width: 100%; }
        .login-box button:hover { background: #2980b9; }
        .error { color: #e74c3c; margin-bottom: 15px; font-size: 14px; }
        .logo { font-size: 48px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">🌐</div>
        <h2>Mothership Access</h2>
        <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Masukkan Password Pusat" required>
            <button type="submit">Masuk Dasbor</button>
        </form>
    </div>
</body>
</html>
