<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';
require_once dirname(__DIR__) . '/includes/uploads.php';
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
    'profile_image' => ['label' => 'Image path or HTTPS URL', 'max' => 255, 'required' => false],
];
$profile = $pdo->query('SELECT * FROM portfolio_profile WHERE id = 1')->fetch() ?: [];
$oldImagePath = (string) ($profile['profile_image'] ?? '');
$errors = [];
$uploadedImagePath = '';

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
        if ($name === 'profile_image' && !allowed_image_reference($value)) {
            $errors[] = 'Use an HTTPS image URL or a valid images/ or uploads/ image path.';
        }
        $values[$name] = $value;
    }

    $imageUpload = $_FILES['profile_image_upload'] ?? [];
    if (is_array($imageUpload) && uploaded_image_present($imageUpload)) {
        try {
            if (!$errors) {
                $uploadedImagePath = store_uploaded_image($imageUpload, 'profile');
                $values['profile_image'] = $uploadedImagePath;
            }
        } catch (RuntimeException $error) {
            $errors[] = $error->getMessage();
        }
    }

    if (!$errors) {
        try {
            $assignments = implode(', ', array_map(static fn(string $field): string => $field . ' = :' . $field, array_keys($values)));
            $values['id'] = 1;
            $stmt = $pdo->prepare('UPDATE portfolio_profile SET ' . $assignments . ' WHERE id = :id');
            $stmt->execute($values);
            if ($oldImagePath !== $values['profile_image']) {
                delete_uploaded_image($oldImagePath);
            }
            admin_set_flash('Portfolio profile updated.');
            header('Location: profile.php');
            exit;
        } catch (Throwable $error) {
            if ($uploadedImagePath !== '') {
                delete_uploaded_image($uploadedImagePath);
            }
            error_log('Portfolio profile update failed: ' . $error->getMessage());
            $errors[] = 'The profile could not be saved. Please try again.';
        }
    } elseif ($uploadedImagePath !== '') {
        delete_uploaded_image($uploadedImagePath);
    }

    $profile = array_merge($profile, $_POST);
    if ($uploadedImagePath !== '') {
        $profile['profile_image'] = $oldImagePath;
    }
}

admin_header('Portfolio Profile');
?>
<p>Update the text and contact information shown on your public portfolio.</p>
<?php foreach ($errors as $error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endforeach; ?>
<section class="panel"><form method="post" class="admin-form" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<?php foreach ($fields as $name => $field): $value = (string) ($profile[$name] ?? ''); ?>
<label for="<?= e($name) ?>"><?= e($field['label']) ?><?= $field['required'] ? ' *' : '' ?></label>
<?php if (in_array($name, ['introduction', 'biography', 'interests', 'goals'], true)): ?>
<textarea id="<?= e($name) ?>" name="<?= e($name) ?>" maxlength="<?= (int) $field['max'] ?>" rows="5" <?= $field['required'] ? 'required' : '' ?>><?= e($value) ?></textarea>
<?php else: ?><input id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" maxlength="<?= (int) $field['max'] ?>" <?= $field['required'] ? 'required' : '' ?>><?php endif; ?>
<?php endforeach; ?>
<label for="profile_image_upload">Upload a new profile image (JPEG, PNG, GIF, or WebP)</label>
<input id="profile_image_upload" name="profile_image_upload" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
<p class="field-help">Maximum <?= e(number_format(min(max((int) ($config['app']['upload_max_bytes'] ?? 3145728), 1024), 10485760) / 1048576, 1)) ?> MB. SVG files are not accepted. Leave the upload empty to keep the image path above.</p>
<button class="primary" type="submit">Save profile</button></form></section>
<?php admin_footer(); ?>
