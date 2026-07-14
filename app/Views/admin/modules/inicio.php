<?php $stats = $stats ?? [['label'=>'Solicitudes','value'=>'24','hint'=>'8 pendientes'],['label'=>'Entradas','value'=>'180','hint'=>'156 disponibles'],['label'=>'Validaciones','value'=>'0','hint'=>'Listo para evento']]; ?>
<div class="admin-stats-grid">
  <?php foreach ($stats as $stat): ?>
    <article class="admin-stat"><span><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($stat['hint'], ENT_QUOTES, 'UTF-8') ?></small></article>
  <?php endforeach; ?>
</div>
<div class="admin-panel-section">
  <h2>Operación rápida</h2>
  <p>Selecciona una tarea del panel lateral o de los accesos principales para continuar.</p>
</div>
