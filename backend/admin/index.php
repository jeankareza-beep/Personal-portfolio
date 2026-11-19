<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';
require_admin();

$pdo = db();
$counts = [];
foreach (['projects', 'skills', 'messages', 'experience'] as $table) {
    $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
}
$unread = (int) $pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
admin_header('Dashboard');
?>
<p>Manage the portfolio content and visitor messages from this dashboard.</p>
<div class="stat-grid">
<?php foreach (['projects' => 'Projects', 'skills' => 'Skills', 'experience' => 'Experiences', 'messages' => 'Messages'] as $key => $label): ?>
    <article class="stat-card"><span><?= e($label) ?></span><strong><?= (int) $counts[$key] ?></strong></article>
<?php endforeach; ?>
</div>
<section class="panel"><h2>Message inbox</h2><p><?= $unread ?> unread message<?= $unread === 1 ? '' : 's' ?>.</p><a class="primary button-link" href="messages.php">Open messages</a></section>
<section class="panel"><h2>Portfolio content</h2><p>Edits you save here are stored in MySQL and returned by the public portfolio API.</p><div class="action-row"><a href="profile.php">Edit profile</a><a href="content.php?type=projects">Manage projects</a><a href="settings.php">Site settings</a></div></section>
<?php admin_footer(); ?>
