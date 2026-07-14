<div class="admin-panel-section">
  <div class="admin-section-heading"><div><h2>Entradas emitidas</h2><p>Listado real de entradas, comprador asociado y estado de ingreso.</p></div><a class="admin-mini-button" href="<?= $basePath ?>/admin/scanner">Abrir escáner</a></div>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ID</th><th>Reserva</th><th>Cliente</th><th>Estado</th><th>Emitida</th><th>Ingreso</th></tr></thead><tbody>
    <?php foreach (($entradas ?? []) as $entrada): ?>
      <tr><td>#<?= (int)$entrada['id'] ?></td><td><?= htmlspecialchars($entrada['request_code'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($entrada['full_name'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="status-pill is-<?= htmlspecialchars($entrada['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($entrada['status'], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars((string)$entrada['issued_at'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)($entrada['entered_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td></tr>
    <?php endforeach; ?>
    <?php if (empty($entradas)): ?><tr><td colspan="6">No hay entradas emitidas todavía.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
