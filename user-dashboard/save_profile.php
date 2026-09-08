<?php
/**
 * Update the signed-in client's own profile (Settings → Profile Information).
 *
 * Two modes, both JSON:
 *   - multipart with an `avatar` file  → save the new photo, return its URL.
 *   - form fields (full_name/email/phone) → update the profile row.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

function sp_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

$me = current_user();
if (empty($me['id'])) sp_fail('Not signed in.', 401);
$uid = (int) $me['id'];
$pdo = db();

// ── Avatar upload ────────────────────────────────────────────────────
if (!empty($_FILES['avatar']['name'])) {
    $f = $_FILES['avatar'];
    if ($f['error'] !== UPLOAD_ERR_OK) sp_fail('Upload failed. Please try again.');
    if ($f['size'] > 5 * 1024 * 1024)  sp_fail('Image is too large (max 5 MB).');

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        sp_fail('Use a JPG, PNG, GIF or WEBP image.');
    }
    $info = @getimagesize($f['tmp_name']);
    if ($info === false) sp_fail('That file is not a valid image.');

    $destDir = __DIR__ . '/../uploads/avatars';
    if (!is_dir($destDir)) @mkdir($destDir, 0777, true);
    $safe = 'av_' . $uid . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $destDir . '/' . $safe)) {
        sp_fail('Could not save the image on the server.', 500);
    }
    $relPath = 'uploads/avatars/' . $safe;

    try {
        // Remove the previous uploaded avatar (keep disk tidy).
        $prev = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $prev->execute([$uid]);
        $old = (string) $prev->fetchColumn();
        if ($old && strpos($old, 'uploads/avatars/') === 0) {
            @unlink(__DIR__ . '/../' . $old);
        }
        $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$relPath, $uid]);
        log_audit('Settings', 'Updated profile photo');
        echo json_encode(['ok' => true, 'avatar' => BASE_URL . '/' . $relPath]);
    } catch (Throwable $e) {
        sp_fail('Server error: ' . $e->getMessage(), 500);
    }
    exit;
}

// ── Profile fields ───────────────────────────────────────────────────
$name    = trim((string) ($_POST['full_name'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$phone   = trim((string) ($_POST['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));

if ($name === '')  sp_fail('Name is required.');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) sp_fail('Enter a valid email address.');

try {
    // Guard against taking another account's email.
    $dupe = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
    $dupe->execute([$email, $uid]);
    if ($dupe->fetch()) sp_fail('That email is already in use by another account.');

    $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = NULLIF(?, ''), location = NULLIF(?, '') WHERE id = ?")
        ->execute([$name, $email, $phone, $address, $uid]);

    // Keep the client's customer record in sync (this address is what appears on
    // the quotation as the installation / bill-to address), if a customer is linked.
    $pdo->prepare("UPDATE customers SET email = ?, phone = NULLIF(?, ''), address = NULLIF(?, '') WHERE user_id = ?")
        ->execute([$email, $phone, $address, $uid]);

    if (isset($_SESSION['full_name'])) $_SESSION['full_name'] = $name;
    log_audit('Settings', 'Updated profile information');
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    sp_fail('Server error: ' . $e->getMessage(), 500);
}
