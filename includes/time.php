<?php
if (!function_exists('humanDuration')) {
function humanDuration(int $mins): string {
  if ($mins < 60) return $mins . ' min' . ($mins !== 1 ? 's' : '');
  if ($mins < 1440) return round($mins / 60, 1) . ' hrs';
  return round($mins / 1440, 1) . ' days';
}
}
