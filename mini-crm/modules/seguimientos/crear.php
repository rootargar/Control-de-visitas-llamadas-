<?php
/**
 * Mini CRM - Crear Seguimiento
 */
$moduloActual = 'seguimientos';
$tituloPagina = 'Nuevo Seguimiento';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('seguimientos');

$error = '';

// Obtener datos para formulario
$tipos = obtenerRegistros("SELECT id, nombre FROM tipos_seguimiento WHERE activo = 1");

// Obtener clientes según permisos
if (isVendedor()) {
    $vendedorId = getVendedorId();
    $clientes = obtenerRegistros("SELECT c.id, c.nombre
                                   FROM clientes c
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
        // Obtener sucursal del cliente
        $cliente = obtenerRegistro("SELECT id_sucursal FROM clientes WHERE id = ?", [$id_cliente]);
        $id_sucursal = $cliente ? $cliente['id_sucursal'] : getSucursalId();

        // Manejar upload de archivo
        $archivo_adjunto = null;
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['archivo'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, ALLOWED_EXTENSIONS)) {
                $error = 'Tipo de archivo no permitido. Solo PDF, JPG, JPEG, PNG.';
            } elseif ($archivo['size'] > MAX_UPLOAD_SIZE) {
                $error = 'El archivo excede el tamaño máximo permitido (5MB).';
            } else {
                $nombreArchivo = 'seg_' . time() . '_' . uniqid() . '.' . $extension;
                $rutaDestino = UPLOADS_PATH . '/' . $nombreArchivo;

                if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    $archivo_adjunto = $nombreArchivo;
                } else {
                    $error = 'Error al subir el archivo.';
                }
            }
        }

        if (empty($error)) {
            $sql = "INSERT INTO seguimientos (id_cliente, id_vendedor, id_tipo, fecha_hora, duracion_minutos,
                    asunto, observaciones, proxima_accion, fecha_proxima, archivo_adjunto, id_sucursal, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$id_cliente, $id_vendedor, $id_tipo, $fecha_hora, $duracion, $asunto,
                       $observaciones, $proxima_accion, $fecha_proxima, $archivo_adjunto, $id_sucursal, getUsuarioId()];

            $id = insertarRegistro($sql, $params);

            if ($id) {
                registrarAuditoria('crear', 'seguimientos', $id, "Nuevo seguimiento para cliente ID $id_cliente");
                header('Location: index.php?msg=creado');
                exit;
            } else {
                $error = 'Error al registrar el seguimiento.';
            }
        }
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Nuevo Seguimiento</h1>
    <p class="page-subtitle">Registrar visita, llamada u otro tipo de contacto</p>
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
                    <option value="">Seleccione cliente</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?php echo $cli['id']; ?>"
                            <?php echo (isset($_POST['id_cliente']) && $_POST['id_cliente'] == $cli['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitizar($cli['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Vendedor *</label>
                <select name="id_vendedor" class="form-control" required>
                    <?php if (isVendedor() && count($vendedores) == 1): ?>
                        <option value="<?php echo $vendedores[0]['id']; ?>" selected>
                            <?php echo sanitizar($vendedores[0]['nombre']); ?>
                        </option>
                    <?php else: ?>
                        <option value="">Seleccione vendedor</option>
                        <?php foreach ($vendedores as $ven): ?>
                            <option value="<?php echo $ven['id']; ?>"
                                <?php echo (isset($_POST['id_vendedor']) && $_POST['id_vendedor'] == $ven['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitizar($ven['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tipo de Seguimiento *</label>
                <select name="id_tipo" class="form-control" required>
                    <option value="">Seleccione tipo</option>
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?php echo $tipo['id']; ?>"
                            <?php echo (isset($_POST['id_tipo']) && $_POST['id_tipo'] == $tipo['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitizar($tipo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Fecha y Hora *</label>
                <input type="datetime-local" name="fecha_hora" class="form-control" required
                       value="<?php echo isset($_POST['fecha_hora']) ? $_POST['fecha_hora'] : date('Y-m-d\TH:i'); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Duración (minutos)</label>
                <input type="number" name="duracion" class="form-control" min="1" max="480"
                       value="<?php echo isset($_POST['duracion']) ? $_POST['duracion'] : ''; ?>"
                       placeholder="Opcional">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Asunto *</label>
            <input type="text" name="asunto" class="form-control" required maxlength="200"
                   value="<?php echo isset($_POST['asunto']) ? $_POST['asunto'] : ''; ?>"
                   placeholder="Breve descripción del motivo">
        </div>

        <div class="form-group">
            <label class="form-label">Observaciones / Resultados</label>
            <textarea name="observaciones" class="form-control" rows="4"
                      placeholder="Detalle de la conversación, acuerdos, compromisos..."><?php echo isset($_POST['observaciones']) ? $_POST['observaciones'] : ''; ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Próxima Acción</label>
                <input type="text" name="proxima_accion" class="form-control" maxlength="500"
                       value="<?php echo isset($_POST['proxima_accion']) ? $_POST['proxima_accion'] : ''; ?>"
                       placeholder="Qué se debe hacer después">
            </div>

            <div class="form-group">
                <label class="form-label">Fecha Próxima Acción</label>
                <input type="date" name="fecha_proxima" class="form-control"
                       value="<?php echo isset($_POST['fecha_proxima']) ? $_POST['fecha_proxima'] : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Archivo Adjunto (PDF, JPG, PNG - Max 5MB)</label>
            <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            <div id="filePreview" class="form-text"></div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success">Registrar Seguimiento</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
previsualizarArchivo('archivo', 'filePreview');
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
