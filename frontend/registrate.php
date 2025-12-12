<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Interbank</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../backend/css/login.css">
</head>
<body>
    <div class="logo-interbank">
        <img src="../backend/img/interbank_logo.png" alt="Interbank">
    </div>

    <div class="contenedor-login">
        <div class="login-box">
            <h2 class="titulo" style="text-align:center; margin-bottom:20px;">Crear Cuenta</h2>
            
            <form action="../backend/controlador/registro.php" method="POST" autocomplete="off">
                <label class="titulo">DNI</label>
                <input type="text" name="dni" class="input" placeholder="Ingresa tu DNI" required pattern="[0-9]{8}" title="8 dígitos numéricos">

                <label class="titulo">Nombre Completo</label>
                <input type="text" name="nombre" class="input" placeholder="Nombre y Apellidos" required>

                <label class="titulo">Correo Electrónico</label>
                <input type="email" name="correo" class="input" placeholder="ejemplo@correo.com" required>

                <label class="titulo">Contraseña</label>
                <input type="password" name="password" class="input" placeholder="Crea tu clave web" required>

                <button type="submit" class="btn-interbank">Registrarme</button>

                <div class="links" style="margin-top: 15px;">
                    <a href="inicio.php">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
