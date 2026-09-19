<?php
/**
 * Lightweight change-signature for the signed-in client, polled by the customer
 * pages so they can auto-refresh when an admin changes something (status update,
 * quote accept/approve, new timeline update, new notification) — no manual refresh.
 *
 * Returns { "sig": "<string>" }. The page remembers the first value and reloads
 * when it changes. Cheap: a handful of MAX()/COUNT() aggregates, no row payloads.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$u   = current_user();
$uid = $u['id'] ?? 0;
if (!$uid) { echo json_encode(['sig' => '']); exit; }

try {
    $st = db()->prepare(
        "SELECT
            (SELECT COALESCE(MAX(UNIX_TIMESTAMP(p.updated_at)),0)
               FROM projects p JOIN customers c ON c.id=p.customer_id WHERE c.user_id=?)                      AS p_upd,
            (SELECT COALESCE(MAX(UNIX_TIMESTAMP(q.updated_at)),0)
               FROM quotations q JOIN customers c ON c.id=q.customer_id WHERE c.user_id=?)                    AS q_upd,
            (SELECT COUNT(*)
               FROM project_updates pu
               JOIN projects p ON p.id=pu.project_id
               JOIN customers c ON c.id=p.customer_id WHERE c.user_id=?)                                      AS upd_cnt,
            (SELECT COALESCE(MAX(UNIX_TIMESTAMP(created_at)),0) FROM notifications WHERE user_id=?)           AS n_max,
            (SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0)                                AS n_unread"
    );
    $st->execute([$uid, $uid, $uid, $uid, $uid]);
    $r = $st->fetch() ?: [];
    $sig = implode('.', [
        (int) ($r['p_upd'] ?? 0),
        (int) ($r['q_upd'] ?? 0),
        (int) ($r['upd_cnt'] ?? 0),
        (int) ($r['n_max'] ?? 0),
        (int) ($r['n_unread'] ?? 0),
    ]);
    echo json_encode(['sig' => $sig]);
} catch (Throwable $e) {
    echo json_encode(['sig' => '']); // on error, don't trigger reloads
}
