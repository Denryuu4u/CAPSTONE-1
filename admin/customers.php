<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'customers';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Customer Profiles – Vast Solutions</title>

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
                    <span>Customer Managements</span>
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
                <h1 class="page-title mb-0">Customer Profiles</h1>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                <button type="button" class="customer-btn" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Customer</span>
                </button>

                <div class="customer-search-wrap">
                    <i class="bi bi-search customer-search-icon"></i>
                    <input type="text" class="form-control customer-search" placeholder="Search customers...">
                </div>
            </div>

            <div class="customer-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 customer-table">
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>EMAIL</th>
                                <th>PHONE</th>
                                <th>PROJECTS</th>
                                <th>LAST STATUS</th>
                                <th class="text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="customer-name">
                                        <span class="customer-avatar">RK</span>
                                        <span>Rivera Kitchens</span>
                                    </div>
                                </td>
                                <td class="customer-email">info@riverakitchens.com</td>
                                <td class="customer-phone">+1 555-0101</td>
                                <td class="customer-projects">5</td>
                                <td><?= project_status_badge('production', 'customer-badge') ?></td>
                                <td class="text-center">
                                    <div class="customer-actions">
                                        <a href="#"
                                            class="customer-action edit-customer-btn"
                                            title="Edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCustomerModal"
                                            data-name="Rivera Kitchens"
                                            data-email="info@riverakitchens.com"
                                            data-phone="+1 555-0101"
                                            data-address="123 Main St, Metro Manila">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="#"
                                            class="customer-action archive-customer-btn"
                                            title="Archive"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveCustomerModal"
                                            data-name="Rivera Kitchens">
                                            <i class="bi bi-archive"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="customer-name">
                                        <span class="customer-avatar">MI</span>
                                        <span>Mendoza Interiors</span>
                                    </div>
                                </td>
                                <td class="customer-email">carlos@mendoza.com</td>
                                <td class="customer-phone">+1 555-0102</td>
                                <td class="customer-projects">3</td>
                                <td><?= project_status_badge('approved', 'customer-badge') ?></td>
                                <td class="text-center">
                                    <div class="customer-actions">
                                        <a href="#" class="customer-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="customer-action" title="Delete"><i class="bi bi-archive"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="customer-name">
                                        <span class="customer-avatar">KDS</span>
                                        <span>Kim Design Studio</span>
                                    </div>
                                </td>
                                <td class="customer-email">sarah@kimdesign.com</td>
                                <td class="customer-phone">+1 555-0103</td>
                                <td class="customer-projects">7</td>
                                <td><?= project_status_badge('quote_submitted', 'customer-badge') ?></td>
                                <td class="text-center">
                                    <div class="customer-actions">
                                        <a href="#" class="customer-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="customer-action" title="Delete"><i class="bi bi-archive"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="customer-name">
                                        <span class="customer-avatar">PR</span>
                                        <span>Park Residences</span>
                                    </div>
                                </td>
                                <td class="customer-email">mgmt@parkres.com</td>
                                <td class="customer-phone">+1 555-0104</td>
                                <td class="customer-projects">2</td>
                                <td><?= project_status_badge('completed', 'customer-badge') ?></td>
                                <td class="text-center">
                                    <div class="customer-actions">
                                        <a href="#" class="customer-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="customer-action" title="Delete"><i class="bi bi-archive"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="customer-name">
                                        <span class="customer-avatar">LCH</span>
                                        <span>Lee Custom Homes</span>
                                    </div>
                                </td>
                                <td class="customer-email">lee@customhomes.com</td>
                                <td class="customer-phone">+1 555-0105</td>
                                <td class="customer-projects">4</td>
                                <td><?= project_status_badge('rejected', 'customer-badge') ?></td>
                                <td class="text-center">
                                    <div class="customer-actions">
                                        <a href="#" class="customer-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="customer-action" title="Delete"><i class="bi bi-archive"></i></a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content add-customer-modal">

                <div class="modal-header add-customer-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body add-customer-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label add-customer-label">Customer Name</label>
                            <input type="text" class="form-control add-customer-input" id="newCustomerName" placeholder="Enter customer name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label add-customer-label">Email</label>
                            <input type="email" class="form-control add-customer-input" id="newCustomerEmail" placeholder="Enter email">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label add-customer-label">Phone Number</label>
                            <input type="text" class="form-control add-customer-input" id="newCustomerPhone" placeholder="Enter phone number">
                        </div>

                        <div class="col-12">
                            <label class="form-label add-customer-label">Address</label>
                            <textarea class="form-control add-customer-input" id="newCustomerAddress" rows="3" placeholder="Enter full address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer add-customer-footer">
                    <button type="button" class="btn btn-light border add-customer-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success add-customer-save" id="saveNewCustomerBtn">Add Customer</button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content edit-customer-modal">

                <div class="modal-header edit-customer-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body edit-customer-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label edit-customer-label">Customer Name</label>
                            <input type="text" class="form-control edit-customer-input" id="editCustomerName" placeholder="Enter customer name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label edit-customer-label">Email</label>
                            <input type="email" class="form-control edit-customer-input" id="editCustomerEmail" placeholder="Enter email">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label edit-customer-label">Phone Number</label>
                            <input type="text" class="form-control edit-customer-input" id="editCustomerPhone" placeholder="Enter phone number">
                        </div>

                        <div class="col-12">
                            <label class="form-label edit-customer-label">Address</label>
                            <textarea class="form-control edit-customer-input" id="editCustomerAddress" rows="3" placeholder="Enter full address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer edit-customer-footer">
                    <button type="button" class="btn btn-light border edit-customer-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success edit-customer-save" id="updateCustomerBtn">Save Changes</button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="archiveCustomerModal" tabindex="-1" aria-labelledby="archiveCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content archive-modal">

            <div class="modal-header archive-modal-header">
                <h5 class="modal-title" id="archiveCustomerModalLabel">Archive Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body archive-modal-body text-center">
                <div class="archive-icon mb-2">
                    <i class="bi bi-archive"></i>
                </div>

                <p class="archive-text mb-1">
                    Are you sure you want to archive
                </p>

                <p class="archive-name fw-semibold" id="archiveCustomerName">
                    Rivera Kitchens
                </p>

                <p class="archive-subtext">
                    This customer will be hidden from active records.
                </p>
            </div>

            <div class="modal-footer archive-modal-footer">
                <button class="btn btn-light border archive-cancel" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button class="btn archive-confirm" id="confirmArchiveBtn">
                    Archive
                </button>
            </div>

        </div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3">

    <div id="archiveToast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                Customer archived successfully.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>

</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3">

    <div id="mainToast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="mainToastMsg">
                Success
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>

</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get("open") === "create") {
                const modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
                modal.show();
            }
        });
        document.addEventListener("DOMContentLoaded", function () {

    let selectedCustomer = "";

    // EDIT CUSTOMER
    document.querySelectorAll(".edit-customer-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            selectedCustomer = this.dataset.name;

            document.getElementById("editCustomerName").value = this.dataset.name || "";
            document.getElementById("editCustomerEmail").value = this.dataset.email || "";
            document.getElementById("editCustomerPhone").value = this.dataset.phone || "";
            document.getElementById("editCustomerAddress").value = this.dataset.address || "";
        });
    });

    document.getElementById("updateCustomerBtn").addEventListener("click", function () {

        // show toast
        document.getElementById("mainToastMsg").textContent =
            selectedCustomer + " details updated successfully.";

        const toast = new bootstrap.Toast(document.getElementById("mainToast"));
        toast.show();

        // close modal
        const modalEl = document.getElementById("editCustomerModal");
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    });

    // ARCHIVE CUSTOMER
    document.querySelectorAll(".archive-customer-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            selectedCustomer = this.dataset.name;

            document.getElementById("archiveCustomerName").textContent = selectedCustomer;
        });
    });

    document.getElementById("confirmArchiveBtn").addEventListener("click", function () {

        // show toast
        document.getElementById("mainToastMsg").textContent =
            selectedCustomer + " archived successfully.";

        const toast = new bootstrap.Toast(document.getElementById("mainToast"));
        toast.show();

        // close modal
        const modalEl = document.getElementById("archiveCustomerModal");
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    });

});
    </script>
    </script>
</body>

</html>