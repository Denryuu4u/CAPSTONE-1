<?php
/** Authenticated image access. Never expose private upload paths to the browser. */
require_once __DIR__ . '/includes/helpers.php';
if (!current_user()) { http_response_code(401); exit('Sign in to view the design.'); }
ensure_project_features();
$id = (int) ($_GET['quotation_id'] ?? 0);
$st = db()->prepare('SELECT q.design_path, c.user_id FROM quotations q JOIN customers c ON c.id=q.customer_id WHERE q.id=?');
$st->execute([$id]); $q = $st->fetch();
if (!$q || (!in_array(current_role(), BACKOFFICE_ROLES, true) && !(current_role() === 'Client' && (int) $q['user_id'] === current_user()['id']))) {
    http_response_code(404); exit('Design not found.');
}
$path = $q['design_path'] ?? '';
if (!preg_match('~^uploads/project-designs/[a-f0-9]{40}\.(jpg|png|webp)$~D', $path) || !is_file(__DIR__ . '/' . $path)) {
    http_response_code(404); exit('Design not found.');
}
$mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][pathinfo($path, PATHINFO_EXTENSION)];
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Disposition: inline; filename="project-design.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
readfile(__DIR__ . '/' . $path);
