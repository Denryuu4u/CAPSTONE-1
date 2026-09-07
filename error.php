<?php
/**
 * Friendly error page. Endpoints that can't complete a request (e.g. a missing
 * server capability, a not-found record, or a bad parameter) redirect here
 * instead of dumping raw text:
 *
 *   header('Location: ' . BASE_URL . '/error.php?msg=' . urlencode($message));
 *
 * Optional query params: msg (shown to the user), title, code (HTTP-ish label).
 */
require_once __DIR__ . '/includes/auth.php';

$msg   = trim((string) ($_GET['msg'] ?? ''));
$title = trim((string) ($_GET['title'] ?? 'Something went wrong'));
if ($msg === '') $msg = 'The request could not be completed. Please try again, or contact support if it keeps happening.';

// Where "home" goes depends on who's viewing.
$home = function_exists('role_home') ? role_home() : (BASE_URL . '/index.php');
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $e($title) ?> – Vast Solutions</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  *{box-sizing:border-box;} body{margin:0;font-family:'Inter',system-ui,sans-serif;background:#f4f5f7;
    min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;color:#1f2937;}
  .err-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 18px 50px rgba(13,27,42,.12);
    max-width:460px;width:100%;padding:36px 34px;text-align:center;}
  .err-icon{width:72px;height:72px;border-radius:50%;background:#fef2f2;color:#dc2626;display:flex;
    align-items:center;justify-content:center;font-size:2rem;margin:0 auto 18px;}
  .err-title{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;color:#0d1b2a;margin:0 0 8px;}
  .err-msg{font-size:.92rem;color:#4b5563;line-height:1.6;margin-bottom:24px;}
  .err-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
  .err-btn{display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;border-radius:9px;
    padding:10px 20px;cursor:pointer;text-decoration:none;border:1px solid transparent;transition:background .15s,border-color .15s;}
  .err-btn-primary{background:#0D9676;color:#fff;} .err-btn-primary:hover{background:#0a7a60;}
  .err-btn-ghost{background:#fff;border-color:#e5e7eb;color:#374151;} .err-btn-ghost:hover{border-color:#cbd5e1;background:#f9fafb;}
</style>
</head>
<body>
  <div class="err-card">
    <div class="err-icon"><i class="bi bi-exclamation-triangle"></i></div>
    <h1 class="err-title"><?= $e($title) ?></h1>
    <p class="err-msg"><?= $e($msg) ?></p>
    <div class="err-actions">
      <a href="#" class="err-btn err-btn-ghost" onclick="history.back();return false;"><i class="bi bi-arrow-left"></i> Go Back</a>
      <a href="<?= $e($home) ?>" class="err-btn err-btn-primary"><i class="bi bi-house-door"></i> Dashboard</a>
    </div>
  </div>
</body>
</html>
