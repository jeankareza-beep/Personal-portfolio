<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';
require_once dirname(__DIR__) . '/includes/uploads.php';
require_admin();

$definitions = [
    'skills' => [
        'title' => 'Skills', 'table' => 'skills', 'label' => 'skill',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100],
            'category' => ['label' => 'Category', 'type' => 'text', 'required' => false, 'max' => 100],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => true, 'max' => 3000],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'required' => false, 'max' => 9999],
        ],
    ],
    'projects' => [
        'title' => 'Projects', 'table' => 'projects', 'label' => 'project',
        'fields' => [
            'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 180],
            'category' => ['label' => 'Category', 'type' => 'text', 'required' => false, 'max' => 80],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => true, 'max' => 3000],
            'image_path' => ['label' => 'Image path or HTTPS URL', 'type' => 'text', 'required' => false, 'max' => 255],
            'project_url' => ['label' => 'Project link', 'type' => 'url', 'required' => false, 'max' => 500],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'required' => false, 'max' => 9999],
        ],
    ],
    'education' => [
        'title' => 'Education', 'table' => 'education', 'label' => 'education record',
        'fields' => [
            'institution' => ['label' => 'School / institution', 'type' => 'text', 'required' => false, 'max' => 180],
            'program' => ['label' => 'Program', 'type' => 'text', 'required' => true, 'max' => 180],
            'details' => ['label' => 'Details', 'type' => 'textarea', 'required' => true, 'max' => 3000],
            'start_year' => ['label' => 'Start year', 'type' => 'year', 'required' => false, 'max' => 9999],
            'end_year' => ['label' => 'End year', 'type' => 'year', 'required' => false, 'max' => 9999],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'required' => false, 'max' => 9999],
        ],
    ],
    'experience' => [
        'title' => 'Experience', 'table' => 'experience', 'label' => 'experience record',
        'fields' => [
            'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 180],
            'organization' => ['label' => 'Organization', 'type' => 'text', 'required' => false, 'max' => 180],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => true, 'max' => 3000],
            'start_date' => ['label' => 'Start date / period', 'type' => 'text', 'required' => false, 'max' => 40],
            'end_date' => ['label' => 'End date / period', 'type' => 'text', 'required' => false, 'max' => 40],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'required' => false, 'max' => 9999],
        ],
    ],
    'achievements' => [
        'title' => 'Achievements', 'table' => 'achievements', 'label' => 'achievement',
        'fields' => [
            'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 180],
            'issuer' => ['label' => 'Awarding organization', 'type' => 'text', 'required' => false, 'max' => 180],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => true, 'max' => 3000],
            'achieved_on' => ['label' => 'Date achieved', 'type' => 'date', 'required' => false, 'max' => 10],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'required' => false, 'max' => 9999],
        ],
    ],
];

$type = (string) ($_GET['type'] ?? '');
if (!isset($definitions[$type])) {
    http_response_code(404);
    exit('Content module not found.');
}
$definition = $definitions[$type];
$pdo = db();
$errors = [];
$editing = null;
$oldImagePath = '';
$uploadedImagePath = '';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id !== false && $id !== null) {
    $stmt = $pdo->prepare('SELECT * FROM ' . $definition['table'] . ' WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $editing = $stmt->fetch();
    if (!$editing) {
        http_response_code(404);
        exit('Record not found.');
    }
    if ($type === 'projects') {
        $oldImagePath = (string) ($editing['image_path'] ?? '');
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_post();
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $hasPostedId = isset($_POST['id']) && (string) $_POST['id'] !== '';
    $postedId = $hasPostedId ? filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
    if ($hasPostedId && ($postedId === false || $postedId === null)) {
        $errors[] = 'Choose a valid record.';
    }

    if ($action === 'delete') {
        if ($postedId === false || $postedId === null) {
            $errors[] = 'Choose a valid record to delete.';
        } else {
            $imagePath = '';
            if ($type === 'projects') {
                $imageStmt = $pdo->prepare('SELECT image_path FROM projects WHERE id = :id');
                $imageStmt->execute(['id' => $postedId]);
                $imagePath = (string) ($imageStmt->fetchColumn() ?: '');
            }
            $stmt = $pdo->prepare('DELETE FROM ' . $definition['table'] . ' WHERE id = :id');
            $stmt->execute(['id' => $postedId]);
            if ($type === 'projects') {
                delete_uploaded_image($imagePath);
            }
            admin_set_flash(ucfirst($definition['label']) . ' deleted.');
            header('Location: content.php?type=' . rawurlencode($type));
            exit;
        }
    } elseif ($action === 'save') {
        $values = [];
        foreach ($definition['fields'] as $name => $field) {
            $value = trim((string) ($_POST[$name] ?? ''));
            $max = (int) $field['max'];
            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if (($field['required'] && $value === '') || $length > $max) {
                $errors[] = $field['label'] . ($value === '' ? ' is required.' : ' is too long.');
                continue;
            }
            if ($field['type'] === 'url' && $value !== '') {
                $validUrl = filter_var($value, FILTER_VALIDATE_URL) !== false;
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                if (!$validUrl || !in_array($scheme, ['http', 'https'], true)) {
                    $errors[] = 'Project link must be a valid HTTP or HTTPS URL.';
                }
            }
            if ($type === 'projects' && $name === 'image_path' && !allowed_image_reference($value)) {
                $errors[] = 'Use an HTTPS image URL or a valid images/ or uploads/ image path.';
            }
            if (in_array($field['type'], ['number', 'year'], true)) {
                if ($value === '') {
                    $values[$name] = $name === 'sort_order' ? 0 : null;
                } elseif (!ctype_digit($value) || (int) $value > $max || (int) $value < 0) {
                    $errors[] = $field['label'] . ' must be a non-negative number up to ' . $max . '.';
                } else {
                    $values[$name] = (int) $value;
                }
            } elseif ($field['type'] === 'date' && $value === '') {
                $values[$name] = null;
            } elseif ($field['type'] === 'date') {
                if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)
                    || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                    $errors[] = $field['label'] . ' must be a real date.';
                } else {
                    $values[$name] = $value;
                }
            } else {
                $values[$name] = $value;
            }
        }

        if ($type === 'projects') {
            $imageUpload = $_FILES['project_image_upload'] ?? [];
            if (is_array($imageUpload) && uploaded_image_present($imageUpload) && !$errors) {
                try {
                    $uploadedImagePath = store_uploaded_image($imageUpload, 'project');
                    $values['image_path'] = $uploadedImagePath;
                } catch (RuntimeException $error) {
                    $errors[] = $error->getMessage();
                }
            }
        }

        if (!$errors) {
            try {
                if ($postedId !== false && $postedId !== null) {
                    $assignments = implode(', ', array_map(static fn(string $field): string => $field . ' = :' . $field, array_keys($values)));
                    $values['id'] = $postedId;
                    $stmt = $pdo->prepare('UPDATE ' . $definition['table'] . ' SET ' . $assignments . ' WHERE id = :id');
                    $stmt->execute($values);
                    admin_set_flash(ucfirst($definition['label']) . ' updated.');
                } else {
                    $columns = implode(', ', array_keys($values));
                    $placeholders = implode(', ', array_map(static fn(string $field): string => ':' . $field, array_keys($values)));
                    $stmt = $pdo->prepare('INSERT INTO ' . $definition['table'] . ' (' . $columns . ') VALUES (' . $placeholders . ')');
                    $stmt->execute($values);
                    admin_set_flash(ucfirst($definition['label']) . ' added.');
                }
                if ($type === 'projects' && $oldImagePath !== (string) ($values['image_path'] ?? '')) {
                    delete_uploaded_image($oldImagePath);
                }
                header('Location: content.php?type=' . rawurlencode($type));
                exit;
            } catch (Throwable $error) {
                if ($uploadedImagePath !== '') {
                    delete_uploaded_image($uploadedImagePath);
                }
                error_log('Portfolio content save failed: ' . $error->getMessage());
                $errors[] = 'The record could not be saved. Please try again.';
                $editing = array_merge($editing ?? [], $_POST);
            }
        }
        if ($uploadedImagePath !== '' && $errors) {
            delete_uploaded_image($uploadedImagePath);
        }
        if ($errors) {
            $editing = array_merge($editing ?? [], $_POST);
            if ($type === 'projects' && $oldImagePath !== '') {
                $editing['image_path'] = $oldImagePath;
            }
        }
    }
}

$stmt = $pdo->query('SELECT * FROM ' . $definition['table'] . ' ORDER BY sort_order, id');
$records = $stmt->fetchAll();
$displayField = array_key_first($definition['fields']);
admin_header('Manage ' . $definition['title']);
?>
<p>Add, edit, or delete <?= e(strtolower($definition['title'])) ?>. Saved records are returned by the public API.</p>
<section class="panel"><h2><?= $editing ? 'Edit record' : 'Add record' ?></h2>
<?php foreach ($errors as $error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endforeach; ?>
<form method="post" class="admin-form"<?= $type === 'projects' ? ' enctype="multipart/form-data"' : '' ?>>
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="save">
<?php if ($editing): ?><input type="hidden" name="id" value="<?= e((string) ($editing['id'] ?? '')) ?>"><?php endif; ?>
<?php foreach ($definition['fields'] as $name => $field): $value = (string) ($editing[$name] ?? ''); ?>
<label for="<?= e($name) ?>"><?= e($field['label']) ?><?= $field['required'] ? ' *' : '' ?></label>
<?php if ($field['type'] === 'textarea'): ?>
<textarea id="<?= e($name) ?>" name="<?= e($name) ?>" maxlength="<?= (int) $field['max'] ?>" rows="4" <?= $field['required'] ? 'required' : '' ?>><?= e($value) ?></textarea>
<?php else: ?>
<input id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($field['type'] === 'year' ? 'number' : $field['type']) ?>" value="<?= e($value) ?>" maxlength="<?= (int) $field['max'] ?>" <?= in_array($field['type'], ['number', 'year'], true) ? 'min="0" max="' . (int) $field['max'] . '"' : '' ?> <?= $field['required'] ? 'required' : '' ?>>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($type === 'projects'): ?>
<label for="project_image_upload">Upload a project image (JPEG, PNG, GIF, or WebP)</label>
<input id="project_image_upload" name="project_image_upload" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
<p class="field-help">Maximum <?= e(number_format(min(max((int) ($config['app']['upload_max_bytes'] ?? 3145728), 1024), 10485760) / 1048576, 1)) ?> MB. SVG files are not accepted. Leave the upload empty to keep the image path above.</p>
<?php endif; ?>
<div class="form-actions"><button class="primary" type="submit">Save</button><?php if ($editing): ?><a class="secondary" href="content.php?type=<?= e($type) ?>">Cancel</a><?php endif; ?></div>
</form></section>
<section class="panel"><h2>Existing records (<?= count($records) ?>)</h2>
<?php if (!$records): ?><p>No records yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th><?= e($definition['fields'][$displayField]['label']) ?></th><th>Actions</th></tr></thead><tbody>
<?php foreach ($records as $record): ?><tr><td><?= e((string) ($record[$displayField] ?? '')) ?></td><td class="actions"><a href="content.php?type=<?= e($type) ?>&amp;id=<?= (int) $record['id'] ?>">Edit</a>
<form method="post" class="inline-form" onsubmit="return confirm('Delete this record?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $record['id'] ?>"><button class="link-button danger-link" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php admin_footer(); ?>
