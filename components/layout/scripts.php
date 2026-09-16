<script src="/intelibin/assets/js/layout-clock.js"></script>
<?php foreach (($scripts ?? []) as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
