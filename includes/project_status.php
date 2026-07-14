<?php
/**
 * Canonical project status vocabulary.
 *
 * A project's status IS its position in the monitoring phase tracker — the two
 * must never drift apart. Every screen that renders a project status (monitoring,
 * customers, admin dashboard, reports, my_projects) reads its labels, badge
 * classes and step index from here.
 *
 * Off-track statuses (on_hold, rejected) sit outside the phase sequence and have
 * no step index.
 */

/** The 10 monitoring phases, in order. Array order defines the step index. */
function project_phases(): array {
    return [
        'quote_submitted' => 'Quote Submitted',
        'approved'        => 'Approved',
        'production'      => 'Production in Progress',
        'mockup'          => 'Mockup',
        'delivery'        => 'Delivery',
        'installation'    => 'Installation',
        'quality_check'   => 'Quality Check',
        'punchlist'       => 'Punchlist',
        'final_approval'  => 'Final Approval',
        'completed'       => 'Completed',
    ];
}

/** Statuses that halt the flow rather than advance it. */
function project_off_track(): array {
    return [
        'on_hold'  => 'On Hold',
        'rejected' => 'Rejected',
    ];
}

function project_statuses(): array {
    return project_phases() + project_off_track();
}

/**
 * Old free-text statuses → canonical keys. Kept so legacy data (and any hand-written
 * markup we missed) still resolves instead of rendering a blank badge.
 */
function project_status_key(string $status): string {
    $s = strtolower(trim($status));
    if ($s === '') return 'quote_submitted';
    if (isset(project_statuses()[$s])) return $s;

    return match ($s) {
        'waiting approval', 'waiting', 'pending', 'pending quote',
        'quote received', 'quote sent'                     => 'quote_submitted',
        'fabrication', 'in fabrication',
        'in progress', 'production in progress'            => 'production',
        'quality check', 'qc'                              => 'quality_check',
        'final approval', 'client sign-off'                => 'final_approval',
        'on hold', 'paused'                                => 'on_hold',
        'approved'                                         => 'approved',
        'completed', 'complete'                            => 'completed',
        'rejected'                                         => 'rejected',
        default                                            => 'quote_submitted',
    };
}

function project_status_label(string $status): string {
    return project_statuses()[project_status_key($status)];
}

/** Colour class. Pair with a sizing base class (.monitor-badge, .customer-badge, …). */
function project_status_class(string $status): string {
    return 'status-' . str_replace('_', '-', project_status_key($status));
}

/** Zero-based index into project_phases(), or null when the status is off-track. */
function project_step_index(string $status): ?int {
    $idx = array_search(project_status_key($status), array_keys(project_phases()), true);
    return $idx === false ? null : $idx;
}

function project_status_badge(string $status, string $baseClass = 'monitor-badge'): string {
    return '<span class="' . $baseClass . ' ' . project_status_class($status) . '">'
         . htmlspecialchars(project_status_label($status)) . '</span>';
}

/**
 * Emits the same vocabulary to JS so client-side rendering (step tracker, modal
 * badges, report charts) cannot fall out of sync with the PHP above.
 */
function project_status_js(): string {
    $data = [
        'phases'   => project_phases(),
        'offTrack' => project_off_track(),
    ];
    return '<script>window.PROJECT_STATUS = ' . json_encode($data) . ';
window.PROJECT_STEPS   = Object.values(PROJECT_STATUS.phases);
window.PROJECT_ALL     = Object.assign({}, PROJECT_STATUS.phases, PROJECT_STATUS.offTrack);
window.statusKey = function(s){
  s = String(s || "").trim().toLowerCase();
  if (PROJECT_ALL[s]) return s;
  const byLabel = Object.keys(PROJECT_ALL).find(k => PROJECT_ALL[k].toLowerCase() === s);
  if (byLabel) return byLabel;
  if (/waiting|pending|quote/.test(s))   return "quote_submitted";
  if (/fabricat|production|progress/.test(s)) return "production";
  if (/quality|qc/.test(s))              return "quality_check";
  if (/final|sign-?off/.test(s))         return "final_approval";
  if (/hold|paused/.test(s))             return "on_hold";
  if (/reject/.test(s))                  return "rejected";
  if (/complete/.test(s))                return "completed";
  if (/approved/.test(s))                return "approved";
  return "quote_submitted";
};
window.statusLabel = function(s){ return PROJECT_ALL[statusKey(s)] || "—"; };
window.statusClass = function(s){ return "status-" + statusKey(s).replace(/_/g, "-"); };
/** Index into PROJECT_STEPS, or -1 when the status is off-track. */
window.getStepIdx  = function(s){ return Object.keys(PROJECT_STATUS.phases).indexOf(statusKey(s)); };
</script>';
}
