<?php
/** @var array<int, array<string, mixed>> $bins */
/** @var string $success */
/** @var string $error */
?>
<div class="page">
  <?php partial('shared/page_header.php', [
    'title' => 'Bins',
    'subtitle' => 'Fill levels, emptying history, and bin management',
    'action' => '<span style="font-size:13px;color:var(--muted)">' . count($bins) . ' registered</span>',
  ]); ?>

  <?php partial('shared/flash.php', [
    'message' => $success,
    'icon' => 'OK',
    'titleStyle' => 'color:var(--green)',
    'style' => 'background:var(--green-bg);border-color:var(--green-border);border-left-color:var(--green)',
  ]); ?>

  <?php partial('shared/flash.php', [
    'message' => $error,
    'icon' => '!',
    'titleStyle' => 'color:var(--red)',
  ]); ?>

  <div class="bins-grid rise d2">
    <?php foreach ($bins as $bin): ?>
    <?php $hasReading = $bin['last_reading'] !== null; ?>
    <?php $pct = $hasReading ? (int)$bin['fill_percent'] : 0; ?>
    <?php $status = $hasReading ? ($bin['status'] ?? 'ready') : 'idle'; ?>

    <div class="card bin-tile">
      <div class="bin-tile-header">
        <div>
          <div class="bin-tile-name"><?= e($bin['name']) ?></div>
          <div class="bin-tile-location">Bin #<?= (int)$bin['id'] ?> · <?= e($bin['location'] ?: 'No location set') ?></div>
        </div>
        <?php partial('shared/status_badge.php', compact('status')); ?>
      </div>

      <div class="meta-table">
        <div class="meta-row">
          <span class="meta-key">Fill level</span>
          <span class="meta-val" style="color:<?= $hasReading ? fillColour($pct) : 'var(--ib-ink-3)' ?>"><?= e(fillDisplay($pct, $hasReading)) ?></span>
        </div>
        <div class="meta-row">
          <span class="meta-key">Height</span>
          <span class="meta-val"><?= (int)$bin['height_cm'] ?> cm</span>
        </div>
        <div class="meta-row">
          <span class="meta-key">Last reading</span>
          <span class="meta-val mono">
            <?= $bin['last_reading'] ? date('d M Y, H:i', strtotime($bin['last_reading'])) : 'No readings yet' ?>
          </span>
        </div>
      </div>

      <form method="POST" class="bin-edit-form">
        <input type="hidden" name="action" value="edit_bin">
        <input type="hidden" name="bin_id" value="<?= (int)$bin['id'] ?>">
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="name" value="<?= e($bin['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Location</label>
          <input type="text" name="location" value="<?= e($bin['location']) ?>">
        </div>
        <div class="form-group">
          <label>Height</label>
          <input type="number" name="height_cm" min="5" max="200" value="<?= (int)$bin['height_cm'] ?>" required>
        </div>
        <div class="bin-actions">
          <a class="btn btn-ghost" href="/intelibin/dashboard.php?bin_id=<?= (int)$bin['id'] ?>">View</a>
          <button class="btn btn-primary">Save</button>
          <button class="btn btn-danger" name="action" value="delete_bin">Delete</button>
        </div>
      </form>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card rise d3">
    <div class="card-title">Add New Bin</div>
    <form method="POST" class="form-row">
      <input type="hidden" name="action" value="add_bin">
      <div class="form-group">
        <label>Bin Name</label>
        <input type="text" name="name" required>
      </div>
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location">
      </div>
      <div class="form-group">
        <label>Height (cm)</label>
        <input type="number" name="height_cm" min="5" max="200" value="30" required>
      </div>
      <button class="btn btn-primary">Save Bin</button>
    </form>
  </div>
</div>
