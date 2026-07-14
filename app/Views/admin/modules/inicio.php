<div class="admin-stats-grid">
  <?php foreach (($stats ?? []) as $stat): ?>
    <article class="admin-stat"><span><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($stat['hint'], ENT_QUOTES, 'UTF-8') ?></small></article>
  <?php endforeach; ?>
</div>
<div class="admin-panel-section">
  <div class="admin-section-heading"><div><h2>Operación del evento</h2><p>Resumen conectado a reservas, entradas y validaciones reales de la base de datos.</p></div><a class="admin-mini-button" href="<?= $basePath ?>/admin/reservas">Revisar solicitudes</a></div>
</div>
