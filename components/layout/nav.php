<?php $links = require __DIR__ . '/nav_links_data.php'; ?>
<nav>
  <?php partial('layout/nav_brand.php'); ?>
  <?php partial('layout/nav_links.php', compact('links', 'active_nav')); ?>
  <?php partial('layout/nav_status.php'); ?>
</nav>
