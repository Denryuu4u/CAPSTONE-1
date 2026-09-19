<?php
/**
 * Project chat: a per-project message channel between the client and the back
 * office (admin/staff). Used by admin/project_chat.php and
 * user-dashboard/project_chat.php, and rendered by includes/chat_modal.php.
 *
 * Self-migrating: the table is created on first use so the feature works on
 * Railway without a manual migration step.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/** Create the project_messages table once (cached per request). */
function chat_ensure_table(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    db()->exec(
        "CREATE TABLE IF NOT EXISTS project_messages (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            project_id  INT UNSIGNED NOT NULL,
            sender_id   INT UNSIGNED NULL,
            sender_role VARCHAR(20)  NOT NULL,   -- 'client' or 'admin'
            sender_name VARCHAR(150) NOT NULL,
            body        VARCHAR(2000) NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pm_project (project_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

/**
 * Can the given user access this project's chat?
 * Back-office roles → any project. Clients → only their own projects.
 */
function chat_can_access(int $projectId, ?array $user): bool
{
    if (!$user || $projectId <= 0) return false;
    $role = $user['role'] ?? '';
    if (in_array($role, ['Super Admin', 'Admin', 'Staff'], true)) return true;

    // Client: the project must belong to a customer linked to this user.
    $st = db()->prepare(
        "SELECT 1 FROM projects p
           JOIN customers c ON c.id = p.customer_id
          WHERE p.id = ? AND c.user_id = ? LIMIT 1"
    );
    $st->execute([$projectId, (int) $user['id']]);
    return (bool) $st->fetchColumn();
}

/** 'admin' for back-office roles, 'client' otherwise. */
function chat_side(string $role): string
{
    return in_array($role, ['Super Admin', 'Admin', 'Staff'], true) ? 'admin' : 'client';
}

/** All messages for a project, oldest first. */
function chat_fetch(int $projectId): array
{
    chat_ensure_table();
    $st = db()->prepare(
        "SELECT id, sender_role, sender_name, body, created_at
           FROM project_messages WHERE project_id = ? ORDER BY created_at ASC, id ASC"
    );
    $st->execute([$projectId]);
    $out = [];
    foreach ($st->fetchAll() as $m) {
        $out[] = [
            'id'     => (int) $m['id'],
            'side'   => $m['sender_role'],                 // 'admin' | 'client'
            'name'   => $m['sender_name'],
            'body'   => $m['body'],
            'time'   => date('M d, g:i A', strtotime($m['created_at'])),
        ];
    }
    return $out;
}

/** Insert a message; returns the new id. */
function chat_send(int $projectId, array $user, string $body): int
{
    chat_ensure_table();
    $body = trim($body);
    if ($body === '') return 0;
    if (function_exists('mb_substr')) $body = mb_substr($body, 0, 2000);
    $side = chat_side($user['role'] ?? '');
    db()->prepare(
        "INSERT INTO project_messages (project_id, sender_id, sender_role, sender_name, body)
         VALUES (?,?,?,?,?)"
    )->execute([$projectId, (int) $user['id'], $side, $user['full_name'] ?? ucfirst($side), $body]);
    return (int) db()->lastInsertId();
}
