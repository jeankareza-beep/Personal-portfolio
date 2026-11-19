<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';

start_secure_session();
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = db();
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $secret = (string) ($config['app']['csrf_secret'] ?? '');
    $remoteHash = hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), $secret);

    $throttle = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE succeeded = 0 AND attempted_at >= (NOW() - INTERVAL 15 MINUTE) AND (remote_hash = :remote_hash OR username = :username)');
    $throttle->execute(['remote_hash' => $remoteHash, 'username' => $username]);
    if ((int) $throttle->fetchColumn() >= 5) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } elseif ($username === '' || strlen($username) > 80 || $password === '' || strlen($password) > 200) {
        $error = 'The username or password is incorrect.';
    } else {
        $lookup = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
        $lookup->execute(['username' => $username]);
        $admin = $lookup->fetch();
        $success = is_array($admin) && password_verify($password, (string) $admin['password_hash']);

        $record = $pdo->prepare('INSERT INTO login_attempts (username, remote_hash, succeeded) VALUES (:username, :remote_hash, :succeeded)');
        $record->execute(['username' => $username, 'remote_hash' => $remoteHash, 'succeeded' => $success ? 1 : 0]);

        if ($success) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = (string) $admin['username'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $update = $pdo->prepare('UPDATE admins SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute(['id' => (int) $admin['id']]);
            header('Location: index.php');
            exit;
        }
        $error = 'The username or password is incorrect.';
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title><link rel="stylesheet" href="assets/admin.css"></head><body>
<main class="auth-card"><h1>Portfolio Admin</h1><p>Sign in to manage your website.</p>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" autocomplete="on">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label for="username">Username</label><input id="username" name="username" required maxlength="80" autocomplete="username">
<label for="password">Password</label><input id="password" name="password" type="password" required maxlength="200" autocomplete="current-password">
<button class="primary" type="submit">Sign in</button>
</form><p><a href="setup.php">First-time setup</a></p></main></body></html>
