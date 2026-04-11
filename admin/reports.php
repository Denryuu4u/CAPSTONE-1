<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'reports';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reports – Vast Solutions</title>

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
                    <span>Reports</span>
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
                <h1 class="page-title mb-0">Reports</h1>
            </div>

            <div class="report-tabs-wrap mb-3">
                <div class="report-tabs">
                    <a href="#" class="report-tab active">Project Reports</a>
                    <a href="#" class="report-tab">Quotation Reports</a>
                    <a href="#" class="report-tab">Cutting List Summary</a>
                    <a href="#" class="report-tab">Costing Reports</a>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 report-filter-row">
                <div class="report-date-wrap">
                    <div class="report-date-group">
                        <i class="bi bi-calendar3 report-date-icon"></i>
                        <input type="date" class="form-control report-date-input" placeholder="From date">
                    </div>

                    <span class="report-to-text">to</span>

                    <div class="report-date-group">
                        <i class="bi bi-calendar3 report-date-icon"></i>
                        <input type="date" class="form-control report-date-input" placeholder="To date">
                    </div>
                </div>

                <button
                    class="report-generate-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#generateReportModal">
                    <i class="bi bi-file-earmark-text"></i>
                    Generate Report
                </button>
            </div>

            <div class="report-periods">
                <a href="#" class="report-period">Weekly</a>
                <a href="#" class="report-period">Monthly</a>
                <a href="#" class="report-period">Yearly</a>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="report-card">
                        <div class="report-card-title">Projects Started vs Completed</div>

                        <div class="chart-box">
                            <div class="y-labels">
                                <span>12</span>
                                <span>9</span>
                                <span>6</span>
                                <span>3</span>
                                <span>0</span>
                            </div>

                            <div class="bar-chart">
                                <div class="bar-group">
                                    <div class="bar started" style="height: 95px;"></div>
                                    <div class="bar completed" style="height: 60px;"></div>
                                    <div class="bar-label">Jan</div>
                                </div>

                                <div class="bar-group">
                                    <div class="bar started" style="height: 142px;"></div>
                                    <div class="bar completed" style="height: 108px;"></div>
                                    <div class="bar-label">Feb</div>
                                </div>

                                <div class="bar-group">
                                    <div class="bar started" style="height: 118px;"></div>
                                    <div class="bar completed" style="height: 84px;"></div>
                                    <div class="bar-label">Mar</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="report-card">
                        <div class="report-card-title">Status Distribution</div>

                        <div class="donut-wrap">
                            <div class="donut-chart"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="generateReportModal" tabindex="-1" aria-labelledby="generateReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content report-modal">

            <div class="modal-header report-modal-header">
                <h5 class="modal-title" id="generateReportModalLabel">Generate Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body report-modal-body">

                <!-- DATE RANGE -->
                <div class="mb-3">
                    <div class="report-modal-label">Date Range</div>
                    <div class="report-modal-subtext">
                        Please select a date range on the main page
                    </div>
                </div>

                <!-- REPORT TYPE -->
                <div class="mb-3">
                    <div class="report-modal-label">Report Type</div>

                    <div class="report-checkbox-group">
                        <label class="report-check">
                            <input type="checkbox" id="allReports">
                            <span>All Reports</span>
                        </label>

                        <label class="report-check">
                            <input type="checkbox">
                            <span>Project Reports</span>
                        </label>

                        <label class="report-check">
                            <input type="checkbox">
                            <span>Quotation Reports</span>
                        </label>

                        <label class="report-check">
                            <input type="checkbox">
                            <span>Cutting List Summary</span>
                        </label>

                        <label class="report-check">
                            <input type="checkbox">
                            <span>Costing Reports</span>
                        </label>
                    </div>
                </div>

                <!-- FORMAT -->
                <div class="mb-2">
                    <div class="report-modal-label">Format</div>

                    <div class="report-radio-group">
                        <label class="report-radio">
                            <input type="radio" name="format" checked>
                            <span>PDF</span>
                        </label>

                        <label class="report-radio">
                            <input type="radio" name="format">
                            <span>Excel</span>
                        </label>
                    </div>
                </div>

            </div>

            <div class="modal-footer report-modal-footer">
                <button class="btn btn-light border">Cancel</button>
                <button class="btn btn-success report-generate-confirm">Generate</button>
            </div>

        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>