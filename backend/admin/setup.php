<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';

start_secure_session();
$pdo = db();
if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
    http_response_code(404);
    exit('Administrator setup is closed.');
}

$setupKey = (string) ($config['app']['setup_key'] ?? '');
$setupKeyReady = strlen($setupKey) >= 32 && !str_starts_with($setupKey, 'REPLACE_WITH_');
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');
    $providedKey = (string) ($_POST['setup_key'] ?? '');

    if (!$setupKeyReady || !hash_equals($setupKey, $providedKey)) {
        $error = 'Setup could not be verified. Check the private setup key in the hosting configuration.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
        $error = 'Choose a username using 3–80 letters, numbers, dots, dashes, or underscores.';
    } elseif (strlen($password) < 12 || strlen($password) > 200) {
        $error = 'Use a password between 12 and 200 characters.';
    } elseif (!hash_equals($password, $confirm)) {
        $error = 'The passwords do not match.';
    } else {
        $insert = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)');
        try {
            $insert->execute(['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $pdo->lastInsertId();
            $_SESSION['admin_username'] = $username;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            admin_set_flash('Administrator account created. Setup is now closed.');
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Admin setup insert failed: ' . $exception->getMessage());
            $error = 'The account could not be created. Please check the database and try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Initial Admin Setup</title><link rel="stylesheet" href="assets/admin.css"></head><body>
<main class="auth-card"><h1>Initial Admin Setup</h1>
<p>This one-time page closes automatically after the first administrator is created.</p>
<?php if (!$setupKeyReady): ?><p class="notice error">Set a private setup key of at least 32 characters in config.local.php before continuing.</p><?php endif; ?>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label for="setup_key">One-time setup key</label><input id="setup_key" name="setup_key" type="password" required autocomplete="off">
<label for="username">Administrator username</label><input id="username" name="username" required minlength="3" maxlength="80" autocomplete="username">
<label for="password">Password (12 characters minimum)</label><input id="password" name="password" type="password" required minlength="12" maxlength="200" autocomplete="new-password">
<label for="password_confirm">Confirm password</label><input id="password_confirm" name="password_confirm" type="password" required minlength="12" maxlength="200" autocomplete="new-password">
<button class="primary" type="submit" <?= $setupKeyReady ? '' : 'disabled' ?>>Create administrator</button>
</form><p><a href="login.php">Go to login</a></p></main></body></html>
