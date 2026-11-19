<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';
require_admin();

$pdo = db();
$settings = $pdo->query('SELECT * FROM site_settings WHERE id = 1')->fetch() ?: [];
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_post();
    verify_csrf();
    $title = trim((string) ($_POST['site_title'] ?? ''));
    $logo = trim((string) ($_POST['logo_text'] ?? ''));
    $color = trim((string) ($_POST['accent_color'] ?? ''));
    if ($title === '' || strlen($title) > 200 || $logo === '' || strlen($logo) > 80) {
        $error = 'Enter a title and logo text within the allowed length.';
    } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $error = 'Accent color must be a six-digit hexadecimal color, such as #2563eb.';
    } else {
        $stmt = $pdo->prepare('UPDATE site_settings SET site_title = :title, logo_text = :logo, accent_color = :color WHERE id = 1');
        $stmt->execute(['title' => $title, 'logo' => $logo, 'color' => $color]);
        admin_set_flash('Website settings updated.');
        header('Location: settings.php');
        exit;
    }
    $settings = array_merge($settings, ['site_title' => $title, 'logo_text' => $logo, 'accent_color' => $color]);
}

admin_header('Website Settings');
?>
<p>These settings are returned with the public portfolio data.</p>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel"><form method="post" class="admin-form">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label for="site_title">Website title</label><input id="site_title" name="site_title" maxlength="200" required value="<?= e((string) ($settings['site_title'] ?? '')) ?>">
<label for="logo_text">Navigation logo text</label><input id="logo_text" name="logo_text" maxlength="80" required value="<?= e((string) ($settings['logo_text'] ?? '')) ?>">
<label for="accent_color">Accent color</label><input id="accent_color" name="accent_color" type="color" required value="<?= e((string) ($settings['accent_color'] ?? '#2563eb')) ?>">
<button class="primary" type="submit">Save settings</button></form></section>
<?php admin_footer(); ?>
