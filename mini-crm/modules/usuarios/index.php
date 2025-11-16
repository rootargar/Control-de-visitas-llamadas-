<?php
/**
 * Mini CRM - Administración de Usuarios
 */
$moduloActual = 'usuarios';
$tituloPagina = 'Usuarios';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('usuarios');

$mensaje = '';
$error = '';

$sucursales = obtenerRegistros("SELECT id, nombre FROM sucursales WHERE activo = 1 ORDER BY nombre");

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificarCSRF()) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $username = sanitizar($_POST['username']);
        $password = $_POST['password'];
        $nombre_completo = sanitizar($_POST['nombre_completo']);
        $email = sanitizar($_POST['email'] ?? '');
        $rol = sanitizar($_POST['rol']);
        $id_sucursal = (int)$_POST['id_sucursal'];

        // Validaciones
        if (empty($username) || empty($password) || empty($nombre_completo) || empty($rol) || $id_sucursal <= 0) {
            $error = 'Todos los campos marcados son obligatorios.';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif (!in_array($rol, [ROL_ADMIN, ROL_SUPERVISOR, ROL_VENDEDOR])) {
            $error = 'Rol no válido.';
        } elseif (isSupervisor() && $rol === ROL_ADMIN) {
            $error = 'No tiene permisos para crear usuarios administradores.';
        } else {
            // Verificar username único
            $check = obtenerRegistro("SELECT id FROM usuarios WHERE username = ?", [$username]);
            if ($check) {
                $error = 'El nombre de usuario ya existe.';
            } else {
                $passwordHash = hashPassword($password);
                $sql = "INSERT INTO usuarios (username, password, nombre_completo, email, rol, id_sucursal)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $id = insertarRegistro($sql, [$username, $passwordHash, $nombre_completo, $email, $rol, $id_sucursal]);

                if ($id) {
                    registrarAuditoria('crear', 'usuarios', $id, "Alta de usuario: $username ($rol)");
                    $mensaje = 'Usuario creado exitosamente.';
                } else {
                    $error = 'Error al crear el usuario.';
                }
            }
        }
    } elseif ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre_completo = sanitizar($_POST['nombre_completo']);
        $email = sanitizar($_POST['email'] ?? '');
        $rol = sanitizar($_POST['rol']);
        $id_sucursal = (int)$_POST['id_sucursal'];
        $password = $_POST['password'] ?? '';

        // Verificar permisos
        $usuarioEditar = obtenerRegistro("SELECT * FROM usuarios WHERE id = ?", [$id]);

        if (!$usuarioEditar) {
            $error = 'Usuario no encontrado.';
        } elseif (isSupervisor() && $usuarioEditar['rol'] === ROL_ADMIN) {
            $error = 'No puede editar usuarios administradores.';
        } elseif (isSupervisor() && $rol === ROL_ADMIN) {
            $error = 'No puede asignar rol de administrador.';
        } else {
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $error = 'La contraseña debe tener al menos 6 caracteres.';
                } else {
                    $passwordHash = hashPassword($password);
                    $sql = "UPDATE usuarios SET nombre_completo = ?, email = ?, rol = ?, id_sucursal = ?,
                            password = ?, updated_at = GETDATE() WHERE id = ?";
                    actualizarRegistro($sql, [$nombre_completo, $email, $rol, $id_sucursal, $passwordHash, $id]);
                }
            } else {
                $sql = "UPDATE usuarios SET nombre_completo = ?, email = ?, rol = ?, id_sucursal = ?,
                        updated_at = GETDATE() WHERE id = ?";
                actualizarRegistro($sql, [$nombre_completo, $email, $rol, $id_sucursal, $id]);
            }

            if (empty($error)) {
                registrarAuditoria('editar', 'usuarios', $id, "Modificación de usuario");
                $mensaje = 'Usuario actualizado exitosamente.';
            }
        }
    } elseif ($accion === 'toggle') {
        $id = (int)$_POST['id'];

        if ($id == getUsuarioId()) {
            $error = 'No puede desactivar su propio usuario.';
        } else {
            $usuario = obtenerRegistro("SELECT * FROM usuarios WHERE id = ?", [$id]);

            if (isSupervisor() && $usuario['rol'] === ROL_ADMIN) {
                $error = 'No puede modificar usuarios administradores.';
            } else {
                $nuevoEstado = $usuario['activo'] ? 0 : 1;
                actualizarRegistro("UPDATE usuarios SET activo = ?, updated_at = GETDATE() WHERE id = ?", [$nuevoEstado, $id]);
                registrarAuditoria('editar', 'usuarios', $id, $nuevoEstado ? "Usuario activado" : "Usuario desactivado");
                $mensaje = $nuevoEstado ? 'Usuario activado.' : 'Usuario desactivado.';
            }
        }
    }
}

// Obtener usuarios según permisos
$where = [];
$params = [];

if (isSupervisor()) {
    // Supervisor solo ve usuarios de su sucursal y no puede ver otros admins
    $where[] = "(id_sucursal = ? OR rol != 'admin')";
    $params[] = getSucursalId();
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT u.*, s.nombre as sucursal_nombre
        FROM usuarios u
        INNER JOIN sucursales s ON u.id_sucursal = s.id
        $whereClause
        ORDER BY u.nombre_completo";
$usuarios = obtenerRegistros($sql, $params);
?>

<div class="page-header">
    <h1 class="page-title">Administración de Usuarios</h1>
    <p class="page-subtitle">Gestión de cuentas y permisos del sistema</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?php echo $mensaje; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Usuarios (<?php echo count($usuarios); ?>)</h3>
        <button onclick="mostrarModal('modalCrear')" class="btn btn-success">Nuevo Usuario</button>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Sucursal</th>
                    <th>Estado</th>
                    <th>Último Acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usr): ?>
                <tr>
                    <td><?php echo $usr['id']; ?></td>
                    <td><strong><?php echo sanitizar($usr['username']); ?></strong></td>
                    <td><?php echo sanitizar($usr['nombre_completo']); ?></td>
                    <td><?php echo sanitizar($usr['email']); ?></td>
                    <td>
                        <?php
                        $rolBadge = 'badge-info';
                        if ($usr['rol'] === ROL_ADMIN) $rolBadge = 'badge-danger';
                        elseif ($usr['rol'] === ROL_SUPERVISOR) $rolBadge = 'badge-warning';
                        ?>
                        <span class="badge <?php echo $rolBadge; ?>">
                            <?php echo ucfirst($usr['rol']); ?>
                        </span>
                    </td>
                    <td><?php echo sanitizar($usr['sucursal_nombre']); ?></td>
                    <td>
                        <?php if ($usr['activo']): ?>
                        <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $usr['ultimo_acceso'] ? date(DATETIME_FORMAT, strtotime($usr['ultimo_acceso'])) : 'Nunca'; ?>
                    </td>
                    <td class="table-actions">
                        <?php if (isAdmin() || (isSupervisor() && $usr['rol'] !== ROL_ADMIN)): ?>
                        <button onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usr)); ?>)"
                                class="btn btn-sm btn-primary">Editar</button>
                        <?php if ($usr['id'] != getUsuarioId()): ?>
                        <form method="POST" style="display:inline;">
                            <?php echo getCSRFField(); ?>
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $usr['id']; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $usr['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                                <?php echo $usr['activo'] ? 'Desactivar' : 'Activar'; ?>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear -->
<div id="modalCrear" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Nuevo Usuario</h3>
            <button onclick="cerrarModal('modalCrear')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="crear">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario *</label>
                    <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_]+" maxlength="50">
                    <small class="form-text">Solo letras, números y guión bajo.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <small class="form-text">Mínimo 6 caracteres.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre_completo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select name="rol" class="form-control" required>
                            <option value="">Seleccione</option>
                            <?php if (isAdmin()): ?>
                            <option value="admin">Administrador</option>
                            <?php endif; ?>
                            <option value="supervisor">Supervisor</option>
                            <option value="vendedor">Vendedor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sucursal *</label>
                        <select name="id_sucursal" class="form-control" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>"><?php echo sanitizar($suc['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModal('modalCrear')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-success">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Usuario</h3>
            <button onclick="cerrarModal('modalEditar')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <input type="text" id="edit_username" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña (dejar vacío para no cambiar)</label>
                    <input type="password" name="password" class="form-control" minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre_completo" id="edit_nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="edit_email" class="form-control">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select name="rol" id="edit_rol" class="form-control" required>
                            <?php if (isAdmin()): ?>
                            <option value="admin">Administrador</option>
                            <?php endif; ?>
                            <option value="supervisor">Supervisor</option>
                            <option value="vendedor">Vendedor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sucursal *</label>
                        <select name="id_sucursal" id="edit_sucursal" class="form-control" required>
                            <?php foreach ($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>"><?php echo sanitizar($suc['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModal('modalEditar')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarUsuario(usr) {
    document.getElementById('edit_id').value = usr.id;
    document.getElementById('edit_username').value = usr.username;
    document.getElementById('edit_nombre').value = usr.nombre_completo;
    document.getElementById('edit_email').value = usr.email || '';
    document.getElementById('edit_rol').value = usr.rol;
    document.getElementById('edit_sucursal').value = usr.id_sucursal;
    mostrarModal('modalEditar');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
