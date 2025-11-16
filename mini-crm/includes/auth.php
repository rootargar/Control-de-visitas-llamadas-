<?php
/**
 * Mini CRM - Funciones de Autenticación y Autorización
 */

require_once __DIR__ . '/config.php';

/**
 * Iniciar sesión segura
 */
function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurar parámetros de sesión seguros
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

        session_name(SESSION_NAME);
        session_start();

        // Verificar expiración de sesión
        if (isset($_SESSION['ultima_actividad'])) {
            if (time() - $_SESSION['ultima_actividad'] > SESSION_LIFETIME) {
                cerrarSesion();
                return false;
            }
        }
        $_SESSION['ultima_actividad'] = time();
    }
    return true;
}

/**
 * Verificar si el usuario está autenticado
 *
 * @return bool
 */
function estaAutenticado() {
    iniciarSesionSegura();
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Requerir autenticación - redirige al login si no está autenticado
 */
function requerirAutenticacion() {
    if (!estaAutenticado()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Autenticar usuario
 *
 * @param string $username Usuario
 * @param string $password Contraseña
 * @param int $id_sucursal ID de sucursal seleccionada
 * @return array|false Datos del usuario o false
 */
function autenticarUsuario($username, $password, $id_sucursal) {
    $sql = "SELECT id, username, password, nombre_completo, email, rol, id_sucursal, activo
            FROM usuarios
            WHERE username = ? AND activo = 1";

    $usuario = obtenerRegistro($sql, [$username]);

    if (!$usuario) {
        return false;
    }

    // Verificar contraseña (soporta hash y texto plano para compatibilidad)
    $passwordValido = false;

    if (strlen($usuario['password']) > 50) {
        // Contraseña hasheada
        $passwordValido = password_verify($password, $usuario['password']);
    } else {
        // Contraseña en texto plano (no recomendado, solo para compatibilidad)
        $passwordValido = ($password === $usuario['password']);
    }

    if (!$passwordValido) {
        return false;
    }

    // Verificar permisos de sucursal
    if ($usuario['rol'] !== ROL_ADMIN && $usuario['id_sucursal'] != $id_sucursal) {
        // Supervisor y vendedor solo pueden acceder a su sucursal
        return false;
    }

    // Actualizar último acceso
    $sqlUpdate = "UPDATE usuarios SET ultimo_acceso = GETDATE() WHERE id = ?";
    ejecutarConsulta($sqlUpdate, [$usuario['id']]);

    // Crear sesión
    iniciarSesionSegura();
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['username'] = $usuario['username'];
    $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['id_sucursal'] = $id_sucursal; // Sucursal seleccionada en login
    $_SESSION['id_sucursal_usuario'] = $usuario['id_sucursal']; // Sucursal asignada al usuario
    $_SESSION['ultima_actividad'] = time();

    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);

    // Registrar en auditoría
    registrarAuditoria('login', 'sistema', null, 'Inicio de sesión exitoso');

    return $usuario;
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    iniciarSesionSegura();

    if (isset($_SESSION['usuario_id'])) {
        registrarAuditoria('logout', 'sistema', null, 'Cierre de sesión');
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Verificar si el usuario es Admin
 *
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === ROL_ADMIN;
}

/**
 * Verificar si el usuario es Supervisor
 *
 * @return bool
 */
function isSupervisor() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === ROL_SUPERVISOR;
}

/**
 * Verificar si el usuario es Vendedor
 *
 * @return bool
 */
function isVendedor() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === ROL_VENDEDOR;
}

/**
 * Obtener el ID del usuario actual
 *
 * @return int|null
 */
function getUsuarioId() {
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Obtener el ID de sucursal actual (seleccionada en login)
 *
 * @return int|null
 */
function getSucursalId() {
    return $_SESSION['id_sucursal'] ?? null;
}

/**
 * Obtener el nombre del usuario actual
 *
 * @return string
 */
function getNombreUsuario() {
    return $_SESSION['nombre_completo'] ?? 'Usuario';
}

/**
 * Obtener el rol del usuario actual
 *
 * @return string
 */
function getRolUsuario() {
    return $_SESSION['rol'] ?? '';
}

/**
 * Verificar acceso a un módulo específico
 *
 * @param string $modulo Nombre del módulo
 * @return bool
 */
function checkAccess($modulo) {
    if (!estaAutenticado()) {
        return false;
    }

    $permisos = [
        'dashboard' => [ROL_ADMIN, ROL_SUPERVISOR, ROL_VENDEDOR],
        'catalogos' => [ROL_ADMIN, ROL_SUPERVISOR],
        'clientes' => [ROL_ADMIN, ROL_SUPERVISOR, ROL_VENDEDOR],
        'vendedores' => [ROL_ADMIN, ROL_SUPERVISOR],
        'seguimientos' => [ROL_ADMIN, ROL_SUPERVISOR, ROL_VENDEDOR],
        'asignaciones' => [ROL_ADMIN, ROL_SUPERVISOR],
        'reportes' => [ROL_ADMIN, ROL_SUPERVISOR, ROL_VENDEDOR],
        'auditoria' => [ROL_ADMIN],
        'usuarios' => [ROL_ADMIN, ROL_SUPERVISOR],
        'perfil' => [ROL_ADMIN, ROL_SUPERVISOR, ROL_VENDEDOR]
    ];

    if (!isset($permisos[$modulo])) {
        return false;
    }

    return in_array(getRolUsuario(), $permisos[$modulo]);
}

/**
 * Requerir acceso a módulo - redirige si no tiene permiso
 *
 * @param string $modulo Nombre del módulo
 */
function requerirAcceso($modulo) {
    requerirAutenticacion();

    if (!checkAccess($modulo)) {
        header('Location: ' . BASE_URL . '/index.php?error=acceso_denegado');
        exit;
    }
}

/**
 * Verificar si puede ver datos de una sucursal específica
 *
 * @param int $id_sucursal ID de la sucursal
 * @return bool
 */
function puedeVerSucursal($id_sucursal) {
    if (isAdmin()) {
        return true; // Admin ve todas las sucursales
    }

    return getSucursalId() == $id_sucursal;
}

/**
 * Obtener filtro SQL de sucursal según rol
 *
 * @param string $alias Alias de tabla (opcional)
 * @return string Condición SQL
 */
function getFiltroSucursal($alias = '') {
    if (isAdmin()) {
        return '1=1'; // Sin filtro
    }

    $campo = $alias ? "{$alias}.id_sucursal" : "id_sucursal";
    return "{$campo} = " . getSucursalId();
}

/**
 * Obtener ID del vendedor asociado al usuario actual
 *
 * @return int|null
 */
function getVendedorId() {
    if (!isVendedor()) {
        return null;
    }

    $sql = "SELECT id FROM vendedores WHERE id_usuario = ?";
    $vendedor = obtenerRegistro($sql, [getUsuarioId()]);

    return $vendedor ? $vendedor['id'] : null;
}

/**
 * Verificar si el vendedor actual puede ver un cliente
 *
 * @param int $id_cliente ID del cliente
 * @return bool
 */
function puedeVerCliente($id_cliente) {
    if (isAdmin() || isSupervisor()) {
        return puedeVerSucursal(obtenerSucursalCliente($id_cliente));
    }

    if (isVendedor()) {
        $vendedor_id = getVendedorId();
        if (!$vendedor_id) return false;

        $sql = "SELECT 1 FROM asignaciones_clientes
                WHERE id_cliente = ? AND id_vendedor = ? AND activo = 1";
        $result = obtenerRegistro($sql, [$id_cliente, $vendedor_id]);

        return $result !== null;
    }

    return false;
}

/**
 * Obtener sucursal de un cliente
 *
 * @param int $id_cliente
 * @return int|null
 */
function obtenerSucursalCliente($id_cliente) {
    $sql = "SELECT id_sucursal FROM clientes WHERE id = ?";
    $cliente = obtenerRegistro($sql, [$id_cliente]);
    return $cliente ? $cliente['id_sucursal'] : null;
}

/**
 * Registrar acción en auditoría
 *
 * @param string $accion Tipo de acción
 * @param string $entidad Entidad afectada
 * @param int|null $id_entidad ID de la entidad
 * @param string $descripcion Descripción adicional
 */
function registrarAuditoria($accion, $entidad, $id_entidad = null, $descripcion = '') {
    $usuario_id = getUsuarioId() ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sucursal_id = getSucursalId();

    $sql = "INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, descripcion, ip_address, id_sucursal)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    ejecutarConsulta($sql, [$usuario_id, $accion, $entidad, $id_entidad, $descripcion, $ip, $sucursal_id]);
}

/**
 * Hash de contraseña seguro
 *
 * @param string $password Contraseña en texto plano
 * @return string Hash de la contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verificar token CSRF
 *
 * @return bool
 */
function verificarCSRF() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Generar token CSRF
 *
 * @return string
 */
function generarCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Obtener campo oculto con token CSRF
 *
 * @return string HTML del campo
 */
function getCSRFField() {
    return '<input type="hidden" name="csrf_token" value="' . generarCSRFToken() . '">';
}
