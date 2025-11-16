<?php
/**
 * Mini CRM - Exportar Reporte a PDF (HTML para imprimir)
 * Nota: Para PDF real, se requeriría biblioteca como TCPDF o FPDF
 * Esta versión genera HTML optimizado para impresión/guardado como PDF
 */
require_once __DIR__ . '/../../includes/auth.php';
requerirAcceso('reportes');

// Construir misma consulta
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
               s.asunto, s.duracion_minutos, suc.nombre as sucursal
        FROM seguimientos s
        INNER JOIN clientes c ON s.id_cliente = c.id
        INNER JOIN vendedores v ON s.id_vendedor = v.id
        INNER JOIN tipos_seguimiento ts ON s.id_tipo = ts.id
        INNER JOIN sucursales suc ON s.id_sucursal = suc.id
        $whereClause
        ORDER BY s.fecha_hora DESC";
$registros = obtenerRegistros($sql, $params);

$nombreArchivo = 'Reporte_Seguimientos_' . date('Y-m-d');

registrarAuditoria('exportar', 'reportes', null, "Exportación de reporte a PDF: $nombreArchivo");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $nombreArchivo; ?></title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .info {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background: #4a5568;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .stat-box {
            text-align: center;
            padding: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            min-width: 150px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4a5568;
        }
        .stat-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #fef3c7; padding: 10px; margin-bottom: 20px; text-align: center;">
        <strong>Instrucciones:</strong> Use Ctrl+P (o Cmd+P en Mac) para imprimir o guardar como PDF.
        <button onclick="window.print()" style="margin-left: 10px; padding: 5px 15px; cursor: pointer;">Imprimir/Guardar PDF</button>
        <button onclick="window.close()" style="margin-left: 5px; padding: 5px 15px; cursor: pointer;">Cerrar</button>
    </div>

    <div class="header">
        <h1>Reporte de Seguimientos</h1>
        <p>Mini CRM - Control de Visitas y Llamadas</p>
    </div>

    <div class="info">
        <p><strong>Fecha de generación:</strong> <?php echo date(DATETIME_FORMAT); ?></p>
        <p><strong>Período:</strong> <?php echo date(DATE_FORMAT, strtotime($filtroFechaDesde)); ?> al <?php echo date(DATE_FORMAT, strtotime($filtroFechaHasta)); ?></p>
        <p><strong>Generado por:</strong> <?php echo sanitizar(getNombreUsuario()); ?></p>
        <p><strong>Total de registros:</strong> <?php echo count($registros); ?></p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-value"><?php echo count($registros); ?></div>
            <div class="stat-label">Total Seguimientos</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo array_sum(array_column($registros, 'duracion_minutos')); ?></div>
            <div class="stat-label">Minutos Totales</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo count(array_unique(array_column($registros, 'vendedor'))); ?></div>
            <div class="stat-label">Vendedores</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha/Hora</th>
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
                <td><?php echo sanitizar($reg['tipo']); ?></td>
                <td><?php echo sanitizar($reg['cliente']); ?></td>
                <td><?php echo sanitizar($reg['vendedor']); ?></td>
                <td><?php echo sanitizar($reg['asunto']); ?></td>
                <td><?php echo $reg['duracion_minutos'] ? $reg['duracion_minutos'] . ' min' : '-'; ?></td>
                <td><?php echo sanitizar($reg['sucursal']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Documento generado automáticamente por Mini CRM</p>
        <p><?php echo $nombreArchivo; ?>.pdf</p>
    </div>
</body>
</html>
