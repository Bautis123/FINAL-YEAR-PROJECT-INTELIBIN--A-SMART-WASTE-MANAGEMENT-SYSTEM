<div class="page">
  <?php partial('dashboard/bin_switcher.php', get_defined_vars()); ?>
  <?php partial('dashboard/full_alert.php', compact('fill')); ?>
  <?php partial('dashboard/stats.php', get_defined_vars()); ?>
  <div class="main-grid rise d2">
    <?php partial('dashboard/bin_card.php', get_defined_vars()); ?>
    <?php partial('dashboard/chart_card.php'); ?>
  </div>
  <?php partial('dashboard/event_log.php', get_defined_vars()); ?>
  <?php partial('dashboard/pipeline.php'); ?>
</div>

<?php partial('dashboard/script_config.php', get_defined_vars()); ?>
