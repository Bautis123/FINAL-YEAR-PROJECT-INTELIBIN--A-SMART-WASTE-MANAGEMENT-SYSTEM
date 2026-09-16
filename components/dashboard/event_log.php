<div class="card rise d4" id="eventLog">
  <div class="card-header">
    <div class="card-title" style="margin:0">Event Log</div>
    <span class="chip c-info" id="logCount"><?= number_format($totalEvents) ?> events</span>
  </div>
  <?php partial('dashboard/event_table.php', compact('logRows')); ?>
  <?php partial('dashboard/event_pagination.php', get_defined_vars()); ?>
</div>
