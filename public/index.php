<?php
session_start();

$isLoggedIn = isset($_SESSION['usuario_id']);
$userRole = $_SESSION['rol'] ?? null;
$userName = $_SESSION['nombre_completo'] ?? '';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAS - Modelo de Asignación de Salas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>MAS</h1>
                <span>Modelo de Asignación de Salas</span>
            </div>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="programacion.php">Programación</a></li>
                <?php if ($userRole === 'docente' || $userRole === 'secretaria'): ?>
                <li><a href="solicitar.php">Solicitar</a></li>
                <li><a href="solicitudes.php">Mis Solicitudes</a></li>
                <?php endif; ?>
                <?php if ($userRole === 'administrador'): ?>
                <li><a href="solicitar.php">Solicitar</a></li>
                <li><a href="solicitudes.php">Solicitudes</a></li>
                <li><a href="admin/salas.php">Administración</a></li>
                <?php endif; ?>
                <li><span>👤 <?php echo htmlspecialchars($userName); ?></span></li>
                <li><a href="logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="dashboard">
            <h2>Bienvenido(a) al Sistema MAS</h2>
            <p>Modelo de Asignación de Salas - Gestión de espacios físicos para la universidad.</p>
            <div class="cards">
                <div class="card">
                    <h3>📊 Programación</h3>
                    <p>Visualiza la disponibilidad de salas en tiempo real</p>
                    <a href="programacion.php" class="btn">Ver Programación</a>
                </div>
                <?php if ($userRole === 'docente' || $userRole === 'secretaria'): ?>
                <div class="card">
                    <h3>📝 Solicitar</h3>
                    <p>Solicita una sala para tus actividades académicas</p>
                    <a href="solicitar.php" class="btn">Solicitar</a>
                </div>
                <div class="card">
                    <h3>📋 Mis Solicitudes</h3>
                    <p>Consulta el estado de tus solicitudes</p>
                    <a href="solicitudes.php" class="btn">Ver Solicitudes</a>
                </div>
                <?php endif; ?>
                <?php if ($userRole === 'administrador'): ?>
                <div class="card admin">
                    <h3>⚙️ Administración</h3>
                    <p>Gestiona salas, usuarios, bloques y más</p>
                    <a href="admin/salas.php" class="btn">Administrar</a>
                </div>
                <?php endif; ?>
                <?php if ($userRole === 'visualizador'): ?>
                <div class="card" style="border-left:4px solid #17a2b8;">
                    <h3>👁️ Visualizador</h3>
                    <p>Modo de solo lectura - Consulta la programación</p>
                    <span style="color:#17a2b8;font-size:12px;">🔒 Solo visualización</span>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <footer>
        <p>&copy; 2026 MAS - Modelo de Asignación de Salas. Proyecto de Título.</p>
    </footer>
    <script src="js/main.js"></script>
</body>
</html>