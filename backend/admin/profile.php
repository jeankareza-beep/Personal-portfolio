<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';
require_admin();

$pdo = db();
$fields = [
    'full_name' => ['label' => 'Full name', 'max' => 160, 'required' => true],
    'headline' => ['label' => 'Headline', 'max' => 200, 'required' => true],
    'introduction' => ['label' => 'Home introduction', 'max' => 3000, 'required' => true],
    'biography' => ['label' => 'Biography', 'max' => 5000, 'required' => true],
    'interests' => ['label' => 'Interests', 'max' => 3000, 'required' => false],
    'goals' => ['label' => 'Goals', 'max' => 3000, 'required' => false],
    'email' => ['label' => 'Email', 'max' => 254, 'required' => false],
    'facebook' => ['label' => 'Facebook / social profile', 'max' => 255, 'required' => false],
    'profile_image' => ['label' => 'Profile image path', 'max' => 255, 'required' => false],
];
$profile = $pdo->query('SELECT * FROM portfolio_profile WHERE id = 1')->fetch() ?: [];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_post();
    verify_csrf();
    $values = [];
    foreach ($fields as $name => $field) {
        $value = trim((string) ($_POST[$name] ?? ''));
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if (($field['required'] && $value === '') || $length > $field['max']) {
            $errors[] = $field['label'] . ($value === '' ? ' is required.' : ' is too long.');
        }
        if ($name === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($name === 'profile_image' && $value !== '' && preg_match('~^(?:https://|images/|uploads/)~i', $value) !== 1) {
            $errors[] = 'Profile image must use HTTPS or a relative images/ or uploads/ path.';
        }
        $values[$name] = $value;
    }
    if (!$errors) {
        $assignments = implode(', ', array_map(static fn(string $field): string => $field . ' = :' . $field, array_keys($values)));
        $values['id'] = 1;
        $stmt = $pdo->prepare('UPDATE portfolio_profile SET ' . $assignments . ' WHERE id = :id');
        $stmt->execute($values);
        admin_set_flash('Portfolio profile updated.');
        header('Location: profile.php');
        exit;
    }
    $profile = array_merge($profile, $_POST);
}

admin_header('Portfolio Profile');
?>
<p>Update the text and contact information shown on your public portfolio.</p>
<?php foreach ($errors as $error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endforeach; ?>
<section class="panel"><form method="post" class="admin-form">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<?php foreach ($fields as $name => $field): $value = (string) ($profile[$name] ?? ''); ?>
<label for="<?= e($name) ?>"><?= e($field['label']) ?><?= $field['required'] ? ' *' : '' ?></label>
<?php if (in_array($name, ['introduction', 'biography', 'interests', 'goals'], true)): ?>
<textarea id="<?= e($name) ?>" name="<?= e($name) ?>" maxlength="<?= (int) $field['max'] ?>" rows="5" <?= $field['required'] ? 'required' : '' ?>><?= e($value) ?></textarea>
<?php else: ?><input id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" maxlength="<?= (int) $field['max'] ?>" <?= $field['required'] ? 'required' : '' ?>><?php endif; ?>
<?php endforeach; ?>
<button class="primary" type="submit">Save profile</button></form></section>
<?php admin_footer(); ?>
