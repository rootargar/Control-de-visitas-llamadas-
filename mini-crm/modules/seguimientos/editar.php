<?php
/**
 * Mini CRM - Editar Seguimiento
 */
$moduloActual = 'seguimientos';
$tituloPagina = 'Editar Seguimiento';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('seguimientos');

$id = (int)($_GET['id'] ?? 0);
$error = '';

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener seguimiento actual
$seguimiento = obtenerRegistro("SELECT * FROM seguimientos WHERE id = ?", [$id]);

if (!$seguimiento) {
    header('Location: index.php');
    exit;
}

// Verificar permisos
if (isVendedor()) {
    $vendedorId = getVendedorId();
    if ($seguimiento['id_vendedor'] != $vendedorId) {
        header('Location: index.php');
        exit;
    }
} elseif (!isAdmin() && $seguimiento['id_sucursal'] != getSucursalId()) {
    header('Location: index.php');
    exit;
}

// Obtener datos para formulario
$tipos = obtenerRegistros("SELECT id, nombre FROM tipos_seguimiento WHERE activo = 1");

if (isVendedor()) {
    $vendedorId = getVendedorId();
    $clientes = obtenerRegistros("SELECT c.id, c.nombre FROM clientes c
                                   INNER JOIN asignaciones_clientes ac ON c.id = ac.id_cliente
                                   WHERE ac.id_vendedor = ? AND ac.activo = 1 AND c.activo = 1
                                   ORDER BY c.nombre", [$vendedorId]);
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE id = ?", [$vendedorId]);
} elseif (isSupervisor()) {
    $clientes = obtenerRegistros("SELECT id, nombre FROM clientes WHERE id_sucursal = ? AND activo = 1 ORDER BY nombre", [getSucursalId()]);
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE id_sucursal = ? AND activo = 1 ORDER BY nombre", [getSucursalId()]);
} else {
    $clientes = obtenerRegistros("SELECT id, nombre FROM clientes WHERE activo = 1 ORDER BY nombre");
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE activo = 1 ORDER BY nombre");
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificarCSRF()) {
    $id_cliente = (int)$_POST['id_cliente'];
    $id_vendedor = (int)$_POST['id_vendedor'];
    $id_tipo = (int)$_POST['id_tipo'];
    $fecha_hora = sanitizar($_POST['fecha_hora']);
    $duracion = !empty($_POST['duracion']) ? (int)$_POST['duracion'] : null;
    $asunto = sanitizar($_POST['asunto']);
    $observaciones = sanitizar($_POST['observaciones'] ?? '');
    $proxima_accion = sanitizar($_POST['proxima_accion'] ?? '');
    $fecha_proxima = !empty($_POST['fecha_proxima']) ? sanitizar($_POST['fecha_proxima']) : null;

    if ($id_cliente <= 0 || $id_vendedor <= 0 || $id_tipo <= 0 || empty($fecha_hora) || empty($asunto)) {
        $error = 'Cliente, vendedor, tipo, fecha y asunto son obligatorios.';
    } else {
        $archivo_adjunto = $seguimiento['archivo_adjunto'];

        // Manejar nuevo archivo
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['archivo'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, ALLOWED_EXTENSIONS)) {
                $error = 'Tipo de archivo no permitido.';
            } elseif ($archivo['size'] > MAX_UPLOAD_SIZE) {
                $error = 'El archivo excede el tamaño máximo.';
            } else {
                $nombreArchivo = 'seg_' . time() . '_' . uniqid() . '.' . $extension;
                $rutaDestino = UPLOADS_PATH . '/' . $nombreArchivo;

                if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    // Eliminar archivo anterior si existe
                    if ($archivo_adjunto && file_exists(UPLOADS_PATH . '/' . $archivo_adjunto)) {
                        unlink(UPLOADS_PATH . '/' . $archivo_adjunto);
                    }
                    $archivo_adjunto = $nombreArchivo;
                }
            }
        }

        if (empty($error)) {
            $sql = "UPDATE seguimientos SET id_cliente = ?, id_vendedor = ?, id_tipo = ?, fecha_hora = ?,
                    duracion_minutos = ?, asunto = ?, observaciones = ?, proxima_accion = ?, fecha_proxima = ?,
                    archivo_adjunto = ?, updated_at = GETDATE() WHERE id = ?";
            $params = [$id_cliente, $id_vendedor, $id_tipo, $fecha_hora, $duracion, $asunto,
                       $observaciones, $proxima_accion, $fecha_proxima, $archivo_adjunto, $id];

            $rows = actualizarRegistro($sql, $params);

            if ($rows >= 0) {
                registrarAuditoria('editar', 'seguimientos', $id, "Modificación de seguimiento");
                header('Location: index.php?msg=editado');
                exit;
            } else {
                $error = 'Error al actualizar el seguimiento.';
            }
        }
    }
}

// Formatear fecha para input datetime-local
$fechaHoraInput = date('Y-m-d\TH:i', strtotime($seguimiento['fecha_hora']));
$fechaProximaInput = $seguimiento['fecha_proxima'] ? date('Y-m-d', strtotime($seguimiento['fecha_proxima'])) : '';
?>

<div class="page-header">
    <h1 class="page-title">Editar Seguimiento #<?php echo $id; ?></h1>
    <p class="page-subtitle">Modificar información del registro</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <?php echo getCSRFField(); ?>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Cliente *</label>
                <select name="id_cliente" class="form-control" required>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?php echo $cli['id']; ?>" <?php echo $seguimiento['id_cliente'] == $cli['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitizar($cli['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Vendedor *</label>
                <select name="id_vendedor" class="form-control" required <?php echo isVendedor() ? 'disabled' : ''; ?>>
                    <?php foreach ($vendedores as $ven): ?>
                        <option value="<?php echo $ven['id']; ?>" <?php echo $seguimiento['id_vendedor'] == $ven['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitizar($ven['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isVendedor()): ?>
                <input type="hidden" name="id_vendedor" value="<?php echo $seguimiento['id_vendedor']; ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tipo *</label>
                <select name="id_tipo" class="form-control" required>
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?php echo $tipo['id']; ?>" <?php echo $seguimiento['id_tipo'] == $tipo['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitizar($tipo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Fecha y Hora *</label>
                <input type="datetime-local" name="fecha_hora" class="form-control" required
                       value="<?php echo $fechaHoraInput; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Duración (minutos)</label>
                <input type="number" name="duracion" class="form-control" min="1"
                       value="<?php echo $seguimiento['duracion_minutos']; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Asunto *</label>
            <input type="text" name="asunto" class="form-control" required maxlength="200"
                   value="<?php echo sanitizar($seguimiento['asunto']); ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="4"><?php echo sanitizar($seguimiento['observaciones']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Próxima Acción</label>
                <input type="text" name="proxima_accion" class="form-control"
                       value="<?php echo sanitizar($seguimiento['proxima_accion']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Fecha Próxima</label>
                <input type="date" name="fecha_proxima" class="form-control" value="<?php echo $fechaProximaInput; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Archivo Adjunto</label>
            <?php if ($seguimiento['archivo_adjunto']): ?>
                <p>Archivo actual: <a href="<?php echo BASE_URL . '/assets/uploads/' . $seguimiento['archivo_adjunto']; ?>" target="_blank">
                    <?php echo sanitizar($seguimiento['archivo_adjunto']); ?>
                </a></p>
            <?php endif; ?>
            <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            <small class="form-text">Dejar vacío para mantener el archivo actual</small>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
