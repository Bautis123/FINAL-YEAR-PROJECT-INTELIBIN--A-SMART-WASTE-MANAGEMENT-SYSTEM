<?php
/** components/dashboard/stats.php | the four-metric strip inside the bin card */
require_once __DIR__ . '/_helpers.php';
$ib_live = $vm['fill'] !== null;
$ib_now  = $vm['now'];
$ib_last = $vm['last_reading_ts'];
$ib_rate = $vm['fill_rate'];

$ib_mFull = '–'; $ib_mFullSub = 'Needs a few readings';
$ib_mRate = '–'; $ib_mRateSub = 'Needs a few readings';
$ib_mCountSub = 'No readings yet';
$ib_mEmpty = '–'; $ib_mEmptySub = 'Nothing recorded';
if ($ib_live) {
    if ($ib_rate === null)      { $ib_mRate = '–'; $ib_mRateSub = 'Just emptied'; $ib_mFull = '–'; $ib_mFullSub = 'Needs more readings'; }
    elseif ($ib_rate < 0.05)    { $ib_mRate = 'Steady'; $ib_mRateSub = 'since last emptied'; $ib_mFull = '> 2 days'; $ib_mFullSub = 'at the average rate'; }
    else {
        $ib_h = $vm['hours_to_full'] ?? (100 - $vm['fill']) / $ib_rate;
        $ib_mRate = '+' . number_format($ib_rate, 1) . '%/h'; $ib_mRateSub = 'since last emptied';
        $ib_mFull = ib_dur((float)$ib_h); $ib_mFullSub = 'at the average rate';
    }
    $ib_mCountSub = $ib_last !== null ? 'Last one ' . ib_rel($ib_now - $ib_last) : 'No readings yet';
    if ($vm['last_emptied_ts'] !== null) { $ib_mEmpty = ib_span($ib_now - $vm['last_emptied_ts']); $ib_mEmptySub = 'ago, ' . ib_when($vm['last_emptied_ts'], $ib_now); }
    else { $ib_mEmpty = '–'; $ib_mEmptySub = 'None in the last 7 days'; }
}
?>
<dl class="ib-metrics">
  <div><dt>Time to full</dt><dd class="ib-v" data-ib="mFull"><?= ib_e($ib_mFull) ?></dd><dd class="ib-s" data-ib="mFullSub"><?= ib_e($ib_mFullSub) ?></dd></div>
  <div><dt>Fill rate</dt><dd class="ib-v" data-ib="mRate"><?= ib_e($ib_mRate) ?></dd><dd class="ib-s" data-ib="mRateSub"><?= ib_e($ib_mRateSub) ?></dd></div>
  <div><dt>Readings</dt><dd class="ib-v" data-ib="mCount"><?= number_format((int)$vm['total_readings']) ?></dd><dd class="ib-s" data-ib="mCountSub"><?= ib_e($ib_mCountSub) ?></dd></div>
  <div><dt>Last emptied</dt><dd class="ib-v" data-ib="mEmpty"><?= ib_e($ib_mEmpty) ?></dd><dd class="ib-s" data-ib="mEmptySub"><?= ib_e($ib_mEmptySub) ?></dd></div>
</dl>
