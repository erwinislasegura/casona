<?php
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editReserva = null;
foreach (($reservas ?? []) as $item) {
    if ((int)$item['id'] === $editId) {
        $editReserva = $item;
        break;
    }
}
?>
<div class="module-card reservations-view">
  <div class="module-title-row">
    <div><span class="module-kicker">Operación</span><h2>Registros del formulario</h2><p>Solicitudes registradas desde el formulario público de la página de inicio, con comprobante y acciones de revisión.</p></div>
  </div>
  <div class="module-toolbar"><a class="admin-mini-button" href="<?= $basePath ?>/admin/reservas">Todas</a><a class="admin-mini-button is-ghost" href="<?= $basePath ?>/admin/reservas?status=pending">Pendientes</a><a class="admin-mini-button is-ghost" href="<?= $basePath ?>/admin/entradas">Ver entradas emitidas</a></div>
  <div class="module-summary-grid"><div><span>Total listado</span><strong><?= count($reservas ?? []) ?></strong></div><div><span>Pendientes</span><strong><?= count(array_filter(($reservas ?? []), static fn($item) => ($item['status'] ?? '') === 'pending')) ?></strong></div><div><span>Confirmadas</span><strong><?= count(array_filter(($reservas ?? []), static fn($item) => ($item['status'] ?? '') === 'approved')) ?></strong></div></div>

  <?php if ($editReserva): ?>
    <form method="post" class="reservation-edit-card">
      <input type="hidden" name="action" value="edit_reserva"><input type="hidden" name="reserva_id" value="<?= (int)$editReserva['id'] ?>">
      <div><span class="module-kicker">Editar reserva</span><h3><?= htmlspecialchars($editReserva['request_code'], ENT_QUOTES, 'UTF-8') ?></h3></div>
      <label><span>Nombre</span><input class="form-control" name="full_name" value="<?= htmlspecialchars($editReserva['full_name'], ENT_QUOTES, 'UTF-8') ?>" required></label>
      <label><span>RUT</span><input class="form-control" name="rut" value="<?= htmlspecialchars((string)$editReserva['rut'], ENT_QUOTES, 'UTF-8') ?>" required></label>
      <label><span>Teléfono</span><input class="form-control" name="phone" value="<?= htmlspecialchars((string)$editReserva['phone'], ENT_QUOTES, 'UTF-8') ?>" required></label>
      <label><span>Email</span><input class="form-control" name="email" type="email" value="<?= htmlspecialchars((string)$editReserva['email'], ENT_QUOTES, 'UTF-8') ?>" required></label>
      <label><span>Personas</span><input class="form-control" name="people_count" type="number" min="1" max="20" value="<?= (int)$editReserva['people_count'] ?>" required></label>
      <div class="module-toolbar"><button class="admin-primary-button" type="submit">Guardar edición</button><a class="admin-mini-button is-ghost" href="<?= $basePath ?>/admin/reservas">Cancelar</a></div>
    </form>
  <?php endif; ?>

  <div class="admin-table-wrap"><table class="admin-table reservations-table improved-reservations"><thead><tr><th>Solicitud</th><th>Cliente y contacto</th><th>Reserva</th><th>Documentos</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach (($reservas ?? []) as $reserva): ?>
      <tr>
        <td><strong><?= htmlspecialchars($reserva['request_code'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string)($reserva['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
        <td><span class="cell-title"><?= htmlspecialchars($reserva['full_name'], ENT_QUOTES, 'UTF-8') ?></span><small>RUT: <?= htmlspecialchars((string)($reserva['rut'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small><small><?= htmlspecialchars((string)($reserva['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string)($reserva['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
        <td><span class="cell-title"><?= (int)$reserva['people_count'] ?> persona(s)</span><small>Total pagado: $<?= number_format((int)$reserva['total_amount'], 0, ',', '.') ?></small></td>
        <td><div class="document-links"><?php if (!empty($reserva['receipt_path'])): ?><a class="receipt-link" href="<?= htmlspecialchars($basePath . $reserva['receipt_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Comprobante</a><?php elseif (!empty($reserva['receipt_name'])): ?><a class="receipt-link" href="<?= $basePath ?>/admin/comprobante/?id=<?= (int)$reserva['id'] ?>" target="_blank" rel="noopener">Comprobante</a><?php else: ?><span>Sin comprobante</span><?php endif; ?><?php if (($reserva['status'] ?? '') === 'approved'): ?><a class="receipt-link is-ticket" href="<?= $basePath ?>/admin/entrada-pdf/?id=<?= (int)$reserva['id'] ?>">Entrada PDF con QR</a><?php endif; ?></div></td>
        <td><span class="status-pill is-<?= htmlspecialchars($reserva['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($reserva['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><details class="actions-dropdown"><summary>Opciones</summary><div class="actions-menu">
          <form method="post"><input type="hidden" name="action" value="update_reserva"><input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>"><input type="hidden" name="status" value="approved"><button class="action-button is-approve" type="submit">Aprobar y emitir PDF con QR</button></form>
          <form method="post"><input type="hidden" name="action" value="update_reserva"><input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>"><input type="hidden" name="status" value="rejected"><button class="action-button is-reject" type="submit">Rechazar</button></form>
          <a class="action-button is-edit" href="<?= $basePath ?>/admin/reservas?edit=<?= (int)$reserva['id'] ?>">Editar datos</a>
          <?php if (($reserva['status'] ?? '') === 'approved'): ?><a class="action-button is-ticket" href="<?= $basePath ?>/admin/entrada-pdf/?id=<?= (int)$reserva['id'] ?>">Descargar entrada PDF con QR</a><?php endif; ?>
          <form method="post" onsubmit="return confirm('¿Eliminar definitivamente esta reserva?');"><input type="hidden" name="action" value="delete_reserva"><input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>"><button class="action-button is-delete" type="submit">Eliminar</button></form>
        </div></details></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($reservas)): ?><tr><td colspan="6"><div class="empty-state">No hay reservas registradas todavía.</div></td></tr><?php endif; ?>
  </tbody></table></div>
</div>
