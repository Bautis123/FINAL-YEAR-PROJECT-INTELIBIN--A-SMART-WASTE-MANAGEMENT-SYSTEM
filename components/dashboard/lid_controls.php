<div class="lid-controls">
  <div class="card-title">Remote Lid Control</div>
  <div class="control-row">
    <button class="btn btn-success" onclick="sendCommand('open', 0)">Open Bin</button>
    <button class="btn btn-ghost" onclick="sendCommand('close', 0)">Close Lid</button>
    <button class="btn btn-primary" onclick="sendCommand('reset', 0)">Reset</button>
  </div>
  <div id="cmdStatus" class="command-status"></div>
</div>
