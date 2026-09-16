<?php partial('shared/stat_card.php', [
  'label' => 'Bin Status',
  'id' => 'sv-status',
  'value' => e(statusLabel($status)),
  'valueStyle' => 'color:' . fillColour($fill) . ';font-size:18px;padding-top:4px',
  'sub' => 'current bin state',
]); ?>
