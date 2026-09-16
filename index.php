<?php
require_once 'includes/auth.php';

header('Location: /intelibin/' . (admin_logged_in() ? 'dashboard.php' : 'login.php'));
exit;
