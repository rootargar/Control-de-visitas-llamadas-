<?php
/**
 * Mini CRM - CRUD de Vendedores
 */
$moduloActual = 'catalogos';
$tituloPagina = 'Vendedores';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('vendedores');

$mensaje = '';
$error = '';

$sucursales = obtenerRegistros("SELECT id, nombre FROM sucursales WHERE activo = 1 ORDER BY nombre");
$usuariosVendedores = obtenerRegistros("SELECT id, username, nombre_completo FROM usuarios WHERE rol = 'vendedor' AND activo = 1 AND id NOT IN (SELECT id_usuario FROM vendedores) ORDER BY nombre_completo");

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificarCSRF()) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $id_usuario = (int)$_POST['id_usuario'];
        $codigo = sanitizar($_POST['codigo']);
        $nombre = sanitizar($_POST['nombre']);
        $telefono = sanitizar($_POST['telefono'] ?? '');
        $email = sanitizar($_POST['email'] ?? '');
        $id_sucursal = (int)$_POST['id_sucursal'];

        if ($id_usuario <= 0 || empty($codigo) || empty($nombre) || $id_sucursal <= 0) {
            $error = 'Usuario, código, nombre y sucursal son obligatorios.';
        } else {
            // Verificar código único
            $check = obtenerRegistro("SELECT id FROM vendedores WHERE codigo = ?", [$codigo]);
            if ($check) {
                $error = 'El código de vendedor ya existe.';
            } else {
                $sql = "INSERT INTO vendedores (id_usuario, codigo, nombre, telefono, email, id_sucursal)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $id = insertarRegistro($sql, [$id_usuario, $codigo, $nombre, $telefono, $email, $id_sucursal]);

                if ($id) {
                    registrarAuditoria('crear', 'vendedores', $id, "Alta de vendedor: $nombre");
                    $mensaje = 'Vendedor creado exitosamente.';
                    // Recargar lista de usuarios
                    $usuariosVendedores = obtenerRegistros("SELECT id, username, nombre_completo FROM usuarios WHERE rol = 'vendedor' AND activo = 1 AND id NOT IN (SELECT id_usuario FROM vendedores) ORDER BY nombre_completo");
                } else {
                    $error = 'Error al crear el vendedor.';
                }
            }
        }
    } elseif ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $codigo = sanitizar($_POST['codigo']);
        $nombre = sanitizar($_POST['nombre']);
        $telefono = sanitizar($_POST['telefono'] ?? '');
        $email = sanitizar($_POST['email'] ?? '');
        $id_sucursal = (int)$_POST['id_sucursal'];

        // Verificar código único
        $check = obtenerRegistro("SELECT id FROM vendedores WHERE codigo = ? AND id != ?", [$codigo, $id]);
        if ($check) {
            $error = 'El código de vendedor ya existe.';
        } else {
            $sql = "UPDATE vendedores SET codigo = ?, nombre = ?, telefono = ?, email = ?,
                    id_sucursal = ?, updated_at = GETDATE() WHERE id = ?";
            $rows = actualizarRegistro($sql, [$codigo, $nombre, $telefono, $email, $id_sucursal, $id]);

            if ($rows > 0) {
                registrarAuditoria('editar', 'vendedores', $id, "Modificación de vendedor: $nombre");
                $mensaje = 'Vendedor actualizado exitosamente.';
            } else {
                $error = 'No se realizaron cambios.';
            }
        }
    } elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];

        $check = obtenerRegistro("SELECT COUNT(*) as total FROM seguimientos WHERE id_vendedor = ?", [$id]);
        if ($check && $check['total'] > 0) {
            $error = 'No se puede eliminar el vendedor porque tiene seguimientos registrados.';
        } else {
            $sql = "UPDATE vendedores SET activo = 0, updated_at = GETDATE() WHERE id = ?";
            actualizarRegistro($sql, [$id]);
            registrarAuditoria('eliminar', 'vendedores', $id, "Vendedor deshabilitado");
            $mensaje = 'Vendedor deshabilitado exitosamente.';
        }
    }
}

// Obtener vendedores
$where = ["v.activo = 1"];
$params = [];

if (!isAdmin()) {
    $where[] = "v.id_sucursal = ?";
    $params[] = getSucursalId();
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT v.*, s.nombre as sucursal_nombre, u.username
        FROM vendedores v
        INNER JOIN sucursales s ON v.id_sucursal = s.id
        INNER JOIN usuarios u ON v.id_usuario = u.id
        WHERE $whereClause
        ORDER BY v.nombre";
$vendedores = obtenerRegistros($sql, $params);
?>

<div class="page-header">
    <h1 class="page-title">Vendedores</h1>
    <p class="page-subtitle">Administración del catálogo de vendedores</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?php echo $mensaje; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Vendedores (<?php echo count($vendedores); ?>)</h3>
        <?php if (!empty($usuariosVendedores)): ?>
        <button onclick="mostrarModal('modalCrear')" class="btn btn-success">Nuevo Vendedor</button>
        <?php else: ?>
        <span class="text-muted">No hay usuarios vendedores disponibles para asignar</span>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Sucursal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendedores as $vendedor): ?>
                <tr>
                    <td><?php echo $vendedor['id']; ?></td>
                    <td><span class="badge badge-primary"><?php echo sanitizar($vendedor['codigo']); ?></span></td>
                    <td><?php echo sanitizar($vendedor['nombre']); ?></td>
                    <td><?php echo sanitizar($vendedor['username']); ?></td>
                    <td><?php echo sanitizar($vendedor['telefono']); ?></td>
                    <td><?php echo sanitizar($vendedor['email']); ?></td>
                    <td><?php echo sanitizar($vendedor['sucursal_nombre']); ?></td>
                    <td class="table-actions">
                        <button onclick="editarVendedor(<?php echo htmlspecialchars(json_encode($vendedor)); ?>)"
                                class="btn btn-sm btn-primary">Editar</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmarEliminar('¿Desea deshabilitar este vendedor?')">
                            <?php echo getCSRFField(); ?>
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $vendedor['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
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
            <h3 class="modal-title">Nuevo Vendedor</h3>
            <button onclick="cerrarModal('modalCrear')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="crear">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Usuario del Sistema *</label>
                    <select name="id_usuario" class="form-control" required>
                        <option value="">Seleccione usuario</option>
                        <?php foreach ($usuariosVendedores as $usr): ?>
                        <option value="<?php echo $usr['id']; ?>">
                            <?php echo sanitizar($usr['nombre_completo']); ?> (<?php echo sanitizar($usr['username']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Código *</label>
                        <input type="text" name="codigo" class="form-control" maxlength="20" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sucursal *</label>
                        <select name="id_sucursal" class="form-control" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($sucursales as $suc): ?>
                                <?php if (isAdmin() || $suc['id'] == getSucursalId()): ?>
                                <option value="<?php echo $suc['id']; ?>"><?php echo sanitizar($suc['nombre']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModal('modalCrear')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-success">Crear Vendedor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Vendedor</h3>
            <button onclick="cerrarModal('modalEditar')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Código *</label>
                        <input type="text" name="codigo" id="edit_codigo" class="form-control" maxlength="20" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sucursal *</label>
                        <select name="id_sucursal" id="edit_id_sucursal" class="form-control" required>
                            <?php foreach ($sucursales as $suc): ?>
                                <?php if (isAdmin() || $suc['id'] == getSucursalId()): ?>
                                <option value="<?php echo $suc['id']; ?>"><?php echo sanitizar($suc['nombre']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" id="edit_telefono" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
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
function editarVendedor(vendedor) {
    document.getElementById('edit_id').value = vendedor.id;
    document.getElementById('edit_codigo').value = vendedor.codigo;
    document.getElementById('edit_nombre').value = vendedor.nombre;
    document.getElementById('edit_telefono').value = vendedor.telefono || '';
    document.getElementById('edit_email').value = vendedor.email || '';
    document.getElementById('edit_id_sucursal').value = vendedor.id_sucursal;
    mostrarModal('modalEditar');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
