<?php
/** Customer request capacity and identical-unit batch metadata. */
const CUSTOMER_PENDING_REQUEST_LIMIT = 5;
const CUSTOMER_ONGOING_PROJECT_LIMIT = 5;
const REQUEST_UNIT_LIMIT = 10;

function ensure_request_quantity(): void
{
    static $ready = false;
    if ($ready) return;
    $columns = array_column(db()->query('SHOW COLUMNS FROM project_requests')->fetchAll(), 'Field');
    if (!in_array('unit_quantity', $columns, true)) {
        try { db()->exec('ALTER TABLE project_requests ADD COLUMN unit_quantity TINYINT UNSIGNED NOT NULL DEFAULT 1'); }
        catch (PDOException $e) { if (($e->errorInfo[1] ?? 0) !== 1060) throw $e; }
    }
    $ready = true;
}

function request_capacity_group(string $status): string
{
    $status = project_status_key($status);
    if (in_array($status, ['completed', 'rejected'], true)) return 'closed';
    return $status === 'quote_submitted' ? 'pending' : 'ongoing';
}

/** One project consumes one slot, regardless of batch quantity or quote revisions. */
function customer_request_counts(int $userId, int $customerId = 0): array
{
    $where = $userId > 0 ? 'c.user_id = ?' : 'c.id = ?';
    $identity = $userId > 0 ? $userId : $customerId;
    $st = db()->prepare("SELECT p.status FROM projects p JOIN customers c ON c.id=p.customer_id WHERE $where");
    $st->execute([$identity]);
    $counts = ['pending' => 0, 'ongoing' => 0];
    foreach ($st->fetchAll() as $row) {
        $group = request_capacity_group($row['status']);
        if ($group !== 'closed') $counts[$group]++;
    }
    // Legacy requests may not have a project shell yet; they still use a slot.
    $st = db()->prepare("SELECT COUNT(*) FROM project_requests r JOIN customers c ON c.id=r.customer_id
        WHERE $where AND r.status <> 'Closed' AND NOT EXISTS (SELECT 1 FROM projects p WHERE p.request_id=r.id)");
    $st->execute([$identity]);
    $counts['pending'] += (int) $st->fetchColumn();
    return $counts;
}

/** Same lock for submissions and approvals, including simultaneous browser sessions. */
function lock_customer_requests(int $userId, int $customerId = 0): void
{
    static $held = [];
    $pdo = db();
    $dbHash = substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 12);
    $key = 'vast-requests-' . $dbHash . ($userId > 0 ? '-u' . $userId : '-c' . $customerId);
    if (isset($held[$key])) return;
    $st = $pdo->prepare('SELECT GET_LOCK(?, 10)'); $st->execute([$key]);
    if ((int) $st->fetchColumn() !== 1) throw new RuntimeException('Customer requests are busy. Please try again.');
    $held[$key] = true;
    register_shutdown_function(static function () use ($pdo, $key) {
        try { $st = $pdo->prepare('SELECT RELEASE_LOCK(?)'); $st->execute([$key]); } catch (Throwable $e) {}
    });
}

function customer_submission_limit(array $counts): ?string
{
    if ($counts['pending'] >= CUSTOMER_PENDING_REQUEST_LIMIT) return 'pendinglimit';
    if ($counts['ongoing'] >= CUSTOMER_ONGOING_PROJECT_LIMIT) return 'ongoinglimit';
    return null;
}

/** Capacity guard for approval, phase changes, reopening and pause/resume. */
function require_project_capacity(int $projectId, string $target): void
{
    if ($projectId <= 0) return;
    $st = db()->prepare('SELECT p.status, p.customer_id, c.user_id FROM projects p LEFT JOIN customers c ON c.id=p.customer_id WHERE p.id=?');
    $st->execute([$projectId]); $row = $st->fetch();
    if (!$row || !$row['customer_id']) return;
    $userId = (int) $row['user_id']; $customerId = (int) $row['customer_id'];
    lock_customer_requests($userId, $customerId);
    $group = request_capacity_group($target);
    if ($group === 'closed' || $group === request_capacity_group($row['status'])) return;
    $counts = customer_request_counts($userId, $customerId);
    $limit = $group === 'pending' ? CUSTOMER_PENDING_REQUEST_LIMIT : CUSTOMER_ONGOING_PROJECT_LIMIT;
    if ($counts[$group] >= $limit) {
        throw new DomainException('This customer already has ' . $limit . ($group === 'pending' ? ' pending requests.' : ' ongoing projects (including paused projects).') . ' Close an existing project before moving another into this stage.');
    }
}

function enforce_project_capacity(int $projectId, string $target): void
{
    try { require_project_capacity($projectId, $target); }
    catch (DomainException $e) { project_control_fail($e->getMessage(), 409); }
    catch (RuntimeException $e) { project_control_fail('Customer requests are busy. Please try again.', 503); }
}
