<?php
/** components/dashboard/full_alert.php | banner shown when fill reaches the alert threshold */
require_once __DIR__ . '/_helpers.php';
$ib_a  = (int)$vm['bin']['alert_at'];
$ib_fl = $vm['fill'] !== null && $vm['fill'] >= $ib_a;
$ib_st = ib_status($ib_fl ? (float)$vm['fill'] : null, $ib_a);
?>
<div class="ib-banner" data-ib="alertBanner" data-ib-s="<?= $ib_fl ? $ib_st['key'] : 'almost' ?>" role="alert"<?= $ib_fl ? '' : ' hidden' ?>>
  <span class="ib-ico"><?= ib_icon('bell') ?></span>
  <div>
    <b data-ib="bannerTitle"><?= ib_e($vm['bin']['name']) ?> is <?= $ib_fl ? (int)round($vm['fill']) : 0 ?>% full</b>
    <span data-ib="bannerDetail"><?= $ib_fl ? 'Collect as soon as possible.' : 'Collection needed soon.' ?></span>
  </div>
  <a href="<?= ib_e($vm['alerts_href']) ?>">View alerts</a>
</div>
