<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> - InteliBin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/intelibin/assets/css/app.css">
  <?php if (!empty($extra_css)): ?><link rel="stylesheet" href="<?= e($extra_css) ?>"><?php endif; ?>
  <?= $extra_head ?? '' ?>
</head>
<body>
