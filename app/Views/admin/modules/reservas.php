<div class="admin-panel-section">
  <div class="admin-section-heading"><div><h2>Reservas recientes</h2><p>Aprueba, rechaza o cancela solicitudes registradas en la base de datos.</p></div><a class="admin-mini-button" href="<?= $basePath ?>/admin/entradas">Ver entradas</a></div>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Código</th><th>Cliente</th><th>Personas</th><th>Total</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
    <?php foreach (($reservas ?? []) as $reserva): ?>
      <tr><td><?= htmlspecialchars($reserva['request_code'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($reserva['full_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$reserva['people_count'] ?></td><td>$<?= number_format((int)$reserva['total_amount'], 0, ',', '.') ?></td><td><span class="status-pill is-<?= htmlspecialchars($reserva['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($reserva['status'], ENT_QUOTES, 'UTF-8') ?></span></td><td><form method="post" class="inline-form"><input type="hidden" name="action" value="update_reserva"><input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>"><select name="status" class="admin-select"><option value="approved">Aprobar</option><option value="rejected">Rechazar</option><option value="cancelled">Cancelar</option><option value="pending">Pendiente</option></select><button type="submit">Guardar</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (empty($reservas)): ?><tr><td colspan="6">No hay reservas registradas todavía.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
