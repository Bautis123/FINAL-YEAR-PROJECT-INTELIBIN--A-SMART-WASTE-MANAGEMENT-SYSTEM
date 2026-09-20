/* ============================================================================
   InteliBin dashboard UI  |  assets/js/dashboard-ui.js
   No dependencies. Draws the bin, chart, metrics, tables and lid controls.
   It never talks to your backend on its own: you feed it data, it draws it.

   Usage (see components/dashboard/page.php):
     IntelibinUI.init({
       pollUrl: 'api/dashboard_state.php?bin=1',   // optional: JSON in the shape below
       pollMs: 5000,
       onCommand: async (cmd, binId) => ({ message: 'Command close queued for bin 1.' }),
       onRangeChange: async (range) => historyArrayOrUndefined   // optional
     });
     IntelibinUI.update({ fill: 63.2, lastReadingAt: Date.now() });   // partial updates are fine

   State shape (all timestamps are milliseconds since epoch):
   {
     bin:      { id, name, location, heightCm, deadZoneCm, alertAt },
     fill:     57.4 | null,             // latest fill %, null = no readings yet
     totalReadings, lastReadingAt,
     lid:      'open' | 'closed' | null,
     visitsToday, visitsAll,
     device:   { sensorOnline, controllerOnline, sensorLabel, controllerLabel },
     history:  [{ t, p, empty? }],      // ascending. "empty" is inferred from a drop of 25+ points
     events:   [{ t, kind, title, detail }],   // omit to derive from history
     fillRate, hoursToFull, lastEmptiedAt      // omit to derive from history
   }
   ============================================================================ */
(function (global) {
  'use strict';

  const MIN = 60000, HOUR = 3600000;
  const RANGES = { '6h': 6 * HOUR, '24h': 24 * HOUR, '7d': 168 * HOUR };
  const RANGE_LABEL = { '6h': 'Last 6 hours', '24h': 'Last 24 hours', '7d': 'Last 7 days' };
  const CX = 180, SY0 = 124, FLOOR = 436, PER = 3;      // bin drawing geometry (viewBox units)
  const EMPTY_DROP = 25;                                 // fill drop (points) that counts as "emptied"
  const CMD_LABEL = { open: 'open', close: 'close', force_open: 'force open', reset: 'reset' };
  const RISKY = { force_open: true, reset: true };       // need a second tap to confirm
  const EVENT_KINDS = {
    emptied:     { key: 'ready',  icon: 'bin' },
    almost:      { key: 'almost', icon: 'bell' },
    full:        { key: 'full',   icon: 'bell' },
    lid_open:    { key: 'ready',  icon: 'unlock' },
    lid_closed:  { key: 'idle',   icon: 'lock' },
    info:        { key: 'idle',   icon: 'info' }
  };

  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /* ---------- formatting (mirrored by components/dashboard/_helpers.php) ---------- */
  const tf = (t, secs) => new Date(t).toLocaleTimeString([], secs
    ? { hour: '2-digit', minute: '2-digit', second: '2-digit' }
    : { hour: '2-digit', minute: '2-digit' });
  const when = t => {
    const d = new Date(t);
    const same = d.toDateString() === new Date().toDateString();
    return (same ? 'Today' : d.toLocaleDateString([], { weekday: 'short' })) + ' ' + tf(t);
  };
  const rel = ms => {
    const s = Math.max(0, Math.floor(ms / 1000));
    if (s < 60) return s + ' s ago';
    const m = Math.floor(s / 60);
    if (m < 60) return m + ' min ago';
    const h = Math.floor(m / 60);
    if (h < 24) return h + ' h ' + (m % 60) + ' min ago';
    return Math.floor(h / 24) + ' d ' + (h % 24) + ' h ago';
  };
  const dur = h => {
    if (!isFinite(h) || h >= 48) return '> 2 days';
    const total = Math.max(1, Math.round(h * 60));
    if (total < 60) return total + ' min';
    if (total < 1440) return Math.floor(total / 60) + ' h ' + (total % 60) + ' min';
    return Math.floor(total / 1440) + ' d ' + Math.floor((total % 1440) / 60) + ' h';
  };
  const span = ms => {
    const m = Math.max(1, Math.floor(ms / MIN));
    if (m < 60) return m + ' min';
    const h = Math.floor(m / 60);
    if (h < 24) return h + ' h ' + (m % 60) + ' min';
    return Math.floor(h / 24) + ' d ' + (h % 24) + ' h';
  };

  /* ---------- history helpers ---------- */
  function normalizeHistory(list) {
    const h = (list || []).map(x => ({ t: +x.t, p: +x.p, empty: x.empty }))
      .filter(x => isFinite(x.t) && isFinite(x.p))
      .sort((a, b) => a.t - b.t);
    h.forEach((x, i) => {
      if (x.empty === undefined) x.empty = i > 0 && (h[i - 1].p - x.p) >= EMPTY_DROP;
    });
    return h;
  }

  /* Works out fill rate, time to full, last emptied and events from raw readings.
     Use it if you do not want to compute these in PHP. */
  function derive(h, alertAt) {
    const out = { events: [] };
    if (!h.length) return out;
    const last = h[h.length - 1];
    let i = h.length - 1;
    while (i > 0 && !h[i].empty) i--;
    const start = h[i];
    const hrs = (last.t - start.t) / HOUR;
    out.fillRate = hrs >= 1 ? (last.p - start.p) / hrs : null;
    out.hoursToFull = (out.fillRate != null && out.fillRate >= 0.05) ? (100 - last.p) / out.fillRate : Infinity;
    out.lastEmptiedAt = null;
    for (let k = h.length - 1; k >= 0; k--) if (h[k].empty) { out.lastEmptiedAt = h[k].t; break; }

    let armed80 = h[0].p < alertAt, armed95 = h[0].p < 95;
    for (let k = 1; k < h.length; k++) {
      const a = h[k - 1], b = h[k];
      if (b.empty) {
        out.events.push({ t: b.t, kind: 'emptied', title: 'Bin emptied', detail: 'Fill dropped from ' + Math.round(a.p) + '% to ' + Math.round(b.p) + '%' });
        armed80 = armed95 = true;
        continue;
      }
      if (armed80 && b.p >= alertAt) { out.events.push({ t: b.t, kind: 'almost', title: 'Passed ' + alertAt + '% full', detail: 'Collection needed soon' }); armed80 = false; }
      else if (!armed80 && b.p < alertAt - 5) armed80 = true;
      if (armed95 && b.p >= 95) { out.events.push({ t: b.t, kind: 'full', title: 'Bin is full', detail: 'Collect as soon as possible' }); armed95 = false; }
      else if (!armed95 && b.p < 90) armed95 = true;
    }
    out.events = out.events.reverse().slice(0, 5);
    return out;
  }

  /* ======================================================================== */
  function create(opts) {
    opts = opts || {};
    const root = opts.root || document.querySelector('[data-ib-root]');
    if (!root) throw new Error('IntelibinUI: no [data-ib-root] element found');
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

    const $ = n => root.querySelector('[data-ib="' + n + '"]');
    const $$ = n => Array.from(root.querySelectorAll('[data-ib="' + n + '"]'));
    const setText = (n, v) => { const e = $(n); if (e && e.textContent !== v) e.textContent = v; };
    const icon = n => '<svg class="ib-ic"><use href="#ib-i-' + n + '"/></svg>';

    let S = {
      bin: { id: 1, name: 'InteliBin', location: '', heightCm: 30, deadZoneCm: 3, alertAt: 80 },
      fill: null, totalReadings: 0, lastReadingAt: null, lid: null, visitsToday: 0, visitsAll: 0,
      device: null, history: [], events: undefined,
      range: '24h', disp: 0, target: 0
    };

    const dist = p => S.bin.deadZoneCm + (100 - p) / 100 * (S.bin.heightCm - S.bin.deadZoneCm);
    const IDLE = { key: 'idle', label: 'Waiting for data' };
    function statusOf(p) {
      if (p >= 95) return { key: 'full', label: 'Full' };
      if (p >= S.bin.alertAt) return { key: 'almost', label: 'Almost full' };
      if (p >= 60) return { key: 'filling', label: 'Filling up' };
      return { key: 'ready', label: 'Ready' };
    }
    const isLive = () => S.fill != null;

    /* ---------- bin drawing ---------- */
    const el = {
      hero: $('hero'), bigBox: $('bigBox'), pill: $('pillText'), big: $('fillBig'), pct: $('fillPct'),
      waste: $('waste'), cone: $('cone'), echo: $('echo'), dim: $('dim'), dimLine: $('dimLine'),
      capB: $('capB'), chip: $('chip'), dimText: $('dimText'),
      pings: [$('ping0'), $('ping1'), $('ping2')]
    };
    let curKey = '', lastBig = '', lastDim = '';

    // Tells you in the console if your markup lost one of the hooks this script needs.
    const REQUIRED = ['hero', 'bigBox', 'fillBig', 'fillPct', 'pillText', 'binSvg', 'waste', 'cone', 'echo', 'dim', 'dimLine',
      'capB', 'chip', 'dimText', 'ping0', 'ping1', 'ping2', 'chartWrap', 'chartSvg', 'tip', 'curLine', 'curDot', 'chartEmpty',
      'rows', 'alertList', 'mFull', 'mRate', 'mCount', 'mEmpty', 'lidPill', 'lidText', 'cmdMsg', 'vToday', 'vAll'];
    const missing = REQUIRED.filter(n => !$(n));
    if (missing.length) console.warn('IntelibinUI: missing data-ib hooks: ' + missing.join(', '));

    function drawBin(now) {
      if (!el.hero || !el.waste || !el.cone || !el.dim || !el.chip || !el.bigBox) return;
      const empty = !isLive();
      const p = S.disp, sy = FLOOR - PER * p;

      el.waste.setAttribute('transform', 'translate(0 ' + sy.toFixed(1) + ')');
      el.waste.style.opacity = p < 0.5 ? 0 : 1;

      const w = Math.min(96, (sy - SY0) * 0.36);
      el.cone.setAttribute('points', CX + ',' + SY0 + ' ' + (CX - w).toFixed(1) + ',' + sy.toFixed(1) + ' ' + (CX + w).toFixed(1) + ',' + sy.toFixed(1));

      el.dim.style.display = empty ? 'none' : '';
      if (!empty) {
        const y2 = sy - 2;
        el.dimLine.setAttribute('y2', y2.toFixed(1));
        el.capB.setAttribute('y1', y2.toFixed(1));
        el.capB.setAttribute('y2', y2.toFixed(1));
        el.echo.setAttribute('cy', y2.toFixed(1));
        el.chip.setAttribute('transform', 'translate(180 ' + ((132 + y2) / 2).toFixed(1) + ')');
        const dd = Math.round(dist(p)) + ' cm';
        if (dd !== lastDim) { el.dimText.textContent = dd; lastDim = dd; }
      }

      if (!reduce) {
        const R = Math.max(0, sy - SY0);
        for (let k = 0; k < 3; k++) {
          const ph = ((now / 2800) + k / 3) % 1, r = ph * R;
          const dx = r * 0.339, dy = r * 0.941;
          el.pings[k].setAttribute('d', 'M' + (CX - dx).toFixed(1) + ' ' + (SY0 + dy).toFixed(1) +
            'A' + r.toFixed(1) + ' ' + r.toFixed(1) + ' 0 0 0 ' + (CX + dx).toFixed(1) + ' ' + (SY0 + dy).toFixed(1));
          el.pings[k].style.opacity = ((1 - ph) * 0.85).toFixed(2);
        }
      }

      const st = empty ? IDLE : statusOf(p);
      if (st.key !== curKey) { curKey = st.key; el.hero.dataset.ibS = st.key; el.pill.textContent = st.label; }
      el.bigBox.classList.toggle('ib-is-empty', empty);
      const big = empty ? '–' : String(Math.round(p));
      if (big !== lastBig) { el.big.textContent = big; lastBig = big; }
    }

    let prev = performance.now();
    function frame(now) {
      const dt = Math.min(80, now - prev); prev = now;
      const target = S.target;
      if (reduce) S.disp = target;
      else {
        S.disp += (target - S.disp) * (1 - Math.exp(-dt / 240));
        if (Math.abs(target - S.disp) < 0.03) S.disp = target;
      }
      drawBin(now);
      requestAnimationFrame(frame);
    }

    /* ---------- metrics, device, table, events ---------- */
    function derived() { return derive(S.history, S.bin.alertAt); }

    function renderMetrics() {
      const now = Date.now(), live = isLive();
      setText('mCount', (+S.totalReadings || 0).toLocaleString());
      setText('vToday', (+S.visitsToday || 0).toLocaleString());
      setText('vAll', (+S.visitsAll || 0).toLocaleString());

      const dev = S.device || {};
      const sLabel = dev.sensorLabel || 'HC-SR04', cLabel = dev.controllerLabel || 'Arduino';

      if (!live) {
        setText('mFull', '–'); setText('mFullSub', 'Needs a few readings');
        setText('mRate', '–'); setText('mRateSub', 'Needs a few readings');
        setText('mCountSub', 'No readings yet');
        setText('mEmpty', '–'); setText('mEmptySub', 'Nothing recorded');
        setText('dSensor', 'No data yet'); setText('dCtrl', 'No data yet');
        setDot('dSensorDot', 'idle'); setDot('dCtrlDot', 'idle');
        setText('dSync', 'Never');
        return;
      }
      const d = (('fillRate' in S) && ('lastEmptiedAt' in S)) ? null : derived();
      const rate = ('fillRate' in S) ? S.fillRate : d.fillRate;
      const lastEmptiedAt = ('lastEmptiedAt' in S) ? S.lastEmptiedAt : d.lastEmptiedAt;

      if (rate == null) { setText('mRate', '–'); setText('mRateSub', 'Just emptied'); setText('mFull', '–'); setText('mFullSub', 'Needs more readings'); }
      else if (rate < 0.05) { setText('mRate', 'Steady'); setText('mRateSub', 'since last emptied'); setText('mFull', '> 2 days'); setText('mFullSub', 'at the average rate'); }
      else {
        const hrs = (S.hoursToFull != null) ? S.hoursToFull : (100 - S.fill) / rate;
        setText('mRate', '+' + rate.toFixed(1) + '%/h'); setText('mRateSub', 'since last emptied');
        setText('mFull', dur(hrs)); setText('mFullSub', 'at the average rate');
      }

      const lastAt = S.lastReadingAt != null ? S.lastReadingAt : (S.history.length ? S.history[S.history.length - 1].t : null);
      setText('mCountSub', lastAt != null ? 'Last one ' + rel(now - lastAt) : 'No readings yet');

      if (lastEmptiedAt != null) { setText('mEmpty', span(now - lastEmptiedAt)); setText('mEmptySub', 'ago, ' + when(lastEmptiedAt)); }
      else { setText('mEmpty', '–'); setText('mEmptySub', 'None in the last 7 days'); }

      const sOn = dev.sensorOnline !== false, cOn = dev.controllerOnline !== false;
      setText('dSensor', sLabel + (sOn ? ' online' : ' offline')); setDot('dSensorDot', sOn ? 'ready' : 'full');
      setText('dCtrl', cLabel + (cOn ? ' connected' : ' offline')); setDot('dCtrlDot', cOn ? 'ready' : 'full');
      setText('dSync', lastAt != null ? rel(now - lastAt) : 'Never');
    }
    function setDot(n, key) { const e = $(n); if (e) e.dataset.ibS = key; }

    function renderTable() {
      const tb = $('rows'); if (!tb) return;
      if (!isLive() || !S.history.length) {
        tb.innerHTML = '<tr><td colspan="4" class="ib-none">No readings yet. Check that the Arduino is powered and connected. New readings will list here.</td></tr>';
        return;
      }
      tb.innerHTML = S.history.slice(-8).reverse().map(x => {
        const st = statusOf(x.p);
        return '<tr data-ib-s="' + st.key + '"><td>' + tf(x.t, true) + '</td><td>' + Math.round(dist(x.p)) + ' cm</td>' +
          '<td><span class="ib-bar"><span style="width:' + x.p.toFixed(0) + '%"></span></span>' + x.p.toFixed(0) + '%</td>' +
          '<td><span class="ib-pill ib-sm"><i></i>' + st.label + '</span></td></tr>';
      }).join('');
    }

    function renderEvents() {
      const ul = $('alertList'); if (!ul) return;
      const list = S.events !== undefined ? S.events : derived().events;
      if (!isLive() || !list || !list.length) {
        ul.innerHTML = '<li class="ib-none">No alerts yet. You will see a message here when the bin passes ' + S.bin.alertAt + '% full.</li>';
        return;
      }
      ul.innerHTML = list.map(a => {
        const k = EVENT_KINDS[a.kind] || EVENT_KINDS.info;
        return '<li data-ib-s="' + k.key + '"><span class="ib-ico">' + icon(k.icon) + '</span>' +
          '<div><b>' + esc(a.title) + '</b><small>' + esc(a.detail) + '</small></div><time>' + when(a.t) + '</time></li>';
      }).join('');
    }

    /* ---------- chart ---------- */
    const chartWrap = $('chartWrap'), chartSvg = $('chartSvg'), tip = $('tip');
    const curLine = $('curLine'), curDot = $('curDot'), chartEmpty = $('chartEmpty');
    let R = null;

    function renderChart() {
      if (!chartWrap) return;
      const W = chartWrap.clientWidth, H = chartWrap.clientHeight;
      if (!W || !H) return;
      chartSvg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
      const m = { l: 46, r: 12, t: 10, b: 30 }, iw = W - m.l - m.r, ih = H - m.t - m.b;
      const now = Date.now(), dur_ = RANGES[S.range], t0 = now - dur_;
      const X = t => m.l + (t - t0) / dur_ * iw;
      const Y = p => m.t + (1 - p / 100) * ih;
      R = { W, H, m, iw, ih, t0, dur: dur_, X, Y };
      const A = S.bin.alertAt;

      let g = '<defs><linearGradient id="ib-ag" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(--ib-chart-area);stop-opacity:.32"/><stop offset="1" style="stop-color:var(--ib-chart-area);stop-opacity:0"/></linearGradient></defs>';
      [0, 25, 50, 75, 100].forEach(v => {
        g += '<line class="' + (v === 0 ? 'ib-base' : 'ib-grid') + '" x1="' + m.l + '" x2="' + (W - m.r) + '" y1="' + Y(v) + '" y2="' + Y(v) + '"/>' +
             '<text class="ib-ax" x="' + (m.l - 10) + '" y="' + (Y(v) + 4) + '" text-anchor="end">' + v + '%</text>';
      });

      const addTick = (t, label) => {
        const x = X(t).toFixed(1);
        g += '<line class="ib-base" x1="' + x + '" x2="' + x + '" y1="' + (m.t + ih) + '" y2="' + (m.t + ih + 5) + '"/>' +
             '<text class="ib-ax" x="' + x + '" y="' + (H - 8) + '" text-anchor="middle">' + label + '</text>';
      };
      const narrow = W < 520;
      if (S.range === '7d') {
        const d = new Date(t0); d.setHours(24, 0, 0, 0);
        while (d.getTime() <= now) { addTick(d.getTime(), d.toLocaleDateString([], { weekday: 'short' })); d.setDate(d.getDate() + 1); }
      } else {
        const stepH = S.range === '6h' ? (narrow ? 2 : 1) : (narrow ? 8 : 4);
        const d = new Date(t0); d.setMinutes(0, 0, 0);
        if (d.getTime() < t0) d.setHours(d.getHours() + 1);
        while (d.getHours() % stepH !== 0) d.setHours(d.getHours() + 1);
        while (d.getTime() <= now) {
          addTick(d.getTime(), d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }));
          d.setHours(d.getHours() + stepH);
        }
      }

      g += '<line class="ib-thr" x1="' + m.l + '" x2="' + (W - m.r) + '" y1="' + Y(A) + '" y2="' + Y(A) + '"/>' +
           '<text class="ib-thr-label" x="' + (W - m.r - 4) + '" y="' + (Y(A) - 6) + '" text-anchor="end">Alert at ' + A + '%</text>';

      const pts = isLive() ? S.history.filter(x => x.t >= t0) : [];
      if (pts.length > 1) {
        let d = '';
        pts.forEach((q, i) => { d += (i ? 'L' : 'M') + X(q.t).toFixed(1) + ' ' + Y(q.p).toFixed(1); });
        const lastPt = pts[pts.length - 1];
        g += '<path d="' + d + 'L' + X(lastPt.t).toFixed(1) + ' ' + Y(0) + 'L' + X(pts[0].t).toFixed(1) + ' ' + Y(0) + 'Z" fill="url(#ib-ag)"/>';
        g += '<path class="ib-line" d="' + d + '"/>';
        pts.forEach(q => {
          if (q.empty) {
            const x = X(q.t).toFixed(1);
            g += '<line class="ib-emp" x1="' + x + '" x2="' + x + '" y1="' + m.t + '" y2="' + (m.t + ih) + '"/>' +
                 '<text class="ib-emp-t" x="' + (+x + 6) + '" y="' + (m.t + 12) + '">Emptied</text>';
          }
        });
        g += '<g data-ib-s="' + statusOf(lastPt.p).key + '"><circle class="ib-halo" cx="' + X(lastPt.t).toFixed(1) + '" cy="' + Y(lastPt.p).toFixed(1) + '" r="10"/>' +
             '<circle class="ib-now" cx="' + X(lastPt.t).toFixed(1) + '" cy="' + Y(lastPt.p).toFixed(1) + '" r="5"/></g>';
      }
      chartSvg.innerHTML = g;
      chartEmpty.style.display = isLive() ? 'none' : 'grid';
      hideCursor();
    }

    function hideCursor() { if (!tip) return; tip.style.display = 'none'; curLine.style.display = 'none'; curDot.style.display = 'none'; }
    function onMove(e) {
      if (!R || !isLive() || !S.history.length) return;
      const box = chartWrap.getBoundingClientRect();
      const px = e.clientX - box.left;
      if (px < R.m.l || px > R.W - R.m.r) { hideCursor(); return; }
      const t = R.t0 + (px - R.m.l) / R.iw * R.dur, h = S.history;
      let lo = 0, hi = h.length - 1;
      while (hi - lo > 1) { const mid = (lo + hi) >> 1; if (h[mid].t < t) lo = mid; else hi = mid; }
      const pt = (t - h[lo].t < h[hi].t - t) ? h[lo] : h[hi];
      const x = R.X(pt.t), y = R.Y(pt.p);
      if (x < R.m.l || x > R.W - R.m.r) { hideCursor(); return; }
      curLine.style.cssText = 'display:block;left:' + x + 'px;top:' + R.m.t + 'px;height:' + R.ih + 'px';
      curDot.dataset.ibS = statusOf(pt.p).key;
      curDot.style.cssText = 'display:block;left:' + x + 'px;top:' + y + 'px';
      tip.innerHTML = '<b>' + pt.p.toFixed(0) + '% full</b><span>' + Math.round(dist(pt.p)) + ' cm from the sensor</span><span>' + when(pt.t) + '</span>' + (pt.empty ? '<span>Bin emptied here</span>' : '');
      tip.style.display = 'block';
      let tx = x + 14;
      if (tx + tip.offsetWidth > R.W - 4) tx = x - 14 - tip.offsetWidth;
      const ty = Math.min(R.H - tip.offsetHeight - 4, Math.max(4, y - tip.offsetHeight / 2));
      tip.style.left = tx + 'px'; tip.style.top = ty + 'px';
    }
    if (chartWrap) {
      chartWrap.addEventListener('pointermove', onMove);
      chartWrap.addEventListener('pointerdown', onMove);
      chartWrap.addEventListener('pointerleave', hideCursor);
      if (window.ResizeObserver) new ResizeObserver(renderChart).observe(chartWrap);
      else addEventListener('resize', renderChart);
    }

    $$('range').forEach(b => b.addEventListener('click', async () => {
      S.range = b.dataset.range;
      $$('range').forEach(x => x.setAttribute('aria-pressed', String(x === b)));
      setText('rangeLabel', RANGE_LABEL[S.range]);
      renderChart();
      if (typeof opts.onRangeChange === 'function') {
        try {
          const h = await opts.onRangeChange(S.range);
          if (Array.isArray(h)) update({ history: h });
        } catch (e) { /* keep what is on screen */ }
      }
    }));

    /* ---------- lid controls ---------- */
    function renderLid() {
      const pill = $('lidPill'); if (!pill) return;
      if (S.lid === 'open') { pill.dataset.ibS = 'ready'; setText('lidText', 'Lid open'); }
      else if (S.lid === 'closed') { pill.dataset.ibS = 'idle'; setText('lidText', 'Lid closed'); }
      else { pill.dataset.ibS = 'idle'; setText('lidText', 'Lid state unknown'); }
    }
    const cmdMsg = $('cmdMsg');
    let lastMsg = { text: cmdMsg ? cmdMsg.textContent : '', kind: '', time: '' };
    let busy = false, confirming = null, confirmTimer = null;

    function showMsg(text, kind, time, remember) {
      if (!cmdMsg) return;
      cmdMsg.className = 'ib-cmd-msg' + (kind ? ' ib-' + kind : '');
      cmdMsg.innerHTML = (kind === 'ok' ? icon('check') : '') + '<span>' + esc(text) + '</span>' + (time ? '<time>' + time + '</time>' : '');
      if (remember) lastMsg = { text, kind, time };
    }
    function clearConfirm() {
      clearTimeout(confirmTimer);
      if (!confirming) return;
      confirming.classList.remove('ib-confirm');
      confirming.querySelector('.ib-lbl').textContent = confirming.dataset.label;
      confirming = null;
      showMsg(lastMsg.text, lastMsg.kind, lastMsg.time, false);
    }
    async function sendCmd(cmd) {
      busy = true;
      $$('cmd').forEach(x => { x.disabled = true; });
      showMsg('Sending command…', '', '', false);
      try {
        if (typeof opts.onCommand !== 'function') throw new Error('Command handler not connected');
        const res = (await opts.onCommand(cmd, S.bin.id)) || {};
        const text = res.message || ('Command ' + CMD_LABEL[cmd] + ' queued for bin ' + S.bin.id + '.');
        showMsg(text, 'ok', tf(Date.now(), true), true);
        if (res.lid) update({ lid: res.lid });
      } catch (err) {
        showMsg((err && err.message) || 'Command failed.', 'err', tf(Date.now(), true), true);
      }
      busy = false;
      $$('cmd').forEach(x => { x.disabled = false; });
    }
    $$('cmd').forEach(b => {
      b.dataset.label = b.querySelector('.ib-lbl').textContent;
      b.addEventListener('click', () => {
        if (busy) return;
        const cmd = b.dataset.cmd;
        if (RISKY[cmd] && confirming !== b) {
          clearConfirm();
          confirming = b;
          b.classList.add('ib-confirm');
          b.querySelector('.ib-lbl').textContent = 'Tap to confirm';
          showMsg('Tap ' + b.dataset.label.toLowerCase() + ' again within a few seconds to send it.', '', '', false);
          confirmTimer = setTimeout(clearConfirm, 3500);
          return;
        }
        clearConfirm();
        sendCmd(cmd);
      });
    });

    /* ---------- top level render ---------- */
    function render() {
      const live = isLive();
      S.target = live ? +S.fill : 0;
      const A = S.bin.alertAt;

      // static bits that depend on the bin config
      setText('binName', S.bin.name || 'InteliBin');
      setText('binLocation', S.bin.location || '');
      setText('ctlBinName', 'Send a command to ' + (S.bin.name || 'InteliBin'));
      setText('binHeight', S.bin.heightCm + ' cm');
      setText('alertAtText', A + '% full');
      const ty = FLOOR - PER * A;
      const tl = $('thrLine'), tt = $('thrText');
      if (tl) { tl.setAttribute('y1', ty); tl.setAttribute('y2', ty); }
      if (tt) { tt.setAttribute('y', ty + 4); tt.textContent = 'Alert ' + A + '%'; }

      const conn = $('conn');
      if (conn) conn.dataset.ibS = live ? 'ready' : 'idle';
      setText('connText', live ? 'Live' : 'Waiting for data');

      const svg = $('binSvg');
      if (live) {
        if (el.pct) el.pct.style.display = '';
        setText('fillNote', 'of capacity used');
        if (svg) svg.setAttribute('aria-label', 'Bin is ' + Math.round(S.fill) + '% full. The sensor reads ' + Math.round(dist(S.fill)) + ' centimetres to the waste.');
        if (chartWrap) chartWrap.setAttribute('aria-label', 'Fill level over time. Currently ' + Math.round(S.fill) + '% full.');
      } else {
        if (el.pct) el.pct.style.display = 'none';
        setText('fillNote', 'Waiting for the first reading from the sensor.');
        if (svg) svg.setAttribute('aria-label', 'Bin is empty. No readings yet.');
        if (chartWrap) chartWrap.setAttribute('aria-label', 'Fill level chart. No readings yet.');
      }

      const flagged = live && S.fill >= A;
      $$('navBadge').forEach(b => { b.hidden = !flagged; });
      const banner = $('alertBanner');
      if (banner) {
        banner.hidden = !flagged;
        if (flagged) {
          banner.dataset.ibS = statusOf(S.fill).key;
          setText('bannerTitle', (S.bin.name || 'This bin') + ' is ' + Math.round(S.fill) + '% full');
          setText('bannerDetail', S.fill >= 95 ? 'Collect as soon as possible.' : 'Collection needed soon.');
        }
      }

      renderMetrics(); renderTable(); renderEvents(); renderChart(); renderLid();
    }

    function update(partial) {
      partial = partial || {};
      const next = Object.assign({}, S, partial);
      if (partial.bin) next.bin = Object.assign({}, S.bin, partial.bin);
      if (partial.history) next.history = normalizeHistory(partial.history);
      S = next;
      render();
    }

    /* ---------- timers ---------- */
    function tickClock() {
      setText('clock', new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
      renderMetrics();
    }
    setText('today', new Date().toLocaleDateString([], { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
    setInterval(tickClock, 1000);

    if (opts.pollUrl) {
      const poll = async () => {
        if (document.hidden) return;
        try {
          const r = await fetch(opts.pollUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
          if (r.ok) update(await r.json());
        } catch (e) { /* try again next tick */ }
      };
      setInterval(poll, opts.pollMs || 5000);
    }

    /* ---------- boot ---------- */
    const seed = root.querySelector('script[data-ib-state]');
    if (seed) { try { update(JSON.parse(seed.textContent)); } catch (e) { console.error('IntelibinUI: bad state JSON', e); } }
    else render();
    tickClock();
    if (opts.animateIn !== false && !reduce) S.disp = 0;   // bin fills up from empty on load
    requestAnimationFrame(frame);

    return { update, getState: () => S, derive };
  }

  global.IntelibinUI = {
    init: function (opts) { this.instance = create(opts); return this.instance; },
    update: function (s) { return this.instance && this.instance.update(s); },
    derive: derive
  };
})(window);
