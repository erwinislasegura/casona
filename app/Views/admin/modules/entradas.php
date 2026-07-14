<div class="module-card tickets-view">
  <div class="module-title-row">
    <div><span class="module-kicker">Accesos</span><h2>Entradas emitidas</h2><p>Listado real de entradas, comprador asociado y estado de ingreso.</p></div>
  </div>
  <div class="module-toolbar"><a class="admin-mini-button" href="<?= $basePath ?>/admin/scanner">Abrir escáner QR</a><a class="admin-mini-button is-ghost" href="<?= $basePath ?>/admin/reservas">Ver reservas</a></div>
  <div class="module-summary-grid"><div><span>Entradas</span><strong><?= count($entradas ?? []) ?></strong></div><div><span>Ingresadas</span><strong><?= count(array_filter(($entradas ?? []), static fn($item) => !empty($item['entered_at']))) ?></strong></div><div><span>Por ingresar</span><strong><?= count(array_filter(($entradas ?? []), static fn($item) => empty($item['entered_at']))) ?></strong></div></div>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ID</th><th>Reserva</th><th>Cliente</th><th>Estado</th><th>Emitida</th><th>Ingreso</th></tr></thead><tbody>
    <?php foreach (($entradas ?? []) as $entrada): ?>
      <tr><td><strong>#<?= (int)$entrada['id'] ?></strong></td><td><?= htmlspecialchars($entrada['request_code'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($entrada['full_name'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="status-pill is-<?= htmlspecialchars($entrada['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($entrada['status'], ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars((string)$entrada['issued_at'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)($entrada['entered_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td></tr>
    <?php endforeach; ?>
    <?php if (empty($entradas)): ?><tr><td colspan="6"><div class="empty-state">No hay entradas emitidas todavía.</div></td></tr><?php endif; ?>
  </tbody></table></div>
</div>
