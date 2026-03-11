<?php
// 1. Activar reporte de errores para ver por qué sale en blanco
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usu_id'])) { 
    header("Location: login_admin.html"); 
    exit(); 
}

$pagina_actual = 'servicios';

// 2. Conexión a la base de datos
$servidor = "localhost"; 
$base_datos = "DB"; 
try {
    // Si no tienes usuario/password en SQL Server, deja los campos vacíos ""
    $conn = new PDO("sqlsrv:server=$servidor;Database=$base_datos", "", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    die("Error crítico de conexión: " . $e->getMessage()); 
}

$mensaje = "";

// 3. Lógica para ELIMINAR
if (isset($_GET['eliminar'])) {
    try {
        $id = $_GET['eliminar'];
        $stmt = $conn->prepare("DELETE FROM Tratamientos WHERE IdTratamiento = ?");
        $stmt->execute([$id]);
        header("Location: servicios.php"); 
        exit();
    } catch (Exception $e) { 
        $mensaje = "Error al eliminar: El servicio podría estar en uso."; 
    }
}

// 4. Lógica para GUARDAR
if (isset($_POST['btn_guardar'])) {
    try {
        $sql = "INSERT INTO Tratamientos (Nombre, Descripcion, Costo, DuracionMinutos) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['nombre'], 
            $_POST['descripcion'], 
            $_POST['costo'], 
            $_POST['duracion']
        ]);
        $mensaje = "Servicio guardado con éxito.";
    } catch (Exception $e) { 
        $mensaje = "Error al guardar: " . $e->getMessage(); 
    }
}

// 5. Lógica para ACTUALIZAR
if (isset($_POST['btn_actualizar'])) {
    try {
        $sql = "UPDATE Tratamientos SET Nombre=?, Descripcion=?, Costo=?, DuracionMinutos=? WHERE IdTratamiento=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['nombre'], 
            $_POST['descripcion'], 
            $_POST['costo'], 
            $_POST['duracion'], 
            $_POST['id_tratamiento']
        ]);
        $mensaje = "Servicio actualizado correctamente.";
    } catch (Exception $e) { 
        $mensaje = "Error al actualizar: " . $e->getMessage(); 
    }
}

// 6. Obtener lista de servicios
$servicios = [];
try {
    $query = $conn->query("SELECT * FROM Tratamientos ORDER BY Nombre ASC");
    $servicios = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "Error al cargar datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios - BEA</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .caja-blanca { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 20px; }
        .table-res { width: 100%; overflow-x: auto; margin-top: 20px; }
        .tabla-servicios { width: 100%; border-collapse: collapse; }
        .tabla-servicios th, .tabla-servicios td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .btn-add { background: #2d7cb5; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 25px; width: 450px; border-radius: 10px; }
        input, textarea { width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Catálogo de Servicios</h1>
            <div class="user-info">
                <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['usu_nombre']); ?></strong></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['usu_nombre'], 0, 1)); ?></div>
            </div>
        </header>

        <div class="caja-blanca">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Tratamientos Dentales</h2>
                <button class="btn-add" onclick="abrirModal('modalNuevo')">+ Nuevo Servicio</button>
            </div>
            
            <?php if($mensaje): ?>
                <p style="color: #2d7cb5; background: #eefaff; padding: 10px; border-radius: 5px; margin: 10px 0;"><?php echo $mensaje; ?></p>
            <?php endif; ?>

            <div class="table-res">
                <table class="tabla-servicios">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Costo</th>
                            <th>Duración</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($servicios)): ?>
                            <tr><td colspan="5" style="text-align:center;">No hay servicios registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach($servicios as $s): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($s['Nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['Descripcion']); ?></td>
                                <td>$<?php echo number_format($s['Costo'], 2); ?></td>
                                <td><?php echo $s['DuracionMinutos']; ?> min</td>
                                <td>
                                    <button onclick='editarServicio(<?php echo json_encode($s); ?>)' style="border:none; background:none; cursor:pointer; font-size:1.2rem;">✏️</button>
                                    <a href="?eliminar=<?php echo $s['IdTratamiento']; ?>" onclick="return confirm('¿Borrar servicio?')" style="text-decoration:none; font-size:1.2rem;">🗑️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalNuevo" class="modal">
        <div class="modal-content">
            <h3>Nuevo Tratamiento</h3>
            <form method="POST">
                <input type="text" name="nombre" placeholder="Nombre (ej: Limpieza)" required>
                <textarea name="descripcion" placeholder="Descripción breve" rows="3"></textarea>
                <input type="number" step="0.01" name="costo" placeholder="Costo ($)" required>
                <input type="number" name="duracion" placeholder="Duración (minutos)" required>
                <button type="submit" name="btn_guardar" class="btn-add" style="width:100%; margin-top:15px;">Guardar</button>
                <button type="button" onclick="cerrarModal('modalNuevo')" style="width:100%; background:none; border:none; color:red; cursor:pointer; margin-top:10px;">Cancelar</button>
            </form>
        </div>
    </div>

    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <h3>Editar Tratamiento</h3>
            <form method="POST">
                <input type="hidden" name="id_tratamiento" id="ed_id">
                <input type="text" name="nombre" id="ed_nombre" required>
                <textarea name="descripcion" id="ed_desc" rows="3"></textarea>
                <input type="number" step="0.01" name="costo" id="ed_costo" required>
                <input type="number" name="duracion" id="ed_duracion" required>
                <button type="submit" name="btn_actualizar" class="btn-add" style="width:100%; margin-top:15px;">Actualizar</button>
                <button type="button" onclick="cerrarModal('modalEditar')" style="width:100%; background:none; border:none; color:red; cursor:pointer; margin-top:10px;">Cerrar</button>
            </form>
        </div>
    </div>

    <script>
        function abrirModal(id) { document.getElementById(id).style.display = 'block'; }
        function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function editarServicio(s) {
            document.getElementById('ed_id').value = s.IdTratamiento;
            document.getElementById('ed_nombre').value = s.Nombre;
            document.getElementById('ed_desc').value = s.Descripcion;
            document.getElementById('ed_costo').value = s.Costo;
            document.getElementById('ed_duracion').value = s.DuracionMinutos;
            abrirModal('modalEditar');
        }
        window.onclick = function(e) { if (e.target.className === 'modal') e.target.style.display = "none"; }
    </script>
</body>
</html>