<?php
session_start();
if (!isset($_SESSION['usu_id'])) { header("Location: login.html"); exit(); }

$servidor = "localhost"; $base_datos = "DB"; 
try {
    $conn = new PDO("sqlsrv:server=$servidor;Database=$base_datos", "", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Error de conexión: " . $e->getMessage()); }

$mensaje = "";
$tipo_msj = "green";

// --- 1. ELIMINAR CON PROTECCIÓN ---
if (isset($_GET['eliminar'])) {
    try {
        $cedula = $_GET['eliminar'];
        $stmt = $conn->prepare("SELECT Usu_Id FROM Pacientes WHERE Cedula = ?");
        $stmt->execute([$cedula]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res) {
            $conn->beginTransaction();
            $u_id = $res['Usu_Id'];
            $conn->prepare("DELETE FROM Pacientes WHERE Cedula = ?")->execute([$cedula]);
            $conn->prepare("DELETE FROM Usuario WHERE Usu_Id = ?")->execute([$u_id]);
            $conn->commit();
            header("Location: pacientes.php?m=ok"); exit();
        }
    } catch (Exception $e) { $conn->rollBack(); $mensaje = "Error: " . $e->getMessage(); $tipo_msj = "red"; }
}

// --- 2. GUARDAR / ACTUALIZAR (Mantenemos tu lógica pero sanitizada) ---
if (isset($_POST['btn_guardar'])) {
    try {
        $conn->beginTransaction();
        $pass = password_hash($_POST['cedula'], PASSWORD_DEFAULT);
        $stmtU = $conn->prepare("INSERT INTO Usuario (Usu_Nombre, Usu_Password, TipoUsuario) VALUES (?, ?, 'Paciente')");
        $stmtU->execute([$_POST['usuario'], $pass]);
        $usu_id = $conn->lastInsertId();

        $sqlP = "INSERT INTO Pacientes (Cedula, Nombre, Apellido, FechaNacimiento, Sexo, Telefono, Correo, Direccion, Estado, CondicionSalud, Usu_Id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $conn->prepare($sqlP)->execute([
            $_POST['cedula'], $_POST['nombre'], $_POST['apellido'], $_POST['fecha_nac'], 
            $_POST['sexo'], $_POST['tel'], $_POST['correo'], $_POST['direccion'], 
            $_POST['estado'], $_POST['condicion'], $usu_id
        ]);
        $conn->commit();
        $mensaje = "Paciente guardado.";
    } catch (Exception $e) { $conn->rollBack(); $mensaje = "Error: " . $e->getMessage(); $tipo_msj = "red"; }
}

if (isset($_POST['btn_actualizar'])) {
    try {
        $sql = "UPDATE Pacientes SET Nombre=?, Apellido=?, FechaNacimiento=?, Sexo=?, Telefono=?, Correo=?, Direccion=?, Estado=?, CondicionSalud=? WHERE Cedula=?";
        $conn->prepare($sql)->execute([
            $_POST['nombre'], $_POST['apellido'], $_POST['fecha_nac'], $_POST['sexo'], 
            $_POST['tel'], $_POST['correo'], $_POST['direccion'], $_POST['estado'], 
            $_POST['condicion'], $_POST['cedula_original']
        ]);
        $mensaje = "Actualizado.";
    } catch (Exception $e) { $mensaje = "Error: " . $e->getMessage(); $tipo_msj = "red"; }
}

// --- CONSULTA ---
$pacientes = $conn->query("SELECT * FROM Pacientes")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pacientes BEA</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .table-res { width: 100%; overflow-x: auto; background: white; padding: 15px; border-radius: 8px; }
        .tabla-pacientes { width: 100%; border-collapse: collapse; min-width: 1400px; }
        .tabla-pacientes th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 2% auto; padding: 20px; width: 500px; border-radius: 8px; border: 1px solid #ccc; }
        .f-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h1>Directorio de Pacientes</h1>
            <button type="button" onclick="document.getElementById('modalNuevo').style.display='block'" style="background:#2d7cb5; color:white; padding:10px; border:none; cursor:pointer;">+ Añadir Paciente</button>
        </header>

        <div class="table-res">
            <table class="tabla-pacientes">
                <thead>
                    <tr>
                        <th>Acciones</th><th>Cédula</th><th>Nombre</th><th>Estado</th><th>Sexo</th><th>Nacimiento</th><th>Teléfono</th><th>Dirección</th><th>Condición</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pacientes as $p): 
                        // Limpieza de datos para evitar errores en JS
                        $p_json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td>
                            <button type="button" onclick='cargarEdicion(<?php echo $p_json; ?>)' style="border:none; background:none; cursor:pointer;">✏️</button>
                            <a href="?eliminar=<?php echo $p['Cedula']; ?>" onclick="return confirm('¿Borrar?')">🗑️</a>
                        </td>
                        <td><strong><?php echo $p['Cedula']; ?></strong></td>
                        <td><?php echo $p['Nombre']." ".$p['Apellido']; ?></td>
                        <td><b style="color:<?php echo $p['Estado']=='Activo'?'green':'red';?>"><?php echo $p['Estado'] ?? 'Activo';?></b></td>
                        <td><?php echo $p['Sexo'];?></td>
                        <td><?php echo $p['FechaNacimiento'];?></td>
                        <td><?php echo $p['Telefono'];?></td>
                        <td><?php echo $p['Direccion'];?></td>
                        <td><small><?php echo $p['CondicionSalud'];?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalNuevo" class="modal">
        <div class="modal-content">
            <h3>Nuevo Paciente</h3>
            <form method="POST">
                <div class="f-grid">
                    <input type="text" name="cedula" placeholder="Cédula" required>
                    <input type="text" name="usuario" placeholder="Usuario Login" required>
                    <input type="text" name="nombre" placeholder="Nombre" required>
                    <input type="text" name="apellido" placeholder="Apellido" required>
                    <input type="date" name="fecha_nac">
                    <select name="sexo"><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select>
                </div>
                <select name="estado"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select>
                <input type="text" name="tel" placeholder="Teléfono">
                <input type="email" name="correo" placeholder="Email">
                <input type="text" name="direccion" placeholder="Dirección">
                <textarea name="condicion" placeholder="Condición Salud"></textarea>
                <button type="submit" name="btn_guardar" style="background:green; color:white; width:100%; padding:10px; margin-top:10px; border:none; cursor:pointer;">Guardar</button>
                <button type="button" onclick="this.parentElement.parentElement.parentElement.style.display='none'" style="width:100%; background:none; color:red; border:none; margin-top:5px; cursor:pointer;">Cancelar</button>
            </form>
        </div>
    </div>

    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <h3>Actualizar Datos</h3>
            <form method="POST">
                <input type="hidden" name="cedula_original" id="ed_ced_orig">
                <div class="f-grid">
                    <input type="text" id="ed_nombre" name="nombre" placeholder="Nombre">
                    <input type="text" id="ed_apellido" name="apellido" placeholder="Apellido">
                </div>
                <input type="date" id="ed_fecha" name="fecha_nac">
                <select id="ed_sexo" name="sexo"><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select>
                <select id="ed_estado" name="estado"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select>
                <input type="text" id="ed_tel" name="tel" placeholder="Teléfono">
                <input type="email" id="ed_correo" name="correo" placeholder="Email">
                <input type="text" id="ed_dir" name="direccion" placeholder="Dirección">
                <textarea id="ed_cond" name="condicion" placeholder="Condición"></textarea>
                <button type="submit" name="btn_actualizar" style="background:#007bff; color:white; width:100%; padding:10px; margin-top:10px; border:none; cursor:pointer;">Actualizar</button>
                <button type="button" onclick="document.getElementById('modalEditar').style.display='none'" style="width:100%; background:none; border:none; margin-top:5px; cursor:pointer;">Cerrar</button>
            </form>
        </div>
    </div>

    <script>
        function cargarEdicion(p) {
            document.getElementById('ed_ced_orig').value = p.Cedula || '';
            document.getElementById('ed_nombre').value = p.Nombre || '';
            document.getElementById('ed_apellido').value = p.Apellido || '';
            document.getElementById('ed_fecha').value = p.FechaNacimiento || '';
            document.getElementById('ed_sexo').value = p.Sexo || 'Masculino';
            document.getElementById('ed_estado').value = p.Estado || 'Activo';
            document.getElementById('ed_tel').value = p.Telefono || '';
            document.getElementById('ed_correo').value = p.Correo || '';
            document.getElementById('ed_dir').value = p.Direccion || '';
            document.getElementById('ed_cond').value = p.CondicionSalud || '';
            document.getElementById('modalEditar').style.display = 'block';
        }
        window.onclick = function(e) { if (e.target.className === 'modal') e.target.style.display = "none"; }
    </script>
</body>
</html>