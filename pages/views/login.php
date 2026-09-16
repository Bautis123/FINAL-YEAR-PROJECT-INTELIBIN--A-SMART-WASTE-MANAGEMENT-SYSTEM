<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - InteliBin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/intelibin/assets/css/pages/login.css">
</head>
<body>
  <main class="login-shell">
    <section class="login-brand-panel">
      <div class="login-bin-icon">
        <div class="login-bin-handle"></div>
        <div class="login-bin-lid"></div>
        <div class="login-bin-body"><span></span><span></span><span></span></div>
      </div>
      <h1>Inteli<span>Bin</span></h1>
      <p>Smart Waste Management</p>
      <div class="portal-label">Administration Portal</div>
    </section>

    <section class="login-form-panel">
      <form method="POST" class="login-card">
        <div class="login-heading">
          <h2>Admin Login</h2>
          <p>Sign in to access the InteliBin administration panel</p>
        </div>

        <?php if ($error): ?>
        <div class="login-error"><?= e($error) ?></div>
        <?php endif; ?>

        <label for="user_id">User ID</label>
        <div class="login-input">
          <span aria-hidden="true"></span>
          <input id="user_id" name="user_id" type="text" value="<?= e($userId) ?>" placeholder="Enter your User ID" required>
        </div>

        <label for="password">Password</label>
        <div class="login-input">
          <span aria-hidden="true">lock</span>
          <input id="password" name="password" type="password" placeholder="Enter your password" required>
          <button type="button" class="password-toggle" aria-label="Show password" data-toggle-password>view</button>
        </div>

        <a href="#" class="forgot-link">Forgot password?</a>
        <button class="login-submit" type="submit"> Sign In</button>
        <div class="secure-note"> Secure access for administrators only</div>
      </form>
    </section>
  </main>
  <script src="/intelibin/assets/js/pages/login.js"></script>
</body>
</html>
