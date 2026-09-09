<?php
session_start();
if (isset($_SESSION['usuario_id'])) { 
    header('Location: index.php'); 
    exit; 
}
require_once '../config/database.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    
    if (empty($email) || empty($contrasena)) {
        $error = 'Complete todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        // COMPARACIÓN EN TEXTO PLANO (TEMPORAL)
        if ($usuario && $contrasena == $usuario['contrasena']) {
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['rol'] = $usuario['rol'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MAS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>MAS</h1>
            <p>Modelo de Asignación de Salas</p>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="correo@ejemplo.cl" required>
                </div>
                
                <div class="form-group">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Ingrese su contraseña" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
            </form>
            
            <p class="register-link">
                ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
            </p>
            
            <div class="demo-credentials">
                <strong>Demo:</strong> admin@mas.cl / admin123 | docente@mas.cl / docente123<br>
                <small>secretaria@mas.cl / secretaria123 | visualizador@mas.cl / visualizador123</small>
            </div>
        </div>
    </div>
</body>
</html>