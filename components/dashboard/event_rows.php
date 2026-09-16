<?php if (empty($logRows)): ?>
<tr><td colspan="3" class="empty-row">No readings yet</td></tr>
<?php else: foreach ($logRows as $r): ?>
<tr data-pct="<?= (int)$r['fill_percent'] ?>" data-status="<?= e($r['status']) ?>">
  <td class="mono"><?= date('d M, H:i:s', strtotime($r['recorded_at'])) ?></td>
  <td style="font-weight:700;color:<?= fillColour((int)$r['fill_percent']) ?>"><?= (int)$r['fill_percent'] ?>%</td>
  <td><span class="chip <?= chipClass($r['status']) ?>"><?= e(statusLabel($r['status'])) ?></span></td>
</tr>
<?php endforeach; endif; ?>
