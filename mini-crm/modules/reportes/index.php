<?php
/**
 * Mini CRM - Módulo de Reportes
 */
$moduloActual = 'reportes';
$tituloPagina = 'Reportes';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('reportes');

// Obtener datos para filtros
$sucursales = obtenerRegistros("SELECT id, nombre FROM sucursales WHERE activo = 1 ORDER BY nombre");
$tipos = obtenerRegistros("SELECT id, nombre FROM tipos_seguimiento WHERE activo = 1");

if (isAdmin()) {
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE activo = 1 ORDER BY nombre");
} elseif (isSupervisor()) {
    $vendedores = obtenerRegistros("SELECT id, nombre FROM vendedores WHERE id_sucursal = ? AND activo = 1 ORDER BY nombre", [getSucursalId()]);
} else {
    $vendedorId = getVendedorId();
    $vendedores = $vendedorId ? obtenerRegistros("SELECT id, nombre FROM vendedores WHERE id = ?", [$vendedorId]) : [];
}

// Filtros
$filtroSucursal = isAdmin() ? (int)($_GET['sucursal'] ?? 0) : getSucursalId();
$filtroVendedor = (int)($_GET['vendedor'] ?? 0);
$filtroTipo = (int)($_GET['tipo'] ?? 0);
$filtroFechaDesde = sanitizar($_GET['fecha_desde'] ?? date('Y-m-01'));
$filtroFechaHasta = sanitizar($_GET['fecha_hasta'] ?? date('Y-m-d'));

// Construir consulta
$where = [];
$params = [];

if (isVendedor()) {
    $vendedorId = getVendedorId();
    if ($vendedorId) {
        $where[] = "s.id_vendedor = ?";
        $params[] = $vendedorId;
    }
} elseif (!isAdmin()) {
    $where[] = "s.id_sucursal = ?";
    $params[] = getSucursalId();
} elseif ($filtroSucursal > 0) {
    $where[] = "s.id_sucursal = ?";
    $params[] = $filtroSucursal;
}

if ($filtroVendedor > 0 && !isVendedor()) {
    $where[] = "s.id_vendedor = ?";
    $params[] = $filtroVendedor;
}

if ($filtroTipo > 0) {
    $where[] = "s.id_tipo = ?";
    $params[] = $filtroTipo;
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

$sql = "SELECT s.*, c.nombre as cliente_nombre, v.nombre as vendedor_nombre, v.codigo as vendedor_codigo,
               ts.nombre as tipo_nombre, suc.nombre as sucursal_nombre
        FROM seguimientos s
        INNER JOIN clientes c ON s.id_cliente = c.id
        INNER JOIN vendedores v ON s.id_vendedor = v.id
        INNER JOIN tipos_seguimiento ts ON s.id_tipo = ts.id
        INNER JOIN sucursales suc ON s.id_sucursal = suc.id
        $whereClause
        ORDER BY s.fecha_hora DESC";
$registros = obtenerRegistros($sql, $params);

// Estadísticas
$totalRegistros = count($registros);
$totalDuracion = array_sum(array_column($registros, 'duracion_minutos'));
$promedioCalificacion = $totalRegistros > 0 ? round($totalDuracion / $totalRegistros, 1) : 0;

// Agrupar por tipo
$porTipo = [];
foreach ($registros as $reg) {
    $tipo = $reg['tipo_nombre'];
    if (!isset($porTipo[$tipo])) {
        $porTipo[$tipo] = 0;
    }
    $porTipo[$tipo]++;
}

// Agrupar por vendedor
$porVendedor = [];
foreach ($registros as $reg) {
    $vendedor = $reg['vendedor_nombre'];
    if (!isset($porVendedor[$vendedor])) {
        $porVendedor[$vendedor] = 0;
    }
    $porVendedor[$vendedor]++;
}
arsort($porVendedor);

registrarAuditoria('visualizar', 'reportes', null, "Consulta de reporte de seguimientos");
?>

<div class="page-header">
    <h1 class="page-title">Reportes de Seguimientos</h1>
    <p class="page-subtitle">Análisis y exportación de datos</p>
</div>

<!-- Filtros -->
<div class="filters">
    <form method="GET" class="filters-row">
        <?php if (isAdmin()): ?>
        <div class="form-group">
            <label class="form-label">Sucursal</label>
            <select name="sucursal" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($sucursales as $suc): ?>
                    <option value="<?php echo $suc['id']; ?>" <?php echo $filtroSucursal == $suc['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitizar($suc['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if (!isVendedor()): ?>
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

        <div class="form-group">
            <label class="form-label">Desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtroFechaDesde; ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtroFechaHasta; ?>">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Generar Reporte</button>
        </div>
    </form>
</div>

<!-- Resumen estadístico -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalRegistros; ?></div>
        <div class="stat-label">Total Seguimientos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalDuracion; ?></div>
        <div class="stat-label">Minutos Totales</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $promedioCalificacion; ?></div>
        <div class="stat-label">Promedio Min/Seguimiento</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count($porVendedor); ?></div>
        <div class="stat-label">Vendedores Activos</div>
    </div>
</div>

<div class="form-row">
    <!-- Por tipo -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Por Tipo de Seguimiento</h3>
        </div>
        <?php if (!empty($porTipo)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porTipo as $tipo => $cantidad): ?>
                <tr>
                    <td><?php echo sanitizar($tipo); ?></td>
                    <td><?php echo $cantidad; ?></td>
                    <td><?php echo round(($cantidad / $totalRegistros) * 100, 1); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted">Sin datos</p>
        <?php endif; ?>
    </div>

    <!-- Por vendedor -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Por Vendedor (Top 10)</h3>
        </div>
        <?php if (!empty($porVendedor)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Vendedor</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($porVendedor as $vendedor => $cantidad): if ($i++ >= 10) break; ?>
                <tr>
                    <td><?php echo sanitizar($vendedor); ?></td>
                    <td><span class="badge badge-primary"><?php echo $cantidad; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted">Sin datos</p>
        <?php endif; ?>
    </div>
</div>

<!-- Detalle y exportación -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Detalle de Seguimientos (<?php echo $totalRegistros; ?>)</h3>
        <div>
            <a href="exportar_csv.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-sm">
                Exportar CSV
            </a>
            <a href="exportar_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-danger btn-sm">
                Exportar PDF
            </a>
            <button onclick="imprimirContenido('tablaReporte')" class="btn btn-secondary btn-sm">
                Imprimir
            </button>
        </div>
    </div>

    <div id="tablaReporte">
        <div class="table-responsive">
            <table class="table" id="tablaExportar">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Asunto</th>
                        <th>Duración</th>
                        <th>Sucursal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $reg): ?>
                    <tr>
                        <td><?php echo date(DATETIME_FORMAT, strtotime($reg['fecha_hora'])); ?></td>
                        <td><?php echo sanitizar($reg['tipo_nombre']); ?></td>
                        <td><?php echo sanitizar($reg['cliente_nombre']); ?></td>
                        <td><?php echo sanitizar($reg['vendedor_nombre']); ?></td>
                        <td><?php echo sanitizar($reg['asunto']); ?></td>
                        <td><?php echo $reg['duracion_minutos'] ? $reg['duracion_minutos'] . ' min' : '-'; ?></td>
                        <td><?php echo sanitizar($reg['sucursal_nombre']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
