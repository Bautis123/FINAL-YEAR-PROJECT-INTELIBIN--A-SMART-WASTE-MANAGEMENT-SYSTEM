<div class="bin-lid"></div>
<div class="sensor-row">
  <div class="sensor-eye"></div>
  <span class="sensor-label">HC-SR04</span>
  <div class="sensor-eye"></div>
</div>
<div class="bin-body dashboard-bin-body">
  <div class="bin-fill" id="binFill" style="height:<?= (int)$fill ?>%;background:<?= fillBg((int)$fill) ?>"></div>
  <svg class="bin-guide-lines" viewBox="0 0 118 148" xmlns="http://www.w3.org/2000/svg">
    <line x1="0" y1="37" x2="118" y2="37" stroke="#dde1e7" stroke-dasharray="3 3" stroke-width="1"/>
    <line x1="0" y1="74" x2="118" y2="74" stroke="#dde1e7" stroke-dasharray="3 3" stroke-width="1"/>
    <line x1="0" y1="111" x2="118" y2="111" stroke="#dde1e7" stroke-dasharray="3 3" stroke-width="1"/>
  </svg>
  <div class="bin-pct-text" id="binPct"><?= (int)$fill ?>%</div>
</div>
