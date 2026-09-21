<?php
$login = [
  'action' => '/intelibin/login.php',
  'fields' => [
    'user' => 'user_id',
    'pass' => 'password',
    'remember' => 'remember_me',
  ],
  'csrf' => [
    'name' => 'login_csrf_token',
    'value' => $csrfToken ?? '',
  ],
  'error' => $error ?? null,
  'username' => $userId ?? '',
  'user_label' => 'Email or username',
  'show_remember' => false,
  'forgot_href' => null,
  'help_text' => null,
  'features' => [
    'Live fill level',
    'Full-bin alerts',
    'Remote lid control',
  ],
  'project_note' => 'Final year project by Bautis Chileshe, University of Zambia',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - InteliBin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Hanken+Grotesk:wght@400;500;600;700;800&display=block" rel="stylesheet">
  <link rel="stylesheet" href="/intelibin/assets/css/pages/login.css?v=20260921-login-fonts">
</head>
<body class="ib-lg-body">
  <?php include __DIR__ . '/../../components/auth/login.php'; ?>
  <script src="/intelibin/assets/js/login-ui.js?v=20260921-login-fonts"></script>
  <script>IntelibinLogin.init();</script>
</body>
</html>
