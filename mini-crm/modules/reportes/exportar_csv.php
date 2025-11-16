<?php
/**
 * Mini CRM - Exportar Reporte a CSV
 */
require_once __DIR__ . '/../../includes/auth.php';
requerirAcceso('reportes');

// Construir misma consulta que en index.php
$filtroSucursal = isAdmin() ? (int)($_GET['sucursal'] ?? 0) : getSucursalId();
$filtroVendedor = (int)($_GET['vendedor'] ?? 0);
$filtroTipo = (int)($_GET['tipo'] ?? 0);
$filtroFechaDesde = sanitizar($_GET['fecha_desde'] ?? date('Y-m-01'));
$filtroFechaHasta = sanitizar($_GET['fecha_hasta'] ?? date('Y-m-d'));

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

$sql = "SELECT s.fecha_hora, ts.nombre as tipo, c.nombre as cliente, v.nombre as vendedor,
               s.asunto, s.observaciones, s.duracion_minutos, s.proxima_accion, s.fecha_proxima,
               suc.nombre as sucursal
        FROM seguimientos s
        INNER JOIN clientes c ON s.id_cliente = c.id
        INNER JOIN vendedores v ON s.id_vendedor = v.id
        INNER JOIN tipos_seguimiento ts ON s.id_tipo = ts.id
        INNER JOIN sucursales suc ON s.id_sucursal = suc.id
        $whereClause
        ORDER BY s.fecha_hora DESC";
$registros = obtenerRegistros($sql, $params);

// Generar nombre de archivo
$nombreArchivo = 'Reporte_Seguimientos_' . date('Y-m-d') . '.csv';

// Headers para descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Escribir BOM para UTF-8
echo "\xEF\xBB\xBF";

// Crear output stream
$output = fopen('php://output', 'w');

// Encabezados
$headers = [
    'Fecha/Hora',
    'Tipo',
    'Cliente',
    'Vendedor',
    'Asunto',
    'Observaciones',
    'Duracion (min)',
    'Proxima Accion',
    'Fecha Proxima',
    'Sucursal'
];
fputcsv($output, $headers);

// Datos
foreach ($registros as $reg) {
    $row = [
        date(DATETIME_FORMAT, strtotime($reg['fecha_hora'])),
        $reg['tipo'],
        $reg['cliente'],
        $reg['vendedor'],
        $reg['asunto'],
        $reg['observaciones'],
        $reg['duracion_minutos'] ?? '',
        $reg['proxima_accion'] ?? '',
        $reg['fecha_proxima'] ? date(DATE_FORMAT, strtotime($reg['fecha_proxima'])) : '',
        $reg['sucursal']
    ];
    fputcsv($output, $row);
}

fclose($output);

registrarAuditoria('exportar', 'reportes', null, "Exportación de reporte a CSV: $nombreArchivo");

exit;
