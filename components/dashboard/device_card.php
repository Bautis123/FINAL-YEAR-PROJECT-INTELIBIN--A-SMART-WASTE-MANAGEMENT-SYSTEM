<?php
/** components/dashboard/device_card.php | sensor, controller, sync and bin settings */
require_once __DIR__ . '/_helpers.php';
$ib_live = $vm['fill'] !== null;
$ib_dev  = $vm['device'];
$ib_sOn  = $ib_dev['sensor_online'] !== false;
$ib_cOn  = $ib_dev['controller_online'] !== false;
$ib_sTxt = !$ib_live ? 'No data yet' : $ib_dev['sensor_label'] . ($ib_sOn ? ' online' : ' offline');
$ib_cTxt = !$ib_live ? 'No data yet' : $ib_dev['controller_label'] . ($ib_cOn ? ' connected' : ' offline');
$ib_sKey = !$ib_live ? 'idle' : ($ib_sOn ? 'ready' : 'full');
$ib_cKey = !$ib_live ? 'idle' : ($ib_cOn ? 'ready' : 'full');
$ib_sync = ($ib_live && $vm['last_reading_ts'] !== null) ? ib_rel($vm['now'] - $vm['last_reading_ts']) : ($ib_live ? '–' : 'Never');
?>
<section class="ib-panel" aria-labelledby="ib-dev-title">
  <h2 id="ib-dev-title">Device</h2>
  <ul class="ib-kv">
    <li><span class="ib-k">Ultrasonic sensor</span><span class="ib-v"><i class="ib-dot" data-ib="dSensorDot" data-ib-s="<?= $ib_sKey ?>"></i><span data-ib="dSensor"><?= ib_e($ib_sTxt) ?></span></span></li>
    <li><span class="ib-k">Controller</span><span class="ib-v"><i class="ib-dot" data-ib="dCtrlDot" data-ib-s="<?= $ib_cKey ?>"></i><span data-ib="dCtrl"><?= ib_e($ib_cTxt) ?></span></span></li>
    <li><span class="ib-k">Last sync</span><span class="ib-v" data-ib="dSync"><?= ib_e($ib_sync) ?></span></li>
    <li><span class="ib-k">Bin height</span><span class="ib-v" data-ib="binHeight"><?= ib_e($vm['bin']['height_cm']) ?> cm</span></li>
    <li><span class="ib-k">Alert threshold</span><span class="ib-v" data-ib="alertAtText"><?= (int)$vm['bin']['alert_at'] ?>% full</span></li>
  </ul>
</section>
