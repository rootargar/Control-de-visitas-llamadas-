<?php
/**
 * Mini CRM - Página de Login
 */
require_once __DIR__ . '/includes/auth.php';

// Si ya está autenticado, redirigir al dashboard
if (estaAutenticado()) {
    header('Location: index.php');
    exit;
}

$error = '';
$sucursales = obtenerRegistros("SELECT id, nombre FROM sucursales WHERE activo = 1 ORDER BY nombre");

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizar($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $id_sucursal = (int)($_POST['id_sucursal'] ?? 0);

    if (empty($username) || empty($password) || $id_sucursal <= 0) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $usuario = autenticarUsuario($username, $password, $id_sucursal);

        if ($usuario) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas o no tiene acceso a la sucursal seleccionada.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mini CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <span>&#128188;</span>
            </div>
            <h1 class="login-title">Mini CRM</h1>
            <p class="text-center text-muted mb-3">Control de Visitas y Llamadas</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?php echo isset($username) ? $username : ''; ?>"
                           placeholder="Ingrese su usuario" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Ingrese su contraseña" required>
                </div>

                <div class="form-group">
                    <label for="id_sucursal" class="form-label">Sucursal</label>
                    <select id="id_sucursal" name="id_sucursal" class="form-control" required>
                        <option value="">Seleccione una sucursal</option>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?php echo $sucursal['id']; ?>"
                                <?php echo (isset($id_sucursal) && $id_sucursal == $sucursal['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitizar($sucursal['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Iniciar Sesión
                    </button>
                </div>
            </form>

            <p class="text-center text-muted mt-3" style="font-size: 0.8rem;">
                Sistema de Control de Visitas y Llamadas<br>
                &copy; <?php echo date('Y'); ?> - Tu Empresa
            </p>
        </div>
    </div>
</body>
</html>
