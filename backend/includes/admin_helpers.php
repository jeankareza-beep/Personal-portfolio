<?php

declare(strict_types=1);

function admin_set_flash(string $message, string $kind = 'success'): void
{
    start_secure_session();
    $_SESSION['admin_flash'] = ['message' => $message, 'kind' => $kind];
}

function admin_show_flash(): void
{
    start_secure_session();
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    if (!is_array($flash)) {
        return;
    }
    $kind = ($flash['kind'] ?? '') === 'error' ? 'error' : 'success';
    echo '<p class="notice ' . $kind . '" role="status">' . e((string) ($flash['message'] ?? '')) . '</p>';
}

function admin_header(string $title): void
{
    $username = (string) ($_SESSION['admin_username'] ?? 'Administrator');
    $safeTitle = e($title);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $safeTitle . ' | Portfolio Admin</title>';
    echo '<link rel="stylesheet" href="assets/admin.css"></head><body>';
    echo '<header class="admin-header"><a class="brand" href="index.php">Portfolio Admin</a>';
    echo '<nav aria-label="Admin navigation">';
    echo '<a href="index.php">Dashboard</a><a href="profile.php">Portfolio Profile</a>';
    echo '<a href="content.php?type=skills">Skills</a><a href="content.php?type=projects">Projects</a>';
    echo '<a href="content.php?type=education">Education</a><a href="content.php?type=experience">Experience</a>';
    echo '<a href="content.php?type=achievements">Achievements</a><a href="messages.php">Messages</a>';
    echo '<a href="settings.php">Settings</a><a href="account.php">Admin Account</a></nav>';
    echo '<div class="admin-user">Signed in as ' . e($username) . '
';
    echo '<form action="logout.php" method="post" class="inline-form"><input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '"><button class="link-button" type="submit">Log out</button></form></div></header>';
    echo '<main class="admin-main"><h1>' . $safeTitle . '</h1>';
    admin_show_flash();
}

function admin_footer(): void
{
    echo '</main><footer class="admin-footer">Portfolio administration</footer></body></html>';
}
