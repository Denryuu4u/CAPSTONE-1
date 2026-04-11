<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'user_management';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Management – Vast Solutions</title>

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
                    <span>User Management</span>
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
                <h1 class="page-title mb-0">User Management</h1>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                <button type="button" class="customer-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add User</span>
                </button>

                <div class="user-search-wrap">
                    <i class="bi bi-search user-search-icon"></i>
                    <input type="text" class="form-control user-search" placeholder="Search users...">
                </div>
            </div>

            <div class="user-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 user-table">
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>EMAIL</th>
                                <th>ROLE</th>
                                <th>STATUS</th>
                                <th>LAST LOGIN</th>
                                <th class="text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="user-name">Admin User</td>
                                <td class="user-email">admin@vastsolutions.com</td>
                                <td><span class="user-role-badge role-admin">Admin</span></td>
                                <td><span class="user-status status-active">Active</span></td>
                                <td class="user-last-login">Mar 09, 2026</td>
                                <td class="text-center">
                                    <div class="user-actions">
                                        <a href="#"
                                            class="user-action edit-user-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal"
                                            data-name="Admin User"
                                            data-email="admin@vastsolutions.com"
                                            data-phone="+1 555-1234"
                                            data-role="Admin"
                                            data-status="Active">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="#"
                                            class="user-action archive-user-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveUserModal"
                                            data-name="Admin User">
                                            <i class="bi bi-archive"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="user-name">Niña</td>
                                <td class="user-email">nina@vastsolutions.com</td>
                                <td><span class="user-role-badge role-staff">Staff</span></td>
                                <td><span class="user-status status-active">Active</span></td>
                                <td class="user-last-login">Mar 08, 2026</td>
                                <td class="text-center">
                                    <div class="user-actions">
                                        <a href="#" class="user-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="user-action" title="Archive"><i class="bi bi-archive"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="user-name">Symon</td>
                                <td class="user-email">symon@vastsolutions.com</td>
                                <td><span class="user-role-badge role-staff">Staff</span></td>
                                <td><span class="user-status status-active">Active</span></td>
                                <td class="user-last-login">Mar 07, 2026</td>
                                <td class="text-center">
                                    <div class="user-actions">
                                        <a href="#" class="user-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="user-action" title="Archive"><i class="bi bi-archive"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="user-name">Angela</td>
                                <td class="user-email">angela@vastsolutions.com</td>
                                <td><span class="user-role-badge role-staff">Staff</span></td>
                                <td><span class="user-status status-active">Active</span></td>
                                <td class="user-last-login">Mar 06, 2026</td>
                                <td class="text-center">
                                    <div class="user-actions">
                                        <a href="#" class="user-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="user-action" title="Archive"><i class="bi bi-archive"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="user-name">Queen</td>
                                <td class="user-email">queen@vastsolutions.com</td>
                                <td><span class="user-role-badge role-staff">Staff</span></td>
                                <td><span class="user-status status-inactive">Inactive</span></td>
                                <td class="user-last-login">Feb 15, 2026</td>
                                <td class="text-center">
                                    <div class="user-actions">
                                        <a href="#" class="user-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="user-action" title="Archive"><i class="bi bi-archive"></i></a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content add-customer-modal">

                <div class="modal-header add-customer-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body add-customer-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label add-customer-label">First Name</label>
                            <input type="text" class="form-control add-customer-input" id="newUserFirstName" placeholder="Enter first name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label add-customer-label">Last Name</label>
                            <input type="text" class="form-control add-customer-input" id="newUserLastName" placeholder="Enter last name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label add-customer-label">Email</label>
                            <input type="email" class="form-control add-customer-input" id="newUserEmail" placeholder="Enter email">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label add-customer-label">Phone Number</label>
                            <input type="text" class="form-control add-customer-input" id="newUserPhone" placeholder="Enter phone number">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label add-customer-label">Role</label>
                            <select class="form-select add-customer-input" id="newUserRole">
                                <option selected disabled>-- Select Role --</option>
                                <option value="Admin">Admin</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label add-customer-label">Status</label>
                            <select class="form-select add-customer-input" id="newUserStatus">
                                <option selected>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer add-customer-footer">
                    <button type="button" class="btn btn-light border add-customer-cancel" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-success add-customer-save" id="saveNewUserBtn">
                        Add User
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content add-customer-modal">

                <div class="modal-header add-customer-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body add-customer-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="add-customer-label">First Name</label>
                            <input type="text" class="form-control add-customer-input" id="editUserName">
                        </div>
                        <div class="col-md-6">
                            <label class="add-customer-label">Last Name</label>
                            <input type="text" class="form-control add-customer-input" id="editUserLastName">
                        </div>

                        <div class="col-md-6">
                            <label class="add-customer-label">Email</label>
                            <input type="email" class="form-control add-customer-input" id="editUserEmail">
                        </div>

                        <div class="col-md-6">
                            <label class="add-customer-label">Phone</label>
                            <input type="text" class="form-control add-customer-input" id="editUserPhone">
                        </div>

                        <div class="col-md-6">
                            <label class="add-customer-label">Role</label>
                            <select class="form-select add-customer-input" id="editUserRole">
                                <option>Admin</option>
                                <option>Staff</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="add-customer-label">Status</label>
                            <select class="form-select add-customer-input" id="editUserStatus">
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="modal-footer add-customer-footer">
                    <button class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" id="updateUserBtn">Save Changes</button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="archiveUserModal" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content archive-modal">

                <div class="modal-header archive-modal-header">
                    <h5 class="modal-title">Archive User</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <div class="archive-icon mb-2">
                        <i class="bi bi-archive"></i>
                    </div>

                    <p>Are you sure you want to archive this user?</p>
                    <small class="text-muted">This user will be hidden from active records.</small>
                </div>

                <div class="modal-footer justify-content-center">
                    <button class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn archive-confirm" id="confirmArchiveUserBtn">Archive</button>
                </div>

            </div>
        </div>
    </div>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">

        <div id="mainToast" class="toast text-bg-success border-0">
            <div class="d-flex">
                <div class="toast-body" id="mainToastMsg">Success</div>
                <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            let selectedUser = "";

            // EDIT USER
            document.querySelectorAll(".edit-user-btn").forEach(btn => {
                btn.addEventListener("click", function() {
                    selectedUser = this.dataset.name;

                    document.getElementById("editUserName").value = this.dataset.name || "";
                    document.getElementById("editUserEmail").value = this.dataset.email || "";
                    document.getElementById("editUserPhone").value = this.dataset.phone || "";
                    document.getElementById("editUserRole").value = this.dataset.role || "";
                    document.getElementById("editUserStatus").value = this.dataset.status || "";
                });
            });

            document.getElementById("updateUserBtn").addEventListener("click", function() {

                document.getElementById("mainToastMsg").textContent =
                    "User details updated successfully.";

                new bootstrap.Toast(document.getElementById("mainToast")).show();

                bootstrap.Modal.getInstance(document.getElementById("editUserModal")).hide();
            });

            // ARCHIVE USER
            document.querySelectorAll(".archive-user-btn").forEach(btn => {
                btn.addEventListener("click", function() {
                    selectedUser = this.dataset.name;

                    document.getElementById("archiveUserName").textContent = selectedUser;
                });
            });

            document.getElementById("confirmArchiveUserBtn").addEventListener("click", function() {

                document.getElementById("mainToastMsg").textContent =
                    "User archived successfully.";

                new bootstrap.Toast(document.getElementById("mainToast")).show();

                bootstrap.Modal.getInstance(document.getElementById("archiveUserModal")).hide();
            });

        });
    </script>

</body>

</html>