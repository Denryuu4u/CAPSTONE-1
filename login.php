<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login – Vast Solutions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style/login.css" />
</head>

<body>

  <!-- LEFT -->
  <div class="left-panel">
    <a class="brand" href="index.html">
      <img src="style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
      Vast Solutions
    </a>
    <h2 class="panel-title">Access Your Account</h2>
    <p class="panel-desc">Sign in to manage your cabinet projects, review quotations, and monitor project progress in one place.</p>
  </div>

  <!-- RIGHT -->
  <div class="right-panel">
    <div class="form-box">
      <a href="index.php" class="back-link">
        &#8592; Back to home
      </a>
      <h1 class="form-title">Welcome back</h1>
      <p class="form-subtitle">Sign in to access your dashboard</p>

      <form action="user-dashboard/dashboard.php" method="POST">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="you@company.com" required />
        </div>
        <div class="mb-1">
          <label class="form-label">Password</label>
          <div class="password-wrap">
            <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required />
            <button type="button" class="toggle-pw" onclick="togglePw('loginPassword', this)">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
        </div>
        <div class="row-extra">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" />
            <label class="form-check-label" for="rememberMe">Remember me</label>
          </div>
          <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
        </div>
        <button type="submit" href="user-dashboard/dashboard.php" class="btn-submit">Sign In</button>
        <div class="form-footer">
          Don't have an account? <a href="signup.php">Sign up</a>
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
  </script>
</body>

</html>