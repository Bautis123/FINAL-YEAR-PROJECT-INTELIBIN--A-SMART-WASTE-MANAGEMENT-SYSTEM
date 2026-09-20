<?php
/** components/layout/sidebar.php | left navigation (replaces the old top nav) */
require_once __DIR__ . '/../dashboard/_helpers.php';
$ib_flag = $vm['fill'] !== null && $vm['fill'] >= $vm['bin']['alert_at'];
?>
<aside class="ib-side">
  <a class="ib-brand" href="<?= ib_e($vm['home_href']) ?>" aria-label="InteliBin home">
    <svg class="ib-mark" viewBox="0 0 40 40" aria-hidden="true">
      <rect width="40" height="40" rx="11" fill="#2DB56F"/>
      <path d="M15.5 8.6a6.5 6.5 0 0 1 9 0" stroke="#0D3628" stroke-width="2" stroke-linecap="round" fill="none"/>
      <rect x="10.5" y="12" width="19" height="3.2" rx="1.6" fill="#0D3628"/>
      <path d="M12 16.5h16l-1.5 13.2a2 2 0 0 1-2 1.8h-9a2 2 0 0 1-2-1.8L12 16.5Z" fill="#0D3628"/>
      <path d="M15.5 24h9" stroke="#2DB56F" stroke-width="1.8" stroke-linecap="round"/>
    </svg>
    <span><b>InteliBin</b><small>Smart waste monitoring</small></span>
  </a>
  <nav class="ib-nav" aria-label="Main">
<?php foreach ($vm['nav'] as $ib_item): ?>
    <a href="<?= ib_e($ib_item['href']) ?>"<?= !empty($ib_item['current']) ? ' aria-current="page"' : '' ?>><?= ib_icon($ib_item['icon']) ?><?= ib_e($ib_item['label']) ?><?php if (!empty($ib_item['badge'])): ?><span class="ib-nav-badge" data-ib="navBadge"<?= $ib_flag ? '' : ' hidden' ?>>1</span><?php endif; ?></a>
<?php endforeach; ?>
  </nav>
  <div class="ib-side-foot"><a href="<?= ib_e($vm['logout_href']) ?>"><?= ib_icon('out') ?><span>Log out</span></a></div>
</aside>
