<?php
/**
 * Mini CRM - Historial de Asignaciones
 */
$moduloActual = 'asignaciones';
$tituloPagina = 'Historial de Asignaciones';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('asignaciones');

// Obtener historial
$where = [];
$params = [];

if (!isAdmin()) {
    $where[] = "c.id_sucursal = ?";
    $params[] = getSucursalId();
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT ac.*, c.nombre as cliente_nombre, v.nombre as vendedor_nombre,
               u.nombre_completo as asignado_por_nombre
        FROM asignaciones_clientes ac
        INNER JOIN clientes c ON ac.id_cliente = c.id
        INNER JOIN vendedores v ON ac.id_vendedor = v.id
        INNER JOIN usuarios u ON ac.asignado_por = u.id
        $whereClause
        ORDER BY ac.created_at DESC";
$historial = obtenerRegistros($sql, $params);
?>

<div class="page-header">
    <h1 class="page-title">Historial de Asignaciones</h1>
    <p class="page-subtitle">Registro histórico de todas las asignaciones</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Historial Completo</h3>
        <a href="index.php" class="btn btn-secondary btn-sm">Volver</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha Asignación</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Motivo</th>
                    <th>Asignado Por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $h): ?>
                <tr>
                    <td><?php echo date(DATETIME_FORMAT, strtotime($h['fecha_asignacion'])); ?></td>
                    <td><?php echo sanitizar($h['cliente_nombre']); ?></td>
                    <td><?php echo sanitizar($h['vendedor_nombre']); ?></td>
                    <td>
                        <?php if ($h['fecha_fin']): ?>
                            <?php echo date(DATETIME_FORMAT, strtotime($h['fecha_fin'])); ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($h['activo']): ?>
                            <span class="badge badge-success">Activa</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo sanitizar($h['motivo_cambio'] ?? '-'); ?></td>
                    <td><?php echo sanitizar($h['asignado_por_nombre']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
