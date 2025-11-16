<?php
/**
 * Mini CRM - Eliminar Seguimiento
 */
require_once __DIR__ . '/../../includes/auth.php';
requerirAcceso('seguimientos');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCSRF()) {
    header('Location: index.php');
    exit;
}

// Solo admin y supervisor pueden eliminar
if (!isAdmin() && !isSupervisor()) {
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    $seguimiento = obtenerRegistro("SELECT * FROM seguimientos WHERE id = ?", [$id]);

    if ($seguimiento) {
        // Verificar permisos de sucursal
        if (!isAdmin() && $seguimiento['id_sucursal'] != getSucursalId()) {
            header('Location: index.php');
            exit;
        }

        // Eliminar archivo adjunto si existe
        if ($seguimiento['archivo_adjunto'] && file_exists(UPLOADS_PATH . '/' . $seguimiento['archivo_adjunto'])) {
            unlink(UPLOADS_PATH . '/' . $seguimiento['archivo_adjunto']);
        }

        // Eliminar registro
        $sql = "DELETE FROM seguimientos WHERE id = ?";
        ejecutarConsulta($sql, [$id]);

        registrarAuditoria('eliminar', 'seguimientos', $id, "Eliminación de seguimiento");
    }
}

header('Location: index.php?msg=eliminado');
exit;
