<?php
/** @var array<int, array<string, mixed>> $bins */
/** @var array<int, array<string, mixed>> $readings */
/** @var int $bin_id */
/** @var string $from */
/** @var string $to */
/** @var string $status */
/** @var int $total */
/** @var int $page_num */
/** @var int $total_pages */
?>
<div class="page">
  <?php partial('shared/page_header.php', [
    'title' => 'Reading History',
    'subtitle' => 'All sensor readings - filter by date range or status',
    'action' => '<a class="btn btn-ghost" href="api/export_csv.php?bin_id=' . (int)$bin_id . '&from=' . e($from) . '&to=' . e($to) . '">Export CSV</a>',
  ]); ?>

  <form method="GET" class="filter-bar rise d2">
    <div class="form-group">
      <label>Bin</label>
      <select name="bin_id">
        <?php foreach ($bins as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= $b['id'] == $bin_id ? 'selected' : '' ?>>
          <?= e($b['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>From</label>
      <input type="date" name="from" value="<?= e($from) ?>">
    </div>

    <div class="form-group">
      <label>To</label>
      <input type="date" name="to" value="<?= e($to) ?>">
    </div>

    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <option value="">All statuses</option>
        <?php foreach (['ready', 'filling', 'almost_full', 'full'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
          <?= e(chipLabel($s)) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button class="btn btn-primary">Filter</button>
    <a href="history.php" class="btn btn-ghost">Reset</a>
  </form>

  <div class="card rise d4">
    <div class="card-header">
      <div class="card-title" style="margin:0"><?= number_format($total) ?> readings found</div>
      <div class="mono">Page <?= $page_num ?> of <?= $total_pages ?></div>
    </div>

    <?php if (empty($readings)): ?>
    <?php partial('shared/empty_state.php', [
      'icon' => '?',
      'title' => 'No readings found',
      'desc' => 'Try adjusting the date range or status filter.',
    ]); ?>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Timestamp</th>
            <th>Bin</th>
            <th>Fill</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($readings as $r): ?>
          <tr>
            <td class="mono"><?= (int)$r['id'] ?></td>
            <td class="mono"><?= date('d M Y, H:i', strtotime($r['recorded_at'])) ?></td>
            <td><?= e($r['bin_name']) ?></td>
            <td style="font-weight:700;color:<?= fillColour((int)$r['fill_percent']) ?>">
              <?= (int)$r['fill_percent'] ?>%
            </td>
            <td>
              <span class="chip <?= chipClass($r['status']) ?>"><?= e(chipLabel($r['status'])) ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="pagination">
      <?php if ($page_num > 1): ?>
      <a class="btn btn-ghost" href="?<?= http_build_query(array_merge($_GET, ['p' => $page_num - 1])) ?>">Prev</a>
      <?php endif; ?>
      <span class="mono">Page <?= $page_num ?> / <?= $total_pages ?></span>
      <?php if ($page_num < $total_pages): ?>
      <a class="btn btn-ghost" href="?<?= http_build_query(array_merge($_GET, ['p' => $page_num + 1])) ?>">Next</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
