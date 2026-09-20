<?php
/**
 * components/dashboard/pipeline.php | data pipeline strip (sensor to dashboard)
 * Feed it $vm['pipeline'] = [['label'=>'Ultrasonic sensor','detail'=>'HC-SR04','state'=>'ready'], ...].
 * state: ready (green) | filling (amber) | almost (orange) | full (red) | idle (grey).
 * Renders nothing when the list is empty. Map your existing pipeline data into this shape.
 */
require_once __DIR__ . '/_helpers.php';
if (!$vm['pipeline']) return;
?>
<section class="ib-panel ib-wide" aria-labelledby="ib-pipe-title">
  <h2 id="ib-pipe-title">Data pipeline</h2>
  <div class="ib-pipe">
<?php foreach ($vm['pipeline'] as $ib_s): ?>
    <div class="ib-step" data-ib-s="<?= ib_e($ib_s['state'] ?? 'idle') ?>">
      <div class="ib-node"><i></i></div>
      <b><?= ib_e($ib_s['label']) ?></b>
      <?php if (!empty($ib_s['detail'])): ?><span><?= ib_e($ib_s['detail']) ?></span><?php endif; ?>
    </div>
<?php endforeach; ?>
  </div>
</section>
