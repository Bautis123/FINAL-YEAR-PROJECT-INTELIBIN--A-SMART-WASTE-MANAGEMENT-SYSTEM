<?php
require_once 'includes/auth.php';
require_admin_login();
require_once 'pages/controllers/settings.php';
require_once 'includes/view.php';
require_once 'includes/ui.php';
require_once 'components/dashboard/_helpers.php';
$vm = $vm ?? ib_shell_vm($active_nav ?? 'settings');
$extra_css = '/intelibin/assets/css/pages/dashboard.css';
partial('layout/head.php', get_defined_vars());
?>
<div class="ib-app" data-ib-root>
  <?php require_once 'components/dashboard/_icons.php'; ?>
  <?php partial('layout/sidebar.php', get_defined_vars()); ?>
  <div class="ib-main">
    <?php partial('layout/topbar.php', get_defined_vars()); ?>
    <?php partial('settings/page.php', get_defined_vars()); ?>
  </div>
</div>
</body>
</html>
