<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> - InteliBin</title>
  <?php $ib_dashboard_fonts = !empty($extra_css) && str_contains((string)$extra_css, 'dashboard.css'); ?>
  <?php if ($ib_dashboard_fonts): ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Hanken+Grotesk:wght@400..700&display=block" rel="stylesheet">
  <?php else: ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <?php endif; ?>
  <link rel="stylesheet" href="/intelibin/assets/css/app.css">
  <?php if (!empty($extra_css)): ?>
  <link rel="stylesheet" href="<?= e($extra_css) ?>">
  <?php endif; ?>
  <?= $extra_head ?? '' ?>
  <?php if ($ib_dashboard_fonts): ?>
  <script defer src="/intelibin/assets/js/page-transitions.js"></script>
  <?php endif; ?>
</head>
<body>
