<?php
/**
 * Mini CRM - CRUD de Sucursales (Solo Admin)
 */
$moduloActual = 'catalogos';
$tituloPagina = 'Sucursales';
require_once __DIR__ . '/../../includes/header.php';

if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/index.php?error=acceso_denegado');
    exit;
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificarCSRF()) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = sanitizar($_POST['nombre']);
        $direccion = sanitizar($_POST['direccion'] ?? '');
        $telefono = sanitizar($_POST['telefono'] ?? '');

        if (empty($nombre)) {
            $error = 'El nombre es obligatorio.';
        } else {
            $sql = "INSERT INTO sucursales (nombre, direccion, telefono) VALUES (?, ?, ?)";
            $id = insertarRegistro($sql, [$nombre, $direccion, $telefono]);
            if ($id) {
                registrarAuditoria('crear', 'sucursales', $id, "Alta de sucursal: $nombre");
                $mensaje = 'Sucursal creada exitosamente.';
            } else {
                $error = 'Error al crear la sucursal.';
            }
        }
    } elseif ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = sanitizar($_POST['nombre']);
        $direccion = sanitizar($_POST['direccion'] ?? '');
        $telefono = sanitizar($_POST['telefono'] ?? '');

        $sql = "UPDATE sucursales SET nombre = ?, direccion = ?, telefono = ?, updated_at = GETDATE() WHERE id = ?";
        $rows = actualizarRegistro($sql, [$nombre, $direccion, $telefono, $id]);
        if ($rows > 0) {
            registrarAuditoria('editar', 'sucursales', $id, "Modificación de sucursal: $nombre");
            $mensaje = 'Sucursal actualizada exitosamente.';
        }
    } elseif ($accion === 'toggle') {
        $id = (int)$_POST['id'];
        $sucursal = obtenerRegistro("SELECT activo FROM sucursales WHERE id = ?", [$id]);
        $nuevoEstado = $sucursal['activo'] ? 0 : 1;
        actualizarRegistro("UPDATE sucursales SET activo = ?, updated_at = GETDATE() WHERE id = ?", [$nuevoEstado, $id]);
        registrarAuditoria('editar', 'sucursales', $id, $nuevoEstado ? "Sucursal activada" : "Sucursal desactivada");
        $mensaje = $nuevoEstado ? 'Sucursal activada.' : 'Sucursal desactivada.';
    }
}

$sucursales = obtenerRegistros("SELECT * FROM sucursales ORDER BY nombre");
?>

<div class="page-header">
    <h1 class="page-title">Sucursales</h1>
    <p class="page-subtitle">Administración de sucursales del sistema</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?php echo $mensaje; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Sucursales</h3>
        <button onclick="mostrarModal('modalCrear')" class="btn btn-success">Nueva Sucursal</button>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sucursales as $suc): ?>
                <tr>
                    <td><?php echo $suc['id']; ?></td>
                    <td><?php echo sanitizar($suc['nombre']); ?></td>
                    <td><?php echo sanitizar($suc['direccion']); ?></td>
                    <td><?php echo sanitizar($suc['telefono']); ?></td>
                    <td>
                        <?php if ($suc['activo']): ?>
                        <span class="badge badge-success">Activa</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <button onclick="editarSucursal(<?php echo htmlspecialchars(json_encode($suc)); ?>)"
                                class="btn btn-sm btn-primary">Editar</button>
                        <form method="POST" style="display:inline;">
                            <?php echo getCSRFField(); ?>
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $suc['id']; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $suc['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                                <?php echo $suc['activo'] ? 'Desactivar' : 'Activar'; ?>
                            </button>
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
            <h3 class="modal-title">Nueva Sucursal</h3>
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
                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModal('modalCrear')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-success">Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Sucursal</h3>
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
                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" id="edit_direccion" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" id="edit_telefono" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModal('modalEditar')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarSucursal(suc) {
    document.getElementById('edit_id').value = suc.id;
    document.getElementById('edit_nombre').value = suc.nombre;
    document.getElementById('edit_direccion').value = suc.direccion || '';
    document.getElementById('edit_telefono').value = suc.telefono || '';
    mostrarModal('modalEditar');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
