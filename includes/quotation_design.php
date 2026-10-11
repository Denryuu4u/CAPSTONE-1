<?php
require_once __DIR__ . '/helpers.php';

/** Accept verified raster images only; never trust the filename or browser MIME. */
function save_quotation_design_upload(array $file): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) throw new InvalidArgumentException('The design image could not be uploaded. Check the server upload limit and try a smaller image.');
    $tmp = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp) || filesize($tmp) > 8 * 1024 * 1024) throw new InvalidArgumentException('Choose a design image up to 8 MB.');
    $info = @getimagesize($tmp);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($types[$mime]) || ($info['mime'] ?? '') !== $mime || $info[0] * $info[1] > 40000000) {
        throw new InvalidArgumentException('Choose a valid JPG, PNG or WebP design image (up to 40 megapixels).');
    }
    $dir = __DIR__ . '/../uploads/project-designs';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('Could not create design storage.');
    // Railway uses apache-errors.conf; XAMPP uses this per-directory rule.
    if (file_put_contents($dir . '/.htaccess', "Require all denied\n") === false) throw new RuntimeException('Could not protect design storage.');
    $path = 'uploads/project-designs/' . bin2hex(random_bytes(20)) . '.' . $types[$mime];
    if (!move_uploaded_file($tmp, __DIR__ . '/../' . $path)) throw new RuntimeException('Could not save the design image.');
    return $path;
}

function remove_unsaved_design(?string $path): void
{
    if ($path && preg_match('~^uploads/project-designs/[a-f0-9]{40}\.(jpg|png|webp)$~D', $path)) @unlink(__DIR__ . '/../' . $path);
}
