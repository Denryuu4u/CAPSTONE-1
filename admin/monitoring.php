<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'monitoring';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Project Monitoring – Vast Solutions</title>

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
                    <span>Project Monitoring</span>
                </div>

                <div class="d-flex align-items-center gap-2">
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
                <h1 class="page-title mb-0">Project Monitoring</h1>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                <div class="monitor-filters">
                    <a href="#" class="monitor-pill active">All</a>
                    <a href="#" class="monitor-pill">Waiting</a>
                    <a href="#" class="monitor-pill">Approved</a>
                    <a href="#" class="monitor-pill">Fabrication</a>
                    <a href="#" class="monitor-pill">Completed</a>
                    <a href="#" class="monitor-pill">Rejected</a>
                </div>

                <div class="monitor-search-wrap">
                    <i class="bi bi-search monitor-search-icon"></i>
                    <input type="text" class="form-control monitor-search" placeholder="Search projects...">
                </div>
            </div>

            <div class="monitor-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 monitor-table">
                        <thead>
                            <tr>
                                <th>CODE</th>
                                <th>PROJECT</th>
                                <th>CUSTOMER</th>
                                <th>STATUS</th>
                                <th>TARGET</th>
                                <th class="text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-project="PRJ-2026-842">
                                <td>PRJ-2026-042</td>
                                <td class="monitor-project">Kitchen Reno Phase 1</td>
                                <td class="monitor-customer">Rivera Kitchens</td>
                                <td><span class="monitor-badge badge-fabrication">Fabrication</span></td>
                                <td class="monitor-target">Mar 15, 2026</td>
                                <td class="text-center">
                                    <div class="monitor-actions">
                                        <div class="action-left">
                                            <a href="#" class="monitor-action complete">
                                                <i class="bi bi-check-circle"></i>
                                                <span>Complete</span>
                                            </a>
                                        </div>

                                        <div class="action-right">
                                            <a href="#"
                                                class="monitor-action view-project-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewProjectModal"
                                                data-code="PRJ-2026-042"
                                                data-project="Kitchen Reno Phase 1"
                                                data-customer="Rivera Kitchens"
                                                data-target="Mar 15, 2026"
                                                data-status="Fabrication"
                                                data-status-class="badge-fabrication"
                                                data-details="Custom kitchen cabinetry with soft-close hinges, melamine panels, and PVC edge banding. Includes island countertop support structure.">
                                                <i class="bi bi-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>PRJ-2026-041</td>
                                <td class="monitor-project">Office Cabinets</td>
                                <td class="monitor-customer">Mendoza Interiors</td>
                                <td><span class="monitor-badge badge-approved-soft">Approved</span></td>
                                <td class="monitor-target">Mar 12, 2026</td>
                                <td class="text-center">
                                    <div class="monitor-actions">
                                        <div class="action-left"></div>

                                        <div class="action-right">
                                            <a href="#" class="monitor-action">
                                                <i class="bi bi-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>PRJ-2026-040</td>
                                <td class="monitor-project">Bathroom Vanity Set</td>
                                <td class="monitor-customer">Kim Design Studio</td>
                                <td><span class="monitor-badge badge-waiting-soft">Waiting Approval</span></td>
                                <td class="monitor-target">Mar 10, 2026</td>
                                <td class="text-center">
                                    <div class="monitor-actions">
                                        <div class="action-left"></div>

                                        <div class="action-right">
                                            <a href="#" class="monitor-action">
                                                <i class="bi bi-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>PRJ-2026-039</td>
                                <td class="monitor-project">Lobby Display Unit</td>
                                <td class="monitor-customer">Park Residences</td>
                                <td><span class="monitor-badge badge-completed-soft">Completed</span></td>
                                <td class="monitor-target">Mar 08, 2026</td>
                                <td class="text-center">
                                    <div class="monitor-actions">
                                        <div class="action-left"></div>

                                        <div class="action-right">
                                            <a href="#" class="monitor-action">
                                                <i class="bi bi-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>PRJ-2026-037</td>
                                <td class="monitor-project">Pantry Cabinets</td>
                                <td class="monitor-customer">Garcia Build Co</td>
                                <td><span class="monitor-badge badge-fabrication">Fabrication</span></td>
                                <td class="monitor-target">Mar 01, 2026</td>
                                <td class="text-center">
                                    <div class="monitor-actions">
                                        <div class="action-left">
                                            <a href="#" class="monitor-action complete">
                                                <i class="bi bi-check-circle"></i>
                                                <span>Complete</span>
                                            </a>
                                        </div>

                                        <div class="action-right">
                                            <a href="#" class="monitor-action">
                                                <i class="bi bi-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="viewProjectModal" tabindex="-1" aria-labelledby="viewProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content project-view-modal">

            <div class="modal-header project-view-header">
                <div>
                    <h5 class="modal-title project-view-title" id="viewProjectModalLabel">Kitchen Reno Phase 1</h5>
                    <div class="project-view-code" id="viewProjectCode">PRJ-2026-042</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body project-view-body">
                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <div class="project-view-label">Customer</div>
                        <div class="project-view-value" id="viewProjectCustomer">Rivera Kitchens</div>
                    </div>

                    <div class="col-md-6">
                        <div class="project-view-label">Target Date</div>
                        <div class="project-view-value" id="viewProjectTarget">Mar 15, 2026</div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="project-view-label">Status</div>
                    <div>
                        <span class="monitor-badge badge-fabrication" id="viewProjectStatus">Fabrication</span>
                    </div>
                </div>

                <div>
                    <div class="project-view-label">Project Details</div>
                    <div class="project-view-details" id="viewProjectDetails">
                        Custom kitchen cabinetry with soft-close hinges, melamine panels, and PVC edge banding. Includes island countertop support structure.
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);
    const projectId = params.get("project");
    const open = params.get("open");

    function fillProjectModal(btn) {
        const project = btn.dataset.project || "";
        const code = btn.dataset.code || "";
        const customer = btn.dataset.customer || "";
        const target = btn.dataset.target || "";
        const status = btn.dataset.status || "";
        const statusClass = btn.dataset.statusClass || "";
        const details = btn.dataset.details || "";

        document.getElementById("viewProjectModalLabel").textContent = project;
        document.getElementById("viewProjectCode").textContent = code;
        document.getElementById("viewProjectCustomer").textContent = customer;
        document.getElementById("viewProjectTarget").textContent = target;
        document.getElementById("viewProjectDetails").textContent = details;

        const statusBadge = document.getElementById("viewProjectStatus");
        statusBadge.textContent = status;
        statusBadge.className = "monitor-badge " + statusClass;
    }

    document.querySelectorAll(".view-project-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            fillProjectModal(this);
        });
    });

    if (projectId) {
        const row = document.querySelector(`[data-project="${projectId}"]`);

        if (row) {
            row.scrollIntoView({ behavior: "smooth", block: "center" });
            row.style.backgroundColor = "#f0fdfa";

            setTimeout(() => {
                row.style.backgroundColor = "";
            }, 3000);

            if (open === "view") {
                const viewBtn = row.querySelector(".view-project-btn");

                if (viewBtn) {
                    fillProjectModal(viewBtn);

                    const modal = new bootstrap.Modal(document.getElementById("viewProjectModal"));
                    modal.show();

                    window.history.replaceState({}, document.title, "monitoring.php");
                }
            }
        }
    }
});
</script>
</body>

</html>