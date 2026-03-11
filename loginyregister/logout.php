<?php
session_start();
session_unset();
session_destroy(); // Destruye la sesión
header("Location: login_admin.html"); // Te devuelve al login
exit();
?>