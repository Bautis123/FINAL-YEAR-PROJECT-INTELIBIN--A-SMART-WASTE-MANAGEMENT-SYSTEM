const cfg = window.dashboardConfig;
const POLL_INTERVAL = 30 * 1000;
let prevPct = null;
let prevTime = null;
let lastAppliedReadingId = Number(cfg.latestReadingId || 0);
let lastAppliedRecordedAt = cfg.latestRecordedAt || null;

const ctx = document.getElementById('chartCanvas').getContext('2d');
const grad = ctx.createLinearGradient(0, 0, 0, 200);
grad.addColorStop(0, 'rgba(37,99,235,0.18)');
grad.addColorStop(1, 'rgba(37,99,235,0.01)');

const chart = new Chart(ctx, {
  type: 'line',
  data: { labels: cfg.labels, datasets: [{ data: cfg.data, borderColor: '#2563eb', borderWidth: 2, backgroundColor: grad, fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: '#2563eb', pointBorderColor: '#fff', pointBorderWidth: 2 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } } }
});

function statusMeta(status) {
  const labels = { ready:'Ready', filling:'Filling', almost_full:'Almost Full', full:'Full' };
  const colours = { ready:'var(--green)', filling:'var(--blue)', almost_full:'var(--amber)', full:'var(--red)' };
  const badges = { ready:'b-green', filling:'b-blue', almost_full:'b-amber', full:'b-red' };
  const chips = { ready:'c-ok', filling:'c-info', almost_full:'c-warn', full:'c-crit' };
  return { status, label: labels[status] ?? 'Ready', colour: colours[status] ?? colours.ready, badge: badges[status] ?? 'b-green', chip: chips[status] ?? 'c-ok' };
}

function lidMeta(lidStatus) {
  const status = ['open', 'closed', 'locked'].includes(lidStatus) ? lidStatus : 'closed';
  const labels = { open:'Open', closed:'Closed', locked:'Locked' };
  return { status, label: labels[status] };
}

function updateETA(pct) {
  const eta = document.getElementById('sv-eta'), sub = document.getElementById('sv-eta-sub'), now = Date.now();
  eta.style.color = '';
  if (pct >= 100) { eta.textContent = 'Full'; eta.style.color = 'var(--red)'; sub.textContent = 'Bin needs emptying'; prevPct = pct; prevTime = now; return; }
  if (prevPct === null) { eta.textContent = '-'; sub.textContent = 'waiting for next reading'; prevPct = pct; prevTime = now; return; }
  const rate = (pct - prevPct) / ((now - prevTime) / 60000);
  if (rate > 0) { const mins = (100 - pct) / rate; eta.textContent = mins < 60 ? Math.round(mins) + ' min' : (mins / 60).toFixed(1) + ' hr'; sub.textContent = '+' + rate.toFixed(1) + '% per min'; }
  else { eta.textContent = '-'; sub.textContent = rate < 0 ? 'level decreasing' : 'no change detected'; }
  prevPct = pct; prevTime = now;
}

function applyReading(d) {
  const pct = Number.isFinite(Number(d.fill_percent)) ? Math.round(Number(d.fill_percent)) : 0;
  const recordedAt = d.recorded_at || null;
  const meta = statusMeta(d.status);
  const lid = lidMeta(d.lid_status);
  const readingId = Number(d.reading_id || 0);
  const isNewReading = readingId
    ? readingId !== lastAppliedReadingId
    : recordedAt !== lastAppliedRecordedAt;

  document.getElementById('sv-fill').innerHTML = `${pct}<span class="stat-unit">%</span>`;
  document.getElementById('sv-fill').style.color = meta.colour;
  document.getElementById('sv-status').textContent = meta.label;
  document.getElementById('sv-status').style.color = meta.colour;
  document.getElementById('sv-last').textContent = recordedAt ? 'Last: ' + new Date(recordedAt).toLocaleTimeString() : 'Last: no reading yet';
  document.getElementById('binFill').style.height = pct + '%';
  document.getElementById('binPct').textContent = pct + '%';
  document.getElementById('statusBadge').className = 'status-badge ' + meta.badge;
  document.getElementById('statusText').textContent = meta.label;
  document.getElementById('lidStateMeta').className = 'meta-val lid-state ' + lid.status;
  document.getElementById('lidStateMeta').textContent = lid.label;
  if (d.height_cm) document.getElementById('binHeightMeta').textContent = Math.round(d.height_cm) + ' cm';
  if (pct >= 80) {
    document.getElementById('alertPct').textContent = pct + '%';
    document.getElementById('fullAlert').style.display = '';
  } else {
    document.getElementById('fullAlert').style.display = 'none';
  }

  if (!recordedAt || !isNewReading) return;

  lastAppliedReadingId = readingId;
  lastAppliedRecordedAt = recordedAt;
  appendChartPoint(d, pct);
  prependLogRow(pct, meta);
  updateETA(pct);
}

function appendChartPoint(d, pct) {
  chart.data.labels.push(new Date(d.recorded_at).toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' }));
  chart.data.datasets[0].data.push(pct);
  if (chart.data.labels.length > 40) { chart.data.labels.shift(); chart.data.datasets[0].data.shift(); }
  chart.update('none');
}

function prependLogRow(pct, meta) {
  if (pct <= 0) return;
  if (cfg.logPage !== 1) return;
  const tbody = document.getElementById('logBody');
  const first = tbody.querySelector('tr[data-pct][data-status]');
  if (first && Number(first.dataset.pct) === pct && first.dataset.status === meta.status) return;
  if (tbody.querySelector('td[colspan]')) tbody.innerHTML = '';
  tbody.insertAdjacentHTML('afterbegin', `<tr data-pct="${pct}" data-status="${meta.status}"><td class="mono">${new Date().toLocaleString('en-GB')}</td><td style="font-weight:700;color:${meta.colour}">${pct}%</td><td><span class="chip ${meta.chip}">${meta.label}</span></td></tr>`);
  const rows = tbody.querySelectorAll('tr');
  if (rows.length > cfg.logPerPage) rows[rows.length - 1].remove();
}

async function poll() {
  try {
    const res = await fetch(cfg.apiUrl);
    if (!res.ok) throw new Error('HTTP ' + res.status);
    applyReading(await res.json());
    document.getElementById('pipelineDot').style.background = 'var(--green)';
    document.getElementById('pipelineLabel').textContent = 'Connected - last update ' + new Date().toLocaleTimeString();
  } catch {
    document.getElementById('pipelineDot').style.background = 'var(--red)';
    document.getElementById('pipelineLabel').textContent = 'Cannot reach server - check PHP/MySQL';
  }
}

async function sendCommand(cmd, override) {
  const statusEl = document.getElementById('cmdStatus');
  if (cmd === 'open' && override && !window.confirm('Force open this bin even if it is full?')) return;
  statusEl.textContent = `Sending '${cmd}' command...`;
  statusEl.style.color = 'var(--blue)';
  try {
    const res = await fetch(cfg.cmdUrl, { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify({ bin_id: cfg.binId, command: cmd, issued_by: 'Operator', override }) });
    const data = await res.json();
    if (data.success) { statusEl.textContent = 'OK ' + data.message; statusEl.style.color = 'var(--green)'; return; }
    statusEl.textContent = data.error ?? 'Command failed';
    statusEl.style.color = 'var(--red)';
  } catch { statusEl.textContent = 'Could not reach server'; statusEl.style.color = 'var(--red)'; }
}

poll();
setInterval(poll, POLL_INTERVAL);
