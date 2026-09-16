<div class="nav-links">
  <?php foreach ($links as $key => $item): ?>
  <a href="<?= e($item['href']) ?>" class="nav-link <?= $active_nav === $key ? 'active' : '' ?>">
    <?= e($item['label']) ?>
  </a>
  <?php endforeach; ?>
</div>
