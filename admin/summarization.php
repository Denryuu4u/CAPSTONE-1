<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false

$active_page = 'summarization';
require_page($active_page); // role gate
$user_name = $_SESSION['full_name'] ?? 'Admin User';

// Approved (and later-phase) projects available for material summarization.
$approvedProjects = [];
try {
    require_once __DIR__ . '/../includes/db.php';
    $approvedProjects = db()->query(
        "SELECT id, project_code, project_name
           FROM projects
          WHERE status IN ('approved','production','mockup','delivery','installation',
                           'quality_check','punchlist','final_approval')
          ORDER BY created_at DESC"
    )->fetchAll();
} catch (Throwable $e) {
    $approvedProjects = []; // fall back to static demo options if DB is unavailable
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Summarization – Vast Solutions</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="admin.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <div class="topbar">
            <div class="d-flex justify-content-between align-items-center w-100">

                <div class="d-flex align-items-center gap-2">
                    <a href="#">Portal</a>
                    <span class="sep">›</span>
                    <span>Summarization</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
                    <div class="user-avatar-sm">
                        <?= strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="lh-sm">
                        <div class="fw-semibold small text-dark"><?= htmlspecialchars($user_name); ?></div>
                        <div class="text-muted" style="font-size: 12px;">Administrator</div>
                    </div>
                </div>

            </div>
        </div>


        <div class="page-content container-fluid py-4 px-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h1 class="page-title mb-0">Summarization of Materials</h1>
                <button type="button" class="summ-library-btn" id="openLibraryBtn"
                        data-bs-toggle="modal" data-bs-target="#libraryModal">
                    <i class="bi bi-journal-text"></i> Custom Material &amp; Edge Library
                </button>
            </div>

            <!-- Project Selector -->
            <div class="summ-card p-3 mb-3">
                <label for="approvedProject" class="summ-label d-block">Select Approved Project</label>
                <select id="approvedProject" class="form-select summ-select">
                    <option selected disabled>-- Select Project --</option>
                    <?php if (!empty($approvedProjects)): ?>
                        <?php foreach ($approvedProjects as $proj): ?>
                            <option value="<?= (int) $proj['id'] ?>"
                                    data-pid="<?= (int) $proj['id'] ?>"
                                    data-name="<?= htmlspecialchars($proj['project_name']) ?>">
                                <?= htmlspecialchars($proj['project_code'] . ' — ' . $proj['project_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback demo options (no approved projects in the database yet) -->
                        <option data-name="Kitchen Reno Phase 1">Kitchen Reno Phase 1</option>
                        <option data-name="Office Cabinets">Office Cabinets</option>
                        <option data-name="Bathroom Vanity Set">Bathroom Vanity Set</option>
                        <option data-name="Lobby Display Unit">Lobby Display Unit</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Empty State (shown when no project selected) -->
            <div id="summEmptyState" class="summ-card summ-empty">
                <div class="summ-empty-inner">
                    <div class="summ-empty-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <p class="summ-empty-text">Select an approved project to begin material summarization.</p>
                </div>
            </div>

            <!-- Upload + Results (shown after project selected) -->
            <div id="summWorkspace" style="display:none;">

                <!-- Upload Zone -->
                <div class="summ-card mb-3">
                    <div class="summ-upload-header">
                        <span class="summ-upload-title">Upload Cutting List / BOM</span>
                    </div>
                    <div class="summ-upload-body">
                        <div class="summ-dropzone" id="summDropzone">
                            <input type="file" id="summFileInput" accept=".cut" style="display:none;">
                            <div class="summ-drop-icon">
                                <i class="bi bi-upload"></i>
                            </div>
                            <p class="summ-drop-label">Drop cutting list file here</p>
                            <p class="summ-drop-sub">Cabinet Vision export (.cut)</p>
                        </div>
                        <div id="summUploadError" class="summ-upload-error" style="display:none;"></div>
                    </div>
                </div>

                <!-- Summarized Results (rendered from the uploaded .cut file) -->
                <div id="summResults" style="display:none;">

                    <?php
                    // Panels (wood), Hardware (hw), Edges (alu) share the same markup;
                    // the JS fills tbody + badge per category after processing.
                    $summSections = [
                        ['category' => 'wood', 'title' => 'Panels',   'icon' => 'bi-grid-3x3-gap'],
                        ['category' => 'hw',   'title' => 'Hardware', 'icon' => 'bi-tools'],
                        ['category' => 'alu',  'title' => 'Edges',    'icon' => 'bi-border-all'],
                    ];
                    foreach ($summSections as $sec): ?>
                    <div class="summ-section-card mb-3" data-category="<?= $sec['category'] ?>">
                        <div class="summ-section-header">
                            <div class="d-flex align-items-center gap-2 summ-collapse-toggle" role="button"
                                 aria-expanded="true" title="Show / hide this list">
                                <i class="bi bi-chevron-down summ-chevron"></i>
                                <span class="summ-section-icon">
                                    <i class="bi <?= $sec['icon'] ?>"></i>
                                </span>
                                <span class="summ-section-title"><?= $sec['title'] ?></span>
                                <span class="summ-section-badge">0 items</span>
                            </div>
                            <button class="summ-excel-btn" data-category="<?= $sec['category'] ?>">
                                <i class="bi bi-download"></i> Excel
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="summ-table">
                                <thead>
                                    <tr>
                                        <th>PART NAME</th>
                                        <th>MATERIAL</th>
                                        <th>DIMENSIONS</th>
                                        <th>EDGING</th>
                                        <th class="text-end">QTY</th>
                                    </tr>
                                </thead>
                                <tbody data-tbody="<?= $sec['category'] ?>">
                                    <tr class="summ-empty-row">
                                        <td colspan="5" class="text-muted text-center">No items.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div><!-- /#summResults -->

                <!-- Sticky action footer (visible whenever results are shown) -->
                <div class="summ-sticky-footer" id="summStickyFooter" style="display:none;">
                    <div class="summ-actions-row">
                        <span class="summ-footer-hint" id="summFooterHint"></span>
                        <button class="summ-zip-btn" id="summDownloadZip">
                            <i class="bi bi-download"></i> Download All (ZIP)
                        </button>
                        <button class="summ-fabrication-btn" id="summMarkFabrication">
                            <i class="bi bi-check-circle"></i> Mark Ready for Production
                        </button>
                    </div>
                </div>

            </div><!-- /#summWorkspace -->

        </div>
    </div>

    <!-- Fabrication Confirm Modal -->
    <div class="modal fade" id="fabricationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
            <div class="modal-content" style="border-radius:14px; border:none;">
                <div class="modal-body p-4 text-center">
                    <div class="summ-modal-icon mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h6 class="fw-bold mb-1" style="font-family:'Syne',sans-serif;">Mark Ready for Production?</h6>
                    <p class="text-muted mb-4" style="font-size:0.8rem;">This will move <strong id="fabricationProjectName"></strong> to <em>Production in Progress</em>, notify the production team, and lock the material list.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-light btn-sm px-4" data-bs-dismiss="modal" style="font-size:0.75rem; border-radius:6px;">Cancel</button>
                        <button class="btn btn-sm px-4 summ-fabrication-confirm" data-bs-dismiss="modal" style="font-size:0.75rem; border-radius:6px; background:var(--teal); color:#fff; border:none;">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Material & Edge Library Modal -->
    <div class="modal fade" id="libraryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:14px; border:none;">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-family:'Syne',sans-serif;">
                        <i class="bi bi-journal-text me-1"></i> Custom Material &amp; Edge Library
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:0.82rem;">
                        Add code &rarr; value overrides. During summarization, any material/edge code that
                        matches a row here is replaced with its value; unmatched codes stay as-is.
                    </p>
                    <div class="row g-3">

                        <?php
                        $libPanels = [
                            ['type' => 'material', 'title' => 'Material Library', 'codeLabel' => 'Material', 'ph' => 'e.g. 16777727'],
                            ['type' => 'edging',   'title' => 'Edge Library',     'codeLabel' => 'Edging',   'ph' => 'e.g. 2WE2LE'],
                        ];
                        foreach ($libPanels as $lp): ?>
                        <div class="col-lg-6">
                            <div class="lib-panel" data-lib="<?= $lp['type'] ?>">
                                <div class="lib-panel-head"><?= $lp['title'] ?></div>

                                <div class="lib-add-row">
                                    <input type="text" class="form-control form-control-sm lib-add-code"
                                           placeholder="<?= $lp['codeLabel'] ?> code (<?= $lp['ph'] ?>)">
                                    <input type="text" class="form-control form-control-sm lib-add-value"
                                           placeholder="Value">
                                    <button type="button" class="lib-add-btn">
                                        <i class="bi bi-plus-lg"></i> Add record
                                    </button>
                                </div>

                                <div class="lib-table-wrap">
                                    <table class="lib-table">
                                        <thead>
                                            <tr>
                                                <th><?= $lp['codeLabel'] ?></th>
                                                <th>Value</th>
                                                <th class="text-end">&nbsp;</th>
                                            </tr>
                                        </thead>
                                        <tbody data-lib-body="<?= $lp['type'] ?>">
                                            <tr><td colspan="3" class="text-muted text-center lib-loading">Loading…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div>
                    <div id="libError" class="summ-upload-error" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const projectSelect   = document.getElementById('approvedProject');
        const emptyState      = document.getElementById('summEmptyState');
        const workspace       = document.getElementById('summWorkspace');
        const dropzone        = document.getElementById('summDropzone');
        const fileInput       = document.getElementById('summFileInput');
        const results         = document.getElementById('summResults');
        const uploadError     = document.getElementById('summUploadError');
        const fabricationBtn  = document.getElementById('summMarkFabrication');
        const projNameEl      = document.getElementById('fabricationProjectName');
        const stickyFooter    = document.getElementById('summStickyFooter');
        const footerHint      = document.getElementById('summFooterHint');

        let currentBatchId   = null;
        let currentProjectNm = '';

        const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g,
            c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

        // Show workspace once a real project (not the placeholder) is selected.
        projectSelect.addEventListener('change', function () {
            const opt = this.selectedOptions[0];
            if (opt && !opt.disabled) {
                currentProjectNm = opt.dataset.name || opt.textContent.trim();
                emptyState.style.display = 'none';
                workspace.style.display  = 'block';
                results.style.display    = 'none';
                currentBatchId = null;
                stickyFooter.style.display = 'none';
                dropzone.classList.remove('summ-dropzone--loaded');
                hideError();
            }
        });

        dropzone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', function () {
            if (this.files.length) handleFile(this.files[0]);
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault(); dropzone.classList.add('summ-dropzone--active');
        });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('summ-dropzone--active'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('summ-dropzone--active');
            if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
        });

        function showError(msg) {
            uploadError.textContent = msg;
            uploadError.style.display = 'block';
        }
        function hideError() {
            uploadError.style.display = 'none';
        }

        // Upload the .cut file, get the summarized JSON, render it.
        function handleFile(file) {
            hideError();
            if (!/\.cut$/i.test(file.name)) {
                showError('Uploaded file is not a .cut file.');
                return;
            }

            const opt = projectSelect.selectedOptions[0];
            const fd = new FormData();
            fd.append('cutfile', file);
            fd.append('project_id', (opt && opt.dataset.pid) ? opt.dataset.pid : '');
            fd.append('project_name', currentProjectNm);

            dropzone.classList.add('summ-dropzone--loading');

            fetch('process_cutlist.php', { method: 'POST', body: fd })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({ ok: false, error: 'Unexpected server response.' }));
                    if (!res.ok || !data.ok) throw new Error(data.error || 'Processing failed.');
                    return data;
                })
                .then(renderResults)
                .catch((err) => showError(err.message))
                .finally(() => dropzone.classList.remove('summ-dropzone--loading'));
        }

        function renderResults(data) {
            currentBatchId = data.batch_id;
            dropzone.classList.add('summ-dropzone--loaded');

            ['wood', 'hw', 'alu'].forEach((cat) => {
                const rows  = data[cat] || [];
                const tbody = document.querySelector(`[data-tbody="${cat}"]`);
                const card  = document.querySelector(`.summ-section-card[data-category="${cat}"]`);
                const badge = card.querySelector('.summ-section-badge');

                badge.textContent = rows.length + (rows.length === 1 ? ' item' : ' items');

                if (!rows.length) {
                    tbody.innerHTML = '<tr class="summ-empty-row"><td colspan="5" class="text-muted text-center">No items.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map((r) => {
                    const dims = (r.width && r.length) ? `${escapeHtml(r.width)} × ${escapeHtml(r.length)}`
                               : escapeHtml(r.width || r.length || '—');
                    return `<tr>
                        <td>${escapeHtml(r.partname) || '<span class="text-muted">—</span>'}</td>
                        <td>${escapeHtml(r.material) || '<span class="text-muted">—</span>'}</td>
                        <td class="text-muted">${dims}</td>
                        <td class="text-muted">${escapeHtml(r.edging) || '—'}</td>
                        <td class="text-end fw-semibold">${escapeHtml(r.qty)}</td>
                    </tr>`;
                }).join('');
            });

            const c = data.counts || {};
            footerHint.textContent =
                `Panels ${c.wood || 0} · Hardware ${c.hw || 0} · Edges ${c.alu || 0}`;

            results.style.display      = 'block';
            stickyFooter.style.display = 'block';
            results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // ── Collapse / expand each summarized section ──
        document.querySelectorAll('.summ-collapse-toggle').forEach((tog) => {
            tog.addEventListener('click', function () {
                const card = this.closest('.summ-section-card');
                const collapsed = card.classList.toggle('collapsed');
                this.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            });
        });

        // Per-category Excel (CSV) download.
        document.querySelectorAll('.summ-excel-btn').forEach((btn) => {
            btn.addEventListener('click', function () {
                if (!currentBatchId) return;
                const cat = this.dataset.category;
                window.location = `export_summary.php?batch=${currentBatchId}&category=${cat}`;
            });
        });

        // Download all three as a ZIP.
        document.getElementById('summDownloadZip').addEventListener('click', function () {
            if (!currentBatchId) return;
            window.location = `export_summary.php?batch=${currentBatchId}&category=all`;
        });

        // Mark Ready for Production → confirm modal.
        fabricationBtn.addEventListener('click', function () {
            projNameEl.textContent = currentProjectNm || 'this project';
            new bootstrap.Modal(document.getElementById('fabricationModal')).show();
        });

        document.querySelector('.summ-fabrication-confirm').addEventListener('click', function () {
            if (!currentBatchId) { showError('Upload and process a cutting list first.'); return; }
            const opt = projectSelect.selectedOptions[0];
            const projectId = (opt && opt.dataset.pid) ? opt.dataset.pid : '';
            if (!projectId) { showError('Select an approved project (from the database) before marking ready.'); return; }

            const body = new URLSearchParams({ batch_id: currentBatchId, project_id: projectId });
            fetch('mark_ready.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({ ok: false, error: 'Unexpected response.' }));
                    if (!res.ok || !data.ok) throw new Error(data.error || 'Failed.');
                    return data;
                })
                .then((data) => {
                    fabricationBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Marked Ready — ' + data.project_code;
                    fabricationBtn.disabled  = true;
                    fabricationBtn.style.opacity = '0.7';
                })
                .catch((err) => showError(err.message));
        });

        // ══ Custom Material & Edge Library ══════════════════════════════
        const libModalEl = document.getElementById('libraryModal');
        const libError   = document.getElementById('libError');

        function libShowError(msg) { libError.textContent = msg; libError.style.display = 'block'; }
        function libHideError()    { libError.style.display = 'none'; }

        function libApi(params) {
            const body = new URLSearchParams(params);
            return fetch('library_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            }).then(async (res) => {
                const data = await res.json().catch(() => ({ ok: false, error: 'Unexpected server response.' }));
                if (!res.ok || !data.ok) throw new Error(data.error || 'Request failed.');
                return data;
            });
        }

        function libRenderRows(type, rows) {
            const tbody = document.querySelector(`[data-lib-body="${type}"]`);
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-muted text-center">No records yet.</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map((r) => `
                <tr data-id="${r.id}">
                    <td><input class="lib-cell lib-cell-code" value="${escapeHtml(r.code)}"></td>
                    <td><input class="lib-cell lib-cell-value" value="${escapeHtml(r.normalized_name)}"></td>
                    <td class="text-end">
                        <button class="lib-del-btn" title="Delete record"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`).join('');
        }

        function libLoad() {
            libHideError();
            fetch('library_api.php?action=list')
                .then((r) => r.json())
                .then((data) => {
                    if (!data.ok) throw new Error(data.error || 'Failed to load libraries.');
                    libRenderRows('material', data.material);
                    libRenderRows('edging', data.edging);
                })
                .catch((err) => libShowError(err.message));
        }

        libModalEl.addEventListener('show.bs.modal', libLoad);

        // Add record (per panel).
        libModalEl.querySelectorAll('.lib-add-btn').forEach((btn) => {
            btn.addEventListener('click', function () {
                libHideError();
                const panel = this.closest('.lib-panel');
                const type  = panel.dataset.lib;
                const codeI = panel.querySelector('.lib-add-code');
                const valI  = panel.querySelector('.lib-add-value');
                const code  = codeI.value.trim();
                if (!code) { libShowError('Please enter a code.'); codeI.focus(); return; }

                libApi({ action: 'add', type, code, value: valI.value.trim() })
                    .then(() => { codeI.value = ''; valI.value = ''; libLoad(); codeI.focus(); })
                    .catch((err) => libShowError(err.message));
            });
        });

        // Inline edit (save on change) + delete, via event delegation.
        libModalEl.querySelectorAll('.lib-table').forEach((table) => {
            const type = table.closest('.lib-panel').dataset.lib;

            table.addEventListener('change', function (e) {
                const cell = e.target.closest('.lib-cell');
                if (!cell) return;
                const tr = cell.closest('tr');
                const id = tr.dataset.id;
                const code  = tr.querySelector('.lib-cell-code').value.trim();
                const value = tr.querySelector('.lib-cell-value').value.trim();
                if (!code) { libShowError('Code cannot be empty.'); libLoad(); return; }
                libApi({ action: 'update', type, id, code, value })
                    .catch((err) => { libShowError(err.message); libLoad(); });
            });

            table.addEventListener('click', function (e) {
                const del = e.target.closest('.lib-del-btn');
                if (!del) return;
                const tr = del.closest('tr');
                libApi({ action: 'delete', type, id: tr.dataset.id })
                    .then(() => tr.remove())
                    .catch((err) => libShowError(err.message));
            });
        });
    </script>
</body>

</html>