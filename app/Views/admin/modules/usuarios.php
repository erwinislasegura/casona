<?php
$adminUsers = $adminUsers ?? [];
$roleOptions = $roleOptions ?? ['admin' => 'Administrador', 'scanner' => 'Validador', 'viewer' => 'Lectura'];
$editingUser = null;
if (!empty($_GET['edit'])) {
    foreach ($adminUsers as $candidate) {
        if ((int)$candidate['id'] === (int)$_GET['edit']) {
            $editingUser = $candidate;
            break;
        }
    }
}
?>
<div class="module-card users-view">
  <div class="module-title-row">
    <div>
      <span class="module-kicker">Seguridad</span>
      <h2>Usuarios y roles</h2>
      <p>Administra las cuentas con acceso al panel y asigna el nivel de permisos de cada integrante.</p>
    </div>
  </div>

  <div class="module-summary-grid">
    <div><span>Usuarios</span><strong><?= count($adminUsers) ?></strong></div>
    <div><span>Activos</span><strong><?= count(array_filter($adminUsers, static fn($user) => (int)($user['is_active'] ?? 0) === 1)) ?></strong></div>
    <div><span>Roles</span><strong><?= count($roleOptions) ?></strong></div>
  </div>

  <form method="post" class="settings-list settings-card">
    <input type="hidden" name="action" value="<?= $editingUser ? 'update_admin_user' : 'create_admin_user' ?>">
    <?php if ($editingUser): ?><input type="hidden" name="user_id" value="<?= (int)$editingUser['id'] ?>"><?php endif; ?>
    <label><span>Nombre</span><input name="name" class="form-control" required value="<?= htmlspecialchars($editingUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
    <label><span>Usuario</span><input name="username" class="form-control" required value="<?= htmlspecialchars($editingUser['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
    <label><span>Correo</span><input name="email" type="email" class="form-control" required value="<?= htmlspecialchars($editingUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
    <label><span>Rol</span><select name="role" class="form-control"><?php foreach ($roleOptions as $value => $label): ?><option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($editingUser['role'] ?? 'viewer') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
    <label><span>Estado</span><select name="is_active" class="form-control"><option value="1" <?= (int)($editingUser['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Activo</option><option value="0" <?= isset($editingUser['is_active']) && (int)$editingUser['is_active'] === 0 ? 'selected' : '' ?>>Inactivo</option></select></label>
    <label><span>Contraseña <?= $editingUser ? '(dejar en blanco para conservar)' : '' ?></span><input name="password" type="password" class="form-control" <?= $editingUser ? '' : 'required' ?> minlength="8"></label>
    <div class="module-toolbar"><button class="admin-primary-button" type="submit"><?= $editingUser ? 'Guardar cambios' : 'Crear usuario' ?></button><?php if ($editingUser): ?><a class="admin-mini-button is-ghost" href="<?= $basePath ?>/admin/usuarios">Cancelar</a><?php endif; ?></div>
  </form>

  <div class="table-responsive mt-4">
    <table class="table align-middle admin-table">
      <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($adminUsers as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($roleOptions[$user['role']] ?? $user['role'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)$user['is_active'] === 1 ? 'Activo' : 'Inactivo' ?></td>
            <td><?= htmlspecialchars($user['last_login_at'] ?? 'Sin acceso', ENT_QUOTES, 'UTF-8') ?></td>
            <td><a class="admin-mini-button" href="<?= $basePath ?>/admin/usuarios?edit=<?= (int)$user['id'] ?>">Editar</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
