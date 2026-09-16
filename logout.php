<?php
require_once 'includes/auth.php';

logout_admin();
header('Location: /intelibin/login.php');
exit;
