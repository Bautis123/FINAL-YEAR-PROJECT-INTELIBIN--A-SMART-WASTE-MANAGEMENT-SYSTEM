<div class="card bin-card">
  <?php partial('dashboard/bin_card_header.php', compact('currentBin')); ?>
  <?php partial('dashboard/bin_visual.php', compact('fill')); ?>
  <?php partial('shared/status_badge.php', ['status'=>$status, 'id'=>'statusBadge', 'textId'=>'statusText']); ?>
  <div class="meta-table bin-meta">
    <div class="meta-row"><span class="meta-key">Bin height</span><span class="meta-val" id="binHeightMeta"><?= (int)$binHeight ?> cm</span></div>
    <div class="meta-row"><span class="meta-key">Lid state</span><span class="meta-val lid-state" id="lidStateMeta"><?= e(ucfirst($lidStatus)) ?></span></div>
  </div>
  <?php partial('dashboard/people_count.php', compact('visitorsToday', 'visitorsTotal')); ?>
  <?php partial('dashboard/lid_controls.php'); ?>
</div>
