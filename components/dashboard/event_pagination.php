<div class="event-pagination">
  <span class="mono">
    Showing <?= (int)$logStart ?>-<?= (int)$logEnd ?> of <?= (int)$totalEvents ?>
  </span>
  <?php partial('dashboard/event_page_link.php', [
    'label' => 'Prev',
    'page' => $logPage - 1,
    'disabled' => $logPage <= 1,
    'binId' => $binId,
  ]); ?>
  <span class="mono">Page <?= (int)$logPage ?> / <?= (int)$logTotalPages ?></span>
  <?php partial('dashboard/event_page_link.php', [
    'label' => 'Next',
    'page' => $logPage + 1,
    'disabled' => $logPage >= $logTotalPages,
    'binId' => $binId,
  ]); ?>
</div>
