<?php
// Configuración de la conexión a SQL Server
$servidor = "localhost";
$base_datos = "DB"; // Pon el nombre real de tu BD
$usuario_db = ""; 
$password_db = ""; 

try {
    $conexion = new PDO("sqlsrv:server=$servidor;Database=$base_datos", $usuario_db, $password_db);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Recibir datos del formulario (Paciente)
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $cedula = $_POST['cedula'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];

    // 2. Recibir datos del formulario (Usuario)
    $usu_nombre = $_POST['usu_nombre'];
    $password_plana = $_POST['password'];
    
    // Encriptar la contraseña
    $password_encriptada = password_hash($password_plana, PASSWORD_DEFAULT);

    // Iniciar transacción
    $conexion->beginTransaction();

    // 3. Insertar primero el Usuario (¡AQUÍ AGREGAMOS 'Paciente'!)
    $sqlUsuario = "INSERT INTO Usuario (Usu_Nombre, Usu_Password, TipoUsuario) VALUES (:usu_nombre, :usu_password, 'Paciente')";
    $stmtUsuario = $conexion->prepare($sqlUsuario);
    $stmtUsuario->execute([
        ':usu_nombre' => $usu_nombre,
        ':usu_password' => $password_encriptada
    ]);

    // Obtener el ID del usuario recién creado
    $usuario_id = $conexion->lastInsertId();

    // 4. Insertar el Paciente, vinculando su nueva cuenta ($usuario_id)
    $sqlPaciente = "INSERT INTO Pacientes (Cedula, Nombre, Apellido, Telefono, Correo, Usu_Id) 
                    VALUES (:cedula, :nombre, :apellido, :telefono, :correo, :usu_id)";
    $stmtPaciente = $conexion->prepare($sqlPaciente);
    $stmtPaciente->execute([
        ':cedula' => $cedula,
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':telefono' => $telefono,
        ':correo' => $correo,
        ':usu_id' => $usuario_id
    ]);

    // Si todo salió bien, confirmamos (commit)
    $conexion->commit();

    // Mensaje de éxito
    echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #2d7cb5;'>¡Registro Exitoso!</h2>";
    echo "<p>La cuenta de paciente para <strong>$nombre $apellido</strong> ha sido creada correctamente.</p>";
    echo "<a href='login.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #2d7cb5; color: white; text-decoration: none; border-radius: 5px;'>Ir a Iniciar Sesión</a>";
    echo "</div>";

} catch (PDOException $e) {
    // Si hay error, deshacer (rollback) cualquier cambio a medias
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    
    // Manejo de errores amigable
    echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #d9534f;'>Error en el Registro</h2>";
    
    // Verificar si es un error de duplicado (Cédula o Usuario ya existen)
    if ($e->getCode() == 23000) {
        echo "<p>El Nombre de Usuario o la Cédula ya están registrados en el sistema.</p>";
    } else {
        echo "<p>Ha ocurrido un problema: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<a href='vista_registro.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #f0ad4e; color: white; text-decoration: none; border-radius: 5px;'>Volver a Intentar</a>";
    echo "</div>";
}

$conexion = null;
?>