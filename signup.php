<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up – Vast Solutions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style/signup.css" />
</head>
<body>

  <!-- LEFT -->
  <div class="left-panel">
    <a class="brand" href="index.html">
      <div class="brand-icon">
        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </div>
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

      <form action="register_process.php" method="POST" id="signupForm" novalidate>
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" placeholder="John Doe" required />
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="you@company.com" required />
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
          <div class="invalid-feedback text-danger" id="pwMismatch" style="display:none; font-size:0.75rem; margin-top:0.3rem;">
            Passwords do not match.
          </div>
        </div>

        <button type="submit" class="btn-submit">Create Account</button>
        <div class="form-footer">
          Already have an account? <a href="login.php">Sign in</a>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePw(id, btn) {
      const input = document.getElementById(id);
      input.type = input.type === 'password' ? 'text' : 'password';
    }

    document.getElementById('signupForm').addEventListener('submit', function(e) {
      const pw = document.getElementById('signupPassword').value;
      const cpw = document.getElementById('confirmPassword').value;
      const msg = document.getElementById('pwMismatch');
      const confirmInput = document.getElementById('confirmPassword');
      if (pw !== cpw) {
        e.preventDefault();
        msg.style.display = 'block';
        confirmInput.classList.add('is-invalid');
      } else {
        msg.style.display = 'none';
        confirmInput.classList.remove('is-invalid');
      }
    });
  </script>
</body>
</html>