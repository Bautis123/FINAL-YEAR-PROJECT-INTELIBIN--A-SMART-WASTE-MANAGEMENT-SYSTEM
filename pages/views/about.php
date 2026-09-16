<?php
/** @var int $totalReadings */
/** @var int $totalBins */
/** @var int $totalAlerts */
/** @var string|null $firstReading */
?>
<div class="page">
  <?php partial('shared/page_header.php', [
    'title' => 'About InteliBin',
    'subtitle' => 'System overview, data pipeline, and hardware specifications',
  ]); ?>

  <div class="card rise d2">
    <div class="card-title">Project Overview</div>
    <p style="color:var(--muted);font-size:13px;line-height:1.7">
      InteliBin is a low-cost IoT prototype that uses an ultrasonic sensor and an Arduino Uno
      to measure waste-bin fill level in real time. Readings are stored in MySQL and displayed
      on this PHP dashboard without page refreshes.
    </p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:14px">
      <span class="chip c-ok">Final Year Project</span>
      <span class="chip c-info">University of Zambia</span>
    </div>
  </div>

  <div class="stats-row rise d2">
    <?php partial('shared/stat_card.php', [
      'label' => 'Total Readings',
      'value' => number_format($totalReadings),
      'valueStyle' => 'color:var(--blue)',
      'sub' => $firstReading ? 'Since ' . date('d M Y', strtotime($firstReading)) : 'No data yet',
    ]); ?>

    <?php partial('shared/stat_card.php', [
      'label' => 'Bins Monitored',
      'value' => (string)$totalBins,
      'valueStyle' => 'color:var(--green)',
      'sub' => 'registered in system',
    ]); ?>

    <?php partial('shared/stat_card.php', [
      'label' => 'Alerts Logged',
      'value' => (string)$totalAlerts,
      'valueStyle' => 'color:var(--red)',
      'sub' => 'full bin events',
    ]); ?>
  </div>

  <div class="card rise d3">
    <div class="card-title">Data Pipeline</div>
    <div class="dashboard-pipeline">
      <?php foreach (['HC-SR04 Sensor', 'Arduino Uno', 'Python Script', 'MySQL Database', 'PHP Dashboard'] as $i => $label): ?>
      <div class="pipeline-mini <?= $i === 4 ? 'active' : '' ?>">
        <span class="pipeline-mini-title"><?= e($label) ?></span>
        <span class="pipeline-mini-sub"><?= $i === 4 ? 'You are here' : 'sensor flow' ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card rise d4">
    <div class="card-title">Hardware Specifications</div>
    <table>
      <thead>
        <tr>
          <th>Component</th>
          <th>Model</th>
          <th>Purpose</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Microcontroller</td>
          <td>Arduino Uno R3</td>
          <td>Reads sensor and sends serial data</td>
        </tr>
        <tr>
          <td>Distance Sensor</td>
          <td>HC-SR04</td>
          <td>Measures distance to waste surface</td>
        </tr>
        <tr>
          <td>Lid Motor</td>
          <td>SG90 Servo</td>
          <td>Automated lid control</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
