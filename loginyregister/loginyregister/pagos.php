<?php
session_start();
if (!isset($_SESSION['usu_id'])) { header("Location: login.html"); exit(); }
$pagina_actual = 'pagos';

$servidor = "localhost"; $base_datos = "DB"; 
try {
    $conn = new PDO("sqlsrv:server=$servidor;Database=$base_datos", "", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

$mensaje = "";

// --- 1. GUARDAR (NUEVO O EDITAR) ---
if (isset($_POST['btn_guardar'])) {
    try {
        if (!empty($_POST['id_cotizacion'])) {
            $sql = "UPDATE Cotizaciones SET Cedula=?, IdProceso=?, MontoTotal=?, FormaPago=?, FechaPago=? WHERE IdCotizacion=?";
            $conn->prepare($sql)->execute([$_POST['cedula'], $_POST['id_proceso'], $_POST['monto'], $_POST['forma_pago'], $_POST['fecha_pago'], $_POST['id_cotizacion']]);
        } else {
            $sql = "INSERT INTO Cotizaciones (Cedula, IdProceso, MontoTotal, FormaPago, FechaPago) VALUES (?, ?, ?, ?, ?)";
            $conn->prepare($sql)->execute([$_POST['cedula'], $_POST['id_proceso'], $_POST['monto'], $_POST['forma_pago'], $_POST['fecha_pago']]);
        }
        $mensaje = "Transacción procesada con éxito.";
    } catch (Exception $e) { $mensaje = "Error: " . $e->getMessage(); }
}

// --- 2. ELIMINAR ---
if (isset($_GET['eliminar'])) {
    $conn->prepare("DELETE FROM Cotizaciones WHERE IdCotizacion = ?")->execute([$_GET['eliminar']]);
    header("Location: pagos.php"); exit();
}

// --- 3. CONSULTAS ---
$pacientes = $conn->query("SELECT Cedula, Nombre, Apellido FROM Pacientes ORDER BY Nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Lista de procesos realizados para el selector del Modal
$procesos_lista = $conn->query("SELECT pr.IdProceso, p.Nombre as PacNom, t.Nombre as TratNom 
                                FROM Procesos pr 
                                JOIN Pacientes p ON pr.Cedula = p.Cedula 
                                JOIN Tratamientos t ON pr.IdTratamiento = t.IdTratamiento")->fetchAll(PDO::FETCH_ASSOC);

// CONSULTA PRINCIPAL: Ahora incluye el nombre del tratamiento realizado
$sqlPagos = "SELECT c.*, p.Nombre as PacNom, p.Apellido as PacApe, t.Nombre as NombreTratamiento
             FROM Cotizaciones c 
             LEFT JOIN Pacientes p ON c.Cedula = p.Cedula 
             LEFT JOIN Procesos pr ON c.IdProceso = pr.IdProceso
             LEFT JOIN Tratamientos t ON pr.IdTratamiento = t.IdTratamiento
             ORDER BY c.FechaPago DESC";
$pagos = $conn->query($sqlPagos)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos - BEA</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .caja-blanca { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 20px; }
        .tabla-pagos { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        .tabla-pagos th, .tabla-pagos td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .btn-azul { background: #2d7cb5; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 25px; width: 450px; border-radius: 10px; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .monto-total { font-weight: bold; color: #2e7d32; }
        .tratamiento-tag { background: #e3f2fd; color: #1976d2; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 500; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Control de Pagos</h1>
            <div class="user-info">
                <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['usu_nombre']); ?></strong></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['usu_nombre'], 0, 1)); ?></div>
            </div>
        </header>

        <div class="caja-blanca">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div>
                    <h2>Ingresos y Facturación</h2>
                    <p>Detalle de cobros por servicios dentales realizados.</p>
                </div>
                <button class="btn-azul" onclick="abrirNuevo()">+ Registrar Pago</button>
            </div>

            <?php if($mensaje) echo "<div style='color:#155724; background:#d4edda; padding:10px; border-radius:5px; margin: 10px 0;'>$mensaje</div>"; ?>

            <div style="overflow-x:auto;">
                <table class="tabla-pagos">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Tratamiento / Proceso</th>
                            <th>Monto</th>
                            <th>Forma de Pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pagos as $p): ?>
                        <tr>
                            <td><?php echo $p['FechaPago']; ?></td>
                            <td><?php echo $p['PacNom']." ".$p['PacApe']; ?></td>
                            <td>
                                <?php if($p['NombreTratamiento']): ?>
                                    <span class="tratamiento-tag"><?php echo $p['NombreTratamiento']; ?></span>
                                <?php else: ?>
                                    <small style="color:gray;">Pago General / Abono</small>
                                <?php endif; ?>
                            </td>
                            <td class="monto-total">$<?php echo number_format($p['MontoTotal'], 2); ?></td>
                            <td><?php echo $p['FormaPago']; ?></td>
                            <td>
                                <button onclick='editarPago(<?php echo json_encode($p); ?>)' style="border:none; background:none; cursor:pointer;">✏️</button>
                                <a href="?eliminar=<?php echo $p['IdCotizacion']; ?>" onclick="return confirm('¿Eliminar registro?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalPago" class="modal">
        <div class="modal-content">
            <h3 id="modalTitulo">Registrar Pago</h3>
            <form method="POST">
                <input type="hidden" name="id_cotizacion" id="id_cotizacion">
                <label>Paciente</label>
                <select name="cedula" id="cedula" required>
                    <option value="">Seleccione...</option>
                    <?php foreach($pacientes as $pc): ?>
                        <option value="<?php echo $pc['Cedula']; ?>"><?php echo $pc['Nombre']." ".$pc['Apellido']; ?></option>
                    <?php endforeach; ?>
                </select>

                <label style="display:block; margin-top:10px;">Vincular a Proceso Realizado</label>
                <select name="id_proceso" id="id_proceso">
                    <option value="">Ninguno (Pago independiente)</option>
                    <?php foreach($procesos_lista as $pr): ?>
                        <option value="<?php echo $pr['IdProceso']; ?>"><?php echo $pr['PacNom']." - ".$pr['TratNom']; ?></option>
                    <?php endforeach; ?>
                </select>

                <label style="display:block; margin-top:10px;">Monto Total ($)</label>
                <input type="number" step="0.01" name="monto" id="monto" required>

                <label style="display:block; margin-top:10px;">Forma de Pago</label>
                <select name="forma_pago" id="forma_pago">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Seguro">Seguro Médico</option>
                </select>

                <label style="display:block; margin-top:10px;">Fecha</label>
                <input type="date" name="fecha_pago" id="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>

                <button type="submit" name="btn_guardar" class="btn-azul" style="width:100%; margin-top:20px;">Guardar Registro</button>
                <button type="button" onclick="cerrarModal()" style="width:100%; background:none; border:none; color:red; cursor:pointer; margin-top:10px;">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalPago');
        function abrirNuevo() {
            document.getElementById('modalTitulo').innerText = "Registrar Pago";
            document.getElementById('id_cotizacion').value = "";
            modal.style.display = 'block';
        }
        function editarPago(p) {
            document.getElementById('modalTitulo').innerText = "Editar Pago";
            document.getElementById('id_cotizacion').value = p.IdCotizacion;
            document.getElementById('cedula').value = p.Cedula;
            document.getElementById('id_proceso').value = p.IdProceso;
            document.getElementById('monto').value = p.MontoTotal;
            document.getElementById('forma_pago').value = p.FormaPago;
            document.getElementById('fecha_pago').value = p.FechaPago;
            modal.style.display = 'block';
        }
        function cerrarModal() { modal.style.display = 'none'; }
    </script>
</body>
</html>