<?php
/**
 * components/dashboard/event_log.php | recent alerts / event feed
 * Item kinds and their icon + colour: emptied, almost, full, lid_open, lid_closed, info.
 * Add your own kinds in EVENT_KINDS (dashboard-ui.js) and $ib_kinds below.
 */
require_once __DIR__ . '/_helpers.php';
$ib_kinds = [
    'emptied'    => ['ready',  'bin'],
    'almost'     => ['almost', 'bell'],
    'full'       => ['full',   'bell'],
    'lid_open'   => ['ready',  'unlock'],
    'lid_closed' => ['idle',   'lock'],
    'info'       => ['idle',   'info'],
];
$ib_events = ($vm['fill'] !== null) ? $vm['events'] : [];
?>
<section class="ib-panel" aria-labelledby="ib-alert-title">
  <div class="ib-panel-head">
    <h2 id="ib-alert-title">Recent alerts</h2>
    <a href="<?= ib_e($vm['alerts_href']) ?>">All alerts</a>
  </div>
  <ul class="ib-feed" data-ib="alertList">
<?php if (!$ib_events): ?>
    <li class="ib-none">No alerts yet. You will see a message here when the bin passes <?= (int)$vm['bin']['alert_at'] ?>% full.</li>
<?php endif; ?>
<?php foreach ($ib_events as $ib_ev): $ib_k = $ib_kinds[$ib_ev['kind']] ?? $ib_kinds['info']; ?>
    <li data-ib-s="<?= $ib_k[0] ?>"><span class="ib-ico"><?= ib_icon($ib_k[1]) ?></span><div><b><?= ib_e($ib_ev['title']) ?></b><small><?= ib_e($ib_ev['detail']) ?></small></div><time><?= ib_e(ib_when($ib_ev['t'], $vm['now'])) ?></time></li>
<?php endforeach; ?>
  </ul>
</section>
