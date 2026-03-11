<?php
// Configuración de la conexión a SQL Server
$servidor = "localhost";
$base_datos = "DB"; // Nombre de tu base de datos
$usuario_db = ""; 
$password_db = ""; 

try {
    $conexion = new PDO("sqlsrv:server=$servidor;Database=$base_datos", $usuario_db, $password_db);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Recibir datos personales
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $direccion = $_POST['direccion'];

    // 2. Recibir datos de cuenta
    $usu_nombre = $_POST['usu_nombre'];
    $password_plana = $_POST['password'];
    $tipo_usuario = $_POST['tipo_usuario']; // Recibimos el texto (Admin, Doctor, etc.)
    
    // Encriptar la contraseña por seguridad
    $password_encriptada = password_hash($password_plana, PASSWORD_DEFAULT);

    // Iniciar Transacción
    $conexion->beginTransaction();

    // 3. Insertar el Usuario usando tu columna original TipoUsuario
    $sqlUsuario = "INSERT INTO Usuario (Usu_Nombre, Usu_Password, TipoUsuario) VALUES (:usu_nombre, :usu_password, :tipo_usuario)";
    $stmtUsuario = $conexion->prepare($sqlUsuario);
    $stmtUsuario->execute([
        ':usu_nombre' => $usu_nombre,
        ':usu_password' => $password_encriptada,
        ':tipo_usuario' => $tipo_usuario
    ]);

    // 4. Insertar la información de contacto en la tabla Personal
    $sqlPersonal = "INSERT INTO Personal (Nombre, Apellido, Cargo, Telefono, Correo, Direccion) 
                    VALUES (:nombre, :apellido, :cargo, :telefono, :correo, :direccion)";
    $stmtPersonal = $conexion->prepare($sqlPersonal);
    $stmtPersonal->execute([
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':cargo' => $tipo_usuario, // Usamos el mismo texto para el cargo
        ':telefono' => $telefono,
        ':correo' => $correo,
        ':direccion' => $direccion
    ]);

    // Confirmar los cambios
    $conexion->commit();

    // Mensaje de éxito
    echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #2d7cb5;'>¡Personal Registrado!</h2>";
    echo "<p>El registro de <strong>$nombre $apellido</strong> con el rol de <strong>$tipo_usuario</strong> fue creado exitosamente.</p>";
    echo "<a href='login_admin.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #2d7cb5; color: white; text-decoration: none; border-radius: 5px;'>Ir a Iniciar Sesión</a>";
    echo "</div>";

} catch (PDOException $e) {
    // Si hay error, deshacer cambios
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #d9534f;'>Error en el Registro</h2>";
    
    if ($e->getCode() == 23000) {
        echo "<p>El Nombre de Usuario ya está en uso. Por favor, elige otro.</p>";
    } else {
        echo "<p>Ha ocurrido un problema: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<a href='register_admin.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #f0ad4e; color: white; text-decoration: none; border-radius: 5px;'>Volver a Intentar</a>";
    echo "</div>";
}

$conexion = null;
?>