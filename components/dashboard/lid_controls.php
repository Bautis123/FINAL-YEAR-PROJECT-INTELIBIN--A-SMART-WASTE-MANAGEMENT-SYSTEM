<?php
/**
 * components/dashboard/lid_controls.php | remote lid control panel
 * The buttons carry data-cmd="open|close|force_open|reset". dashboard-ui.js calls your
 * onCommand(cmd, binId) handler (see page.php) and shows the returned message.
 * Force open and Reset ask for a second tap before they send.
 */
require_once __DIR__ . '/_helpers.php';
$ib_lid = $vm['lid'];
$ib_lidState = $ib_lid === 'open' ? ['ready', 'Lid open'] : ($ib_lid === 'closed' ? ['idle', 'Lid closed'] : ['idle', 'Lid state unknown']);
?>
<section class="ib-panel" aria-labelledby="ib-ctl-title">
  <div class="ib-panel-head">
    <div>
      <h2 id="ib-ctl-title">Remote lid control</h2>
      <p data-ib="ctlBinName">Send a command to <?= ib_e($vm['bin']['name']) ?></p>
    </div>
    <span class="ib-pill" data-ib="lidPill" data-ib-s="<?= $ib_lidState[0] ?>"><i></i><span data-ib="lidText"><?= $ib_lidState[1] ?></span></span>
  </div>
  <div class="ib-ctl">
    <button class="ib-btn ib-primary" type="button" data-ib="cmd" data-cmd="open"><?= ib_icon('unlock') ?><span class="ib-lbl">Open bin</span></button>
    <button class="ib-btn" type="button" data-ib="cmd" data-cmd="close"><?= ib_icon('lock') ?><span class="ib-lbl">Close lid</span></button>
    <button class="ib-btn ib-danger" type="button" data-ib="cmd" data-cmd="force_open"><?= ib_icon('zap') ?><span class="ib-lbl">Force open</span></button>
    <button class="ib-btn" type="button" data-ib="cmd" data-cmd="reset"><?= ib_icon('reset') ?><span class="ib-lbl">Reset</span></button>
  </div>
  <p class="ib-cmd-msg" data-ib="cmdMsg" role="status" aria-live="polite">No commands sent yet.</p>
</section>
