<?php partial('shared/stat_card.php', [
  'label' => 'Total Readings',
  'id' => 'sv-count',
  'value' => (string)$totalReadings,
  'valueStyle' => 'color:var(--amber)',
  'subId' => 'sv-last',
  'sub' => $initial ? 'Last: ' . date('H:i:s', strtotime($initial['recorded_at'])) : 'No readings yet',
]); ?>
