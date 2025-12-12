<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Clave - Interbank</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../backend/css/login.css">
</head>
<body>
    <div class="logo-interbank">
        <img src="../backend/img/interbank_logo.png" alt="Interbank">
    </div>

    <div class="contenedor-login">
        <div class="login-box">
            <h2 class="titulo" style="text-align:center; margin-bottom:20px;">Restablecer Contraseña</h2>
            
            <form action="../backend/controlador/cambiar_pass.php" method="POST" autocomplete="off">
                <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                    Por seguridad, ingresa tus datos para validar tu identidad.
                </p>

                <label class="titulo">DNI</label>
                <input type="text" name="dni" class="input" required>

                <label class="titulo">Correo Electrónico registrado</label>
                <input type="email" name="correo" class="input" required>

                <label class="titulo">Nueva Contraseña</label>
                <input type="password" name="new_password" class="input" placeholder="Mínimo 6 caracteres" required>

                <button type="submit" class="btn-interbank">Cambiar Contraseña</button>

                <div class="links" style="margin-top: 15px;">
                    <a href="inicio.php">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
