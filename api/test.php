<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password = 'admin123';

echo "Hash: " . $hash . "<br>";
echo "Contraseña: " . $password . "<br>";
echo "Verificación: " . (password_verify($password, $hash) ? "✅ OK" : "❌ FALLO") . "<br>";
?>