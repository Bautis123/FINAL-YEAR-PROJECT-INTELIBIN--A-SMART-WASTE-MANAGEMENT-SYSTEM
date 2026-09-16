<div class="alert-banner rise d1" id="fullAlert" style="<?= $fill < 80 ? 'display:none' : '' ?>">
  <span class="alert-pill">ALERT</span>
  <div class="alert-banner-text">
    <div class="alert-banner-title" style="color:var(--red)">Bin is Full - Needs Emptying</div>
    <div class="alert-banner-desc">Fill level is at <strong id="alertPct"><?= (int)$fill ?>%</strong>. Automatic lid opening is disabled.</div>
  </div>
  <button class="btn btn-ghost danger-outline" onclick="document.getElementById('fullAlert').style.display='none'">Dismiss</button>
</div>
