<aside class="sidebar">
    <div class="logo-container">
        <h2>🦷 BEA</h2>
    </div>
    <ul class="nav-menu">
        <li class="<?php echo ($pagina_actual == 'dashboard') ? 'activo' : ''; ?>"><a href="dashboard.php">Home </a></li>
        <li class="<?php echo ($pagina_actual == 'citas') ? 'activo' : ''; ?>"><a href="citas.php">Citas</a></li>
        <li class="<?php echo ($pagina_actual == 'pacientes') ? 'activo' : ''; ?>"><a href="pacientes.php">Pacientes</a></li>
        <li class="<?php echo ($pagina_actual == 'historial') ? 'activo' : ''; ?>"><a href="historial.php">Historial Clínico</a></li>
        <li class="<?php echo ($pagina_actual == 'pagos') ? 'activo' : ''; ?>"><a href="pagos.php">Pagos</a></li>
        <li class="<?php echo ($pagina_actual == 'servicios') ? 'activo' : ''; ?>"><a href="servicios.php">Servicios</a></li>
    </ul>
    <div class="logout">
        <a href="logout.php">🚪 Cerrar Sesión</a>
    </div>
</aside>