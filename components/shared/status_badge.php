<div class="status-badge <?= e(statusBadge($status)) ?>" id="<?= e($id ?? '') ?>">
  <div class="s-dot"></div>
  <span id="<?= e($textId ?? '') ?>"><?= e(statusLabel($status)) ?></span>
</div>
