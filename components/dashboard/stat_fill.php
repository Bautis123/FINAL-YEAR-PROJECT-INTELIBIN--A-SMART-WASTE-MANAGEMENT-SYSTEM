<?php partial('shared/stat_card.php', [
  'label' => 'Fill Level',
  'id' => 'sv-fill',
  'value' => $fill . '<span class="stat-unit">%</span>',
  'valueStyle' => 'color:' . fillColour($fill),
  'sub' => 'of bin capacity',
]); ?>
