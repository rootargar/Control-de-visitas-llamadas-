<?php
/**
 * Mini CRM - Archivo de Configuración
 * Configuraciones globales de la aplicación
 */

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de sesión
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('SESSION_NAME', 'MINICRM_SESSION');

// Rutas de la aplicación
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('MODULES_PATH', BASE_PATH . '/modules');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', ASSETS_PATH . '/uploads');

// URL base (ajustar según instalación)
define('BASE_URL', '/mini-crm');

// Configuración de uploads
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// Formato de fechas
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');

// Roles del sistema
define('ROL_ADMIN', 'admin');
define('ROL_SUPERVISOR', 'supervisor');
define('ROL_VENDEDOR', 'vendedor');

// Sucursales disponibles
$SUCURSALES_DISPONIBLES = [
    'Matriz',
    'Mazatlan',
    'Mochis',
    'Guasave',
    'Guamuchil',
    'TRP Mazatlan'
];

// Configuración de errores (cambiar a false en producción)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Incluir archivo de conexión
require_once INCLUDES_PATH . '/conexion.php';
