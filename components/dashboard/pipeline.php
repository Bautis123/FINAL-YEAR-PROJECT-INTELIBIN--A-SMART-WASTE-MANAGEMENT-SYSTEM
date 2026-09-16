<div class="card rise d5">
  <div class="card-header">
    <div class="card-title" style="margin:0">Data Pipeline</div>
    <div class="pipeline-status">
      <div class="live-dot" id="pipelineDot"></div>
      <span id="pipelineLabel">Polling MySQL every 30s</span>
    </div>
  </div>
  <div class="dashboard-pipeline">
    <?php foreach (['Arduino Uno','USB Serial','MySQL DB','PHP API','Dashboard'] as $i => $label): ?>
    <?php partial('dashboard/pipeline_step.php', ['label'=>$label, 'active'=>$i === 4]); ?>
    <?php if ($i < 4): ?><span class="pipeline-arrow">-&gt;</span><?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
