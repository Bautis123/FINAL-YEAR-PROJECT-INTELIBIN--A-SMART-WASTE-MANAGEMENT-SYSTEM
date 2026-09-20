<?php
/**
 * components/dashboard/bin_visual.php | the bin drawing (SVG)
 * PHP draws the current state so it looks right before JavaScript loads;
 * dashboard-ui.js then animates it (waste rises, sonar rings pulse, distance label follows).
 * Geometry: 0% fill sits at y=436, 100% at y=136, so surface y = 436 - 3 * fill.
 */
require_once __DIR__ . '/_helpers.php';
$ib_live = $vm['fill'] !== null;
$ib_p    = $ib_live ? (float)$vm['fill'] : 0.0;
$ib_sy   = 436 - 3 * $ib_p;
$ib_w    = min(96, ($ib_sy - 124) * 0.36);
$ib_y2   = $ib_sy - 2;
$ib_thr  = 436 - 3 * (int)$vm['bin']['alert_at'];
$ib_f    = fn($n) => number_format($n, 1, '.', '');
$ib_aria = $ib_live
    ? 'Bin is ' . round($ib_p) . '% full. The sensor reads ' . round(ib_dist($ib_p, $vm['bin'])) . ' centimetres to the waste.'
    : 'Bin is empty. No readings yet.';
?>
<svg class="ib-bin-svg" data-ib="binSvg" viewBox="0 0 360 470" role="img" aria-label="<?= ib_e($ib_aria) ?>">
  <defs>
    <clipPath id="ib-inside"><path d="M74 104H286L275 436H85Z"/></clipPath>
    <linearGradient id="ib-wg" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" style="stop-color:var(--ib-sc);stop-opacity:.95"/>
      <stop offset="1" style="stop-color:var(--ib-sc);stop-opacity:.6"/>
    </linearGradient>
    <linearGradient id="ib-cg" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#2DB56F" stop-opacity=".30"/>
      <stop offset="1" stop-color="#2DB56F" stop-opacity=".03"/>
    </linearGradient>
  </defs>

  <g>
    <line x1="52" x2="61" y1="436" y2="436" stroke="#9BB0A2"/><text class="ib-tick" x="44" y="440" text-anchor="end">0%</text>
    <line x1="52" x2="61" y1="361" y2="361" stroke="#9BB0A2"/><text class="ib-tick" x="44" y="365" text-anchor="end">25%</text>
    <line x1="52" x2="61" y1="286" y2="286" stroke="#9BB0A2"/><text class="ib-tick" x="44" y="290" text-anchor="end">50%</text>
    <line x1="52" x2="61" y1="211" y2="211" stroke="#9BB0A2"/><text class="ib-tick" x="44" y="215" text-anchor="end">75%</text>
    <line x1="52" x2="61" y1="136" y2="136" stroke="#9BB0A2"/><text class="ib-tick" x="44" y="140" text-anchor="end">100%</text>
  </g>

  <path d="M66 96H294L282 436Q281 446 272 446H88Q79 446 78 436Z" fill="#fff" fill-opacity=".72"/>

  <g clip-path="url(#ib-inside)">
    <polygon data-ib="cone" points="180,124 <?= $ib_f(180 - $ib_w) ?>,<?= $ib_f($ib_sy) ?> <?= $ib_f(180 + $ib_w) ?>,<?= $ib_f($ib_sy) ?>" fill="url(#ib-cg)"/>
    <path class="ib-ping" data-ib="ping0"/><path class="ib-ping" data-ib="ping1"/><path class="ib-ping" data-ib="ping2"/>
    <g data-ib="waste" transform="translate(0 <?= $ib_f($ib_sy) ?>)" style="opacity:<?= $ib_p < 0.5 ? 0 : 1 ?>">
      <path d="M60 6L80 0L98 8L118 -2L140 6L158 -4L176 5L198 -3L220 7L240 0L262 8L282 1L300 6V340H60Z" fill="url(#ib-wg)"/>
      <path d="M60 6L80 0L98 8L118 -2L140 6L158 -4L176 5L198 -3L220 7L240 0L262 8L282 1L300 6" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="1.5" stroke-linejoin="round"/>
      <g fill="#fff" fill-opacity=".24">
        <circle cx="112" cy="34" r="9"/>
        <rect x="150" y="26" width="26" height="12" rx="4" transform="rotate(-14 163 32)"/>
        <circle cx="216" cy="44" r="12"/>
        <rect x="238" y="20" width="18" height="18" rx="5" transform="rotate(20 247 29)"/>
        <circle cx="96" cy="86" r="14"/>
        <rect x="132" y="70" width="34" height="14" rx="5" transform="rotate(8 149 77)"/>
        <circle cx="188" cy="96" r="10"/>
        <rect x="220" y="84" width="28" height="16" rx="6" transform="rotate(-18 234 92)"/>
        <circle cx="266" cy="112" r="13"/>
        <rect x="112" y="132" width="30" height="14" rx="5"/>
        <circle cx="170" cy="150" r="15"/>
        <rect x="212" y="140" width="36" height="16" rx="6" transform="rotate(10 230 148)"/>
        <circle cx="100" cy="190" r="12"/>
        <rect x="150" y="196" width="28" height="14" rx="5" transform="rotate(-8 164 203)"/>
        <circle cx="250" cy="200" r="14"/>
        <circle cx="120" cy="250" r="16"/>
        <rect x="180" y="250" width="34" height="16" rx="6"/>
      </g>
    </g>
  </g>

  <path d="M66 96H294L282 436Q281 446 272 446H88Q79 446 78 436Z" fill="none" stroke="#6E8A79" stroke-width="2" stroke-linejoin="round"/>

  <line class="ib-thr-l" data-ib="thrLine" x1="58" x2="298" y1="<?= $ib_thr ?>" y2="<?= $ib_thr ?>"/>
  <text class="ib-thr-t" data-ib="thrText" x="302" y="<?= $ib_thr + 4 ?>">Alert <?= (int)$vm['bin']['alert_at'] ?>%</text>

  <g data-ib="dim"<?= $ib_live ? '' : ' style="display:none"' ?>>
    <line data-ib="dimLine" x1="180" x2="180" y1="132" y2="<?= $ib_f($ib_y2) ?>" stroke="#17694A" stroke-opacity=".85" stroke-dasharray="3 3"/>
    <line data-ib="capA" x1="172" x2="188" y1="132" y2="132" stroke="#17694A" stroke-width="1.6"/>
    <line data-ib="capB" x1="172" x2="188" y1="<?= $ib_f($ib_y2) ?>" y2="<?= $ib_f($ib_y2) ?>" stroke="#17694A" stroke-width="1.6"/>
    <circle data-ib="echo" cx="180" cy="<?= $ib_f($ib_y2) ?>" r="3.5" fill="#17694A"/>
    <g data-ib="chip" transform="translate(180 <?= $ib_f((132 + $ib_y2) / 2) ?>)">
      <rect x="-31" y="-12" width="62" height="24" rx="12" fill="#fff" stroke="#17694A" stroke-opacity=".55"/>
      <text data-ib="dimText" y="4.5" text-anchor="middle" fill="#0D3628" font-size="12.5" font-weight="700"><?= round(ib_dist($ib_p, $vm['bin'])) ?> cm</text>
    </g>
  </g>

  <rect x="56" y="74" width="248" height="24" rx="9" fill="#D3E4D8" stroke="#6E8A79"/>
  <rect x="152" y="62" width="56" height="14" rx="6" fill="#D3E4D8" stroke="#6E8A79"/>
  <rect x="128" y="98" width="104" height="26" rx="7" fill="#0D3628" stroke="#0D3628"/>
  <circle cx="154" cy="111" r="9.5" fill="#12382A" stroke="#7EF0B4" stroke-opacity=".75"/>
  <circle cx="154" cy="111" r="4.5" fill="#7EF0B4" fill-opacity=".4"/>
  <circle cx="206" cy="111" r="9.5" fill="#12382A" stroke="#7EF0B4" stroke-opacity=".75"/>
  <circle cx="206" cy="111" r="4.5" fill="#7EF0B4" fill-opacity=".4"/>
  <text x="101" y="119" text-anchor="middle" fill="#17694A" font-size="10" font-weight="600">HC-SR04</text>
</svg>
