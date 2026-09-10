<?php
/**
 * Save branding identity (company name + landing-page tagline).
 * Super Admin only. Logo is handled separately by save_logo.php.
 * POST: company_name, tagline  → JSON {ok}
 */
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

if (current_role() !== 'Super Admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only a Super Admin can change branding.']);
    exit;
}

$name    = trim((string) ($_POST['company_name'] ?? ''));
$tagline = trim((string) ($_POST['tagline'] ?? ''));
if ($name === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Company name is required.']);
    exit;
}

try {
    $pdo = db();
    ensure_company_branding(); // ensures the row + tagline column exist
    $pdo->prepare("UPDATE company_settings SET company_name = ?, tagline = NULLIF(?, '') WHERE id = 1")
        ->execute([mb_substr($name, 0, 150), mb_substr($tagline, 0, 255)]);
    log_audit('Settings', 'Updated branding (name/tagline)', $name);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
