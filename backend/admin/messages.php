<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin_helpers.php';
require_admin();

$pdo = db();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_post();
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = (string) ($_POST['action'] ?? '');
    if ($id === false || $id === null) {
        admin_set_flash('Choose a valid message.', 'error');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        admin_set_flash('Message deleted.');
    } elseif ($action === 'read' || $action === 'unread') {
        $stmt = $pdo->prepare('UPDATE messages SET is_read = :is_read WHERE id = :id');
        $stmt->execute(['is_read' => $action === 'read' ? 1 : 0, 'id' => $id]);
        admin_set_flash($action === 'read' ? 'Message marked as read.' : 'Message marked as unread.');
    } else {
        admin_set_flash('Unknown message action.', 'error');
    }
    header('Location: messages.php');
    exit;
}

$messages = $pdo->query('SELECT id, name, email, subject, message, is_read, created_at FROM messages ORDER BY created_at DESC LIMIT 200')->fetchAll();
admin_header('Contact Messages');
?>
<p>Messages are stored in MySQL. Showing the most recent 200.</p>
<?php if (!$messages): ?><section class="panel"><p>No messages received yet.</p></section><?php endif; ?>
<?php foreach ($messages as $message): ?>
<article class="panel message-card <?= (int) $message['is_read'] === 0 ? 'unread' : '' ?>">
<div class="message-heading"><h2><?= e((string) $message['subject']) ?></h2><span><?= (int) $message['is_read'] === 0 ? 'Unread' : 'Read' ?></span></div>
<p><strong><?= e((string) $message['name']) ?></strong> · <a href="mailto:<?= e((string) $message['email']) ?>"><?= e((string) $message['email']) ?></a> · <?= e((string) $message['created_at']) ?></p>
<p class="message-body"><?= nl2br(e((string) $message['message'])) ?></p>
<div class="action-row">
<form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $message['id'] ?>"><input type="hidden" name="action" value="<?= (int) $message['is_read'] === 0 ? 'read' : 'unread' ?>"><button class="secondary" type="submit">Mark as <?= (int) $message['is_read'] === 0 ? 'read' : 'unread' ?></button></form>
<form method="post" class="inline-form" onsubmit="return confirm('Delete this message permanently?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $message['id'] ?>"><input type="hidden" name="action" value="delete"><button class="secondary danger-link" type="submit">Delete</button></form>
</div></article>
<?php endforeach; ?>
<?php admin_footer(); ?>
