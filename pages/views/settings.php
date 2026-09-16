<?php
/** @var array<int, array<string, mixed>> $bins */
/** @var string $success */
/** @var string $error */
?>
<div class="page">
  <?php partial('shared/page_header.php', [
    'title' => 'Settings',
    'subtitle' => 'Configure bin details and system preferences',
  ]); ?>

  <?php partial('shared/flash.php', [
    'message' => $success,
    'icon' => 'OK',
    'titleStyle' => 'color:var(--green)',
  ]); ?>

  <?php partial('shared/flash.php', [
    'message' => $error,
    'icon' => '!',
    'titleStyle' => 'color:var(--red)',
  ]); ?>

  <div class="card rise d2">
    <div class="card-title">Bin Configuration</div>
    <?php foreach ($bins as $bin): ?>
    <form method="POST" class="settings-form">
      <input type="hidden" name="action" value="update_bin">
      <input type="hidden" name="bin_id" value="<?= (int)$bin['id'] ?>">
      <div class="settings-bin-label">Bin #<?= (int)$bin['id'] ?></div>

      <div class="form-row">
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
      </div>

      <button class="btn btn-primary">Save Changes</button>
    </form>
    <?php endforeach; ?>
  </div>

  <div class="card rise d3">
    <div class="card-title">Fill Level Thresholds</div>
    <table>
      <thead>
        <tr>
          <th>Range</th>
          <th>Status</th>
          <th>Alert?</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>0% - 19%</td>
          <td><span class="chip c-ok">Ready</span></td>
          <td>No</td>
        </tr>
        <tr>
          <td>20% - 49%</td>
          <td><span class="chip c-info">Filling</span></td>
          <td>No</td>
        </tr>
        <tr>
          <td>50% - 79%</td>
          <td><span class="chip c-warn">Almost Full</span></td>
          <td>No</td>
        </tr>
        <tr>
          <td>80% - 100%</td>
          <td><span class="chip c-crit">Full</span></td>
          <td>Yes</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="card rise d4">
    <div class="card-title">API & Connection</div>
    <div class="meta-table">
      <div class="meta-row">
        <span class="meta-key">Latest reading endpoint</span>
        <code class="meta-val">/intelibin/api/fetch_latest.php?bin_id=1</code>
      </div>
      <div class="meta-row">
        <span class="meta-key">Poll interval</span>
        <span class="meta-val">Every 30 seconds</span>
      </div>
      <div class="meta-row">
        <span class="meta-key">Database</span>
        <span class="meta-val">MySQL - <code>intelibin</code></span>
      </div>
    </div>
  </div>
</div>
