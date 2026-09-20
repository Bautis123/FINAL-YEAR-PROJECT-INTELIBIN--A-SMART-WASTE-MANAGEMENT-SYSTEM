<?php
/** components/layout/topbar.php | page title, connection pill and clock */
require_once __DIR__ . '/../dashboard/_helpers.php';
$ib_live = ($vm['device']['sensor_online'] ?? false) || ($vm['device']['controller_online'] ?? false);
?>
<header class="ib-top">
  <div>
    <h1>Dashboard</h1>
    <p data-ib="today"><?= ib_e(date('l, F j, Y', $vm['now'])) ?></p>
  </div>
  <div class="ib-top-right">
    <span class="ib-pill" data-ib="conn" data-ib-s="<?= $ib_live ? 'ready' : 'idle' ?>"><i></i><span data-ib="connText"><?= $ib_live ? 'Live' : 'Offline' ?></span></span>
    <span class="ib-clock" data-ib="clock" aria-label="Current time"><?= ib_e(date('h:i:s A', $vm['now'])) ?></span>
  </div>
</header>
