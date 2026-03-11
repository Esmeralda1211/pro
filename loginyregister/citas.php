<?php
session_start();
if (!isset($_SESSION['usu_id'])) { header("Location: login.html"); exit(); }
$pagina_actual = 'citas';

// Configuración de conexión
$servidor = "localhost"; $base_datos = "DB"; 
try {
    $conn = new PDO("sqlsrv:server=$servidor;Database=$base_datos", "", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

$mensaje = "";

// --- 1. LÓGICA DE GUARDAR (NUEVA O EDITAR) ---
if (isset($_POST['btn_guardar'])) {
    try {
        // Obtenemos datos del paciente para cumplir con el esquema de la tabla
        $stmtP = $conn->prepare("SELECT Nombre, Apellido, Telefono, FechaNacimiento FROM Pacientes WHERE Cedula = ?");
        $stmtP->execute([$_POST['cedula']]);
        $p = $stmtP->fetch(PDO::FETCH_ASSOC);

        if ($p) {
            if (!empty($_POST['id_cita'])) {
                // ACTUALIZAR
                $sql = "UPDATE Citas SET Nombre=?, Apellido=?, Telefono=?, FechaNacimiento=?, Cedula=?, IdDoctor=?, MetodoPago=?, Observacion=?, FechaCita=?, HoraCita=?, Asegurado=? WHERE IdCita=?";
                $params = [$p['Nombre'], $p['Apellido'], $p['Telefono'], $p['FechaNacimiento'], $_POST['cedula'], $_POST['id_doctor'], $_POST['metodo_pago'], $_POST['observacion'], $_POST['fecha_cita'], $_POST['hora_cita'], $_POST['asegurado'], $_POST['id_cita']];
            } else {
                // INSERTAR
                $sql = "INSERT INTO Citas (Nombre, Apellido, Telefono, FechaNacimiento, Cedula, IdDoctor, MetodoPago, Observacion, FechaCita, HoraCita, Asegurado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [$p['Nombre'], $p['Apellido'], $p['Telefono'], $p['FechaNacimiento'], $_POST['cedula'], $_POST['id_doctor'], $_POST['metodo_pago'], $_POST['observacion'], $_POST['fecha_cita'], $_POST['hora_cita'], $_POST['asegurado']];
            }
            $conn->prepare($sql)->execute($params);
            $mensaje = "Operación realizada con éxito.";
        }
    } catch (Exception $e) { $mensaje = "Error: " . $e->getMessage(); }
}

// --- 2. ELIMINAR ---
if (isset($_GET['eliminar'])) {
    $conn->prepare("DELETE FROM Citas WHERE IdCita = ?")->execute([$_GET['eliminar']]);
    header("Location: citas.php"); exit();
}

// --- 3. CONSULTAS ---
$pacientes = $conn->query("SELECT Cedula, Nombre, Apellido FROM Pacientes ORDER BY Nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$doctores = $conn->query("SELECT IdDoctor, Nombre, Apellido FROM Doctores ORDER BY Nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$citas = $conn->query("SELECT C.*, D.Nombre as DocNom, D.Apellido as DocApe FROM Citas C LEFT JOIN Doctores D ON C.IdDoctor = D.IdDoctor ORDER BY C.FechaCita DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Citas - BEA</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .caja-blanca { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 20px; }
        .tabla-citas { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabla-citas th, .tabla-citas td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 25px; width: 550px; border-radius: 10px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        input, select, textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn-add { background: #2d7cb5; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Gestión de Citas</h1>
            <div class="user-info">
                <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['usu_nombre']); ?></strong></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['usu_nombre'], 0, 1)); ?></div>
            </div>
        </header>

        <div class="caja-blanca">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Calendario de Citas</h2>
                <button class="btn-add" onclick="nuevaCita()">+ Agendar Cita</button>
            </div>

            <?php if($mensaje) echo "<p style='color:green;'>$mensaje</p>"; ?>

            <table class="tabla-citas">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Paciente</th>
                        <th>Doctor</th>
                        <th>Asegurado</th>
                        <th>Método Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($citas as $c): ?>
                    <tr>
                        <td><strong><?php echo $c['FechaCita']; ?></strong><br><small><?php echo $c['HoraCita']; ?></small></td>
                        <td><?php echo $c['Nombre']." ".$c['Apellido']; ?></td>
                        <td>Dr. <?php echo $c['DocNom']." ".$c['DocApe']; ?></td>
                        <td><?php echo $c['Asegurado']; ?></td>
                        <td><?php echo $c['MetodoPago']; ?></td>
                        <td>
                            <button onclick='editarCita(<?php echo json_encode($c); ?>)' style="background:none; border:none; cursor:pointer;">✏️</button>
                            <a href="?eliminar=<?php echo $c['IdCita']; ?>" onclick="return confirm('¿Eliminar?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalCita" class="modal">
        <div class="modal-content">
            <h3 id="modalTitulo">Agendar Cita</h3>
            <form method="POST">
                <input type="hidden" name="id_cita" id="id_cita">
                <div class="grid-2">
                    <div>
                        <label>Paciente</label>
                        <select name="cedula" id="cedula" required>
                            <?php foreach($pacientes as $p): ?>
                                <option value="<?php echo $p['Cedula']; ?>"><?php echo $p['Nombre']." ".$p['Apellido']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Doctor</label>
                        <select name="id_doctor" id="id_doctor" required>
                            <?php foreach($doctores as $d): ?>
                                <option value="<?php echo $d['IdDoctor']; ?>">Dr. <?php echo $d['Nombre']." ".$d['Apellido']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label>Fecha</label><input type="date" name="fecha_cita" id="fecha_cita" required></div>
                    <div><label>Hora</label><input type="time" name="hora_cita" id="hora_cita" required></div>
                    <div>
                        <label>Método de Pago</label>
                        <select name="metodo_pago" id="metodo_pago">
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta">Tarjeta</option>
                            <option value="Seguro">Seguro</option>
                        </select>
                    </div>
                    <div>
                        <label>¿Asegurado?</label>
                        <select name="asegurado" id="asegurado">
                            <option value="No">No</option>
                            <option value="Si">Si</option>
                        </select>
                    </div>
                </div>
                <label style="display:block; margin-top:10px;">Observación</label>
                <textarea name="observacion" id="observacion"></textarea>
                
                <button type="submit" name="btn_guardar" class="btn-add" style="width:100%; margin-top:15px;">Guardar Cita</button>
                <button type="button" onclick="document.getElementById('modalCita').style.display='none'" style="width:100%; border:none; background:none; color:red; cursor:pointer; margin-top:10px;">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        function nuevaCita() {
            document.getElementById('modalTitulo').innerText = "Agendar Cita";
            document.getElementById('id_cita').value = "";
            document.getElementById('modalCita').style.display = 'block';
        }
        function editarCita(c) {
            document.getElementById('modalTitulo').innerText = "Editar Cita";
            document.getElementById('id_cita').value = c.IdCita;
            document.getElementById('cedula').value = c.Cedula;
            document.getElementById('id_doctor').value = c.IdDoctor;
            document.getElementById('fecha_cita').value = c.FechaCita;
            document.getElementById('hora_cita').value = c.HoraCita.substring(0,5);
            document.getElementById('metodo_pago').value = c.MetodoPago;
            document.getElementById('asegurado').value = c.Asegurado;
            document.getElementById('observacion').value = c.Observacion;
            document.getElementById('modalCita').style.display = 'block';
        }
    </script>
</body>
</html>