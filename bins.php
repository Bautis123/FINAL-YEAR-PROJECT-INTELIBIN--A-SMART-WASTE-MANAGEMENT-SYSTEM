<?php
require_once 'includes/auth.php';
require_admin_login();
require_once 'pages/controllers/bins.php';
require_once 'includes/header.php';
partial('bins/page.php', get_defined_vars());
require_once 'includes/footer.php';
