<a href="?bin_id=<?= (int)$b['id'] ?>" class="bin-tab <?= $b['id'] === $binId ? 'active' : '' ?>">
  BIN <?= e($b['name']) ?>
  <?php if ($b['id'] === $binId): ?>
  <span class="bin-tab-loc"><?= e($b['location'] ?: '') ?></span>
  <?php endif; ?>
</a>
