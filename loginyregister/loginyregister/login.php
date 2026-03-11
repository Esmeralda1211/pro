<?php
$servidor = "localhost"; 
$base_datos = "DB"; // Pon el nombre real de tu base de datos
$usuario_db = ""; 
$password_db = ""; 

try {
    $conexion = new PDO("sqlsrv:server=$servidor;Database=$base_datos", $usuario_db, $password_db);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_ingresado = $_POST['usuario']; 
    $password_ingresada = $_POST['password'];

    $sql = "SELECT * FROM Usuario WHERE Usu_Nombre = :usuario";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':usuario' => $usuario_ingresado]);
    $usuario_encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validamos contraseña Y verificamos que sea un 'Paciente'
    if ($usuario_encontrado && password_verify($password_ingresada, $usuario_encontrado['Usu_Password']) && $usuario_encontrado['TipoUsuario'] === 'Paciente') {
        
        // ¡Login Exitoso! Creamos la "llave" de sesión
        session_start();
        $_SESSION['usu_id'] = $usuario_encontrado['Usu_Id']; 
        $_SESSION['usu_nombre'] = $usuario_encontrado['Usu_Nombre'];
        $_SESSION['tipo_usuario'] = $usuario_encontrado['TipoUsuario'];
        
        // Redirección inmediata al Index principal
        header("Location: index.php"); // <--- AQUÍ ESTÁ EL CAMBIO
        exit();

    } else {
        echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
        echo "<h2 style='color: #d9534f;'>Error de credenciales</h2>";
        echo "<p>El usuario o contraseña son incorrectos, o no tienes cuenta de Paciente.</p>";
        echo "<a href='login.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #f0ad4e; color: white; text-decoration: none; border-radius: 5px;'>Volver a intentar</a>";
        echo "</div>";
    }

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
$conexion = null;
?>