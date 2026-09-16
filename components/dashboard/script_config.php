<script>
window.dashboardConfig = {
  apiUrl: '/intelibin/api/fetch_latest.php?bin_id=<?= (int)$binId ?>',
  cmdUrl: '/intelibin/api/command.php',
  binId: <?= (int)$binId ?>,
  logPage: <?= (int)$logPage ?>,
  logPerPage: <?= (int)$logPerPage ?>,
  latestReadingId: <?= (int)($initial['reading_id'] ?? 0) ?>,
  latestRecordedAt: <?= json_encode($initial['recorded_at'] ?? null) ?>,
  labels: <?= json_encode(array_map(fn($r) => date('H:i', strtotime($r['recorded_at'])), $chartRows)) ?>,
  data: <?= json_encode(array_map(fn($r) => (int)$r['fill_percent'], $chartRows)) ?>
};
</script>
<script src="/intelibin/assets/js/pages/dashboard.js"></script>
