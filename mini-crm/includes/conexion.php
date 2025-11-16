<?php
/**
 * Mini CRM - Archivo de Conexión a MSSQL
 *
 * IMPORTANTE: Reemplace estos valores con su configuración real
 * o use su archivo conexion.php existente
 */

// Configuración de la base de datos MSSQL
$serverName = "localhost"; // o nombre del servidor SQL Server
$database = "mini_crm";
$username = "sa"; // usuario de SQL Server
$password = "your_password"; // contraseña

// Opciones de conexión
$connectionOptions = [
    "Database" => $database,
    "Uid" => $username,
    "PWD" => $password,
    "CharacterSet" => "UTF-8",
    "ReturnDatesAsStrings" => true,
    "TrustServerCertificate" => true
];

// Establecer conexión usando sqlsrv
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Error de conexión: " . print_r(sqlsrv_errors(), true));
    } else {
        die("Error de conexión a la base de datos. Contacte al administrador.");
    }
}

/**
 * Función helper para ejecutar consultas parametrizadas
 * Protege contra SQL Injection
 *
 * @param string $sql Consulta SQL con parámetros ?
 * @param array $params Array de parámetros
 * @return mixed Resultado de la consulta o false en caso de error
 */
function ejecutarConsulta($sql, $params = []) {
    global $conn;

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Error SQL: " . print_r(sqlsrv_errors(), true));
        }
        return false;
    }

    return $stmt;
}

/**
 * Obtener un solo registro
 *
 * @param string $sql Consulta SQL
 * @param array $params Parámetros
 * @return array|null Registro o null
 */
function obtenerRegistro($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    if ($stmt === false) return null;

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);

    return $row;
}

/**
 * Obtener múltiples registros
 *
 * @param string $sql Consulta SQL
 * @param array $params Parámetros
 * @return array Array de registros
 */
function obtenerRegistros($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    if ($stmt === false) return [];

    $rows = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $row;
    }
    sqlsrv_free_stmt($stmt);

    return $rows;
}

/**
 * Insertar registro y obtener ID generado
 *
 * @param string $sql Consulta INSERT
 * @param array $params Parámetros
 * @return int|false ID insertado o false
 */
function insertarRegistro($sql, $params = []) {
    global $conn;

    $stmt = ejecutarConsulta($sql, $params);
    if ($stmt === false) return false;

    sqlsrv_free_stmt($stmt);

    // Obtener el último ID insertado
    $result = sqlsrv_query($conn, "SELECT SCOPE_IDENTITY() AS id");
    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($result);

    return $row ? (int)$row['id'] : false;
}

/**
 * Actualizar registros
 *
 * @param string $sql Consulta UPDATE
 * @param array $params Parámetros
 * @return int Número de filas afectadas
 */
function actualizarRegistro($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    if ($stmt === false) return 0;

    $rowsAffected = sqlsrv_rows_affected($stmt);
    sqlsrv_free_stmt($stmt);

    return $rowsAffected;
}

/**
 * Sanitizar entrada para prevenir XSS
 *
 * @param string $input Entrada a sanitizar
 * @return string Entrada sanitizada
 */
function sanitizar($input) {
    if (is_array($input)) {
        return array_map('sanitizar', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Cerrar conexión (llamar al finalizar scripts si es necesario)
 */
function cerrarConexion() {
    global $conn;
    if ($conn) {
        sqlsrv_close($conn);
    }
}
