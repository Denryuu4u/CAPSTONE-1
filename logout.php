<?php
/**
 * Log out: destroy the session and return to the public landing page.
 *
 * Note: while DEV_MODE is on (see includes/auth.php), visiting any page again
 * auto-logs-in the dev user, so logout mainly matters once real auth is enabled.
 */
require_once __DIR__ . '/includes/auth.php'; // for BASE_URL + session bootstrap
$_SESSION = [];
session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/index.php');
exit;
