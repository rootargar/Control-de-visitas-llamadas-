<?php
/**
 * Mini CRM - Módulo de Seguimientos (Visitas/Llamadas)
 */
$moduloActual = 'seguimientos';
$tituloPagina = 'Seguimientos';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('seguimientos');

$mensaje = isset($_GET['msg']) ? sanitizar($_GET['msg']) : '';
$error = '';

// Obtener datos para filtros
$tipos = obtenerRegistros("SELECT id, nombre FROM tipos_seguimiento WHERE activo = 1");
$vendedores = [];
$clientes = [];

if (isAdmin()) {
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE activo = 1 ORDER BY nombre");
} elseif (isSupervisor()) {
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE id_sucursal = ? AND activo = 1 ORDER BY nombre", [getSucursalId()]);
}

// Filtros
$filtroTipo = (int)($_GET['tipo'] ?? 0);
$filtroVendedor = (int)($_GET['vendedor'] ?? 0);
$filtroFechaDesde = sanitizar($_GET['fecha_desde'] ?? '');
$filtroFechaHasta = sanitizar($_GET['fecha_hasta'] ?? '');

// Construir consulta
$where = [];
$params = [];

// Filtro por sucursal/vendedor según rol
if (isVendedor()) {
    $vendedorId = getVendedorId();
    if ($vendedorId) {
        $where[] = "s.id_vendedor = ?";
        $params[] = $vendedorId;
    }
} elseif (!isAdmin()) {
    $where[] = "s.id_sucursal = ?";
    $params[] = getSucursalId();
}

if ($filtroTipo > 0) {
    $where[] = "s.id_tipo = ?";
    $params[] = $filtroTipo;
}

if ($filtroVendedor > 0 && !isVendedor()) {
    $where[] = "s.id_vendedor = ?";
    $params[] = $filtroVendedor;
}

if (!empty($filtroFechaDesde)) {
    $where[] = "CAST(s.fecha_hora AS DATE) >= ?";
    $params[] = $filtroFechaDesde;
}

if (!empty($filtroFechaHasta)) {
    $where[] = "CAST(s.fecha_hora AS DATE) <= ?";
    $params[] = $filtroFechaHasta;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT TOP 100 s.*, c.nombre as cliente_nombre, v.nombre as vendedor_nombre,
               ts.nombre as tipo_nombre, suc.nombre as sucursal_nombre
        FROM seguimientos s
        INNER JOIN clientes c ON s.id_cliente = c.id
        INNER JOIN vendedores v ON s.id_vendedor = v.id
        INNER JOIN tipos_seguimiento ts ON s.id_tipo = ts.id
        INNER JOIN sucursales suc ON s.id_sucursal = suc.id
        $whereClause
        ORDER BY s.fecha_hora DESC";
$seguimientos = obtenerRegistros($sql, $params);
?>

<div class="page-header">
    <h1 class="page-title">Seguimientos</h1>
    <p class="page-subtitle">Registro y consulta de visitas y llamadas a clientes</p>
</div>

<?php if ($mensaje === 'creado'): ?>
    <div class="alert alert-success">Seguimiento registrado exitosamente.</div>
<?php elseif ($mensaje === 'editado'): ?>
    <div class="alert alert-success">Seguimiento actualizado exitosamente.</div>
<?php elseif ($mensaje === 'eliminado'): ?>
    <div class="alert alert-success">Seguimiento eliminado exitosamente.</div>
<?php endif; ?>

<!-- Filtros -->
<div class="filters">
    <form method="GET" class="filters-row">
        <div class="form-group">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>" <?php echo $filtroTipo == $tipo['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitizar($tipo['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!isVendedor() && !empty($vendedores)): ?>
        <div class="form-group">
            <label class="form-label">Vendedor</label>
            <select name="vendedor" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($vendedores as $ven): ?>
                    <option value="<?php echo $ven['id']; ?>" <?php echo $filtroVendedor == $ven['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitizar($ven['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtroFechaDesde; ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtroFechaHasta; ?>">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <a href="index.php" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Seguimientos (<?php echo count($seguimientos); ?>)</h3>
        <a href="crear.php" class="btn btn-success">Nuevo Seguimiento</a>
    </div>

    <div class="table-responsive">
        <table class="table" id="tablaSeguimientos">
            <thead>
                <tr>
                    <th>Fecha/Hora</th>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <?php if (!isVendedor()): ?>
                    <th>Vendedor</th>
                    <?php endif; ?>
                    <th>Asunto</th>
                    <th>Próxima Acción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($seguimientos as $seg): ?>
                <tr>
                    <td><?php echo date(DATETIME_FORMAT, strtotime($seg['fecha_hora'])); ?></td>
                    <td>
                        <span class="badge badge-info"><?php echo sanitizar($seg['tipo_nombre']); ?></span>
                    </td>
                    <td><?php echo sanitizar($seg['cliente_nombre']); ?></td>
                    <?php if (!isVendedor()): ?>
                    <td><?php echo sanitizar($seg['vendedor_nombre']); ?></td>
                    <?php endif; ?>
                    <td><?php echo sanitizar(substr($seg['asunto'], 0, 50)) . (strlen($seg['asunto']) > 50 ? '...' : ''); ?></td>
                    <td>
                        <?php if ($seg['fecha_proxima']): ?>
                            <small><?php echo date(DATE_FORMAT, strtotime($seg['fecha_proxima'])); ?></small><br>
                            <small class="text-muted"><?php echo sanitizar(substr($seg['proxima_accion'], 0, 30)); ?></small>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <a href="ver.php?id=<?php echo $seg['id']; ?>" class="btn btn-sm btn-secondary">Ver</a>
                        <a href="editar.php?id=<?php echo $seg['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                        <?php if (isAdmin() || isSupervisor()): ?>
                        <form method="POST" action="eliminar.php" style="display:inline;"
                              onsubmit="return confirmarEliminar('¿Desea eliminar este seguimiento?')">
                            <?php echo getCSRFField(); ?>
                            <input type="hidden" name="id" value="<?php echo $seg['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
