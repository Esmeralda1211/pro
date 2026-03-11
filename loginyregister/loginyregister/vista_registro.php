<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Clínica Dental</title>
    <link rel="stylesheet" href="login.css"> 
</head>
<body>
    <div class="contenedor" style="max-width: 600px;"> <div class="formulario-caja">
            <div class="logo">
                <span class="icono-diente">&#9786;</span>
                <h1>Clínica Dental Sonrisas</h1>
            </div>
            
            <h2>Crear Cuenta</h2>
            <p class="subtitulo">Regístrate como paciente en el sistema</p>
            
            <form action="register.php" method="POST">
                
                <h3 style="margin-bottom: 15px; color: #2d7cb5; font-size: 16px;">Datos Personales</h3>
                
                <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <div class="campo" style="flex: 1; margin-bottom: 0;">
                        <label for="nombre">Nombre(s)</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Tus nombres" required>
                    </div>
                    <div class="campo" style="flex: 1; margin-bottom: 0;">
                        <label for="apellido">Apellido(s)</label>
                        <input type="text" id="apellido" name="apellido" placeholder="Tus apellidos" required>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <div class="campo" style="flex: 1; margin-bottom: 0;">
                        <label for="cedula">Cédula</label>
                        <input type="text" id="cedula" name="cedula" placeholder="Ej. 12345678901" required>
                    </div>
                    <div class="campo" style="flex: 1; margin-bottom: 0;">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" placeholder="Ej. 8091234567" required>
                    </div>
                </div>

                <div class="campo">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" placeholder="ejemplo@correo.com" required>
                </div>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">
                
                <h3 style="margin-bottom: 15px; color: #2d7cb5; font-size: 16px;">Datos de Acceso</h3>

                <div class="campo">
                    <label for="usu_nombre">Nombre de Usuario</label>
                    <input type="text" id="usu_nombre" name="usu_nombre" placeholder="Crea un nombre de usuario" required>
                </div>
                
                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Crea una contraseña segura" required>
                </div>
                
                <button type="submit" class="btn-entrar">Completar Registro</button>
            </form>
            
            <div class="enlace">
                <p>¿Ya tienes cuenta? <a href="login.html">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>