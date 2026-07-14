<div class="module-card reservations-view">
  <div class="module-title-row">
    <div><span class="module-kicker">Operación</span><h2>Reservas recientes</h2><p>Aprueba, rechaza o cancela solicitudes registradas en la base de datos.</p></div>
  </div>
  <div class="module-toolbar"><a class="admin-mini-button" href="<?= $basePath ?>/admin/entradas">Ver entradas emitidas</a><a class="admin-mini-button is-ghost" href="<?= $basePath ?>/admin/reservas?status=pending">Filtrar pendientes</a></div>
  <div class="module-summary-grid"><div><span>Total listado</span><strong><?= count($reservas ?? []) ?></strong></div><div><span>Pendientes</span><strong><?= count(array_filter(($reservas ?? []), static fn($item) => ($item['status'] ?? '') === 'pending')) ?></strong></div><div><span>Confirmadas</span><strong><?= count(array_filter(($reservas ?? []), static fn($item) => ($item['status'] ?? '') === 'approved')) ?></strong></div></div>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Código</th><th>Cliente</th><th>Personas</th><th>Total</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
    <?php foreach (($reservas ?? []) as $reserva): ?>
      <tr><td><strong><?= htmlspecialchars($reserva['request_code'], ENT_QUOTES, 'UTF-8') ?></strong></td><td><?= htmlspecialchars($reserva['full_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$reserva['people_count'] ?></td><td>$<?= number_format((int)$reserva['total_amount'], 0, ',', '.') ?></td><td><span class="status-pill is-<?= htmlspecialchars($reserva['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($reserva['status'], ENT_QUOTES, 'UTF-8') ?></span></td><td><form method="post" class="inline-form"><input type="hidden" name="action" value="update_reserva"><input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>"><select name="status" class="admin-select"><option value="approved">Aprobar</option><option value="rejected">Rechazar</option><option value="cancelled">Cancelar</option><option value="pending">Pendiente</option></select><button type="submit">Guardar</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (empty($reservas)): ?><tr><td colspan="6"><div class="empty-state">No hay reservas registradas todavía.</div></td></tr><?php endif; ?>
  </tbody></table></div>
</div>
