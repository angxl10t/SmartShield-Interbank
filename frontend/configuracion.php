<?php
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: inicio.php");
    exit;
}

require_once "../backend/bd/conexion.php";

$idUsuario = $_SESSION['id_usuario'];
$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';

$sql = "SELECT 
            t.id_tarjeta,
            t.uso_internacional,
            t.modo_inteligente,
            t.limite_credito,
            t.saldo_disponible,
            c.id_config,
            c.limite_semanal,
            c.horario_inicio,
            c.horario_fin
        FROM tarjetas t
        LEFT JOIN config_seguridad_tarjeta c
            ON c.id_tarjeta = t.id_tarjeta
        WHERE t.id_usuario = :id_usuario
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id_usuario' => $idUsuario]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("No se encontró tarjeta asociada al usuario.");
}

$idTarjeta         = (int)$data['id_tarjeta'];
$idConfig         = $data['id_config'] ?? null;

$usoInternacional = (int)($data['uso_internacional'] ?? 0);
$modoInteligente  = (int)($data['modo_inteligente'] ?? 0);
$limiteSemanal    = isset($data['limite_semanal']) ? (float)$data['limite_semanal'] : 1200.00;
$horarioInicio    = $data['horario_inicio'] ?? '06:00:00';
$horarioFin       = $data['horario_fin'] ?? '23:00:00';

$mensajeOk = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nuevoUsoInternacional = isset($_POST['uso_internacional']) ? 1 : 0;
    $nuevoModoInteligente  = isset($_POST['modo_inteligente']) ? 1 : 0;

    $nuevoLimiteSemanal = isset($_POST['limite_semanal']) ? (float)$_POST['limite_semanal'] : 0;
    $nuevoHorarioInicio = $_POST['horario_inicio'] ?? '06:00';
    $nuevoHorarioFin    = $_POST['horario_fin'] ?? '23:00';

    if (strlen($nuevoHorarioInicio) === 5) {
        $nuevoHorarioInicio .= ':00';
    }
    if (strlen($nuevoHorarioFin) === 5) {
        $nuevoHorarioFin .= ':00';
    }

    try {
        $pdo->beginTransaction();

        $sqlTarjeta = "UPDATE tarjetas
                       SET uso_internacional = :uso,
                           modo_inteligente  = :modo
                       WHERE id_tarjeta = :id_tarjeta";

        $stmtTarjeta = $pdo->prepare($sqlTarjeta);
        $stmtTarjeta->execute([
            ':uso'        => $nuevoUsoInternacional,
            ':modo'       => $nuevoModoInteligente,
            ':id_tarjeta' => $idTarjeta
        ]);

        if ($idConfig) {
            $sqlConfig = "UPDATE config_seguridad_tarjeta
                          SET limite_semanal      = :limite,
                              horario_inicio      = :h_ini,
                              horario_fin         = :h_fin,
                              ultima_actualizacion = NOW()
                          WHERE id_config = :id_config";
            $stmtConfig = $pdo->prepare($sqlConfig);
            $stmtConfig->execute([
                ':limite'   => $nuevoLimiteSemanal,
                ':h_ini'    => $nuevoHorarioInicio,
                ':h_fin'    => $nuevoHorarioFin,
                ':id_config' => $idConfig
            ]);
        } else {
            $sqlConfig = "INSERT INTO config_seguridad_tarjeta
                          (id_tarjeta, limite_diario, limite_mensual, horario_inicio, horario_fin,
                           modo_viaje, notificar_email, notificar_sms, ultima_actualizacion,
                           limite_semanal, gasto_semanal_actual, fecha_ultimo_reset_semanal,
                           gasto_mensual_actual)
                          VALUES
                          (:id_tarjeta, 0, 0, :h_ini, :h_fin,
                           0, 1, 0, NOW(),
                           :limite, 0, NOW(),
                           0)";
            $stmtConfig = $pdo->prepare($sqlConfig);
            $stmtConfig->execute([
                ':id_tarjeta' => $idTarjeta,
                ':h_ini'      => $nuevoHorarioInicio,
                ':h_fin'      => $nuevoHorarioFin,
                ':limite'     => $nuevoLimiteSemanal
            ]);
        }

        $pdo->commit();

        $usoInternacional = $nuevoUsoInternacional;
        $modoInteligente  = $nuevoModoInteligente;
        $limiteSemanal    = $nuevoLimiteSemanal;
        $horarioInicio    = $nuevoHorarioInicio;
        $horarioFin       = $nuevoHorarioFin;

        $mensajeOk = "La configuración de seguridad se guardó correctamente.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensajeError = "Ocurrió un error al guardar la configuración.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Configuración de seguridad</title>
    <link rel="stylesheet" href="../backend/css/dashboard.css">
    <link rel="stylesheet" href="../backend/css/configuracion.css">
</head>

<body>
    <header class="header">
        <div class="header-left">
            <div class="header-logo">
                <span>▢</span>
                <span>Interbank</span>
            </div>

            <button class="nav-toggle" id="navToggle">☰</button>

            <nav class="nav-main" id="navMain">
                <a href="index.php">Inicio</a>
                <a href="movimientos.php">Movimientos</a>
                <a href="configuracion.php" class="activo">Configuración</a>
                <a href="#">Seguridad</a>
            </nav>
        </div>

        <div class="header-right">
            <div class="user-menu" id="userMenu">
                <div class="user-name">
                    <?php echo htmlspecialchars($nombreUsuario); ?> ▾
                </div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="#">Mi perfil</a>
                    <a href="/frontend/configuracion.php">Configuración</a>
                    <a href="#">Claves y seguridad</a>
                    <a href="../backend/controlador/logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-config">
        <div class="config-card">
            <h2>Configuración de seguridad</h2>
            <p class="config-subtitle">
                Ajusta cómo se puede usar tu tarjeta y define límites para prevenir fraudes.
            </p>

            <?php if ($mensajeOk): ?>
                <div class="alert success"><?php echo htmlspecialchars($mensajeOk); ?></div>
            <?php endif; ?>

            <?php if ($mensajeError): ?>
                <div class="alert error"><?php echo htmlspecialchars($mensajeError); ?></div>
            <?php endif; ?>

            <form method="post" class="config-form">
                <div class="cfg-item">
                    <div class="cfg-text">
                        <strong>Activar uso internacional</strong>
                        <span>Permite compras en el extranjero y en comercios en línea del exterior.</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="uso_internacional" <?php echo $usoInternacional ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="cfg-item">
                    <div class="cfg-text">
                        <strong>Definir horarios permitidos</strong>
                        <span>Solo se aceptarán compras dentro del rango seleccionado.</span>
                        <div class="cfg-horarios">
                            <label>
                                Desde
                                <input type="time" name="horario_inicio"
                                    value="<?php echo htmlspecialchars(substr($horarioInicio, 0, 5)); ?>">
                            </label>
                            <label>
                                Hasta
                                <input type="time" name="horario_fin"
                                    value="<?php echo htmlspecialchars(substr($horarioFin, 0, 5)); ?>">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="cfg-item">
                    <div class="cfg-text">
                        <strong>Límite máximo de gasto semanal</strong>
                        <span>Define un tope aproximado de consumo para proteger tu tarjeta.</span>
                    </div>
                    <div class="cfg-monto">
                        <span>S/</span>
                        <input type="number" step="0.01" min="0" name="limite_semanal"
                            value="<?php echo htmlspecialchars(number_format($limiteSemanal, 2, '.', '')); ?>">
                    </div>
                </div>

                <div class="cfg-item">
                    <div class="cfg-text">
                        <strong>Activar modo inteligente</strong>
                        <span>Analiza tus patrones de uso para detectar operaciones inusuales.</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="modo_inteligente" <?php echo $modoInteligente ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <button type="submit" class="btn-guardar">Guardar cambios</button>
            </form>
        </div>
    </main>

    <script>
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');
        const navToggle = document.getElementById('navToggle');
        const navMain = document.getElementById('navMain');

        if (userMenu && userDropdown) {
            userMenu.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.style.display =
                    userDropdown.style.display === 'block' ? 'none' : 'block';
            });
        }

        if (navToggle && navMain) {
            navToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                navMain.classList.toggle('show');
            });
        }

        document.addEventListener('click', (e) => {
            if (userMenu && userDropdown && !userMenu.contains(e.target)) {
                userDropdown.style.display = 'none';
            }
            if (navMain && navToggle &&
                !navMain.contains(e.target) &&
                !navToggle.contains(e.target)) {
                navMain.classList.remove('show');
            }
        });
    </script>

    <script src="https://cdn.botpress.cloud/webchat/v3.4/inject.js"></script>
    <script src="https://files.bpcontent.cloud/2025/11/15/08/20251115083512-2553XNUV.js" defer></script>
</body>

</html>