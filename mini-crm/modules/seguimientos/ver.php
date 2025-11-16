<?php
/**
 * Mini CRM - Ver Detalle de Seguimiento
 */
$moduloActual = 'seguimientos';
$tituloPagina = 'Detalle de Seguimiento';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('seguimientos');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$sql = "SELECT s.*, c.nombre as cliente_nombre, c.telefono1 as cliente_telefono, c.email as cliente_email,
               v.nombre as vendedor_nombre, v.codigo as vendedor_codigo,
               ts.nombre as tipo_nombre, suc.nombre as sucursal_nombre,
               u.nombre_completo as creado_por_nombre
        FROM seguimientos s
        INNER JOIN clientes c ON s.id_cliente = c.id
        INNER JOIN vendedores v ON s.id_vendedor = v.id
        INNER JOIN tipos_seguimiento ts ON s.id_tipo = ts.id
        INNER JOIN sucursales suc ON s.id_sucursal = suc.id
        INNER JOIN usuarios u ON s.created_by = u.id
        WHERE s.id = ?";
$seguimiento = obtenerRegistro($sql, [$id]);

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

registrarAuditoria('visualizar', 'seguimientos', $id, 'Consulta de detalle de seguimiento');
?>

<div class="page-header">
    <h1 class="page-title">Detalle de Seguimiento #<?php echo $id; ?></h1>
    <p class="page-subtitle">Información completa del registro</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="badge badge-info"><?php echo sanitizar($seguimiento['tipo_nombre']); ?></span>
            <?php echo sanitizar($seguimiento['asunto']); ?>
        </h3>
        <div>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">Editar</a>
            <a href="index.php" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>

    <div class="form-row">
        <div>
            <h4 style="margin-bottom: 1rem; color: var(--color-primary-dark);">Información General</h4>

            <p><strong>Fecha y Hora:</strong><br>
                <?php echo date(DATETIME_FORMAT, strtotime($seguimiento['fecha_hora'])); ?>
            </p>

            <p><strong>Tipo:</strong><br>
                <?php echo sanitizar($seguimiento['tipo_nombre']); ?>
            </p>

            <?php if ($seguimiento['duracion_minutos']): ?>
            <p><strong>Duración:</strong><br>
                <?php echo $seguimiento['duracion_minutos']; ?> minutos
            </p>
            <?php endif; ?>

            <p><strong>Sucursal:</strong><br>
                <?php echo sanitizar($seguimiento['sucursal_nombre']); ?>
            </p>

            <p><strong>Registrado por:</strong><br>
                <?php echo sanitizar($seguimiento['creado_por_nombre']); ?><br>
                <small class="text-muted"><?php echo date(DATETIME_FORMAT, strtotime($seguimiento['created_at'])); ?></small>
            </p>
        </div>

        <div>
            <h4 style="margin-bottom: 1rem; color: var(--color-primary-dark);">Cliente</h4>

            <p><strong>Nombre:</strong><br>
                <?php echo sanitizar($seguimiento['cliente_nombre']); ?>
            </p>

            <?php if ($seguimiento['cliente_telefono']): ?>
            <p><strong>Teléfono:</strong><br>
                <?php echo sanitizar($seguimiento['cliente_telefono']); ?>
            </p>
            <?php endif; ?>

            <?php if ($seguimiento['cliente_email']): ?>
            <p><strong>Email:</strong><br>
                <?php echo sanitizar($seguimiento['cliente_email']); ?>
            </p>
            <?php endif; ?>

            <h4 style="margin: 1.5rem 0 1rem; color: var(--color-primary-dark);">Vendedor</h4>

            <p><strong>Nombre:</strong><br>
                <?php echo sanitizar($seguimiento['vendedor_nombre']); ?>
            </p>

            <p><strong>Código:</strong><br>
                <span class="badge badge-primary"><?php echo sanitizar($seguimiento['vendedor_codigo']); ?></span>
            </p>
        </div>
    </div>

    <hr style="margin: 1.5rem 0; border-color: var(--color-border);">

    <h4 style="margin-bottom: 1rem; color: var(--color-primary-dark);">Observaciones / Resultados</h4>
    <div style="background: var(--color-bg-light); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem;">
        <?php if ($seguimiento['observaciones']): ?>
            <?php echo nl2br(sanitizar($seguimiento['observaciones'])); ?>
        <?php else: ?>
            <em class="text-muted">Sin observaciones registradas</em>
        <?php endif; ?>
    </div>

    <?php if ($seguimiento['proxima_accion'] || $seguimiento['fecha_proxima']): ?>
    <h4 style="margin-bottom: 1rem; color: var(--color-primary-dark);">Próxima Acción</h4>
    <div style="background: #fefcbf; padding: 1rem; border-radius: var(--border-radius); border: 1px solid #fbd38d; margin-bottom: 1.5rem;">
        <?php if ($seguimiento['fecha_proxima']): ?>
            <p><strong>Fecha programada:</strong> <?php echo date(DATE_FORMAT, strtotime($seguimiento['fecha_proxima'])); ?></p>
        <?php endif; ?>
        <?php if ($seguimiento['proxima_accion']): ?>
            <p><strong>Acción:</strong> <?php echo sanitizar($seguimiento['proxima_accion']); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($seguimiento['archivo_adjunto']): ?>
    <h4 style="margin-bottom: 1rem; color: var(--color-primary-dark);">Archivo Adjunto</h4>
    <p>
        <a href="<?php echo BASE_URL . '/assets/uploads/' . $seguimiento['archivo_adjunto']; ?>"
           target="_blank" class="btn btn-secondary">
            Descargar: <?php echo sanitizar($seguimiento['archivo_adjunto']); ?>
        </a>
    </p>
    <?php endif; ?>

    <div class="mt-3">
        <a href="index.php" class="btn btn-secondary">Volver a la Lista</a>
        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">Editar Seguimiento</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
