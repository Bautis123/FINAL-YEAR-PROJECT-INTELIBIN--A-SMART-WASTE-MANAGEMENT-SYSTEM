<?php if (!empty($message)): ?>
<div class="alert-banner rise <?= e($class ?? '') ?>" style="<?= e($style ?? '') ?>">
  <span><?= e($icon ?? '') ?></span>
  <div class="alert-banner-text">
    <div class="alert-banner-title" style="<?= e($titleStyle ?? '') ?>"><?= e($message) ?></div>
  </div>
</div>
<?php endif; ?>
