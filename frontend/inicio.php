<?php
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login - Interbank</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../backend/css/login.css">
    <script src="../backend/js/teclado.js" defer></script>
</head>

<body>
    <div class="logo-interbank">
        <img src="../backend/img/interbank_logo.png" alt="Interbank">
    </div>

    <div class="contenedor-login">

        <div class="login-box">

            <form id="formLogin" action="../backend/controlador/login.php" method="POST" autocomplete="off">
                <label for="dni" class="titulo">DNI</label>
                <input
                    type="text"
                    id="dni"
                    name="dni"
                    class="input"
                    placeholder="Número de documento"
                    required>

                <label for="password" class="titulo">Contraseña</label>
                <div class="password-box">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input"
                        placeholder="Contraseña"
                        required>
                    <span class="ver" onclick="togglePassword()">👁</span>
                </div>

                <div class="teclado" id="teclado">
                </div>

                <div class="recordar">
                    <input type="checkbox" id="recordar" name="recordar">
                    <label for="recordar">Recordar documento</label>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div style="color:#d9534f; font-size:13px; margin-top:10px;">
                        <?php
                        switch ($_GET['error']) {
                            case 'campos_vacios':
                                echo "Por favor, completa todos los campos.";
                                break;
                            case 'usuario_no_encontrado':
                                echo "El documento ingresado no está registrado.";
                                break;
                            case 'usuario_bloqueado':
                                echo "Tu usuario se encuentra bloqueado. Contacta con el banco.";
                                break;
                            case 'contrasena_invalida':
                                echo "Contraseña incorrecta. Inténtalo nuevamente.";
                                break;
                            default:
                                echo "Ocurrió un error al iniciar sesión.";
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-interbank">Siguiente</button>

                <div class="links">
    <a href="registrate.php">Registrarte</a> |
    <a href="recuperar.php">Olvidé mi contraseña</a> |
    <a href="#">Ayuda</a>
</div>

            </form>

        </div>

    </div>

</body>

</html>
