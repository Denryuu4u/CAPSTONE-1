<?php
/**
 * Per-project cost tracking (the "Project Tracking" ledger).
 *
 * Mirrors the business's tracking spreadsheet: a project has a contract
 * PROJECT AMOUNT, a list of material/supplier EXPENSES, and dated LABOR rows
 * split into Assembly and Installation. From these we derive:
 *   PROJECT COST   = expenses + assembly labor + installation labor
 *   PROFIT / LOSS  = amount - cost
 *
 * Storage (3 tables, self-migrating so it also works on Railway):
 *   project_tracking (one row per project) → tracking_expenses, tracking_labor
 */

require_once __DIR__ . '/db.php';

/** Create the tracking tables if they don't exist. Runs once per request. */
function ensure_tracking_tables(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = db();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS project_tracking (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id INT UNSIGNED NOT NULL,
            project_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            notes TEXT DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tracking_project (project_id),
            CONSTRAINT fk_tracking_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tracking_expenses (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            tracking_id INT UNSIGNED NOT NULL,
            label VARCHAR(120) NOT NULL,
            amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY ix_texp_tracking (tracking_id),
            CONSTRAINT fk_texp_tracking FOREIGN KEY (tracking_id) REFERENCES project_tracking(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tracking_labor (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            tracking_id INT UNSIGNED NOT NULL,
            phase ENUM('assembly','installation') NOT NULL DEFAULT 'assembly',
            work_date DATE DEFAULT NULL,
            manpower VARCHAR(120) NOT NULL,
            rate DECIMAL(12,2) NOT NULL DEFAULT 0,
            gas_toll DECIMAL(12,2) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY ix_tlab_tracking (tracking_id),
            CONSTRAINT fk_tlab_tracking FOREIGN KEY (tracking_id) REFERENCES project_tracking(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

/** Compute the derived totals for a tracking ledger. */
function tracking_compute(float $amount, array $expenses, array $labor): array
{
    $expTotal = 0.0;
    foreach ($expenses as $e) $expTotal += (float) ($e['amount'] ?? 0);

    $asm = 0.0; $ins = 0.0;
    foreach ($labor as $l) {
        $t = (float) ($l['rate'] ?? 0) + (float) ($l['gas_toll'] ?? 0);
        if (($l['phase'] ?? 'assembly') === 'installation') $ins += $t; else $asm += $t;
    }
    $cost = $expTotal + $asm + $ins;
    return [
        'expenses_total'     => round($expTotal, 2),
        'assembly_total'     => round($asm, 2),
        'installation_total' => round($ins, 2),
        'project_cost'       => round($cost, 2),
        'profit_loss'        => round($amount - $cost, 2),
    ];
}

/**
 * Load a project's full tracking ledger + the project's own header info.
 * Returns null only if the project itself doesn't exist. If the project exists
 * but has no tracking yet, returns an empty ledger (amount seeded from the
 * approved quotation total when available) so the editor can start fresh.
 */
function get_project_tracking(int $projectId): ?array
{
    ensure_tracking_tables();
    $pdo = db();

    $ps = $pdo->prepare(
        "SELECT p.id, p.project_code, p.project_name, c.name AS customer,
                (SELECT total_amount FROM quotations q WHERE q.project_id = p.id
                  ORDER BY (q.status='Approved') DESC, q.id DESC LIMIT 1) AS quote_total
           FROM projects p LEFT JOIN customers c ON c.id = p.customer_id
          WHERE p.id = ?"
    );
    $ps->execute([$projectId]);
    $proj = $ps->fetch();
    if (!$proj) return null;

    $ts = $pdo->prepare("SELECT * FROM project_tracking WHERE project_id = ?");
    $ts->execute([$projectId]);
    $t = $ts->fetch();

    $expenses = [];
    $labor    = [];
    $amount   = 0.0;
    $notes    = '';
    $exists   = false;

    if ($t) {
        $exists = true;
        $amount = (float) $t['project_amount'];
        $notes  = (string) ($t['notes'] ?? '');
        $ex = $pdo->prepare("SELECT label, amount FROM tracking_expenses WHERE tracking_id = ? ORDER BY sort_order, id");
        $ex->execute([$t['id']]);
        foreach ($ex as $r) $expenses[] = ['label' => $r['label'], 'amount' => (float) $r['amount']];
        $lb = $pdo->prepare("SELECT phase, work_date, manpower, rate, gas_toll FROM tracking_labor WHERE tracking_id = ? ORDER BY phase, sort_order, id");
        $lb->execute([$t['id']]);
        foreach ($lb as $r) $labor[] = [
            'phase' => $r['phase'], 'work_date' => $r['work_date'],
            'manpower' => $r['manpower'], 'rate' => (float) $r['rate'], 'gas_toll' => (float) $r['gas_toll'],
        ];
    } else {
        // Seed the contract amount from the approved quotation if there is one.
        $amount = (float) ($proj['quote_total'] ?? 0);
    }

    return [
        'exists'   => $exists,
        'project'  => [
            'id' => (int) $proj['id'], 'code' => $proj['project_code'],
            'name' => $proj['project_name'], 'customer' => $proj['customer'] ?? '—',
        ],
        'amount'   => $amount,
        'notes'    => $notes,
        'expenses' => $expenses,
        'labor'    => $labor,
        'totals'   => tracking_compute($amount, $expenses, $labor),
    ];
}
