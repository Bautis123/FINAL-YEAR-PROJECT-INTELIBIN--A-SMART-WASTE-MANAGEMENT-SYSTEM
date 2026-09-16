<div class="table-wrap">
  <table>
    <thead><tr><th>Time</th><th>Fill %</th><th>Status</th></tr></thead>
    <tbody id="logBody">
      <?php partial('dashboard/event_rows.php', compact('logRows')); ?>
    </tbody>
  </table>
</div>
