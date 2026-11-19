<?php

declare(strict_types=1);

function uploaded_image_present(array $file): bool
{
    return isset($file['error']) && (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
}

function allowed_image_reference(string $path): bool
{
    if ($path === '') {
        return true;
    }
    if (preg_match('~^https://~i', $path) === 1) {
        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }
    if (preg_match('~^images/[A-Za-z0-9_.-]+$~', $path) === 1) {
        return true;
    }
    return preg_match('~^uploads/(?:profile|project)-[a-f0-9]{32}\.(?:jpg|png|gif|webp)$~', $path) === 1;
}

function store_uploaded_image(array $file, string $prefix): string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'The selected image exceeds the PHP upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'The selected image is too large.',
            UPLOAD_ERR_PARTIAL => 'The image upload was incomplete. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server upload folder is unavailable.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not save the image.',
            UPLOAD_ERR_EXTENSION => 'The server blocked this image upload.',
        ];
        throw new RuntimeException($messages[$error] ?? 'The image upload failed.');
    }

    if (!in_array($prefix, ['profile', 'project'], true)) {
        throw new RuntimeException('The image upload type is invalid.');
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The uploaded image could not be verified.');
    }

    $configuredLimit = (int) ($GLOBALS['config']['app']['upload_max_bytes'] ?? 3145728);
    $maximumBytes = min(max($configuredLimit, 1024), 10485760);
    $actualBytes = filesize($temporaryPath);
    if ($actualBytes === false || $actualBytes < 1 || $actualBytes > $maximumBytes) {
        throw new RuntimeException('Choose an image no larger than ' . number_format($maximumBytes / 1048576, 1) . ' MB.');
    }

    if (!class_exists('finfo')) {
        throw new RuntimeException('Image validation is unavailable on this PHP host.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporaryPath);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!is_string($mime) || !isset($extensions[$mime])) {
        throw new RuntimeException('Choose a JPEG, PNG, GIF, or WebP image. SVG and other file types are not allowed.');
    }

    $imageInfo = @getimagesize($temporaryPath);
    if (!is_array($imageInfo) || ($imageInfo['mime'] ?? '') !== $mime
        || (int) ($imageInfo[0] ?? 0) < 1 || (int) ($imageInfo[1] ?? 0) < 1
        || (int) $imageInfo[0] * (int) $imageInfo[1] > 40000000) {
        throw new RuntimeException('The selected file is not a supported image or its dimensions are too large.');
    }

    $uploadDirectory = APP_ROOT . '/uploads';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('The image upload folder could not be created.');
    }

    $filename = $prefix . '-' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = $uploadDirectory . '/' . $filename;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('The image could not be saved. Check the host upload-folder permissions.');
    }
    @chmod($destination, 0644);

    return 'uploads/' . $filename;
}

function delete_uploaded_image(string $path): void
{
    if (!preg_match('~^uploads/(?:profile|project)-[a-f0-9]{32}\.(?:jpg|png|gif|webp)$~', $path)) {
        return;
    }
    $target = APP_ROOT . '/' . $path;
    if (is_file($target)) {
        @unlink($target);
    }
}
