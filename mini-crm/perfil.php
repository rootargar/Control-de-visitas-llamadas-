<?php
/**
 * Mini CRM - Perfil de Usuario
 */
$tituloPagina = 'Mi Perfil';
require_once __DIR__ . '/includes/header.php';

$mensaje = '';
$error = '';

// Obtener datos del usuario actual
$usuario = obtenerRegistro("SELECT * FROM usuarios WHERE id = ?", [getUsuarioId()]);
$sucursal = obtenerRegistro("SELECT nombre FROM sucursales WHERE id = ?", [$usuario['id_sucursal']]);

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificarCSRF()) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'cambiar_password') {
        $passwordActual = $_POST['password_actual'] ?? '';
        $passwordNuevo = $_POST['password_nuevo'] ?? '';
        $passwordConfirmar = $_POST['password_confirmar'] ?? '';

        if (empty($passwordActual) || empty($passwordNuevo) || empty($passwordConfirmar)) {
            $error = 'Todos los campos de contraseña son obligatorios.';
        } elseif ($passwordNuevo !== $passwordConfirmar) {
            $error = 'Las contraseñas nuevas no coinciden.';
        } elseif (strlen($passwordNuevo) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            // Verificar contraseña actual
            $passwordValido = false;
            if (strlen($usuario['password']) > 50) {
                $passwordValido = password_verify($passwordActual, $usuario['password']);
            } else {
                $passwordValido = ($passwordActual === $usuario['password']);
            }

            if (!$passwordValido) {
                $error = 'La contraseña actual es incorrecta.';
            } else {
                // Actualizar contraseña
                $nuevoHash = hashPassword($passwordNuevo);
                actualizarRegistro("UPDATE usuarios SET password = ?, updated_at = GETDATE() WHERE id = ?",
                                  [$nuevoHash, getUsuarioId()]);
                registrarAuditoria('editar', 'usuarios', getUsuarioId(), 'Cambio de contraseña propio');
                $mensaje = 'Contraseña actualizada exitosamente.';
            }
        }
    } elseif ($accion === 'actualizar_perfil') {
        $email = sanitizar($_POST['email'] ?? '');
        $nombre = sanitizar($_POST['nombre_completo'] ?? '');

        if (empty($nombre)) {
            $error = 'El nombre es obligatorio.';
        } else {
            actualizarRegistro("UPDATE usuarios SET nombre_completo = ?, email = ?, updated_at = GETDATE() WHERE id = ?",
                              [$nombre, $email, getUsuarioId()]);

            $_SESSION['nombre_completo'] = $nombre;
            $_SESSION['email'] = $email;

            registrarAuditoria('editar', 'usuarios', getUsuarioId(), 'Actualización de perfil');
            $mensaje = 'Perfil actualizado exitosamente.';

            // Recargar datos
            $usuario = obtenerRegistro("SELECT * FROM usuarios WHERE id = ?", [getUsuarioId()]);
        }
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Mi Perfil</h1>
    <p class="page-subtitle">Administración de cuenta de usuario</p>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?php echo $mensaje; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="form-row">
    <!-- Información del perfil -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Información Personal</h3>
        </div>

        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="actualizar_perfil">

            <div class="form-group">
                <label class="form-label">Usuario</label>
                <input type="text" class="form-control" value="<?php echo sanitizar($usuario['username']); ?>" disabled>
                <small class="form-text">El nombre de usuario no se puede cambiar.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre Completo *</label>
                <input type="text" name="nombre_completo" class="form-control" required
                       value="<?php echo sanitizar($usuario['nombre_completo']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo sanitizar($usuario['email']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Rol</label>
                <input type="text" class="form-control" value="<?php echo ucfirst($usuario['rol']); ?>" disabled>
            </div>

            <div class="form-group">
                <label class="form-label">Sucursal Asignada</label>
                <input type="text" class="form-control" value="<?php echo sanitizar($sucursal['nombre']); ?>" disabled>
            </div>

            <div class="form-group">
                <label class="form-label">Último Acceso</label>
                <input type="text" class="form-control"
                       value="<?php echo $usuario['ultimo_acceso'] ? date(DATETIME_FORMAT, strtotime($usuario['ultimo_acceso'])) : 'Nunca'; ?>"
                       disabled>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>

    <!-- Cambiar contraseña -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Cambiar Contraseña</h3>
        </div>

        <form method="POST">
            <?php echo getCSRFField(); ?>
            <input type="hidden" name="accion" value="cambiar_password">

            <div class="form-group">
                <label class="form-label">Contraseña Actual *</label>
                <input type="password" name="password_actual" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Nueva Contraseña *</label>
                <input type="password" name="password_nuevo" class="form-control" required minlength="6">
                <small class="form-text">Mínimo 6 caracteres.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Confirmar Nueva Contraseña *</label>
                <input type="password" name="password_confirmar" class="form-control" required minlength="6">
            </div>

            <button type="submit" class="btn btn-warning">Cambiar Contraseña</button>
        </form>
    </div>
</div>

<!-- Información de sesión -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Información de Sesión Actual</h3>
    </div>

    <div class="form-row">
        <div>
            <p><strong>Sucursal en Sesión:</strong><br>
                <?php echo sanitizar($sucursal['nombre']); ?>
            </p>
        </div>
        <div>
            <p><strong>Sesión Iniciada:</strong><br>
                <?php echo isset($_SESSION['ultima_actividad']) ? date(DATETIME_FORMAT, $_SESSION['ultima_actividad']) : '-'; ?>
            </p>
        </div>
        <div>
            <p><strong>Tiempo de Sesión:</strong><br>
                <?php echo round((time() - ($_SESSION['ultima_actividad'] ?? time())) / 60); ?> minutos
            </p>
        </div>
        <div>
            <p><strong>IP Actual:</strong><br>
                <?php echo $_SERVER['REMOTE_ADDR']; ?>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
