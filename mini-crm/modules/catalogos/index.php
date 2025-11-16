<?php
/**
 * Mini CRM - Índice de Catálogos
 */
$moduloActual = 'catalogos';
$tituloPagina = 'Catálogos';
require_once __DIR__ . '/../../includes/header.php';
requerirAcceso('catalogos');
?>

<div class="page-header">
    <h1 class="page-title">Catálogos</h1>
    <p class="page-subtitle">Administración de catálogos del sistema</p>
</div>

<div class="dashboard-grid">
    <?php if (checkAccess('vendedores')): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Vendedores</h3>
        </div>
        <p>Gestione el catálogo de vendedores y sus sucursales asignadas.</p>
        <a href="vendedores.php" class="btn btn-primary">Administrar Vendedores</a>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Clientes</h3>
        </div>
        <p>Administre la información de clientes, contactos y datos fiscales.</p>
        <a href="clientes.php" class="btn btn-primary">Administrar Clientes</a>
    </div>

    <?php if (isAdmin()): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sucursales</h3>
        </div>
        <p>Configure las sucursales disponibles en el sistema.</p>
        <a href="sucursales.php" class="btn btn-primary">Administrar Sucursales</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tipos de Seguimiento</h3>
        </div>
        <p>Defina los tipos de seguimiento (visita, llamada, etc.).</p>
        <a href="tipos_seguimiento.php" class="btn btn-primary">Administrar Tipos</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
