<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rut = trim($_POST['rut'] ?? '');
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $contrasena_confirm = $_POST['contrasena_confirm'] ?? '';

    if (empty($rut) || empty($nombre_completo) || empty($email) || empty($contrasena)) {
        $error = 'Por favor, complete todos los campos.';
    } elseif ($contrasena !== $contrasena_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($contrasena) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'El correo electrónico ya está registrado.';
            } else {
                $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE rut = ?");
                $stmt->execute([$rut]);
                if ($stmt->fetch()) {
                    $error = 'El RUT ya está registrado.';
                } else {
                    // Guardar contraseña en texto plano (TEMPORAL)
                    $stmt = $pdo->prepare("INSERT INTO usuarios (rut, nombre_completo, email, contrasena, rol) VALUES (?, ?, ?, ?, 'docente')");
                    $stmt->execute([$rut, $nombre_completo, $email, $contrasena]);
                    $success = '¡Registro exitoso! Ahora puedes iniciar sesión.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar usuario: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - MAS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>MAS</h1>
            <p>Crear una cuenta</p>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="rut">RUT</label>
                    <input type="text" id="rut" name="rut" placeholder="11.111.111-1" required>
                </div>
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="correo@ejemplo.cl" required>
                </div>
                <div class="form-group">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="form-group">
                    <label for="contrasena_confirm">Confirmar Contraseña</label>
                    <input type="password" id="contrasena_confirm" name="contrasena_confirm" placeholder="Repita la contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrarse</button>
            </form>
            
            <p class="register-link">
                ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
            </p>
        </div>
    </div>
</body>
</html>