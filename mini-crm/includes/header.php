<?php
/**
 * Mini CRM - Header y Navegación Principal
 */
require_once __DIR__ . '/auth.php';
requerirAutenticacion();

// Obtener nombre de sucursal actual
$sucursalActual = obtenerRegistro("SELECT nombre FROM sucursales WHERE id = ?", [getSucursalId()]);
$nombreSucursal = $sucursalActual ? $sucursalActual['nombre'] : 'Sin asignar';

// Determinar página activa
$paginaActual = basename($_SERVER['PHP_SELF'], '.php');
$moduloActual = isset($moduloActual) ? $moduloActual : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($tituloPagina) ? $tituloPagina . ' - ' : ''; ?>Mini CRM</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="<?php echo BASE_URL; ?>/index.php" class="navbar-brand">
                &#128188; Mini CRM
            </a>

            <button class="navbar-toggle" onclick="toggleMenu()">&#9776;</button>

            <ul class="navbar-menu" id="navbarMenu">
                <li>
                    <a href="<?php echo BASE_URL; ?>/index.php"
                       class="<?php echo $paginaActual === 'index' ? 'active' : ''; ?>">
                        Inicio
                    </a>
                </li>

                <?php if (checkAccess('catalogos')): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/modules/catalogos/index.php"
                       class="<?php echo $moduloActual === 'catalogos' ? 'active' : ''; ?>">
                        Catálogos
                    </a>
                </li>
                <?php endif; ?>

                <?php if (checkAccess('seguimientos')): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/modules/seguimientos/index.php"
                       class="<?php echo $moduloActual === 'seguimientos' ? 'active' : ''; ?>">
                        Seguimientos
                    </a>
                </li>
                <?php endif; ?>

                <?php if (checkAccess('asignaciones')): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/modules/asignaciones/index.php"
                       class="<?php echo $moduloActual === 'asignaciones' ? 'active' : ''; ?>">
                        Asignaciones
                    </a>
                </li>
                <?php endif; ?>

                <?php if (checkAccess('reportes')): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/modules/reportes/index.php"
                       class="<?php echo $moduloActual === 'reportes' ? 'active' : ''; ?>">
                        Reportes
                    </a>
                </li>
                <?php endif; ?>

                <?php if (checkAccess('auditoria')): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/modules/auditoria/index.php"
                       class="<?php echo $moduloActual === 'auditoria' ? 'active' : ''; ?>">
                        Auditoría
                    </a>
                </li>
                <?php endif; ?>

                <?php if (checkAccess('usuarios')): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/modules/usuarios/index.php"
                       class="<?php echo $moduloActual === 'usuarios' ? 'active' : ''; ?>">
                        Usuarios
                    </a>
                </li>
                <?php endif; ?>

                <li>
                    <a href="<?php echo BASE_URL; ?>/perfil.php"
                       class="<?php echo $paginaActual === 'perfil' ? 'active' : ''; ?>">
                        Perfil
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>/logout.php">
                        Cerrar Sesión
                    </a>
                </li>
            </ul>

            <div class="navbar-user">
                <div class="navbar-user-info">
                    <div class="navbar-user-name"><?php echo sanitizar(getNombreUsuario()); ?></div>
                    <div class="navbar-user-role">
                        <?php echo ucfirst(getRolUsuario()); ?> - <?php echo sanitizar($nombreSucursal); ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
