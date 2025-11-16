<?php
/**
 * Mini CRM - CRUD de Tipos de Seguimiento (Solo Admin)
 */
$moduloActual = 'catalogos';
$tituloPagina = 'Tipos de Seguimiento';
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
        $descripcion = sanitizar($_POST['descripcion'] ?? '');

        if (empty($nombre)) {
            $error = 'El nombre es obligatorio.';
        } else {
            $sql = "INSERT INTO tipos_seguimiento (nombre, descripcion) VALUES (?, ?)";
            $id = insertarRegistro($sql, [$nombre, $descripcion]);
            if ($id) {
                registrarAuditoria('crear', 'tipos_seguimiento', $id, "Alta de tipo: $nombre");
                $mensaje = 'Tipo de seguimiento creado exitosamente.';
            }
        }
    } elseif ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = sanitizar($_POST['nombre']);
        $descripcion = sanitizar($_POST['descripcion'] ?? '');

        $sql = "UPDATE tipos_seguimiento SET nombre = ?, descripcion = ? WHERE id = ?";
        actualizarRegistro($sql, [$nombre, $descripcion, $id]);
        registrarAuditoria('editar', 'tipos_seguimiento', $id, "Modificación de tipo: $nombre");
        $mensaje = 'Tipo actualizado exitosamente.';
    } elseif ($accion === 'toggle') {
        $id = (int)$_POST['id'];
        $tipo = obtenerRegistro("SELECT activo FROM tipos_seguimiento WHERE id = ?", [$id]);
        $nuevoEstado = $tipo['activo'] ? 0 : 1;
        actualizarRegistro("UPDATE tipos_seguimiento SET activo = ? WHERE id = ?", [$nuevoEstado, $id]);
        $mensaje = $nuevoEstado ? 'Tipo activado.' : 'Tipo desactivado.';
    }
}

$tipos = obtenerRegistros("SELECT * FROM tipos_seguimiento ORDER BY nombre");
?>

<div class="page-header">
    <h1 class="page-title">Tipos de Seguimiento</h1>
    <p class="page-subtitle">Configurar tipos de seguimiento (visita, llamada, etc.)</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?php echo $mensaje; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Tipos</h3>
        <button onclick="mostrarModal('modalCrear')" class="btn btn-success">Nuevo Tipo</button>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tipos as $tipo): ?>
                <tr>
                    <td><?php echo $tipo['id']; ?></td>
                    <td><?php echo sanitizar($tipo['nombre']); ?></td>
                    <td><?php echo sanitizar($tipo['descripcion']); ?></td>
                    <td>
                        <?php if ($tipo['activo']): ?>
                        <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <button onclick="editarTipo(<?php echo htmlspecialchars(json_encode($tipo)); ?>)"
                                class="btn btn-sm btn-primary">Editar</button>
                        <form method="POST" style="display:inline;">
                            <?php echo getCSRFField(); ?>
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $tipo['id']; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $tipo['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                                <?php echo $tipo['activo'] ? 'Desactivar' : 'Activar'; ?>
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
            <h3 class="modal-title">Nuevo Tipo de Seguimiento</h3>
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
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3"></textarea>
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
            <h3 class="modal-title">Editar Tipo</h3>
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
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
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
function editarTipo(tipo) {
    document.getElementById('edit_id').value = tipo.id;
    document.getElementById('edit_nombre').value = tipo.nombre;
    document.getElementById('edit_descripcion').value = tipo.descripcion || '';
    mostrarModal('modalEditar');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
