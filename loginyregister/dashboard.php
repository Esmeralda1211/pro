<?php
// 1. Abrimos la sesión para buscar la "llave"
session_start();

// 2. EL CANDADO (Solo para el Dashboard Principal)
// Si la llave (usu_id) NO existe, lo regresamos al login inmediatamente
if (!isset($_SESSION['usu_id'])) { 
    header("Location: login_admin.html"); 
    exit(); 
}

// 3. Indicamos en qué página estamos para pintar el menú
$pagina_actual = 'dashboard'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - BEA</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Panel de Control</h1>
            <div class="user-info">
                <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['usu_nombre']); ?></strong></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['usu_nombre'], 0, 1)); ?></div>
            </div>
        </header>

        <div class="caja-blanca">
            <h2>Bienvenido a BEA</h2>
            <p>Este es el panel principal. Navega usando el menú de la izquierda.</p>
        </div>
    </main>

</body>
</html>