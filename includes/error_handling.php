<?php
/** Friendly HTTP failures; technical diagnostics stay in the PHP server log. */
function app_log_error(Throwable $error): string
{
    $reference = str_replace('.', '', uniqid('ERR-'));
    // Do not log POST bodies, passwords, session values or stack arguments.
    @error_log('[' . $reference . '] ' . get_class($error) . ': ' . $error->getMessage()
        . ' in ' . $error->getFile() . ':' . $error->getLine());
    return $reference;
}

function app_error_message(Throwable $error, string $message = 'We could not complete your request. Please try again in a moment.'): string
{
    app_log_error($error);
    return $message;
}

function app_error_base_url(): string
{
    $base = getenv('BASE_URL');
    return $base === false ? '/CAPSTONE-1' : rtrim($base, '/');
}

function app_error_wants_json(): bool
{
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type: application/json') === 0) return true;
    }
    if (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') return true;
    // Authentication/database initialization can fail before an action sets its
    // Content-Type. Identify the existing JSON endpoints before those includes.
    return in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), [
        'archive_entity.php', 'backup_action.php', 'contact_action.php', 'create_quotation.php',
        'delete_gallery_image.php', 'generate_revert_code.php', 'import_material_totals.php',
        'library_api.php', 'mark_messages_read.php', 'mark_notifications_read.php', 'mark_ready.php',
        'post_update.php', 'process_cutlist.php', 'revert_project_status.php', 'revise_quotation.php',
        'revoke_revert_code.php', 'save_branding.php', 'save_customer.php', 'save_gallery_image.php',
        'save_legal.php', 'save_logo.php', 'save_password.php', 'save_project.php', 'save_settings.php',
        'save_tracking.php', 'save_user.php', 'send_project_design.php', 'set_project_state.php',
        'tracking_data.php', 'update_project_status.php', 'update_quotation.php', 'change_password.php',
        'confirm_completion.php', 'poll_updates.php', 'post_comment.php', 'report_completion_issue.php',
        'save_address.php', 'save_profile.php',
    ], true);
}

function app_render_failure(Throwable $error): void
{
    $json = app_error_wants_json();
    $GLOBALS['app_failure_rendered'] = true;
    $reference = app_log_error($error);
    // Discard a partially rendered page or unfinished JSON response.
    while (ob_get_level() > ($GLOBALS['app_error_buffer_level'] ?? 0)) ob_end_clean();
    if (!headers_sent()) {
        foreach (['Content-Type', 'Content-Length', 'Content-Disposition', 'Location'] as $header) header_remove($header);
        http_response_code(500);
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . ($json ? 'application/json' : 'text/html') . '; charset=utf-8');
    }
    $message = 'We could not complete your request. Please try again in a moment.';
    if ($json) {
        echo json_encode(['ok'=>false, 'error'=>$message, 'reference'=>$reference]);
        return;
    }
    $home = htmlspecialchars(app_error_base_url() . '/index.php', ENT_QUOTES, 'UTF-8');
    $ref = htmlspecialchars($reference, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Something went wrong – Vast Solutions</title><style>'
        . '*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f4f5f7;color:#1f2937;font-family:Arial,sans-serif}'
        . 'main{max-width:460px;padding:36px;background:white;border:1px solid #e5e7eb;border-radius:16px;text-align:center;box-shadow:0 18px 50px #0d1b2a15}'
        . '.icon{font-size:32px;color:#b45309}h1{font-size:24px;color:#0d1b2a}p{line-height:1.6;color:#4b5563}small{display:block;color:#6b7280;margin:20px 0}'
        . 'a{display:inline-block;background:#0d9676;color:white;border-radius:8px;padding:12px 20px;text-decoration:none}</style></head><body>'
        . '<main role="alert"><div class="icon" aria-hidden="true">!</div><h1>Something went wrong</h1><p>' . $message . '</p>'
        . '<p>If this keeps happening, contact the administrator and share the reference below.</p><small>Reference: ' . $ref . '</small><a href="' . $home . '">Go to home page</a></main></body></html>';
}

// CLI tools/tests retain normal exception behavior and terminal diagnostics.
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
    $GLOBALS['app_error_buffer_level'] = ob_get_level();
    ob_start();
    $GLOBALS['app_error_memory_reserve'] = str_repeat(' ', 32768);
    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) return false; // respect intentional @ suppression
        if (in_array($severity, [E_WARNING, E_USER_WARNING, E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
        // PHP logs notices/deprecations, but never prints them into a response.
        return false;
    });
    set_exception_handler('app_render_failure');
    register_shutdown_function(static function (): void {
        $GLOBALS['app_error_memory_reserve'] = null;
        if (!empty($GLOBALS['app_failure_rendered'])) return;
        $last = error_get_last();
        if ($last && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            app_render_failure(new ErrorException($last['message'], 0, $last['type'], $last['file'], $last['line']));
        }
    });
}
