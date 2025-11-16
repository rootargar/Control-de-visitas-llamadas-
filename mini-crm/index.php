<?php
/**
 * Mini CRM - Dashboard Principal
 */
$tituloPagina = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// Obtener métricas según rol y sucursal
$filtroSucursal = getFiltroSucursal('s');
$filtroVendedor = '';

if (isVendedor()) {
    $vendedorId = getVendedorId();
    if ($vendedorId) {
        $filtroVendedor = " AND s.id_vendedor = " . $vendedorId;
    }
}

// Total de visitas este mes
$sqlVisitasMes = "SELECT COUNT(*) as total
                  FROM seguimientos s
                  WHERE $filtroSucursal
                  AND MONTH(s.fecha_hora) = MONTH(GETDATE())
                  AND YEAR(s.fecha_hora) = YEAR(GETDATE())
                  $filtroVendedor";
$visitasMes = obtenerRegistro($sqlVisitasMes);
$totalVisitasMes = $visitasMes ? $visitasMes['total'] : 0;

// Total de clientes
if (isVendedor() && $vendedorId) {
    $sqlClientes = "SELECT COUNT(DISTINCT ac.id_cliente) as total
                    FROM asignaciones_clientes ac
                    INNER JOIN clientes c ON ac.id_cliente = c.id
                    WHERE ac.id_vendedor = ? AND ac.activo = 1";
    $clientes = obtenerRegistro($sqlClientes, [$vendedorId]);
} else {
    $filtroSucursalCliente = getFiltroSucursal('c');
    $sqlClientes = "SELECT COUNT(*) as total FROM clientes c WHERE $filtroSucursalCliente AND c.activo = 1";
    $clientes = obtenerRegistro($sqlClientes);
}
$totalClientes = $clientes ? $clientes['total'] : 0;

// Total de vendedores
if (!isVendedor()) {
    $filtroSucursalVendedor = getFiltroSucursal('v');
    $sqlVendedores = "SELECT COUNT(*) as total FROM vendedores v WHERE $filtroSucursalVendedor AND v.activo = 1";
    $vendedores = obtenerRegistro($sqlVendedores);
    $totalVendedores = $vendedores ? $vendedores['total'] : 0;
}

// Próximas acciones (próximos 7 días)
$sqlProximas = "SELECT COUNT(*) as total
                FROM seguimientos s
                WHERE $filtroSucursal
                AND s.fecha_proxima IS NOT NULL
                AND s.fecha_proxima BETWEEN GETDATE() AND DATEADD(day, 7, GETDATE())
                $filtroVendedor";
$proximas = obtenerRegistro($sqlProximas);
$totalProximas = $proximas ? $proximas['total'] : 0;

// Visitas por vendedor (top 5 este mes)
if (!isVendedor()) {
    $sqlTopVendedores = "SELECT TOP 5 v.nombre, COUNT(s.id) as total_visitas
                          FROM vendedores v
                          LEFT JOIN seguimientos s ON v.id = s.id_vendedor
                              AND MONTH(s.fecha_hora) = MONTH(GETDATE())
                              AND YEAR(s.fecha_hora) = YEAR(GETDATE())
                          WHERE " . getFiltroSucursal('v') . " AND v.activo = 1
                          GROUP BY v.id, v.nombre
                          ORDER BY total_visitas DESC";
    $topVendedores = obtenerRegistros($sqlTopVendedores);
}

// Últimos seguimientos
$sqlUltimos = "SELECT TOP 10 s.*, c.nombre as cliente_nombre, v.nombre as vendedor_nombre,
                      ts.nombre as tipo_nombre
               FROM seguimientos s
               INNER JOIN clientes c ON s.id_cliente = c.id
               INNER JOIN vendedores v ON s.id_vendedor = v.id
               INNER JOIN tipos_seguimiento ts ON s.id_tipo = ts.id
               WHERE " . getFiltroSucursal('s') . "
               $filtroVendedor
               ORDER BY s.fecha_hora DESC";
$ultimosSeguimientos = obtenerRegistros($sqlUltimos);

// Próximas acciones detalladas
$sqlProximasDetalle = "SELECT TOP 5 s.*, c.nombre as cliente_nombre, v.nombre as vendedor_nombre
                       FROM seguimientos s
                       INNER JOIN clientes c ON s.id_cliente = c.id
                       INNER JOIN vendedores v ON s.id_vendedor = v.id
                       WHERE " . getFiltroSucursal('s') . "
                       AND s.fecha_proxima IS NOT NULL
                       AND s.fecha_proxima >= GETDATE()
                       $filtroVendedor
                       ORDER BY s.fecha_proxima ASC";
$proximasAcciones = obtenerRegistros($sqlProximasDetalle);
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Resumen de actividades y métricas principales</p>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'acceso_denegado'): ?>
    <div class="alert alert-danger">No tiene permisos para acceder a ese módulo.</div>
<?php endif; ?>

<!-- Tarjetas de métricas -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalVisitasMes; ?></div>
        <div class="stat-label">Seguimientos este mes</div>
    </div>

    <div class="stat-card">
        <div class="stat-value"><?php echo $totalClientes; ?></div>
        <div class="stat-label">Clientes <?php echo isVendedor() ? 'asignados' : 'activos'; ?></div>
    </div>

    <?php if (!isVendedor()): ?>
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalVendedores; ?></div>
        <div class="stat-label">Vendedores activos</div>
    </div>
    <?php endif; ?>

    <div class="stat-card">
        <div class="stat-value"><?php echo $totalProximas; ?></div>
        <div class="stat-label">Acciones próximos 7 días</div>
    </div>
</div>

<div class="form-row">
    <!-- Próximas acciones -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Próximas Acciones Programadas</h3>
        </div>

        <?php if (empty($proximasAcciones)): ?>
            <p class="text-muted">No hay acciones programadas próximamente.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <?php if (!isVendedor()): ?>
                            <th>Vendedor</th>
                            <?php endif; ?>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximasAcciones as $accion): ?>
                        <tr>
                            <td><?php echo date(DATE_FORMAT, strtotime($accion['fecha_proxima'])); ?></td>
                            <td><?php echo sanitizar($accion['cliente_nombre']); ?></td>
                            <?php if (!isVendedor()): ?>
                            <td><?php echo sanitizar($accion['vendedor_nombre']); ?></td>
                            <?php endif; ?>
                            <td><?php echo sanitizar($accion['proxima_accion']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Top vendedores -->
    <?php if (!isVendedor() && !empty($topVendedores)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Top Vendedores (Este Mes)</h3>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vendedor</th>
                        <th>Seguimientos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topVendedores as $vendedor): ?>
                    <tr>
                        <td><?php echo sanitizar($vendedor['nombre']); ?></td>
                        <td>
                            <span class="badge badge-primary">
                                <?php echo $vendedor['total_visitas']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Últimos seguimientos -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Últimos Seguimientos Registrados</h3>
        <a href="<?php echo BASE_URL; ?>/modules/seguimientos/index.php" class="btn btn-secondary btn-sm">
            Ver todos
        </a>
    </div>

    <?php if (empty($ultimosSeguimientos)): ?>
        <p class="text-muted">No hay seguimientos registrados.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <?php if (!isVendedor()): ?>
                        <th>Vendedor</th>
                        <?php endif; ?>
                        <th>Asunto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimosSeguimientos as $seg): ?>
                    <tr>
                        <td><?php echo date(DATETIME_FORMAT, strtotime($seg['fecha_hora'])); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo sanitizar($seg['tipo_nombre']); ?>
                            </span>
                        </td>
                        <td><?php echo sanitizar($seg['cliente_nombre']); ?></td>
                        <?php if (!isVendedor()): ?>
                        <td><?php echo sanitizar($seg['vendedor_nombre']); ?></td>
                        <?php endif; ?>
                        <td><?php echo sanitizar($seg['asunto']); ?></td>
                        <td class="table-actions">
                            <a href="<?php echo BASE_URL; ?>/modules/seguimientos/ver.php?id=<?php echo $seg['id']; ?>"
                               class="btn btn-sm btn-secondary">Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
