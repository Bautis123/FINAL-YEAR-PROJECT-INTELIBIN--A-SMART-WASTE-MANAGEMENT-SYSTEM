<?php
/**
 * components/dashboard/page.php | main dashboard structure
 *
 *   $vm = [ ... see _helpers.php ... ];      // build from your database
 *   include 'components/dashboard/page.php';
 *
 * Set $ib_skip_init = true before including if you initialise the UI yourself.
 */
require_once __DIR__ . '/_helpers.php';
$vm = ib_vm($vm ?? []);
?>
<div class="ib-app" data-ib-root>
  <?php include __DIR__ . '/_icons.php'; ?>
  <?php include __DIR__ . '/../layout/sidebar.php'; ?>

  <div class="ib-main">
    <?php include __DIR__ . '/../layout/topbar.php'; ?>
    <?php include __DIR__ . '/bin_switcher.php'; ?>
    <?php include __DIR__ . '/full_alert.php'; ?>
    <?php include __DIR__ . '/bin_card.php'; ?>

    <div class="ib-row">
      <?php include __DIR__ . '/lid_controls.php'; ?>
      <?php include __DIR__ . '/people_count.php'; ?>
    </div>

    <div class="ib-row">
      <?php include __DIR__ . '/chart_card.php'; ?>
      <?php include __DIR__ . '/device_card.php'; ?>
    </div>

    <div class="ib-row">
      <?php include __DIR__ . '/recent_readings.php'; ?>
      <?php include __DIR__ . '/event_log.php'; ?>
    </div>

    <?php include __DIR__ . '/pipeline.php'; ?>

    <footer class="ib-foot">
      <span><?= ib_e($vm['footer_left']) ?></span>
      <span><?= ib_e($vm['footer_right']) ?></span>
    </footer>
  </div>

  <script type="application/json" data-ib-state><?= ib_state_json($vm) ?></script>
</div>

<?php if (empty($ib_skip_init)): ?>
<script src="<?= ib_e($vm['asset_base']) ?>/js/dashboard-ui.js"></script>
<script>
  IntelibinUI.init({
    pollUrl: '/intelibin/api/dashboard_state.php?bin_id=<?= (int)$vm['bin']['id'] ?>',
    pollMs: 5000,
    onCommand: async function (cmd, binId) {
      const payload = {
        bin_id: binId,
        command: cmd === 'force_open' ? 'open' : cmd,
        override: cmd === 'force_open' ? 1 : 0,
        issued_by: 'Operator'
      };
      const res = await fetch('/intelibin/api/command.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.error) throw new Error(data.error || 'Command failed');
      return { message: data.message || ('Command ' + payload.command + ' queued for bin ' + binId + '.') };
    }
  });
</script>
<?php endif; ?>
