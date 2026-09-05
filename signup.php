<?php
$signupError = trim((string) ($_GET['error'] ?? ''));
$signupEmail = trim((string) ($_GET['email'] ?? ''));
$signupName  = trim((string) ($_GET['name'] ?? ''));

// Load the Terms & Privacy documents so we can show them in modals (no page nav).
// Wrapped so the sign-up form still renders even if the DB/legal table is missing.
$terms = $privacy = null;
try {
    require_once __DIR__ . '/includes/legal.php';
    $terms   = legal_doc('terms');
    $privacy = legal_doc('privacy');
} catch (Throwable $e) {
    $terms = $privacy = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up – Vast Solutions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style/signup.css" />
  <style>
    /* Legal modal content (matches legal.php styling) */
    .legal-modal .modal-title { font-family:'Syne',sans-serif; font-weight:800; }
    .legal-modal-body { font-size:.9rem; line-height:1.6; color:#374151; }
    .legal-modal-body .legal-h { font-size:1rem; font-weight:700; margin:18px 0 6px; color:#111827; }
    .legal-modal-body p { margin:0 0 10px; }
    .legal-modal-body ul { margin:0 0 12px; padding-left:22px; }
    .legal-modal-body li { margin-bottom:4px; }
    .legal-updated { color:#6b7280; font-size:.78rem; margin-bottom:14px; }
    .terms-link { background:none; border:none; padding:0; color:#0D9676; font:inherit;
                  text-decoration:underline; cursor:pointer; }
  </style>
</head>
<body>

  <!-- LEFT -->
  <div class="left-panel">
    <a class="brand" href="index.php">
      <img src="style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
      Vast Solutions
    </a>
    <h2 class="panel-title">Access Your Account</h2>
    <p class="panel-desc">Sign in to manage your cabinet projects, review quotations, and monitor project progress in one place.</p>
  </div>

  <!-- RIGHT -->
  <div class="right-panel">
    <div class="form-box">
      <a href="index.php" class="back-link">&#8592; Back to home</a>
      <h1 class="form-title">Create your account</h1>
      <p class="form-subtitle">Join Vast Solutions to manage your projects</p>

      <?php if ($signupError): ?>
        <div style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;font-size:.82rem;padding:.6rem .8rem;border-radius:8px;margin-bottom:1rem;"><?= htmlspecialchars($signupError) ?></div>
      <?php endif; ?>
      <!-- Client-side validation errors land here; the form is NOT submitted, so inputs are kept. -->
      <div id="jsError" style="display:none;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;font-size:.82rem;padding:.6rem .8rem;border-radius:8px;margin-bottom:1rem;"></div>

      <form action="register_process.php" method="POST" id="signupForm" novalidate>
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($signupName) ?>" required />
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="you@company.com" value="<?= htmlspecialchars($signupEmail) ?>" required />
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="password-wrap">
            <input type="password" name="password" id="signupPassword" class="form-control" placeholder="••••••••" required minlength="8" />
            <button type="button" class="toggle-pw" onclick="togglePw('signupPassword', this)">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Confirm Password</label>
          <div class="password-wrap">
            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="••••••••" required />
            <button type="button" class="toggle-pw" onclick="togglePw('confirmPassword', this)">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="mb-3" style="display:flex; align-items:flex-start; gap:.5rem;">
          <input type="checkbox" name="agree" id="agreeTerms" value="1" required style="margin-top:.2rem;" />
          <label for="agreeTerms" style="font-size:.82rem; color:#4b5563; line-height:1.4;">
            I have read and agree to the
            <button type="button" class="terms-link" data-bs-toggle="modal" data-bs-target="#termsModal">Terms &amp; Conditions</button> and
            <button type="button" class="terms-link" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</button>.
          </label>
        </div>

        <button type="submit" class="btn-submit">Create Account</button>
        <div class="form-footer">
          Already have an account? <a href="login.php">Sign in</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Terms & Conditions modal -->
  <div class="modal fade legal-modal" id="termsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?= htmlspecialchars($terms['title'] ?? 'Terms & Conditions') ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body legal-modal-body">
          <?php if ($terms): ?>
            <div class="legal-updated">Version <?= (int) $terms['version'] ?> · Last updated <?= htmlspecialchars(date('F d, Y', strtotime($terms['updated_at']))) ?></div>
            <?= legal_render($terms['body']) ?>
          <?php else: ?>
            <p>The Terms &amp; Conditions are not available right now. Please try again later.</p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="document.getElementById('agreeTerms').checked=true;">I Agree</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Privacy Policy modal -->
  <div class="modal fade legal-modal" id="privacyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?= htmlspecialchars($privacy['title'] ?? 'Privacy Policy') ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body legal-modal-body">
          <?php if ($privacy): ?>
            <div class="legal-updated">Version <?= (int) $privacy['version'] ?> · Last updated <?= htmlspecialchars(date('F d, Y', strtotime($privacy['updated_at']))) ?></div>
            <?= legal_render($privacy['body']) ?>
          <?php else: ?>
            <p>The Privacy Policy is not available right now. Please try again later.</p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="document.getElementById('agreeTerms').checked=true;">I Agree</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePw(id, btn) {
      const input = document.getElementById(id);
      input.type = input.type === 'password' ? 'text' : 'password';
    }

    // Validate on the client so mistakes (short password, unchecked terms, mismatch)
    // show inline WITHOUT submitting — the typed values are never cleared.
    document.getElementById('signupForm').addEventListener('submit', function (e) {
      const form  = e.target;
      const name  = form.full_name.value.trim();
      const email = form.email.value.trim();
      const pw    = document.getElementById('signupPassword').value;
      const cpw   = document.getElementById('confirmPassword').value;
      const agree = document.getElementById('agreeTerms').checked;
      const errors = [];

      if (!name)  errors.push('Please enter your full name.');
      if (!email) errors.push('Please enter your email address.');
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Please enter a valid email address.');
      if (pw.length < 8) errors.push('Password must be at least 8 characters.');
      if (pw !== cpw)    errors.push('Passwords do not match.');
      if (!agree)        errors.push('You must agree to the Terms & Conditions and Privacy Policy.');

      const box = document.getElementById('jsError');
      if (errors.length) {
        e.preventDefault();
        box.innerHTML = errors.map(msg => '• ' + msg).join('<br>');
        box.style.display = 'block';
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else {
        box.style.display = 'none';
      }
    });
  </script>
</body>
</html>
