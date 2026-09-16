<?php if ($disabled): ?>
<span class="btn btn-ghost disabled"><?= e($label) ?></span>
<?php else: ?>
<a
  class="btn btn-ghost"
  href="?<?= http_build_query(['bin_id' => $binId, 'log_page' => $page]) ?>#eventLog"
>
  <?= e($label) ?>
</a>
<?php endif; ?>
