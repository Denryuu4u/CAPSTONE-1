<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'summarization';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
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
            </div>

            <!-- Project Selector -->
            <div class="summ-card p-3 mb-3">
                <label for="approvedProject" class="summ-label d-block">Select Approved Project</label>
                <select id="approvedProject" class="form-select summ-select">
                    <option selected disabled>-- Select Project --</option>
                    <option>Kitchen Reno Phase 1</option>
                    <option>Office Cabinets</option>
                    <option>Bathroom Vanity Set</option>
                    <option>Lobby Display Unit</option>
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
                            <input type="file" id="summFileInput" accept=".csv,.xlsx,.xml" style="display:none;">
                            <div class="summ-drop-icon">
                                <i class="bi bi-upload"></i>
                            </div>
                            <p class="summ-drop-label">Drop cutting list file here</p>
                            <p class="summ-drop-sub">Cabinet Vision export (.csv, .xlsx, .xml)</p>
                        </div>
                    </div>
                </div>

                <!-- Summarized Results (shown after clicking dropzone) -->
                <div id="summResults" style="display:none;">

                    <!-- Panels -->
                    <div class="summ-section-card mb-3">
                        <div class="summ-section-header">
                            <div class="d-flex align-items-center gap-2">
                                <span class="summ-section-icon">
                                    <i class="bi bi-grid-3x3-gap"></i>
                                </span>
                                <span class="summ-section-title">Panels</span>
                                <span class="summ-section-badge">3 items</span>
                            </div>
                            <button class="summ-excel-btn">
                                <i class="bi bi-download"></i> Excel
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="summ-table">
                                <thead>
                                    <tr>
                                        <th>MATERIAL</th>
                                        <th>DIMENSIONS</th>
                                        <th class="text-end">QTY</th>
                                        <th>UNIT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Melamine White 18mm</td>
                                        <td class="text-muted">2440 x 1220mm</td>
                                        <td class="text-end fw-semibold">12</td>
                                        <td class="text-muted">sheets</td>
                                    </tr>
                                    <tr>
                                        <td>Melamine White 18mm</td>
                                        <td class="text-muted">1830 x 610mm</td>
                                        <td class="text-end fw-semibold">8</td>
                                        <td class="text-muted">sheets</td>
                                    </tr>
                                    <tr>
                                        <td>Plywood 12mm</td>
                                        <td class="text-muted">2440 x 1220mm</td>
                                        <td class="text-end fw-semibold">4</td>
                                        <td class="text-muted">sheets</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Hardware -->
                    <div class="summ-section-card mb-3">
                        <div class="summ-section-header">
                            <div class="d-flex align-items-center gap-2">
                                <span class="summ-section-icon">
                                    <i class="bi bi-tools"></i>
                                </span>
                                <span class="summ-section-title">Hardware</span>
                                <span class="summ-section-badge">3 items</span>
                            </div>
                            <button class="summ-excel-btn">
                                <i class="bi bi-download"></i> Excel
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="summ-table">
                                <thead>
                                    <tr>
                                        <th>MATERIAL</th>
                                        <th>DIMENSIONS</th>
                                        <th class="text-end">QTY</th>
                                        <th>UNIT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Blum Hinge 110° Soft Close</td>
                                        <td class="text-muted">N/A</td>
                                        <td class="text-end fw-semibold">24</td>
                                        <td class="text-muted">pcs</td>
                                    </tr>
                                    <tr>
                                        <td>Drawer Slide Full Extension 18"</td>
                                        <td class="text-muted">450mm</td>
                                        <td class="text-end fw-semibold">12</td>
                                        <td class="text-muted">pairs</td>
                                    </tr>
                                    <tr>
                                        <td>Bar Pull Handle Stainless</td>
                                        <td class="text-muted">160mm</td>
                                        <td class="text-end fw-semibold">18</td>
                                        <td class="text-muted">pcs</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Edges -->
                    <div class="summ-section-card mb-4">
                        <div class="summ-section-header">
                            <div class="d-flex align-items-center gap-2">
                                <span class="summ-section-icon">
                                    <i class="bi bi-border-all"></i>
                                </span>
                                <span class="summ-section-title">Edges</span>
                                <span class="summ-section-badge">2 items</span>
                            </div>
                            <button class="summ-excel-btn">
                                <i class="bi bi-download"></i> Excel
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="summ-table">
                                <thead>
                                    <tr>
                                        <th>MATERIAL</th>
                                        <th>DIMENSIONS</th>
                                        <th class="text-end">QTY</th>
                                        <th>UNIT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>PVC Edge Band White 2mm</td>
                                        <td class="text-muted">50m roll</td>
                                        <td class="text-end fw-semibold">3</td>
                                        <td class="text-muted">rolls</td>
                                    </tr>
                                    <tr>
                                        <td>PVC Edge Band White 0.5mm</td>
                                        <td class="text-muted">50m roll</td>
                                        <td class="text-end fw-semibold">2</td>
                                        <td class="text-muted">rolls</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="summ-actions-row">
                        <button class="summ-zip-btn" id="summDownloadZip">
                            <i class="bi bi-download"></i> Download All (ZIP)
                        </button>
                        <button class="summ-fabrication-btn" id="summMarkFabrication">
                            <i class="bi bi-check-circle"></i> Mark Ready for Production
                        </button>
                    </div>

                </div><!-- /#summResults -->

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const projectSelect   = document.getElementById('approvedProject');
        const emptyState      = document.getElementById('summEmptyState');
        const workspace       = document.getElementById('summWorkspace');
        const dropzone        = document.getElementById('summDropzone');
        const fileInput       = document.getElementById('summFileInput');
        const results         = document.getElementById('summResults');
        const fabricationBtn  = document.getElementById('summMarkFabrication');
        const projNameEl      = document.getElementById('fabricationProjectName');

        // Show workspace when a project is selected
        projectSelect.addEventListener('change', function () {
            if (this.value && this.value !== '-- Select Project --') {
                emptyState.style.display = 'none';
                workspace.style.display  = 'block';
                results.style.display    = 'none';
                dropzone.classList.remove('summ-dropzone--loaded');
            }
        });

        // Click dropzone → show file picker (frontend-only: just reveals results)
        dropzone.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            if (this.files.length) {
                showResults();
            }
        });

        // Drag-over styling
        dropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropzone.classList.add('summ-dropzone--active');
        });
        dropzone.addEventListener('dragleave', function () {
            dropzone.classList.remove('summ-dropzone--active');
        });
        dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropzone.classList.remove('summ-dropzone--active');
            showResults();
        });

        function showResults() {
            dropzone.classList.add('summ-dropzone--loaded');
            results.style.display = 'block';
            results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Individual Excel buttons (frontend-only stub)
        document.querySelectorAll('.summ-excel-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const section = this.closest('.summ-section-card').querySelector('.summ-section-title').textContent;
                alert('Downloading ' + section + ' Excel — (placeholder)');
            });
        });

        // Download All ZIP (frontend-only stub)
        document.getElementById('summDownloadZip').addEventListener('click', function () {
            alert('Downloading all as ZIP — (placeholder)');
        });

        // Mark Ready for Fabrication → confirm modal
        fabricationBtn.addEventListener('click', function () {
            projNameEl.textContent = projectSelect.value;
            new bootstrap.Modal(document.getElementById('fabricationModal')).show();
        });

        document.querySelector('.summ-fabrication-confirm').addEventListener('click', function () {
            fabricationBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Marked Ready';
            fabricationBtn.disabled  = true;
            fabricationBtn.style.opacity = '0.7';
        });
    </script>
</body>

</html>