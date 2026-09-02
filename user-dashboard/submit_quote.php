<?php
/**
 * Handle a client's "Request a Quote" submission (request_quote.php).
 * Creates: project_request (+ uploaded files) and a project shell
 * (status quote_submitted), notifies admins, then returns to my_projects.
 */
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: request_quote.php');
    exit;
}

$user = current_user();
$pdo  = db();

/** Resolve (or create) the customer record for the current client user. */
function resolve_customer(PDO $pdo, array $user): int
{
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $id = $stmt->fetchColumn();
    if ($id) return (int) $id;

    // No customer yet — create one from the user account.
    $stmt = $pdo->prepare(
        "INSERT INTO customers (user_id, name, contact_person, email)
         VALUES (?,?,?,?)"
    );
    $stmt->execute([
        $user['id'],
        $user['full_name'] ?: 'Client',
        $user['full_name'] ?: null,
        null,
    ]);
    return (int) $pdo->lastInsertId();
}

// ── Collect + sanitise input ─────────────────────────────────────────
$projectName  = trim($_POST['project_name']  ?? '');
$category     = trim($_POST['category']       ?? '');
$materialType = trim($_POST['material_type']  ?? '');
$dimensions   = trim($_POST['dimensions']     ?? '');
$targetDate   = trim($_POST['target_completion'] ?? '');
$notes        = trim($_POST['notes']          ?? '');
$reference    = trim($_POST['reference_design'] ?? '');
$budgetRaw    = trim($_POST['budget']         ?? '');
$budget       = ($budgetRaw !== '') ? (float) preg_replace('/[^0-9.]/', '', $budgetRaw) : null;

if ($projectName === '') {
    header('Location: request_quote.php?error=name');
    exit;
}

$customerId = resolve_customer($pdo, $user);

try {
    $pdo->beginTransaction();

    // 1) Project request
    $reqCode = next_code('REQ');
    $stmt = $pdo->prepare(
        "INSERT INTO project_requests
            (request_code, customer_id, submitted_by, project_name, category,
             material_type, dimensions, budget, target_completion, reference_design, notes, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?, 'Requesting Quotation')"
    );
    $stmt->execute([
        $reqCode, $customerId, $user['id'], $projectName, $category,
        $materialType, ($dimensions ?: null), $budget, ($targetDate ?: null), ($reference ?: null), ($notes ?: null),
    ]);
    $requestId = (int) $pdo->lastInsertId();

    // 2) Uploaded design files
    if (!empty($_FILES['design_files']) && is_array($_FILES['design_files']['name'])) {
        $allowed  = ['pdf', 'dwg', 'skp', 'jpg', 'jpeg', 'png'];
        $destDir  = __DIR__ . '/../uploads';
        $fileStmt = $pdo->prepare(
            "INSERT INTO request_files (request_id, file_name, file_path, file_type, file_size)
             VALUES (?,?,?,?,?)"
        );
        foreach ($_FILES['design_files']['name'] as $i => $origName) {
            if (($_FILES['design_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;

            $safe    = 'req' . $requestId . '_' . uniqid() . '.' . $ext;
            $tmp     = $_FILES['design_files']['tmp_name'][$i];
            $size    = (int) ($_FILES['design_files']['size'][$i] ?? 0);
            if (move_uploaded_file($tmp, $destDir . '/' . $safe)) {
                $fileStmt->execute([$requestId, $origName, 'uploads/' . $safe, $ext, $size]);
            }
        }
    }

    // 3) Project shell (enters the monitoring pipeline at quote_submitted)
    $prjCode = next_code('PRJ');
    $stmt = $pdo->prepare(
        "INSERT INTO projects
            (project_code, customer_id, request_id, project_name, category, description, target_completion, status, progress)
         VALUES (?,?,?,?,?,?,?, 'quote_submitted', 0)"
    );
    $stmt->execute([$prjCode, $customerId, $requestId, $projectName, $category, ($notes ?: null), ($targetDate ?: null)]);
    $projectId = (int) $pdo->lastInsertId();

    $pdo->commit();

    // 4) Notify admins + audit
    notify([
        'target_role' => 'Admin',
        'type'        => 'request',
        'title'       => 'New quote request',
        'message'     => "{$projectName} ({$reqCode})",
        'link'        => '../admin/project-requests.php',
        'severity'    => 'info',
        'project_id'  => $projectId,
    ]);
    log_audit('Project Requests', "Submitted quote request {$reqCode}", $projectName);

    header('Location: my_projects.php?submitted=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: request_quote.php?error=server');
    exit;
}
