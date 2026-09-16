<?php
/** @var string $success */
/** @var int $openAlerts */
/** @var int $resolvedAlerts */
/** @var int $totalAlerts */
/** @var int|string|null $avgResponse */
/** @var array<int, array<string, mixed>> $open */
/** @var array<int, array<string, mixed>> $resolved */
?>
<div class="page">
  <?php partial('shared/page_header.php', [
    'title' => 'Alerts',
    'subtitle' => 'Full bin events - open alerts need attention, resolved ones are logged for records',
    'action' => $openAlerts
      ? '<span class="chip c-crit">' . $openAlerts . ' open</span>'
      : '<span class="chip c-ok">All clear</span>',
  ]); ?>

  <?php partial('shared/flash.php', [
    'message' => $success,
    'icon' => 'OK',
    'titleStyle' => 'color:var(--green)',
    'style' => 'background:var(--green-bg);border-color:var(--green-border);border-left-color:var(--green)',
  ]); ?>

  <div class="stats-row rise d1">
    <?php partial('shared/stat_card.php', [
      'label' => 'Open Alerts',
      'value' => (string)$openAlerts,
      'valueStyle' => 'color:var(--red)',
      'sub' => $openAlerts ? 'Need attention now' : 'All clear',
    ]); ?>

    <?php partial('shared/stat_card.php', [
      'label' => 'Resolved',
      'value' => (string)$resolvedAlerts,
      'valueStyle' => 'color:var(--green)',
      'sub' => 'bins emptied',
    ]); ?>

    <?php partial('shared/stat_card.php', [
      'label' => 'Total Alerts',
      'value' => (string)$totalAlerts,
      'valueStyle' => 'color:var(--amber)',
      'sub' => 'all time',
    ]); ?>

    <?php partial('shared/stat_card.php', [
      'label' => 'Avg Response',
      'value' => $avgResponse ? humanDuration((int)$avgResponse) : '-',
      'valueStyle' => 'color:var(--blue);font-size:20px',
      'sub' => 'time to resolve',
    ]); ?>
  </div>

  <div class="card rise d2">
    <div class="card-header">
      <div class="card-title">Open Alerts</div>
    </div>

    <?php if (empty($open)): ?>
    <?php partial('shared/empty_state.php', [
      'icon' => 'OK',
      'title' => 'No open alerts',
      'desc' => 'All bins are below 80%.',
    ]); ?>
    <?php else: ?>
    <?php foreach ($open as $a): ?>
    <?php $mins = (int)$a['mins_open']; ?>
    <form method="POST" class="open-alert-row <?= urgencyClass($mins) ?>">
      <input type="hidden" name="action" value="resolve">
      <input type="hidden" name="alert_id" value="<?= (int)$a['id'] ?>">
      <strong><?= e($a['bin_name']) ?></strong>
      <span><?= (int)$a['fill_percent'] ?>%</span>
      <span><?= humanDuration($mins) ?></span>
      <button class="btn btn-success">Mark Emptied</button>
    </form>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card rise d3">
    <div class="card-header">
      <div class="card-title">Resolved History</div>
      <span class="chip c-ok"><?= (int)$resolvedAlerts ?> total</span>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Bin</th>
            <th>Fill</th>
            <th>Triggered</th>
            <th>Resolved</th>
            <th>Response</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resolved as $a): ?>
          <tr>
            <td><?= e($a['bin_name']) ?></td>
            <td><?= (int)$a['fill_percent'] ?>%</td>
            <td class="mono"><?= date('d M Y, H:i', strtotime($a['triggered_at'])) ?></td>
            <td class="mono"><?= date('d M Y, H:i', strtotime($a['resolved_at'])) ?></td>
            <td><?= $a['mins_to_resolve'] !== null ? humanDuration((int)$a['mins_to_resolve']) : '-' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
