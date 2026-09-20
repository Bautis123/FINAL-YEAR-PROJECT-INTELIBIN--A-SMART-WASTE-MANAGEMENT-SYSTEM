<?php
require_once 'includes/auth.php';
require_admin_login();
require_once 'pages/controllers/dashboard.php';
require_once 'includes/view.php';
require_once 'includes/ui.php';
partial('layout/head.php', get_defined_vars());
partial('dashboard/page.php', get_defined_vars());
?>
</body>
</html>
