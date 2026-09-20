<?php
/** components/dashboard/bin_card.php | the hero: bin drawing, big fill number, status pill and stats */
require_once __DIR__ . '/_helpers.php';
$ib_live = $vm['fill'] !== null;
$ib_st   = ib_status($ib_live ? (float)$vm['fill'] : null, (int)$vm['bin']['alert_at']);
?>
<section class="ib-hero" data-ib="hero" data-ib-s="<?= $ib_st['key'] ?>" aria-label="Current bin status">
  <?php include __DIR__ . '/bin_visual.php'; ?>
  <div class="ib-hero-copy">
    <div class="ib-hero-head">
      <div>
        <h2 data-ib="binName"><?= ib_e($vm['bin']['name']) ?></h2>
        <p><?= ib_icon('pin') ?><span data-ib="binLocation"><?= ib_e($vm['bin']['location']) ?></span></p>
      </div>
      <span class="ib-pill" data-ib="heroPill"><i></i><span data-ib="pillText"><?= ib_e($ib_st['label']) ?></span></span>
    </div>
    <div class="ib-hero-read">
      <div class="ib-big<?= $ib_live ? '' : ' ib-is-empty' ?>" data-ib="bigBox"><span data-ib="fillBig"><?= $ib_live ? (int)round($vm['fill']) : '–' ?></span><small data-ib="fillPct"<?= $ib_live ? '' : ' style="display:none"' ?>>%</small></div>
      <p data-ib="fillNote"><?= $ib_live ? 'of capacity used' : 'Waiting for the first reading from the sensor.' ?></p>
    </div>
    <?php include __DIR__ . '/stats.php'; ?>
  </div>
</section>
