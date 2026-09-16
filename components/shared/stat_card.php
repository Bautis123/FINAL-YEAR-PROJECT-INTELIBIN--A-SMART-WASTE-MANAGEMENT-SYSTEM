<div class="stat-card">
  <div class="stat-header">
    <span class="stat-label"><?= e($label) ?></span>
    <?php if (!empty($icon)): ?><div class="stat-icon <?= e($iconClass ?? '') ?>"><?= e($icon) ?></div><?php endif; ?>
  </div>
  <div class="stat-value" id="<?= e($id ?? '') ?>" style="<?= e($valueStyle ?? '') ?>">
    <?= $value ?>
  </div>
  <div class="stat-sub" id="<?= e($subId ?? '') ?>"><?= $sub ?></div>
</div>
