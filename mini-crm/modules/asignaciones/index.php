<?php
/**
 * Mini CRM - Asignaciones de Clientes a Vendedores
 */
$moduloActual = 'asignaciones';
$tituloPagina = 'Asignaciones';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('asignaciones');

$mensaje = '';
$error = '';

// Obtener vendedores y clientes según permisos
if (isAdmin()) {
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE activo = 1 ORDER BY nombre");
    $clientes = obtenerRegistros("SELECT id, nombre FROM clientes WHERE activo = 1 ORDER BY nombre");
} else {
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE id_sucursal = ? AND activo = 1 ORDER BY nombre", [getSucursalId()]);
    $clientes = obtenerRegistros("SELECT id, nombre FROM clientes WHERE id_sucursal = ? AND activo = 1 ORDER BY nombre", [getSucursalId()]);
}

// Procesar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificarCSRF()) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'asignar') {
        $id_cliente = (int)$_POST['id_cliente'];
        $id_vendedor = (int)$_POST['id_vendedor'];
        $motivo = sanitizar($_POST['motivo'] ?? '');

        if ($id_cliente <= 0 || $id_vendedor <= 0) {
            $error = 'Debe seleccionar cliente y vendedor.';
        } else {
            // Verificar si ya tiene asignación activa
            $asignacionActual = obtenerRegistro("SELECT id FROM asignaciones_clientes WHERE id_cliente = ? AND activo = 1", [$id_cliente]);

            if ($asignacionActual) {
                // Desactivar asignación anterior
                actualizarRegistro("UPDATE asignaciones_clientes SET activo = 0, fecha_fin = GETDATE(), motivo_cambio = ? WHERE id = ?",
                                  [$motivo, $asignacionActual['id']]);
            }

            // Crear nueva asignación
            $sql = "INSERT INTO asignaciones_clientes (id_cliente, id_vendedor, asignado_por, motivo_cambio)
                    VALUES (?, ?, ?, ?)";
            $id = insertarRegistro($sql, [$id_cliente, $id_vendedor, getUsuarioId(), $motivo]);

            if ($id) {
                registrarAuditoria('crear', 'asignaciones_clientes', $id, "Asignación de cliente $id_cliente a vendedor $id_vendedor");
                $mensaje = 'Cliente asignado exitosamente.';
            } else {
                $error = 'Error al asignar el cliente.';
            }
        }
    } elseif ($accion === 'desasignar') {
        $id = (int)$_POST['id'];

        actualizarRegistro("UPDATE asignaciones_clientes SET activo = 0, fecha_fin = GETDATE() WHERE id = ?", [$id]);
        registrarAuditoria('editar', 'asignaciones_clientes', $id, "Desasignación de cliente");
        $mensaje = 'Cliente desasignado exitosamente.';
    }
}

// Filtro por vendedor
$filtroVendedor = (int)($_GET['vendedor'] ?? 0);

// Obtener asignaciones activas
$where = ["ac.activo = 1"];
$params = [];

if (!isAdmin()) {
    $where[] = "c.id_sucursal = ?";
    $params[] = getSucursalId();
}

if ($filtroVendedor > 0) {
    $where[] = "ac.id_vendedor = ?";
    $params[] = $filtroVendedor;
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT ac.*, c.nombre as cliente_nombre, v.nombre as vendedor_nombre, v.codigo as vendedor_codigo,
               u.nombre_completo as asignado_por_nombre, suc.nombre as sucursal_nombre
        FROM asignaciones_clientes ac
        INNER JOIN clientes c ON ac.id_cliente = c.id
        INNER JOIN vendedores v ON ac.id_vendedor = v.id
        INNER JOIN usuarios u ON ac.asignado_por = u.id
        INNER JOIN sucursales suc ON c.id_sucursal = suc.id
        WHERE $whereClause
        ORDER BY v.nombre, c.nombre";
$asignaciones = obtenerRegistros($sql, $params);

// Clientes sin asignar
$sqlSinAsignar = "SELECT c.id, c.nombre, suc.nombre as sucursal_nombre
                  FROM clientes c
                  INNER JOIN sucursales suc ON c.id_sucursal = suc.id
                  WHERE c.activo = 1
                  AND c.id NOT IN (SELECT id_cliente FROM asignaciones_clientes WHERE activo = 1)";
if (!isAdmin()) {
    $sqlSinAsignar .= " AND c.id_sucursal = " . getSucursalId();
}
$sqlSinAsignar .= " ORDER BY c.nombre";
$clientesSinAsignar = obtenerRegistros($sqlSinAsignar);
?>

<div class="page-header">
    <h1 class="page-title">Asignaciones de Clientes</h1>
    <p class="page-subtitle">Gestión de asignación de clientes a vendedores</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?php echo $mensaje; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filtro -->
<div class="filters">
    <form method="GET" class="filters-row">
        <div class="form-group">
            <label class="form-label">Filtrar por Vendedor</label>
            <select name="vendedor" class="form-control" onchange="this.form.submit()">
                <option value="">Todos los vendedores</option>
                <?php foreach ($vendedores as $ven): ?>
                    <option value="<?php echo $ven['id']; ?>" <?php echo $filtroVendedor == $ven['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitizar($ven['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <a href="index.php" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="form-row">
    <!-- Nueva Asignación -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nueva Asignación</h3>
        </div>

        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="asignar">

            <div class="form-group">
                <label class="form-label">Cliente *</label>
                <select name="id_cliente" class="form-control" required>
                    <option value="">Seleccione cliente</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?php echo $cli['id']; ?>">
                            <?php echo sanitizar($cli['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Vendedor *</label>
                <select name="id_vendedor" class="form-control" required>
                    <option value="">Seleccione vendedor</option>
                    <?php foreach ($vendedores as $ven): ?>
                        <option value="<?php echo $ven['id']; ?>">
                            <?php echo sanitizar($ven['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Motivo (opcional)</label>
                <input type="text" name="motivo" class="form-control" placeholder="Razón de la asignación o cambio">
            </div>

            <button type="submit" class="btn btn-success btn-block">Asignar Cliente</button>
        </form>
    </div>

    <!-- Clientes sin asignar -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Clientes Sin Asignar (<?php echo count($clientesSinAsignar); ?>)</h3>
        </div>

        <?php if (empty($clientesSinAsignar)): ?>
            <p class="text-muted">Todos los clientes están asignados.</p>
        <?php else: ?>
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Sucursal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientesSinAsignar as $cli): ?>
                        <tr>
                            <td><?php echo sanitizar($cli['nombre']); ?></td>
                            <td><?php echo sanitizar($cli['sucursal_nombre']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Lista de asignaciones activas -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Asignaciones Activas (<?php echo count($asignaciones); ?>)</h3>
        <a href="historial.php" class="btn btn-secondary btn-sm">Ver Historial</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Vendedor</th>
                    <th>Cliente</th>
                    <th>Sucursal</th>
                    <th>Fecha Asignación</th>
                    <th>Asignado Por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asignaciones as $asig): ?>
                <tr>
                    <td>
                        <span class="badge badge-primary"><?php echo sanitizar($asig['vendedor_codigo']); ?></span>
                        <?php echo sanitizar($asig['vendedor_nombre']); ?>
                    </td>
                    <td><?php echo sanitizar($asig['cliente_nombre']); ?></td>
                    <td><?php echo sanitizar($asig['sucursal_nombre']); ?></td>
                    <td><?php echo date(DATE_FORMAT, strtotime($asig['fecha_asignacion'])); ?></td>
                    <td><?php echo sanitizar($asig['asignado_por_nombre']); ?></td>
                    <td class="table-actions">
                        <form method="POST" style="display:inline;" onsubmit="return confirmarEliminar('¿Desea desasignar este cliente?')">
                            <?php echo getCSRFField(); ?>
                            <input type="hidden" name="accion" value="desasignar">
                            <input type="hidden" name="id" value="<?php echo $asig['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-warning">Desasignar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
