<?php
/** components/dashboard/recent_readings.php | last 8 readings */
require_once __DIR__ . '/_helpers.php';
$ib_live = $vm['fill'] !== null && $vm['history'];
$ib_rows = $ib_live ? array_slice(array_reverse($vm['history']), 0, 8) : [];
?>
<section class="ib-panel" aria-labelledby="ib-read-title">
  <div class="ib-panel-head">
    <h2 id="ib-read-title">Recent readings</h2>
    <a href="<?= ib_e($vm['history_href']) ?>">View history</a>
  </div>
  <div class="ib-tablewrap">
    <table>
      <thead><tr><th scope="col">Time</th><th scope="col">Distance</th><th scope="col">Fill level</th><th scope="col">Status</th></tr></thead>
      <tbody data-ib="rows">
<?php if (!$ib_rows): ?>
        <tr><td colspan="4" class="ib-none">No readings yet. Check that the Arduino is powered and connected. New readings will list here.</td></tr>
<?php endif; ?>
<?php foreach ($ib_rows as $ib_r): $ib_st = ib_status($ib_r['p'], (int)$vm['bin']['alert_at']); ?>
        <tr data-ib-s="<?= $ib_st['key'] ?>"><td><?= ib_time($ib_r['t'], true) ?></td><td><?= round(ib_dist($ib_r['p'], $vm['bin'])) ?> cm</td><td><span class="ib-bar"><span style="width:<?= number_format($ib_r['p'], 0) ?>%"></span></span><?= number_format($ib_r['p'], 0) ?>%</td><td><span class="ib-pill ib-sm"><i></i><?= $ib_st['label'] ?></span></td></tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
