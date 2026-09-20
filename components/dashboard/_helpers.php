<?php
/**
 * components/dashboard/_helpers.php
 *
 * The data contract for the whole dashboard lives here. Build ONE array ($vm)
 * from your database, pass it to page.php, and every component reads from it.
 * All timestamps are unix SECONDS in PHP (they are converted to ms for JS).
 * Set date_default_timezone_set('Africa/Lusaka') somewhere early in your app.
 *
 * $vm keys (anything you leave out gets a safe default):
 *   bin            ['id','name','location','height_cm','dead_zone_cm','alert_at']
 *   bins           list of ['id','name','location','fill']   (tabs show only when 2+)
 *   fill           latest fill % (float) or null when there are no readings yet
 *   total_readings int          last_reading_ts unix seconds|null
 *   lid            'open' | 'closed' | null
 *   visits_today   int          visits_all int
 *   device         ['sensor_online','controller_online','sensor_label','controller_label']
 *   history        list of ['t'=>unix sec,'p'=>fill %,'empty'=>bool?]  ascending, ~7 days
 *   events         list of ['t','kind','title','detail']   (omit to derive from history)
 *                  kind: emptied | almost | full | lid_open | lid_closed | info
 *   fill_rate, hours_to_full, last_emptied_ts     (omit to derive from history)
 *   pipeline       list of ['label','detail','state']  state: ready|filling|almost|full|idle
 *   nav            list of ['label','href','icon','current','badge']  (defaults provided)
 *   logout_href, alerts_href, history_href, asset_base
 */
if (!function_exists('ib_e')) {

function ib_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function ib_icon(string $name): string { return '<svg class="ib-ic"><use href="#ib-i-' . ib_e($name) . '"/></svg>'; }

function ib_default_nav(): array {
    return [
        ['label' => 'Dashboard', 'href' => '#', 'icon' => 'dash', 'current' => true],
        ['label' => 'Bins',      'href' => '#', 'icon' => 'bin'],
        ['label' => 'History',   'href' => '#', 'icon' => 'hist'],
        ['label' => 'Alerts',    'href' => '#', 'icon' => 'bell', 'badge' => true],
        ['label' => 'Settings',  'href' => '#', 'icon' => 'sliders'],
        ['label' => 'About',     'href' => '#', 'icon' => 'info'],
    ];
}

function ib_app_nav(string $active = 'dashboard', ?float $fill = null, int $alertAt = 80): array {
    $items = [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '/intelibin/dashboard.php', 'icon' => 'dash'],
        ['id' => 'bins', 'label' => 'Bins', 'href' => '/intelibin/bins.php', 'icon' => 'bin'],
        ['id' => 'history', 'label' => 'History', 'href' => '/intelibin/history.php', 'icon' => 'hist'],
        ['id' => 'alerts', 'label' => 'Alerts', 'href' => '/intelibin/alerts.php', 'icon' => 'bell', 'badge' => ($fill !== null && $fill >= $alertAt)],
        ['id' => 'settings', 'label' => 'Settings', 'href' => '/intelibin/settings.php', 'icon' => 'sliders'],
        ['id' => 'about', 'label' => 'About', 'href' => '/intelibin/about.php', 'icon' => 'info'],
    ];
    foreach ($items as &$item) $item['current'] = $item['id'] === $active;
    return $items;
}

function ib_shell_vm(string $active = 'dashboard', array $overrides = []): array {
    $fill = $overrides['fill'] ?? null;
    $alertAt = (int)($overrides['bin']['alert_at'] ?? 80);
    return array_replace_recursive([
        'fill' => $fill,
        'bin' => ['id' => 1, 'name' => 'InteliBin #1', 'location' => '', 'height_cm' => 30, 'dead_zone_cm' => 4, 'alert_at' => $alertAt],
        'device' => ['sensor_online' => false, 'controller_online' => false, 'sensor_label' => 'HC-SR04 pair', 'controller_label' => 'Arduino Uno'],
        'nav' => ib_app_nav($active, $fill, $alertAt),
        'home_href' => '/intelibin/dashboard.php',
        'logout_href' => '/intelibin/logout.php',
        'now' => time(),
    ], $overrides);
}

/* ---------- status, distance, formatting (mirrors dashboard-ui.js) ---------- */
function ib_status(?float $p, int $alertAt = 80): array {
    if ($p === null) return ['key' => 'idle',    'label' => 'Waiting for data'];
    if ($p >= 95)      return ['key' => 'full',    'label' => 'Full'];
    if ($p >= $alertAt) return ['key' => 'almost',  'label' => 'Almost full'];
    if ($p >= 60)      return ['key' => 'filling', 'label' => 'Filling up'];
    return ['key' => 'ready', 'label' => 'Ready'];
}
function ib_dist(float $p, array $bin): float {
    return $bin['dead_zone_cm'] + (100 - $p) / 100 * ($bin['height_cm'] - $bin['dead_zone_cm']);
}
function ib_time(int $ts, bool $secs = false): string { return date($secs ? 'h:i:s A' : 'h:i A', $ts); }
function ib_when(int $ts, int $now): string {
    return (date('Y-m-d', $ts) === date('Y-m-d', $now) ? 'Today' : date('D', $ts)) . ' ' . ib_time($ts);
}
function ib_rel(int $sec): string {
    $s = max(0, $sec);
    if ($s < 60) return $s . ' s ago';
    $m = intdiv($s, 60);
    if ($m < 60) return $m . ' min ago';
    $h = intdiv($m, 60);
    if ($h < 24) return $h . ' h ' . ($m % 60) . ' min ago';
    return intdiv($h, 24) . ' d ' . ($h % 24) . ' h ago';
}
function ib_span(int $sec): string {
    $m = max(1, intdiv($sec, 60));
    if ($m < 60) return $m . ' min';
    $h = intdiv($m, 60);
    if ($h < 24) return $h . ' h ' . ($m % 60) . ' min';
    return intdiv($h, 24) . ' d ' . ($h % 24) . ' h';
}
function ib_dur(float $h): string {
    if (!is_finite($h) || $h >= 48) return '> 2 days';
    $t = max(1, (int)round($h * 60));
    if ($t < 60) return $t . ' min';
    if ($t < 1440) return intdiv($t, 60) . ' h ' . ($t % 60) . ' min';
    return intdiv($t, 1440) . ' d ' . intdiv($t % 1440, 60) . ' h';
}

/* ---------- history normalising and deriving ---------- */
function ib_history(array $list): array {
    $h = [];
    foreach ($list as $x) $h[] = ['t' => (int)$x['t'], 'p' => (float)$x['p'], 'empty' => $x['empty'] ?? null];
    usort($h, fn($a, $b) => $a['t'] <=> $b['t']);
    foreach ($h as $i => &$x) {
        if ($x['empty'] === null) $x['empty'] = $i > 0 && ($h[$i - 1]['p'] - $x['p']) >= 25;
        $x['empty'] = (bool)$x['empty'];
    }
    return $h;
}
function ib_derive(array $h, int $alertAt): array {
    $out = ['fill_rate' => null, 'hours_to_full' => null, 'last_emptied_ts' => null, 'events' => []];
    if (!$h) return $out;
    $n = count($h); $last = $h[$n - 1];
    $i = $n - 1; while ($i > 0 && !$h[$i]['empty']) $i--;
    $hrs = ($last['t'] - $h[$i]['t']) / 3600;
    $out['fill_rate'] = $hrs >= 1 ? ($last['p'] - $h[$i]['p']) / $hrs : null;
    $out['hours_to_full'] = ($out['fill_rate'] !== null && $out['fill_rate'] >= 0.05) ? (100 - $last['p']) / $out['fill_rate'] : null;
    for ($k = $n - 1; $k >= 0; $k--) if ($h[$k]['empty']) { $out['last_emptied_ts'] = $h[$k]['t']; break; }
    $a80 = $h[0]['p'] < $alertAt; $a95 = $h[0]['p'] < 95; $ev = [];
    for ($k = 1; $k < $n; $k++) {
        $a = $h[$k - 1]; $b = $h[$k];
        if ($b['empty']) {
            $ev[] = ['t' => $b['t'], 'kind' => 'emptied', 'title' => 'Bin emptied', 'detail' => 'Fill dropped from ' . round($a['p']) . '% to ' . round($b['p']) . '%'];
            $a80 = $a95 = true; continue;
        }
        if ($a80 && $b['p'] >= $alertAt) { $ev[] = ['t' => $b['t'], 'kind' => 'almost', 'title' => 'Passed ' . $alertAt . '% full', 'detail' => 'Collection needed soon']; $a80 = false; }
        elseif (!$a80 && $b['p'] < $alertAt - 5) $a80 = true;
        if ($a95 && $b['p'] >= 95) { $ev[] = ['t' => $b['t'], 'kind' => 'full', 'title' => 'Bin is full', 'detail' => 'Collect as soon as possible']; $a95 = false; }
        elseif (!$a95 && $b['p'] < 90) $a95 = true;
    }
    $out['events'] = array_slice(array_reverse($ev), 0, 5);
    return $out;
}

/* ---------- build the full view model with defaults ---------- */
function ib_vm(array $in = []): array {
    $d = [
        'bin'     => ['id' => 1, 'name' => 'InteliBin #1', 'location' => '', 'height_cm' => 30, 'dead_zone_cm' => 3, 'alert_at' => 80],
        'bins'    => [],
        'fill'    => null,
        'total_readings' => 0, 'last_reading_ts' => null,
        'lid'     => null, 'visits_today' => 0, 'visits_all' => 0,
        'device'  => ['sensor_online' => null, 'controller_online' => null, 'sensor_label' => 'HC-SR04', 'controller_label' => 'Arduino'],
        'history' => [], 'pipeline' => [], 'nav' => null,
        'logout_href' => '#', 'alerts_href' => '#', 'history_href' => '#', 'home_href' => '#',
        'asset_base' => 'assets', 'now' => time(),
        'footer_left' => 'InteliBin (c) ' . date('Y') . ', Smart Waste Monitoring System',
        'footer_right' => 'University of Zambia, Final Year Project',
    ];
    $vm = array_replace_recursive($d, $in);
    foreach (['history', 'bins', 'pipeline', 'events', 'nav'] as $k) if (array_key_exists($k, $in)) $vm[$k] = $in[$k];
    if ($vm['nav'] === null) $vm['nav'] = ib_default_nav();
    $vm['history'] = ib_history($vm['history']);
    $der = ib_derive($vm['history'], (int)$vm['bin']['alert_at']);
    foreach (['fill_rate', 'hours_to_full', 'last_emptied_ts'] as $k) if (!array_key_exists($k, $in)) $vm[$k] = $der[$k];
    if (array_key_exists('fill_rate', $in) && !array_key_exists('hours_to_full', $in)) {
        $vm['hours_to_full'] = ($vm['fill_rate'] !== null && $vm['fill_rate'] >= 0.05 && $vm['fill'] !== null) ? (100 - $vm['fill']) / $vm['fill_rate'] : null;
    }
    if (!array_key_exists('events', $in)) $vm['events'] = $der['events'];
    if ($vm['last_reading_ts'] === null && $vm['history']) $vm['last_reading_ts'] = end($vm['history'])['t'];
    return $vm;
}

/* ---------- JSON handed to dashboard-ui.js (camelCase, milliseconds) ---------- */
function ib_state(array $vm): array {
    $ms = fn($t) => $t === null ? null : (int)$t * 1000;
    $b = $vm['bin']; $dv = $vm['device'];
    return [
        'bin' => ['id' => $b['id'], 'name' => $b['name'], 'location' => $b['location'],
                  'heightCm' => (float)$b['height_cm'], 'deadZoneCm' => (float)$b['dead_zone_cm'], 'alertAt' => (int)$b['alert_at']],
        'fill'          => $vm['fill'] === null ? null : (float)$vm['fill'],
        'totalReadings' => (int)$vm['total_readings'],
        'lastReadingAt' => $ms($vm['last_reading_ts']),
        'lid'           => $vm['lid'],
        'visitsToday'   => (int)$vm['visits_today'],
        'visitsAll'     => (int)$vm['visits_all'],
        'device'        => ['sensorOnline' => $dv['sensor_online'], 'controllerOnline' => $dv['controller_online'],
                            'sensorLabel' => $dv['sensor_label'], 'controllerLabel' => $dv['controller_label']],
        'history'       => array_map(fn($x) => ['t' => $x['t'] * 1000, 'p' => (float)$x['p'], 'empty' => (bool)$x['empty']], $vm['history']),
        'fillRate'      => $vm['fill_rate'],
        'hoursToFull'   => $vm['hours_to_full'],
        'lastEmptiedAt' => $ms($vm['last_emptied_ts']),
        'events'        => array_map(fn($e) => ['t' => $e['t'] * 1000, 'kind' => $e['kind'], 'title' => $e['title'], 'detail' => $e['detail']], $vm['events']),
    ];
}
function ib_state_json(array $vm): string {
    return json_encode(ib_state($vm), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
}

}
