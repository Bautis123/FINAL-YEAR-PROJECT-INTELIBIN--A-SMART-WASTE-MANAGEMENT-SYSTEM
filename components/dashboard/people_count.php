<div class="people-panel">
  <div class="card-title">People Count</div>
  <div class="people-grid">
    <?php partial('dashboard/people_stat.php', ['id'=>'visitorsToday','value'=>$visitorsToday,'label'=>'visits today','color'=>'var(--blue)']); ?>
    <?php partial('dashboard/people_stat.php', ['id'=>'visitorsTotal','value'=>$visitorsTotal,'label'=>'all time','color'=>'var(--purple)']); ?>
  </div>
  <div class="people-note">Based on sensor readings per use cycle</div>
</div>
