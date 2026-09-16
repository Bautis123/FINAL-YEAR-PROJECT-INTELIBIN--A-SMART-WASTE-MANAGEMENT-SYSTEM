<?php
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/time.php';

$page_title = $page_title ?? 'InteliBin';
$active_nav = $active_nav ?? '';

partial('layout/head.php', get_defined_vars());
partial('layout/nav.php', get_defined_vars());
