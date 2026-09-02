<?php
/**
 * Legal documents (Terms & Conditions, Privacy Policy) + client acceptance.
 *
 * The editable content lives in `legal_documents` (one row per doc, each with a
 * `version`). Whenever a Super Admin saves a change (admin/save_legal.php) the
 * version is bumped, which invalidates every client's prior acceptance and forces
 * them to re-read + re-accept via user-dashboard/agreements.php.
 *
 * Acceptances are recorded in `user_agreements` (user_id, doc_key, version).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/** The two document keys we manage, in display order. */
const LEGAL_KEYS = ['terms', 'privacy'];

/** All legal documents (terms first, then privacy). */
function legal_docs(): array
{
    return db()->query(
        "SELECT * FROM legal_documents ORDER BY FIELD(doc_key,'terms','privacy'), doc_key"
    )->fetchAll();
}

/** A single legal document by key, or null. */
function legal_doc(string $key): ?array
{
    $st = db()->prepare("SELECT * FROM legal_documents WHERE doc_key = ? LIMIT 1");
    $st->execute([$key]);
    return $st->fetch() ?: null;
}

/**
 * Documents whose CURRENT version the given user has not yet accepted.
 * An empty array means the user is fully up to date.
 */
function legal_pending(int $userId): array
{
    $st = db()->prepare(
        "SELECT d.*
           FROM legal_documents d
          WHERE NOT EXISTS (
                SELECT 1 FROM user_agreements a
                 WHERE a.user_id = ? AND a.doc_key = d.doc_key AND a.version = d.version)
          ORDER BY FIELD(d.doc_key,'terms','privacy'), d.doc_key"
    );
    $st->execute([$userId]);
    return $st->fetchAll();
}

/** Record that a user accepted the current version of every legal document. */
function legal_record_acceptance(int $userId, ?string $ip = null): void
{
    $docs = legal_docs();
    $ins  = db()->prepare(
        "INSERT INTO user_agreements (user_id, doc_key, version, ip_address)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE accepted_at = CURRENT_TIMESTAMP, ip_address = VALUES(ip_address)"
    );
    foreach ($docs as $d) {
        $ins->execute([$userId, $d['doc_key'], (int) $d['version'], $ip]);
    }
}

/**
 * Page guard for the client portal: if the signed-in client has outstanding
 * legal documents to accept, send them to the acceptance page first.
 * No-op for guests and for back-office roles (Super Admin / Admin / Staff).
 *
 * Call this right after require_login() on client pages.
 */
function require_agreements(string $agreementsPath = 'agreements.php'): void
{
    $u = current_user();
    if (!$u) {
        return; // guests are handled by require_login()
    }
    if (($u['role'] ?? '') !== 'Client') {
        return; // only clients must accept the customer-facing terms
    }
    if (legal_pending((int) $u['id'])) {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . $agreementsPath . '?next=' . urlencode(basename($next)));
        exit;
    }
}

/**
 * Render a legal document body (simple markup) as safe HTML.
 * Markup:  "# heading", "- bullet", "-- sub-bullet", blank line = new block,
 * everything else = paragraph text. All text is HTML-escaped.
 */
function legal_render(string $body): string
{
    $lines = preg_split('/\r\n|\r|\n/', $body);
    $html  = '';
    $list  = 0; // current open <ul> nesting depth (0,1,2)
    $para  = [];

    $esc = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

    $flushPara = function () use (&$para, &$html, $esc) {
        if ($para) {
            $html .= '<p>' . $esc(implode(' ', $para)) . '</p>';
            $para = [];
        }
    };
    $closeLists = function (int $to = 0) use (&$list, &$html) {
        while ($list > $to) { $html .= '</ul>'; $list--; }
    };

    foreach ($lines as $raw) {
        $line = rtrim($raw);
        $trim = ltrim($line);

        if ($trim === '') {                 // blank → end current block
            $flushPara();
            $closeLists(0);
            continue;
        }
        if (str_starts_with($trim, '# ')) { // heading
            $flushPara(); $closeLists(0);
            $html .= '<h3 class="legal-h">' . $esc(substr($trim, 2)) . '</h3>';
            continue;
        }
        if (str_starts_with($trim, '-- ')) { // sub-bullet
            $flushPara();
            while ($list < 2) { $html .= '<ul>'; $list++; }
            $html .= '<li>' . $esc(substr($trim, 3)) . '</li>';
            continue;
        }
        if (str_starts_with($trim, '- ')) {  // bullet
            $flushPara();
            $closeLists(1);
            while ($list < 1) { $html .= '<ul>'; $list++; }
            $html .= '<li>' . $esc(substr($trim, 2)) . '</li>';
            continue;
        }
        // plain paragraph text (accumulate consecutive lines)
        $closeLists(0);
        $para[] = $trim;
    }
    $flushPara();
    $closeLists(0);
    return $html;
}
