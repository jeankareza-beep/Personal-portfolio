<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$allowedOrigin = rtrim((string) ($config['app']['site_origin'] ?? ''), '/');
$origin = rtrim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''), '/');

if ($origin !== '' && $origin !== $allowedOrigin) {
    json_response(['ok' => false, 'error' => 'Origin not allowed.'], 403);
}

if ($origin !== '' && hash_equals($allowedOrigin, $origin)) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Max-Age: 600');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    if ($origin === '' || $origin !== $allowedOrigin) {
        http_response_code(403);
        exit;
    }
    http_response_code(204);
    exit;
}

$resource = (string) ($_GET['resource'] ?? 'portfolio');
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

function contact_csrf_secret(): string
{
    global $config;
    $secret = (string) ($config['app']['csrf_secret'] ?? '');
    if (strlen($secret) < 32 || str_starts_with($secret, 'REPLACE_WITH_')) {
        throw new RuntimeException('Contact CSRF secret is not configured.');
    }
    return $secret;
}

function issue_contact_csrf_token(): string
{
    $payload = time() . ':' . bin2hex(random_bytes(16));
    $signature = hash_hmac('sha256', $payload, contact_csrf_secret());
    $encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    return $encoded . '.' . $signature;
}

function validate_contact_csrf_token(string $token): bool
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return false;
    }

    $encoded = strtr($parts[0], '-_', '+/');
    $padding = strlen($encoded) % 4;
    if ($padding !== 0) {
        $encoded .= str_repeat('=', 4 - $padding);
    }
    $payload = base64_decode($encoded, true);
    if (!is_string($payload) || !preg_match('/^(\d{10}):([a-f0-9]{32})$/', $payload, $matches)) {
        return false;
    }

    $issuedAt = (int) $matches[1];
    if ($issuedAt > time() + 60 || time() - $issuedAt > 600) {
        return false;
    }

    try {
        $expected = hash_hmac('sha256', $payload, contact_csrf_secret());
    } catch (Throwable) {
        return false;
    }
    return hash_equals($expected, $parts[1]);
}

function request_json_body(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (!str_starts_with($contentType, 'application/json')) {
        json_response(['ok' => false, 'error' => 'Unsupported request format.'], 415);
    }

    $raw = file_get_contents('php://input', false, null, 0, 8193);
    if (!is_string($raw) || strlen($raw) > 8192) {
        json_response(['ok' => false, 'error' => 'Request is too large.'], 413);
    }

    try {
        $body = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
    }
    if (!is_array($body)) {
        json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
    }
    return $body;
}

if ($resource === 'portfolio') {
    if ($method !== 'GET') {
        json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
    }

    try {
        $pdo = db();
        $profile = $pdo->query('SELECT full_name, headline, introduction, biography, interests, goals, email, facebook, profile_image FROM portfolio_profile WHERE id = 1')->fetch();
        $settings = $pdo->query('SELECT site_title, logo_text, accent_color FROM site_settings WHERE id = 1')->fetch();
        $skills = $pdo->query('SELECT id, name, category, description, sort_order FROM skills ORDER BY sort_order, id')->fetchAll();
        $projects = $pdo->query('SELECT id, title, category, description, image_path, project_url, sort_order FROM projects ORDER BY sort_order, id')->fetchAll();
        $education = $pdo->query('SELECT id, institution, program, details, start_year, end_year, sort_order FROM education ORDER BY sort_order, id')->fetchAll();
        $experience = $pdo->query('SELECT id, title, organization, description, start_date, end_date, sort_order FROM experience ORDER BY sort_order, id')->fetchAll();
        $achievements = $pdo->query('SELECT id, title, issuer, description, achieved_on, sort_order FROM achievements ORDER BY sort_order, id')->fetchAll();

        json_response([
            'ok' => true,
            'data' => [
                'profile' => $profile ?: null,
                'settings' => $settings ?: null,
                'skills' => $skills,
                'projects' => $projects,
                'education' => $education,
                'experience' => $experience,
                'achievements' => $achievements,
            ],
        ]);
    } catch (Throwable $error) {
        error_log('Portfolio API read failed: ' . $error->getMessage());
        json_response(['ok' => false, 'error' => 'Portfolio content is temporarily unavailable.'], 503);
    }
}

if ($resource === 'contact-csrf') {
    if ($method !== 'GET') {
        json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
    }
    try {
        json_response(['ok' => true, 'token' => issue_contact_csrf_token(), 'expires_in' => 600]);
    } catch (Throwable $error) {
        error_log('Contact token issue failed: ' . $error->getMessage());
        json_response(['ok' => false, 'error' => 'Contact form is not configured yet.'], 503);
    }
}

if ($resource === 'contact') {
    if ($method !== 'POST') {
        json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
    }
    if ($origin === '' || $origin !== $allowedOrigin) {
        json_response(['ok' => false, 'error' => 'Request could not be verified.'], 403);
    }

    $csrf = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($csrf === '' || !validate_contact_csrf_token($csrf)) {
        json_response(['ok' => false, 'error' => 'The contact form expired. Please try again.'], 403);
    }

    $body = request_json_body();
    if (trim((string) ($body['website'] ?? '')) !== '') {
        json_response(['ok' => true, 'message' => 'Your message has been sent.']);
    }

    $name = trim((string) ($body['name'] ?? ''));
    $email = trim((string) ($body['email'] ?? ''));
    $subject = trim((string) ($body['subject'] ?? ''));
    $message = trim((string) ($body['message'] ?? ''));
    $length = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

    if ($name === '' || $length($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $length($email) > 254 || $subject === '' || $length($subject) > 180
        || $message === '' || $length($message) > 4000) {
        json_response(['ok' => false, 'error' => 'Please check the form fields and try again.'], 422);
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $senderHash = hash_hmac('sha256', $ip, contact_csrf_secret());

    try {
        $pdo = db();
        $rate = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE sender_hash = :sender_hash AND created_at >= (NOW() - INTERVAL 1 HOUR)');
        $rate->execute(['sender_hash' => $senderHash]);
        if ((int) $rate->fetchColumn() >= 3) {
            json_response(['ok' => false, 'error' => 'Please wait before sending another message.'], 429);
        }

        $insert = $pdo->prepare('INSERT INTO messages (name, email, subject, message, sender_hash) VALUES (:name, :email, :subject, :message, :sender_hash)');
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'sender_hash' => $senderHash,
        ]);
        json_response(['ok' => true, 'message' => 'Your message has been sent.']);
    } catch (Throwable $error) {
        error_log('Contact message save failed: ' . $error->getMessage());
        json_response(['ok' => false, 'error' => 'Your message could not be saved right now. Please try again later.'], 503);
    }
}

json_response(['ok' => false, 'error' => 'Unknown API resource.'], 404);
