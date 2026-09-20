<?php
if (!function_exists('e')) {
function e(mixed $value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
}

if (!function_exists('fillColour')) {
function fillColour(int $pct): string {
  if ($pct >= 80) return 'var(--red)';
  if ($pct >= 50) return 'var(--amber)';
  if ($pct >= 20) return 'var(--blue)';
  return 'var(--green)';
}
}

if (!function_exists('fillDisplay')) {
function fillDisplay(mixed $pct, bool $hasReading = true): string {
  return $hasReading ? ((int)$pct . '%') : 'No readings';
}
}

if (!function_exists('fillBg')) {
function fillBg(int $pct): string {
  if ($pct >= 80) return 'linear-gradient(to top,#fca5a5,#f87171)';
  if ($pct >= 50) return 'linear-gradient(to top,#fcd34d,#fbbf24)';
  if ($pct >= 20) return 'linear-gradient(to top,#93c5fd,#60a5fa)';
  return 'linear-gradient(to top,#86efac,#4ade80)';
}
}

if (!function_exists('statusLabel')) {
function statusLabel(string $s): string {
  return match($s) { 'full'=>'Full','almost_full'=>'Almost Full','filling'=>'Filling','idle'=>'No data', default=>'Ready' };
}
}

if (!function_exists('statusBadge')) {
function statusBadge(string $s): string {
  return match($s) { 'full'=>'b-red','almost_full'=>'b-amber','filling'=>'b-blue','idle'=>'b-idle', default=>'b-green' };
}
}

if (!function_exists('chipClass')) {
function chipClass(string $s): string {
  return match($s) { 'full'=>'c-crit','almost_full'=>'c-warn','filling'=>'c-info','idle'=>'c-idle', default=>'c-ok' };
}
}

if (!function_exists('chipLabel')) {
function chipLabel(string $s): string {
  return statusLabel($s);
}
}

if (!function_exists('urgencyClass')) {
function urgencyClass(int $mins): string {
  if ($mins >= 120) return 'urgency-critical';
  if ($mins >= 30) return 'urgency-high';
  return 'urgency-new';
}
}

if (!function_exists('urgencyLabel')) {
function urgencyLabel(int $mins): string {
  if ($mins >= 120) return 'Critical';
  if ($mins >= 30) return 'Overdue';
  return 'New';
}
}
