<?php
/** Save settings from settings.php. Profile+password: any back-office user.
 *  Company / website / costing: Super Admin + Admin only. */
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings_self', true); // any back-office user (profile at minimum)
header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$f = fn($k) => trim((string) ($_POST[$k] ?? ''));
$n = fn($k, $d) => is_numeric($_POST[$k] ?? null) ? (float) $_POST[$k] : (float) $d;

try {
    // Company / website / default-costing settings — admin-managed only.
    if (role_can('settings')) {
        $pdo->prepare(
            "UPDATE company_settings SET
                company_name=?, email=?, contact_number=?, address=?,
                web_email=?, web_phone=?, web_location=?,
                default_markup_pct=?, default_contingency_pct=?, default_service_pct=?, default_protection_pct=?
              WHERE id=1"
        )->execute([
            $f('company_name') ?: 'Vast Solutions',
            $f('email') ?: null,
            $f('contact_number') ?: null,
            $f('address') ?: null,
            $f('web_email') ?: null,
            $f('web_phone') ?: null,
            $f('web_location') ?: null,
            $n('default_markup_pct', 15), $n('default_contingency_pct', 5),
            $n('default_service_pct', 10), $n('default_protection_pct', 3),
        ]);
    }

    // Profile Information — update the signed-in user's own account.
    // Blank fields keep the existing value (COALESCE) so nothing is wiped.
    $me = current_user();
    if (!empty($me['id']) && $f('profile_name') !== '') {
        $pdo->prepare(
            "UPDATE users SET
                full_name = ?,
                email = COALESCE(NULLIF(?, ''), email),
                phone = NULLIF(?, '')
              WHERE id = ?"
        )->execute([
            $f('profile_name'),
            $f('profile_email'),
            $f('profile_phone'),
            (int) $me['id'],
        ]);
        // Keep the session display name in sync.
        if (isset($_SESSION['full_name'])) $_SESSION['full_name'] = $f('profile_name');
    }

    log_audit('Settings', 'Updated company + profile settings');
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
