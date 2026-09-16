<?php if (count($allBins) > 1): ?>
<div class="bin-switcher rise d1">
  <span class="bin-switcher-label">Monitoring:</span>
  <div class="bin-switcher-tabs">
    <?php foreach ($allBins as $b): ?>
    <?php partial('dashboard/bin_tab.php', compact('b', 'binId')); ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
