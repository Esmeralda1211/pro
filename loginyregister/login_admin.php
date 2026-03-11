<?php
$servidor = "localhost"; 
$base_datos = "DB"; 
$usuario_db = ""; 
$password_db = ""; 

try {
    $conexion = new PDO("sqlsrv:server=$servidor;Database=$base_datos", $usuario_db, $password_db);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_ingresado = $_POST['usuario']; 
    $password_ingresada = $_POST['password'];

    // Buscamos los datos del usuario directamente en la tabla Usuario
    $sql = "SELECT * FROM Usuario WHERE Usu_Nombre = :usuario";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':usuario' => $usuario_ingresado]);
    $usuario_encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    // Definimos qué tipos de usuario pueden entrar a este panel
    $roles_permitidos = ['Admin', 'Doctor', 'Recepcionista'];

    // Validamos contraseña Y verificamos que el tipo de usuario esté en la lista permitida
    if ($usuario_encontrado && password_verify($password_ingresada, $usuario_encontrado['Usu_Password']) && in_array($usuario_encontrado['TipoUsuario'], $roles_permitidos)) {
        
        // ¡Login Exitoso! Creamos la "llave" de sesión
        session_start();
        $_SESSION['usu_id'] = $usuario_encontrado['Usu_Id']; 
        $_SESSION['usu_nombre'] = $usuario_encontrado['Usu_Nombre'];
        $_SESSION['tipo_usuario'] = $usuario_encontrado['TipoUsuario']; // Guardamos el rol (ej. 'Doctor')
        
        // Redirección inmediata al Dashboard
        header("Location: dashboard.php"); 
        exit();

    } else {
        echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
        echo "<h2 style='color: #d9534f;'>Acceso Denegado</h2>";
        echo "<p>Credenciales incorrectas o no tienes permisos de acceso administrativo.</p>";
        echo "<a href='login_admin.html' style='display: inline-block; margin-top: 20px; margin-right: 10px; padding: 10px 20px; background-color: #f0ad4e; color: white; text-decoration: none; border-radius: 5px;'>Volver a intentar</a>";
        
        // Botón en caso de que no tenga usuario y necesite registrarse
        echo "<br><br><p>¿No tienes una cuenta administrativa?</p>";
        echo "<a href='register_admin.html' style='display: inline-block; padding: 10px 20px; background-color: #5bc0de; color: white; text-decoration: none; border-radius: 5px;'>Registrarse aquí</a>";
        echo "</div>";
    }

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
$conexion = null;
?>