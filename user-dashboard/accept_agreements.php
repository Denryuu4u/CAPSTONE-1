<?php
/**
 * Record a client's acceptance of the current legal documents, then return
 * them to where they were headed. Posted from agreements.php.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/legal.php';
require_login();

$u = current_user();
if (!$u || ($u['role'] ?? '') !== 'Client') {
    header('Location: dashboard.php');
    exit;
}

$next = basename((string) ($_POST['next'] ?? 'dashboard.php')) ?: 'dashboard.php';

// Require the checkbox; if missing, bounce back to the gate.
if (($_POST['agree'] ?? '') !== '1') {
    header('Location: agreements.php?next=' . urlencode($next));
    exit;
}

legal_record_acceptance((int) $u['id'], $_SERVER['REMOTE_ADDR'] ?? null);
log_audit('Auth', 'Accepted latest Terms & Privacy Policy');

header('Location: ' . $next);
exit;
