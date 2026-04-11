<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'settings';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Settings – Vast Solutions</title>

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
                    <span>Settings</span>
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
                <h1 class="page-title mb-0">Settings</h1>

                <div class="d-flex align-items-center settings-topbar-actions">
                    <a href="#" class="settings-save-btn">
                        <i class="bi bi-floppy"></i>
                        <span>Save Changes</span>
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="settings-card mb-3">
                        <div class="settings-card-title">Company Information</div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="settings-label">Company Name</label>
                                <input type="text" class="settings-input" value="My Company">
                            </div>
                            <div class="col-md-6">
                                <label class="settings-label">Email</label>
                                <input type="email" class="settings-input" value="info@mycompany.com">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">Address</label>
                            <input type="text" class="settings-input" value="123 Main Street, City">
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">Contact Number</label>
                            <input type="text" class="settings-input" value="+1 234 567 890">
                        </div>

                        <hr class="settings-divider">

                        <div>
                            <label class="settings-label">Company Logo</label>
                            <a href="#" class="settings-upload-btn">
                                <i class="bi bi-upload"></i>
                                <span>Upload Logo</span>
                            </a>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-title">Profile Information</div>

                        <div class="profile-top">
                            <div class="profile-avatar">JD</div>
                            <button type="button" class="profile-photo-btn">Change Photo</button>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="settings-label">First Name</label>
                                <input type="text" class="settings-input" value="John">
                            </div>
                            <div class="col-md-6">
                                <label class="settings-label">Last Name</label>
                                <input type="text" class="settings-input" value="Doe">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">Email</label>
                            <input type="email" class="settings-input" value="john@company.com">
                        </div>

                        <div>
                            <label class="settings-label">Phone</label>
                            <input type="text" class="settings-input" value="+1 (555) 123-4567">
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="settings-card">
                        <div class="settings-card-title">Default Costing Settings</div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="settings-label">Default Markup %</label>
                                <input type="text" class="settings-input" value="15">
                            </div>
                            <div class="col-md-6">
                                <label class="settings-label">Default Contingency %</label>
                                <input type="text" class="settings-input" value="5">
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="settings-label">Default Service %</label>
                                <input type="text" class="settings-input" value="10">
                            </div>
                            <div class="col-md-6">
                                <label class="settings-label">Default Protection %</label>
                                <input type="text" class="settings-input" value="3">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>