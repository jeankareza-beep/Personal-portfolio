<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';
require_admin();

$pdo = db();
$adminId = (int) $_SESSION['admin_id'];
$stmt = $pdo->prepare('SELECT username, password_hash FROM admins WHERE id = :id');
$stmt->execute(['id' => $adminId]);
$account = $stmt->fetch();
if (!$account) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_post();
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['new_password_confirm'] ?? '');

    if (!password_verify($currentPassword, (string) $account['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
        $error = 'Username must be 3–80 letters, numbers, dots, dashes, or underscores.';
    } elseif ($newPassword !== '' && (strlen($newPassword) < 12 || strlen($newPassword) > 200)) {
        $error = 'New password must be 12–200 characters.';
    } elseif ($newPassword !== '' && !hash_equals($newPassword, $confirm)) {
        $error = 'New passwords do not match.';
    } else {
        $passwordHash = $newPassword === '' ? (string) $account['password_hash'] : password_hash($newPassword, PASSWORD_DEFAULT);
        try {
            $update = $pdo->prepare('UPDATE admins SET username = :username, password_hash = :password_hash WHERE id = :id');
            $update->execute(['username' => $username, 'password_hash' => $passwordHash, 'id' => $adminId]);
            $_SESSION['admin_username'] = $username;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            session_regenerate_id(true);
            admin_set_flash('Admin account updated.');
            header('Location: account.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Admin account update failed: ' . $exception->getMessage());
            $error = 'That username may already be in use.';
        }
    }
}

admin_header('Admin Account');
?>
<p>Enter your current password to save account changes. Leave both new password fields blank to keep the current password.</p>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel"><form method="post" class="admin-form" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label for="username">Username</label><input id="username" name="username" maxlength="80" required value="<?= e((string) ($_POST['username'] ?? $account['username'])) ?>" autocomplete="username">
<label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password">
<label for="new_password">New password (optional, 12 characters minimum)</label><input id="new_password" name="new_password" type="password" minlength="12" maxlength="200" autocomplete="new-password">
<label for="new_password_confirm">Confirm new password</label><input id="new_password_confirm" name="new_password_confirm" type="password" minlength="12" maxlength="200" autocomplete="new-password">
<button class="primary" type="submit">Update account</button></form></section>
<?php admin_footer(); ?>
