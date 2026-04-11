<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'audit_logs';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Audit Logs – Vast Solutions</title>

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
                    <span>Audit Logs</span>
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

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="page-title mb-0">Audit Logs</h1>
            </div>

            <div class="audit-filter-grid">
                <div class="audit-filter-item">
                    <label for="dateFrom">Date From</label>
                    <input type="date" id="dateFrom" class="form-control audit-input">
                </div>

                <div class="audit-filter-item">
                    <label for="dateTo">Date To</label>
                    <input type="date" id="dateTo" class="form-control audit-input">
                </div>

                <div class="audit-filter-item">
                    <label for="userFilter">User</label>
                    <select id="userFilter" class="form-select audit-select">
                        <option selected>All</option>
                        <option>John Admin</option>
                        <option>Maria Santos</option>
                        <option>Carlos Reyes</option>
                        <option>Ana Cruz</option>
                    </select>
                </div>

                <div class="audit-filter-item">
                    <label for="moduleFilter">Module</label>
                    <select id="moduleFilter" class="form-select audit-select">
                        <option selected>All</option>
                        <option>Quotations</option>
                        <option>Project Requests</option>
                        <option>Customers</option>
                        <option>Auth</option>
                    </select>
                </div>
            </div>

            <div class="audit-table-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 audit-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="audit-timestamp">2025-03-12 14:32</td>
                                <td class="audit-user">John Admin</td>
                                <td><span class="audit-badge badge-admin">Admin</span></td>
                                <td class="audit-action">Created Quotation</td>
                                <td><span class="audit-badge badge-module">Quotations</span></td>
                                <td class="audit-details">Created quotation QT-2025-001 for Maria Santos</td>
                            </tr>

                            <tr>
                                <td class="audit-timestamp">2025-03-12 13:15</td>
                                <td class="audit-user">Maria Santos</td>
                                <td><span class="audit-badge badge-customer">Customer</span></td>
                                <td class="audit-action">Submitted Project Request</td>
                                <td><span class="audit-badge badge-module">Project Requests</span></td>
                                <td class="audit-details">Submitted request REQ-2025-005 — Kitchen Cab</td>
                            </tr>

                            <tr>
                                <td class="audit-timestamp">2025-03-12 11:00</td>
                                <td class="audit-user">John Admin</td>
                                <td><span class="audit-badge badge-admin">Admin</span></td>
                                <td class="audit-action">Sent Quotation</td>
                                <td><span class="audit-badge badge-module">Quotations</span></td>
                                <td class="audit-details">Sent quotation QT-2025-001 to Maria Santos</td>
                            </tr>

                            <tr>
                                <td class="audit-timestamp">2025-03-11 16:45</td>
                                <td class="audit-user">Carlos Reyes</td>
                                <td><span class="audit-badge badge-customer">Customer</span></td>
                                <td class="audit-action">Approved Quotation</td>
                                <td><span class="audit-badge badge-module">Quotations</span></td>
                                <td class="audit-details">Approved quotation QT-2025-002</td>
                            </tr>

                            <tr>
                                <td class="audit-timestamp">2025-03-11 10:20</td>
                                <td class="audit-user">John Admin</td>
                                <td><span class="audit-badge badge-admin">Admin</span></td>
                                <td class="audit-action">Added Customer</td>
                                <td><span class="audit-badge badge-module">Customers</span></td>
                                <td class="audit-details">Added new customer Ana Cruz</td>
                            </tr>

                            <tr>
                                <td class="audit-timestamp">2025-03-10 09:00</td>
                                <td class="audit-user">John Admin</td>
                                <td><span class="audit-badge badge-admin">Admin</span></td>
                                <td class="audit-action">User Login</td>
                                <td><span class="audit-badge badge-module">Auth</span></td>
                                <td class="audit-details">Admin logged in</td>
                            </tr>

                            <tr>
                                <td class="audit-timestamp">2025-03-10 08:30</td>
                                <td class="audit-user">Maria Santos</td>
                                <td><span class="audit-badge badge-customer">Customer</span></td>
                                <td class="audit-action">User Login</td>
                                <td><span class="audit-badge badge-module">Auth</span></td>
                                <td class="audit-details">Customer logged in</td>
                            </tr>

                            <tr>
                                <td class="audit-timestamp">2025-03-09 15:10</td>
                                <td class="audit-user">Ana Cruz</td>
                                <td><span class="audit-badge badge-customer">Customer</span></td>
                                <td class="audit-action">Rejected Quotation</td>
                                <td><span class="audit-badge badge-module">Quotations</span></td>
                                <td class="audit-details">Rejected quotation QT-2025-003</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>