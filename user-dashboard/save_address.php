<?php
/**
 * Manage the signed-in client's saved addresses (Settings → Saved Addresses, and
 * "Add a new address" on Request a Quote).
 *
 * POST action = add     (label, address, make_default)
 *             | update  (id, label, address)
 *             | delete  (id)
 *             | default (id)
 * → JSON { ok, id, addresses: [ {id, label, address, is_default}, ... ] }  (default first)
 */
require_once __DIR__ . '/../includes/helpers.php';
require_client(true);
header('Content-Type: application/json; charset=utf-8');

const MAX_ADDRESSES = 10;

function sa_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') sa_fail('POST required.', 405);

$uid    = (int) current_user()['id'];
$action = (string) ($_POST['action'] ?? '');
$id     = (int) ($_POST['id'] ?? 0);
$label  = mb_substr(trim((string) ($_POST['label'] ?? '')), 0, 60);
$addr   = trim((string) preg_replace('/\s+/', ' ', (string) ($_POST['address'] ?? '')));
$pdo    = db();

$existing = client_addresses($uid); // also creates the table / carries over an old single address
$owned    = array_map(fn($a) => (int) $a['id'], $existing);

$validAddress = function () use ($addr): void {
    if (mb_strlen($addr) < 5)   sa_fail('Enter the full address.');
    if (mb_strlen($addr) > 255) sa_fail('That address is too long (max 255 characters).');
};
$mustOwn = function () use ($id, $owned): void {
    if (!in_array($id, $owned, true)) sa_fail('Address not found.', 404);
};

try {
    switch ($action) {
        case 'add':
            $validAddress();
            if (count($existing) >= MAX_ADDRESSES) {
                sa_fail('You can save up to ' . MAX_ADDRESSES . ' addresses. Delete one to add another.');
            }
            // The first address is always the default.
            $makeDefault = !$existing || !empty($_POST['make_default']);
            if ($makeDefault) {
                $pdo->prepare("UPDATE client_addresses SET is_default = 0 WHERE user_id = ?")->execute([$uid]);
            }
            $pdo->prepare("INSERT INTO client_addresses (user_id, label, address, is_default) VALUES (?,?,?,?)")
                ->execute([$uid, $label !== '' ? $label : null, $addr, $makeDefault ? 1 : 0]);
            $id = (int) $pdo->lastInsertId();
            break;

        case 'update':
            $mustOwn();
            $validAddress();
            $pdo->prepare("UPDATE client_addresses SET label = ?, address = ? WHERE id = ? AND user_id = ?")
                ->execute([$label !== '' ? $label : null, $addr, $id, $uid]);
            break;

        case 'delete':
            $mustOwn();
            // Past requests keep their own copy of the address, so this is safe.
            $pdo->prepare("DELETE FROM client_addresses WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
            break;

        case 'default':
            $mustOwn();
            $pdo->prepare("UPDATE client_addresses SET is_default = (id = ?) WHERE user_id = ?")->execute([$id, $uid]);
            break;

        default:
            sa_fail('Unknown action.');
    }

    sync_client_default_address($uid);
    log_audit('Settings', 'Saved addresses: ' . $action);
    echo json_encode(['ok' => true, 'id' => $id, 'addresses' => client_addresses($uid)]);
} catch (Throwable $e) {
    sa_fail('Server error: ' . $e->getMessage(), 500);
}
