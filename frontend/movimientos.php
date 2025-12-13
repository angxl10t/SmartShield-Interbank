<?php
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: inicio.php");
    exit;
}

require_once "../backend/bd/conexion.php";

$idUsuario     = $_SESSION['id_usuario'];
$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';

$rango = $_GET['rango'] ?? '7';

$condiciones = "WHERE id_usuario = :id_usuario";
$params = [':id_usuario' => $idUsuario];

if ($rango === '7' || $rango === '30') {
    $dias = (int)$rango;
    $fechaDesde = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
    $condiciones .= " AND fecha_hora >= :desde";
    $params[':desde'] = $fechaDesde;
}

$sql = "SELECT 
            id_transaccion,
            fecha_hora,
            moneda,
            monto,
            descripcion,
            destino,
            tipo,
            alias_destino,
            numero_cuenta
        FROM transacciones
        $condiciones
        ORDER BY fecha_hora DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatearFecha($fecha)
{
    if (!$fecha) return '';
    $dt = new DateTime($fecha);
    return $dt->format('d/m/Y H:i');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Movimientos - SmartShield Interbank</title>
    <link rel="stylesheet" href="../backend/css/dashboard.css">
    <link rel="stylesheet" href="../backend/css/movimientos.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <header class="header">
        <div class="header-left">
            <div class="header-logo">
                <span>▢</span>
                <span>Interbank</span>
            </div>

            <button class="nav-toggle" id="navToggle">
                ☰
            </button>

            <nav class="nav-main" id="navMain">
                <a href="index.php">Inicio</a>
                <a href="movimientos.php" class="activo">Movimientos</a>
                <a href="configuracion.php">Configuración</a>
                <a href="configuracion.php">Seguridad</a>
            </nav>
        </div>

        <div class="header-right">
            <div class="user-menu" id="userMenu">
                <div class="user-name">
                    <?php echo htmlspecialchars($nombreUsuario); ?> ▾
                </div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="configuracion.php">Mi perfil</a>
                    <a href="configuracion.php">Configuración</a>
                    <a href="configuracion.php">Claves y seguridad</a>
                    <a href="../backend/controlador/logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container movimientos-main">
        <div class="movimientos-wrapper">

            <div class="mov-header">
                <div>
                    <h1 class="mov-titulo">Movimientos recientes</h1>
                    <p class="mov-subtitulo">
                        Revisa las transferencias simuladas realizadas con tu tarjeta protegida.
                    </p>
                </div>
            </div>

            <form class="mov-filtros" method="get" action="movimientos.php">
                <div class="filtro-item">
                    <label for="rango">Período</label>
                    <select name="rango" id="rango" onchange="this.form.submit()">
                        <option value="7" <?php echo ($rango === '7')   ? 'selected' : ''; ?>>Últimos 7 días</option>
                        <option value="30" <?php echo ($rango === '30')  ? 'selected' : ''; ?>>Últimos 30 días</option>
                        <option value="todo" <?php echo ($rango === 'todo') ? 'selected' : ''; ?>>Todos los movimientos</option>
                    </select>
                </div>
                <div class="filtro-item filtro-info">
                    <?php if ($rango === '7'): ?>
                        Mostrando movimientos de los últimos 7 días.
                    <?php elseif ($rango === '30'): ?>
                        Mostrando movimientos de los últimos 30 días.
                    <?php else: ?>
                        Mostrando todos los movimientos registrados.
                    <?php endif; ?>
                </div>
            </form>

            <?php if (empty($movimientos)): ?>
                <div class="mov-vacio">
                    <div class="mov-vacio-icono">💳</div>
                    <div class="mov-vacio-textos">
                        <div class="mov-vacio-titulo">Aún no tienes movimientos registrados</div>
                        <div class="mov-vacio-texto">
                            Realiza una transferencia desde la pantalla de Inicio para ver aquí tus operaciones simuladas.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="mov-lista">
                    <?php foreach ($movimientos as $mov): ?>
                        <?php
                        $monto        = (float)($mov['monto'] ?? 0);
                        $moneda       = $mov['moneda'] ?? 'PEN';
                        $descripcion  = $mov['descripcion'] ?: 'Transferencia simulada';
                        $destino      = $mov['destino'] ?? '';
                        $tipo         = $mov['tipo'] ?? 'transferencia';
                        $alias        = trim($mov['alias_destino'] ?? '');
                        $numeroCuenta = trim($mov['numero_cuenta'] ?? '');
                        $titulo = $alias !== '' ? $alias : ($destino !== '' ? $destino : 'Transferencia');
                        ?>
                        <div class="mov-item">
                            <div class="mov-icono">
                                ⬆
                            </div>
                            <div class="mov-detalle">
                                <div class="mov-linea1">
                                    <span class="mov-desc">
                                        <?php echo htmlspecialchars($titulo); ?>
                                    </span>
                                    <span class="mov-monto">
                                        - <?php echo htmlspecialchars($moneda); ?>
                                        <?php echo number_format($monto, 2); ?>
                                    </span>
                                </div>

                                <div class="mov-linea2">
                                    <span class="mov-destino">
                                        <?php echo htmlspecialchars(ucfirst($tipo)); ?>
                                    </span>
                                    <span class="mov-fecha">
                                        <?php echo htmlspecialchars(formatearFecha($mov['fecha_hora'])); ?>
                                    </span>
                                </div>

                                <?php if ($numeroCuenta !== '' || $descripcion !== ''): ?>
                                    <div class="mov-linea3">
                                        <?php if ($numeroCuenta !== ''): ?>
                                            <span class="mov-cuenta">
                                                Cuenta: <?php echo htmlspecialchars($numeroCuenta); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($descripcion !== ''): ?>
                                            <span class="mov-descripcion">
                                                · <?php echo htmlspecialchars($descripcion); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');
        const navToggle = document.getElementById('navToggle');
        const navMain = document.getElementById('navMain');

        if (userMenu && userDropdown) {
            userMenu.addEventListener('click', (e) => {
                e.stopPropagation();
                const visible = userDropdown.style.display === 'block';
                userDropdown.style.display = visible ? 'none' : 'block';
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
