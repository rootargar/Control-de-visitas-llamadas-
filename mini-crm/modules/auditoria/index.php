<?php
/**
 * Mini CRM - Módulo de Auditoría (Solo Admin)
 */
$moduloActual = 'auditoria';
$tituloPagina = 'Auditoría';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('auditoria');

// Filtros
$filtroUsuario = (int)($_GET['usuario'] ?? 0);
$filtroAccion = sanitizar($_GET['accion'] ?? '');
$filtroEntidad = sanitizar($_GET['entidad'] ?? '');
$filtroFechaDesde = sanitizar($_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-7 days')));
$filtroFechaHasta = sanitizar($_GET['fecha_hasta'] ?? date('Y-m-d'));

// Obtener usuarios para filtro
$usuarios = obtenerRegistros("SELECT id, username, nombre_completo FROM usuarios ORDER BY nombre_completo");

// Construir consulta
$where = [];
$params = [];

if ($filtroUsuario > 0) {
    $where[] = "a.id_usuario = ?";
    $params[] = $filtroUsuario;
}

if (!empty($filtroAccion)) {
    $where[] = "a.accion = ?";
    $params[] = $filtroAccion;
}

if (!empty($filtroEntidad)) {
    $where[] = "a.entidad LIKE ?";
    $params[] = "%$filtroEntidad%";
}

if (!empty($filtroFechaDesde)) {
    $where[] = "CAST(a.created_at AS DATE) >= ?";
    $params[] = $filtroFechaDesde;
}

if (!empty($filtroFechaHasta)) {
    $where[] = "CAST(a.created_at AS DATE) <= ?";
    $params[] = $filtroFechaHasta;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT TOP 500 a.*, u.username, u.nombre_completo, s.nombre as sucursal_nombre
        FROM auditoria a
        INNER JOIN usuarios u ON a.id_usuario = u.id
        LEFT JOIN sucursales s ON a.id_sucursal = s.id
        $whereClause
        ORDER BY a.created_at DESC";
$registros = obtenerRegistros($sql, $params);

// Obtener acciones únicas para filtro
$acciones = obtenerRegistros("SELECT DISTINCT accion FROM auditoria ORDER BY accion");
?>

<div class="page-header">
    <h1 class="page-title">Auditoría del Sistema</h1>
    <p class="page-subtitle">Registro de todas las acciones realizadas en el sistema</p>
</div>

<!-- Filtros -->
<div class="filters">
    <form method="GET" class="filters-row">
        <div class="form-group">
            <label class="form-label">Usuario</label>
            <select name="usuario" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $usr): ?>
                    <option value="<?php echo $usr['id']; ?>" <?php echo $filtroUsuario == $usr['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitizar($usr['nombre_completo']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Acción</label>
            <select name="accion" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($acciones as $acc): ?>
                    <option value="<?php echo sanitizar($acc['accion']); ?>" <?php echo $filtroAccion == $acc['accion'] ? 'selected' : ''; ?>>
                        <?php echo ucfirst(sanitizar($acc['accion'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Entidad</label>
            <input type="text" name="entidad" class="form-control" placeholder="Ej: clientes, usuarios..."
                   value="<?php echo $filtroEntidad; ?>">
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
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="index.php" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registros de Auditoría (<?php echo count($registros); ?>)</h3>
        <button onclick="exportarTablaCSV('tablaAuditoria', 'Auditoria_<?php echo date('Y-m-d'); ?>.csv')"
                class="btn btn-success btn-sm">Exportar CSV</button>
    </div>

    <div class="table-responsive">
        <table class="table" id="tablaAuditoria">
            <thead>
                <tr>
                    <th>Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Entidad</th>
                    <th>ID Entidad</th>
                    <th>Descripción</th>
                    <th>IP</th>
                    <th>Sucursal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?php echo date(DATETIME_FORMAT, strtotime($reg['created_at'])); ?></td>
                    <td>
                        <strong><?php echo sanitizar($reg['username']); ?></strong><br>
                        <small class="text-muted"><?php echo sanitizar($reg['nombre_completo']); ?></small>
                    </td>
                    <td>
                        <?php
                        $badgeClass = 'badge-info';
                        switch($reg['accion']) {
                            case 'crear': $badgeClass = 'badge-success'; break;
                            case 'editar': $badgeClass = 'badge-warning'; break;
                            case 'eliminar': $badgeClass = 'badge-danger'; break;
                            case 'login': $badgeClass = 'badge-primary'; break;
                            case 'logout': $badgeClass = 'badge-secondary'; break;
                        }
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo ucfirst(sanitizar($reg['accion'])); ?>
                        </span>
                    </td>
                    <td><?php echo sanitizar($reg['entidad']); ?></td>
                    <td><?php echo $reg['id_entidad'] ?? '-'; ?></td>
                    <td><?php echo sanitizar($reg['descripcion']); ?></td>
                    <td><small><?php echo sanitizar($reg['ip_address']); ?></small></td>
                    <td><?php echo sanitizar($reg['sucursal_nombre'] ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
