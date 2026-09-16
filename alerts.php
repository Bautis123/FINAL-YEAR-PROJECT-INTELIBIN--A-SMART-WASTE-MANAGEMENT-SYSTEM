<?php
require_once 'includes/auth.php';
require_admin_login();
require_once 'pages/controllers/alerts.php';
require_once 'includes/header.php';
partial('alerts/page.php', get_defined_vars());
require_once 'includes/footer.php';
