<?php
/**
 * Mini CRM - CRUD de Clientes
 */
$moduloActual = 'catalogos';
$tituloPagina = 'Clientes';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('clientes');

$mensaje = '';
$error = '';

// Obtener sucursales para el formulario
$sucursales = obtenerRegistros("SELECT id, nombre FROM sucursales WHERE activo = 1 ORDER BY nombre");

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificarCSRF()) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = sanitizar($_POST['nombre']);
        $rfc = sanitizar($_POST['rfc'] ?? '');
        $direccion = sanitizar($_POST['direccion'] ?? '');
        $telefono1 = sanitizar($_POST['telefono1'] ?? '');
        $telefono2 = sanitizar($_POST['telefono2'] ?? '');
        $email = sanitizar($_POST['email'] ?? '');
        $id_sucursal = (int)$_POST['id_sucursal'];
        $notas = sanitizar($_POST['notas'] ?? '');

        if (empty($nombre) || $id_sucursal <= 0) {
            $error = 'Nombre y sucursal son obligatorios.';
        } else {
            $sql = "INSERT INTO clientes (nombre, rfc, direccion, telefono1, telefono2, email, id_sucursal, notas)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $id = insertarRegistro($sql, [$nombre, $rfc, $direccion, $telefono1, $telefono2, $email, $id_sucursal, $notas]);

            if ($id) {
                registrarAuditoria('crear', 'clientes', $id, "Alta de cliente: $nombre");
                $mensaje = 'Cliente creado exitosamente.';
            } else {
                $error = 'Error al crear el cliente.';
            }
        }
    } elseif ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = sanitizar($_POST['nombre']);
        $rfc = sanitizar($_POST['rfc'] ?? '');
        $direccion = sanitizar($_POST['direccion'] ?? '');
        $telefono1 = sanitizar($_POST['telefono1'] ?? '');
        $telefono2 = sanitizar($_POST['telefono2'] ?? '');
        $email = sanitizar($_POST['email'] ?? '');
        $id_sucursal = (int)$_POST['id_sucursal'];
        $notas = sanitizar($_POST['notas'] ?? '');

        $sql = "UPDATE clientes SET nombre = ?, rfc = ?, direccion = ?, telefono1 = ?,
                telefono2 = ?, email = ?, id_sucursal = ?, notas = ?, updated_at = GETDATE()
                WHERE id = ?";
        $rows = actualizarRegistro($sql, [$nombre, $rfc, $direccion, $telefono1, $telefono2, $email, $id_sucursal, $notas, $id]);

        if ($rows > 0) {
            registrarAuditoria('editar', 'clientes', $id, "Modificación de cliente: $nombre");
            $mensaje = 'Cliente actualizado exitosamente.';
        } else {
            $error = 'No se realizaron cambios.';
        }
    } elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];

        // Verificar si tiene seguimientos
        $check = obtenerRegistro("SELECT COUNT(*) as total FROM seguimientos WHERE id_cliente = ?", [$id]);
        if ($check && $check['total'] > 0) {
            $error = 'No se puede eliminar el cliente porque tiene seguimientos registrados.';
        } else {
            $sql = "UPDATE clientes SET activo = 0, updated_at = GETDATE() WHERE id = ?";
            actualizarRegistro($sql, [$id]);
            registrarAuditoria('eliminar', 'clientes', $id, "Cliente deshabilitado");
            $mensaje = 'Cliente deshabilitado exitosamente.';
        }
    }
}

// Filtros
$filtroNombre = sanitizar($_GET['nombre'] ?? '');
$filtroSucursalId = (int)($_GET['sucursal'] ?? 0);

// Obtener clientes según permisos
$where = ["c.activo = 1"];
$params = [];

if (!isAdmin()) {
    $where[] = "c.id_sucursal = ?";
    $params[] = getSucursalId();
} elseif ($filtroSucursalId > 0) {
    $where[] = "c.id_sucursal = ?";
    $params[] = $filtroSucursalId;
}

if (!empty($filtroNombre)) {
    $where[] = "(c.nombre LIKE ? OR c.rfc LIKE ?)";
    $params[] = "%$filtroNombre%";
    $params[] = "%$filtroNombre%";
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT c.*, s.nombre as sucursal_nombre
        FROM clientes c
        INNER JOIN sucursales s ON c.id_sucursal = s.id
        WHERE $whereClause
        ORDER BY c.nombre";
$clientes = obtenerRegistros($sql, $params);
?>

<div class="page-header">
    <h1 class="page-title">Clientes</h1>
    <p class="page-subtitle">Administración del catálogo de clientes</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?php echo $mensaje; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filtros -->
<div class="filters">
    <form method="GET" class="filters-row">
        <div class="form-group">
            <label class="form-label">Buscar</label>
            <input type="text" name="nombre" class="form-control" placeholder="Nombre o RFC"
                   value="<?php echo $filtroNombre; ?>">
        </div>
        <?php if (isAdmin()): ?>
        <div class="form-group">
            <label class="form-label">Sucursal</label>
            <select name="sucursal" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($sucursales as $suc): ?>
                    <option value="<?php echo $suc['id']; ?>" <?php echo $filtroSucursalId == $suc['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitizar($suc['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <a href="clientes.php" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Clientes (<?php echo count($clientes); ?>)</h3>
        <button onclick="mostrarModal('modalCrear')" class="btn btn-success">Nuevo Cliente</button>
    </div>

    <div class="table-responsive">
        <table class="table" id="tablaClientes">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>RFC</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Sucursal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?php echo $cliente['id']; ?></td>
                    <td><?php echo sanitizar($cliente['nombre']); ?></td>
                    <td><?php echo sanitizar($cliente['rfc']); ?></td>
                    <td><?php echo sanitizar($cliente['telefono1']); ?></td>
                    <td><?php echo sanitizar($cliente['email']); ?></td>
                    <td><?php echo sanitizar($cliente['sucursal_nombre']); ?></td>
                    <td class="table-actions">
                        <button onclick="editarCliente(<?php echo htmlspecialchars(json_encode($cliente)); ?>)"
                                class="btn btn-sm btn-primary">Editar</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmarEliminar('¿Desea deshabilitar este cliente?')">
                            <?php echo getCSRFField(); ?>
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
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
            <h3 class="modal-title">Nuevo Cliente</h3>
            <button onclick="cerrarModal('modalCrear')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="crear">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">RFC</label>
                        <input type="text" name="rfc" class="form-control" maxlength="20">
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
                    <label class="form-label">Dirección</label>
                    <textarea name="direccion" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Teléfono 1</label>
                        <input type="text" name="telefono1" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono 2</label>
                        <input type="text" name="telefono2" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModal('modalCrear')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-success">Crear Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Cliente</h3>
            <button onclick="cerrarModal('modalEditar')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">RFC</label>
                        <input type="text" name="rfc" id="edit_rfc" class="form-control" maxlength="20">
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
                    <label class="form-label">Dirección</label>
                    <textarea name="direccion" id="edit_direccion" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Teléfono 1</label>
                        <input type="text" name="telefono1" id="edit_telefono1" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono 2</label>
                        <input type="text" name="telefono2" id="edit_telefono2" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="edit_email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" id="edit_notas" class="form-control" rows="3"></textarea>
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
function editarCliente(cliente) {
    document.getElementById('edit_id').value = cliente.id;
    document.getElementById('edit_nombre').value = cliente.nombre;
    document.getElementById('edit_rfc').value = cliente.rfc || '';
    document.getElementById('edit_direccion').value = cliente.direccion || '';
    document.getElementById('edit_telefono1').value = cliente.telefono1 || '';
    document.getElementById('edit_telefono2').value = cliente.telefono2 || '';
    document.getElementById('edit_email').value = cliente.email || '';
    document.getElementById('edit_id_sucursal').value = cliente.id_sucursal;
    document.getElementById('edit_notas').value = cliente.notas || '';
    mostrarModal('modalEditar');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
