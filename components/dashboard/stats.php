<div class="stats-row rise d1">
  <?php partial('dashboard/stat_fill.php', compact('fill')); ?>
  <?php partial('dashboard/stat_status.php', compact('fill', 'status')); ?>
  <?php partial('dashboard/stat_readings.php', compact('initial', 'totalReadings')); ?>
  <?php partial('dashboard/stat_eta.php'); ?>
</div>
