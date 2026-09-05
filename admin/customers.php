<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'customers';
require_page($active_page); // role gate
$user_name = $_SESSION['full_name'] ?? 'Admin User';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/project_status.php';
$customers = db()->query(
    "SELECT c.*,
            (SELECT COUNT(*) FROM projects p WHERE p.customer_id = c.id) AS project_count,
            (SELECT p.status FROM projects p WHERE p.customer_id = c.id ORDER BY p.created_at DESC LIMIT 1) AS last_status
       FROM customers c
      WHERE c.is_archived = 0
      ORDER BY c.name"
)->fetchAll();
$initials = fn($name) => strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', array_slice(explode(' ', trim($name)), 0, 2))));

// Contact-form messages (public "Let's Talk" form → reviewed/replied to here).
require_once __DIR__ . '/../includes/contact.php';
$messages = [];
try {
    ensure_contact_messages_table();
    $messages = db()->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
} catch (Throwable $e) { $messages = []; }
$unreadCount = 0;
foreach ($messages as $m) { if (empty($m['is_read'])) $unreadCount++; }
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

    <link rel="stylesheet" href="admin.css?v=<?= @filemtime(__DIR__ . '/admin.css') ?: '1' ?>">
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

            <!-- Tabs: Customers | Messages (contact-form inbox) -->
            <ul class="nav nav-pills page-tabs mb-3" id="customerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-customers" type="button">
                        <i class="bi bi-people me-1"></i> Customers
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-messages" type="button">
                        <i class="bi bi-envelope me-1"></i> Messages
                        <?php if ($unreadCount > 0): ?>
                        <span class="badge rounded-pill bg-danger ms-1" id="msgUnreadBadge"><?= (int) $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
            <!-- ===== CUSTOMERS TAB ===== -->
            <div class="tab-pane fade show active" id="tab-customers" role="tabpanel">

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
                            <?php if (empty($customers)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No customers yet.</td></tr>
                            <?php else: foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <div class="customer-name">
                                        <span class="customer-avatar"><?= htmlspecialchars($initials($c['name'])) ?></span>
                                        <span><?= htmlspecialchars($c['name']) ?></span>
                                    </div>
                                </td>
                                <td class="customer-email"><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                                <td class="customer-phone"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                                <td class="customer-projects"><?= (int) $c['project_count'] ?></td>
                                <td><?= $c['last_status'] ? project_status_badge($c['last_status'], 'customer-badge') : '<span class="text-muted">—</span>' ?></td>
                                <td class="text-center">
                                    <div class="customer-actions">
                                        <a href="#" class="customer-action edit-customer-btn" title="Edit"
                                           data-bs-toggle="modal" data-bs-target="#editCustomerModal"
                                           data-id="<?= (int) $c['id'] ?>"
                                           data-name="<?= htmlspecialchars($c['name']) ?>"
                                           data-contact="<?= htmlspecialchars($c['contact_person'] ?? '') ?>"
                                           data-email="<?= htmlspecialchars($c['email'] ?? '') ?>"
                                           data-phone="<?= htmlspecialchars($c['phone'] ?? '') ?>"
                                           data-industry="<?= htmlspecialchars($c['industry'] ?? '') ?>"
                                           data-address="<?= htmlspecialchars($c['address'] ?? '') ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="#" class="customer-action archive-customer-btn" title="Archive"
                                           data-bs-toggle="modal" data-bs-target="#archiveCustomerModal"
                                           data-id="<?= (int) $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>">
                                            <i class="bi bi-archive"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            </div><!-- /#tab-customers -->

            <!-- ===== MESSAGES TAB (contact-form inbox) ===== -->
            <div class="tab-pane fade" id="tab-messages" role="tabpanel">
                <p class="text-muted mb-3" style="font-size:.9rem;">
                    Messages from the website contact form.
                </p>
                <?php if (empty($messages)): ?>
                    <div class="customer-card p-4 text-center text-muted">No messages yet.</div>
                <?php else: ?>
                <div class="customer-card contact-msg-card">
                    <div class="list-group list-group-flush">
                        <?php foreach ($messages as $m):
                            $isRead   = !empty($m['is_read']);
                            $subj     = trim((string) ($m['subject'] ?? ''));
                            $preview  = trim(preg_replace('/\s+/', ' ', (string) $m['message']));
                            $dateFull = date('M d, Y g:i A', strtotime($m['created_at']));
                        ?>
                        <button type="button"
                            class="list-group-item list-group-item-action contact-msg-row<?= $isRead ? '' : ' unread' ?>"
                            data-id="<?= (int) $m['id'] ?>"
                            data-name="<?= htmlspecialchars($m['name']) ?>"
                            data-email="<?= htmlspecialchars($m['email']) ?>"
                            data-subject="<?= htmlspecialchars($subj) ?>"
                            data-message="<?= htmlspecialchars((string) $m['message']) ?>"
                            data-date="<?= htmlspecialchars($dateFull) ?>"
                            data-read="<?= $isRead ? '1' : '0' ?>">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div class="msg-line">
                                    <span class="msg-dot"></span>
                                    <span class="msg-name"><?= htmlspecialchars($m['name']) ?></span>
                                    <?php if ($subj !== ''): ?><span class="msg-subj">— <?= htmlspecialchars($subj) ?></span><?php endif; ?>
                                    <span class="msg-preview"><?= htmlspecialchars($preview) ?></span>
                                </div>
                                <small class="msg-date text-muted flex-shrink-0"><?= htmlspecialchars($dateFull) ?></small>
                            </div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div><!-- /#tab-messages -->
            </div><!-- /.tab-content -->

        </div>
    </div>

    <!-- Contact message detail modal (populated by JS on row click) -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mmSubject">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-1"><strong id="mmName"></strong> &lt;<a id="mmEmail" href="#"></a>&gt;</div>
                    <div class="text-muted small mb-3" id="mmDate"></div>
                    <div id="mmMessage" style="white-space:pre-wrap; font-size:.95rem; color:#374151;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="mmReadBtn">Mark unread</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="mmDelBtn"><i class="bi bi-trash"></i> Delete</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
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
            // Open the Messages tab directly (e.g. from a notification link).
            if (urlParams.get("tab") === "messages") {
                const t = document.querySelector('[data-bs-target="#tab-messages"]');
                if (t) new bootstrap.Tab(t).show();
            }
        });
        document.addEventListener("DOMContentLoaded", function () {

    let selectedCustomer = "", selectedCustomerId = 0;

    function post(url, data) {
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(data) })
            .then(async r => { const d = await r.json().catch(() => ({ ok: false, error: 'Bad response' })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; });
    }

    // ADD CUSTOMER
    const addBtn = document.getElementById("saveNewCustomerBtn");
    if (addBtn) addBtn.addEventListener("click", function () {
        post('save_customer.php', {
            name: document.getElementById("newCustomerName").value,
            email: document.getElementById("newCustomerEmail").value,
            phone: document.getElementById("newCustomerPhone").value,
            address: document.getElementById("newCustomerAddress").value,
        }).then(() => location.reload()).catch(e => alert(e.message));
    });

    // EDIT CUSTOMER
    document.querySelectorAll(".edit-customer-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            selectedCustomer = this.dataset.name;
            selectedCustomerId = this.dataset.id;
            document.getElementById("editCustomerName").value = this.dataset.name || "";
            document.getElementById("editCustomerEmail").value = this.dataset.email || "";
            document.getElementById("editCustomerPhone").value = this.dataset.phone || "";
            document.getElementById("editCustomerAddress").value = this.dataset.address || "";
        });
    });
    const updBtn = document.getElementById("updateCustomerBtn");
    if (updBtn) updBtn.addEventListener("click", function () {
        post('save_customer.php', {
            id: selectedCustomerId,
            name: document.getElementById("editCustomerName").value,
            email: document.getElementById("editCustomerEmail").value,
            phone: document.getElementById("editCustomerPhone").value,
            address: document.getElementById("editCustomerAddress").value,
        }).then(() => location.reload()).catch(e => alert(e.message));
    });

    // ARCHIVE CUSTOMER
    document.querySelectorAll(".archive-customer-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            selectedCustomer = this.dataset.name;
            selectedCustomerId = this.dataset.id;
            document.getElementById("archiveCustomerName").textContent = selectedCustomer;
        });
    });
    document.getElementById("confirmArchiveBtn").addEventListener("click", function () {
        post('archive_entity.php', { type: 'customer', id: selectedCustomerId, action: 'archive' })
            .then(() => location.reload()).catch(e => alert(e.message));
    });

    // ===== CONTACT MESSAGES (Messages tab) — open full message in a modal =====
    const msgModalEl = document.getElementById('messageModal');
    const msgModal = msgModalEl ? new bootstrap.Modal(msgModalEl) : null;
    let curMsgId = 0, curMsgRead = false;

    function setUnreadBadge(delta) {
        const b = document.getElementById('msgUnreadBadge');
        if (!b) return;
        const n = (parseInt(b.textContent, 10) || 0) + delta;
        if (n > 0) b.textContent = n; else b.remove();
    }

    document.querySelectorAll('.contact-msg-row').forEach(row => {
        row.addEventListener('click', function () {
            curMsgId   = this.dataset.id;
            curMsgRead = this.dataset.read === '1';
            const subj = this.dataset.subject || '';
            document.getElementById('mmSubject').textContent = subj || 'Message';
            document.getElementById('mmName').textContent    = this.dataset.name;
            const a = document.getElementById('mmEmail');
            a.textContent = this.dataset.email;
            a.href = 'mailto:' + this.dataset.email + '?subject=' +
                     encodeURIComponent('Re: ' + (subj || 'Your inquiry to Vast Solutions'));
            document.getElementById('mmDate').textContent    = this.dataset.date;
            document.getElementById('mmMessage').textContent = this.dataset.message;
            document.getElementById('mmReadBtn').textContent = curMsgRead ? 'Mark unread' : 'Mark read';
            if (msgModal) msgModal.show();

            // Opening an unread message marks it read (updates row + tab badge live).
            if (!curMsgRead) {
                post('contact_action.php', { id: curMsgId, action: 'read' }).then(() => {
                    this.classList.remove('unread');
                    this.dataset.read = '1';
                    curMsgRead = true;
                    document.getElementById('mmReadBtn').textContent = 'Mark unread';
                    setUnreadBadge(-1);
                }).catch(() => {});
            }
        });
    });

    const mmDel = document.getElementById('mmDelBtn');
    if (mmDel) mmDel.addEventListener('click', function () {
        if (!confirm('Delete this message? This cannot be undone.')) return;
        post('contact_action.php', { id: curMsgId, action: 'delete' })
            .then(() => location.reload()).catch(e => alert(e.message));
    });
    const mmRead = document.getElementById('mmReadBtn');
    if (mmRead) mmRead.addEventListener('click', function () {
        post('contact_action.php', { id: curMsgId, action: curMsgRead ? 'unread' : 'read' })
            .then(() => location.reload()).catch(e => alert(e.message));
    });
});
    </script>
    </script>
</body>

</html>