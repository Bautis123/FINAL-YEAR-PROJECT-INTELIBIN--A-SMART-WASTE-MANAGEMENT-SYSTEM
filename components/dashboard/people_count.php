<?php
/** components/dashboard/people_count.php | visits today and all time */
require_once __DIR__ . '/_helpers.php';
?>
<section class="ib-panel" aria-labelledby="ib-ppl-title">
  <h2 id="ib-ppl-title">People count</h2>
  <div class="ib-stats">
    <div><b data-ib="vToday"><?= number_format((int)$vm['visits_today']) ?></b><span>visits today</span></div>
    <div><b data-ib="vAll"><?= number_format((int)$vm['visits_all']) ?></b><span>all time</span></div>
  </div>
  <p class="ib-note">Based on sensor readings per use cycle</p>
</section>
