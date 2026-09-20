<?php
/**
 * components/dashboard/chart_card.php | fill level over time
 * The chart is drawn by dashboard-ui.js from $vm['history']. The 6h / 24h / 7d buttons filter
 * the history you provide; pass onRangeChange in page.php if you want to fetch per range.
 */
require_once __DIR__ . '/_helpers.php';
$ib_live = $vm['fill'] !== null;
?>
<section class="ib-panel" aria-labelledby="ib-chart-title">
  <div class="ib-panel-head">
    <div>
      <h2 id="ib-chart-title">Fill level</h2>
      <p data-ib="rangeLabel">Last 24 hours</p>
    </div>
    <div class="ib-seg" role="group" aria-label="Time range">
      <button type="button" data-ib="range" data-range="6h" aria-pressed="false">6h</button>
      <button type="button" data-ib="range" data-range="24h" aria-pressed="true">24h</button>
      <button type="button" data-ib="range" data-range="7d" aria-pressed="false">7d</button>
    </div>
  </div>
  <div class="ib-chart-wrap" data-ib="chartWrap" role="img" aria-label="Fill level chart">
    <svg data-ib="chartSvg" aria-hidden="true"></svg>
    <div class="ib-cur" data-ib="curLine"></div>
    <div class="ib-curdot" data-ib="curDot"></div>
    <div class="ib-tip" data-ib="tip"></div>
    <div class="ib-chart-empty" data-ib="chartEmpty"<?= $ib_live ? '' : ' style="display:grid"' ?>><div><b>No readings yet</b><span>The chart starts drawing as soon as the sensor sends its first reading.</span></div></div>
  </div>
</section>
