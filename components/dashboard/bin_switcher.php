<?php
/** components/dashboard/bin_switcher.php | bin tabs. Only renders when there are 2+ bins. */
require_once __DIR__ . '/_helpers.php';
if (count($vm['bins']) < 2) return;
?>
<nav class="ib-switch" aria-label="Choose a bin">
<?php foreach ($vm['bins'] as $ib_b):
    $ib_st = ib_status(isset($ib_b['fill']) ? (float)$ib_b['fill'] : null, (int)$vm['bin']['alert_at']);
    $ib_active = (string)$ib_b['id'] === (string)$vm['bin']['id']; ?>
  <a class="ib-tab" href="<?= ib_e($ib_b['href'] ?? ('?bin=' . $ib_b['id'])) ?>" data-ib-s="<?= $ib_st['key'] ?>"<?= $ib_active ? ' aria-current="true"' : '' ?>><i></i><?= ib_e($ib_b['name']) ?><?php if (!empty($ib_b['location'])): ?> <small><?= ib_e($ib_b['location']) ?></small><?php endif; ?></a>
<?php endforeach; ?>
</nav>
