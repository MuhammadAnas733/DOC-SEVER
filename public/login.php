<?php
// public/login.php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';

use Hospital\Auth;

if (session_status() === PHP_SESSION_NONE) {
    session_save_path(__DIR__ . '/../sessions');
    session_start();
}

if (Auth::check()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (Auth::login($username, $password)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Medical Records System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=2.29">
</head>
<body class="login-body">
    <div class="login-card">
        <img src="assets/logo.png" alt="AIH Logo" class="login-logo">
        <h2>Medical Records System</h2>
        
        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; font-size: 0.9rem; font-weight: 500;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn primary" style="width: 100%;">Login</button>
        </form>
        
        <p style="margin-top: 3rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.6; max-width: 280px; margin-left: auto; margin-right: auto; opacity: 0.8;">
            Access restricted to authorized personnel of<br>
            <strong>Advanced International Hospital.</strong>
        </p>
    </div>
</body>
</html>
