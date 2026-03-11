<?php
session_start();
if (!isset($_SESSION['usu_id'])) { header("Location: login.html"); exit(); }
$pagina_actual = 'historial';

$servidor = "localhost"; $base_datos = "DB"; 
try {
    $conn = new PDO("sqlsrv:server=$servidor;Database=$base_datos", "", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

$mensaje = "";

// --- 1. GUARDAR O ACTUALIZAR ANTECEDENTES ---
if (isset($_POST['btn_guardar'])) {
    try {
        if (!empty($_POST['id_historial'])) {
            $sql = "UPDATE HistorialMedico SET CondicionSalud=?, Habitos=?, Alergias=? WHERE IdHistorial=?";
            $conn->prepare($sql)->execute([$_POST['condicion'], $_POST['habitos'], $_POST['alergias'], $_POST['id_historial']]);
        } else {
            $sql = "INSERT INTO HistorialMedico (Nombre, Apellido, CondicionSalud, Habitos, Alergias) VALUES (?, ?, ?, ?, ?)";
            $conn->prepare($sql)->execute([$_POST['p_nombre'], $_POST['p_apellido'], $_POST['condicion'], $_POST['habitos'], $_POST['alergias']]);
        }
        $mensaje = "Historial actualizado correctamente.";
    } catch (Exception $e) { $mensaje = "Error: " . $e->getMessage(); }
}

// --- 2. BUSQUEDA DE PACIENTE ---
$paciente_sel = null;
$eventos_clinicos = [];
if (isset($_GET['buscar_cedula']) && !empty($_GET['buscar_cedula'])) {
    $cedula = $_GET['buscar_cedula'];
    
    // Datos básicos y Antecedentes
    $sqlP = "SELECT P.*, H.IdHistorial, H.CondicionSalud, H.Habitos, H.Alergias 
             FROM Pacientes P 
             LEFT JOIN HistorialMedico H ON P.Nombre = H.Nombre AND P.Apellido = H.Apellido
             WHERE P.Cedula = ?";
    $stmt = $conn->prepare($sqlP);
    $stmt->execute([$cedula]);
    $paciente_sel = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($paciente_sel) {
        // Línea de tiempo: Consultas y Diagnósticos unidos
        $sqlE = "SELECT FechaConsulta as Fecha, Motivo as Actividad, Observaciones as Detalle, 'Consulta' as Tipo 
                 FROM Consultas WHERE Cedula = ?
                 UNION
                 SELECT GETDATE(), 'Diagnóstico', Descripcion, 'Médico' 
                 FROM Diagnosticos WHERE Cedula = ?
                 ORDER BY Fecha DESC";
        $stmtE = $conn->prepare($sqlE);
        $stmtE->execute([$cedula, $cedula]);
        $eventos_clinicos = $stmtE->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $mensaje = "Paciente no encontrado.";
    }
}

$todos_pacientes = $conn->query("SELECT Cedula, Nombre, Apellido FROM Pacientes ORDER BY Nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Clínico - BEA</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .caja-blanca { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 20px; }
        .grid-historial { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-top: 20px; }
        .card { border: 1px solid #eee; padding: 15px; border-radius: 8px; background: #fafafa; }
        .linea-tiempo { border-left: 2px solid #2d7cb5; margin-left: 10px; padding-left: 20px; }
        .evento { margin-bottom: 20px; position: relative; }
        .evento::before { content: ''; position: absolute; left: -27px; top: 5px; width: 12px; height: 12px; background: #2d7cb5; border-radius: 50%; }
        .badge { font-size: 0.75em; padding: 3px 8px; border-radius: 10px; background: #e3f2fd; color: #1976d2; }
        input, textarea, select { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 5px; }
        .btn-azul { background: #2d7cb5; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Historial Clínico</h1>
            <div class="user-info">
                <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['usu_nombre']); ?></strong></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['usu_nombre'], 0, 1)); ?></div>
            </div>
        </header>

        <div class="caja-blanca">
            <h2>Expedientes Médicos</h2>
            <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <select name="buscar_cedula" required>
                    <option value="">Seleccione un paciente para ver su historial...</option>
                    <?php foreach($todos_pacientes as $tp): ?>
                        <option value="<?php echo $tp['Cedula']; ?>" <?php if(isset($_GET['buscar_cedula']) && $_GET['buscar_cedula'] == $tp['Cedula']) echo 'selected'; ?>>
                            <?php echo $tp['Nombre']." ".$tp['Apellido']." (".$tp['Cedula'].")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-azul">Buscar</button>
            </form>

            <?php if($mensaje) echo "<p style='color:orange;'>$mensaje</p>"; ?>

            <?php if($paciente_sel): ?>
                <div class="grid-historial">
                    <div class="card">
                        <h3>Antecedentes del Paciente</h3>
                        <p><strong>Paciente:</strong> <?php echo $paciente_sel['Nombre']." ".$paciente_sel['Apellido']; ?></p>
                        <hr>
                        <form method="POST">
                            <input type="hidden" name="id_historial" value="<?php echo $paciente_sel['IdHistorial']; ?>">
                            <input type="hidden" name="p_nombre" value="<?php echo $paciente_sel['Nombre']; ?>">
                            <input type="hidden" name="p_apellido" value="<?php echo $paciente_sel['Apellido']; ?>">
                            
                            <label>Condición de Salud</label>
                            <textarea name="condicion" rows="3"><?php echo $paciente_sel['CondicionSalud']; ?></textarea>
                            
                            <label>Hábitos</label>
                            <input type="text" name="habitos" value="<?php echo $paciente_sel['Habitos']; ?>" placeholder="Ej: Fumador, Higiene bucal frecuente">
                            
                            <label>Alergias</label>
                            <textarea name="alergias" rows="2"><?php echo $paciente_sel['Alergias']; ?></textarea>
                            
                            <button type="submit" name="btn_guardar" class="btn-azul" style="width:100%; margin-top:10px;">Actualizar Antecedentes</button>
                        </form>
                    </div>

                    <div class="card">
                        <h3>Evolución y Visitas</h3>
                        <div class="linea-tiempo">
                            <?php if(empty($eventos_clinicos)): ?>
                                <p>No hay visitas o diagnósticos registrados aún.</p>
                            <?php else: ?>
                                <?php foreach($eventos_clinicos as $ev): ?>
                                    <div class="evento">
                                        <span class="badge"><?php echo $ev['Tipo']; ?></span>
                                        <small style="color:gray;"><?php echo is_object($ev['Fecha']) ? $ev['Fecha']->format('d/m/Y') : $ev['Fecha']; ?></small>
                                        <h4 style="margin:5px 0;"><?php echo $ev['Actividad']; ?></h4>
                                        <p style="font-size: 0.9em; color:#555;"><?php echo $ev['Detalle']; ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>