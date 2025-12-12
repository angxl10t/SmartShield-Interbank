<?php
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: inicio.php");
    exit;
}

require_once "../backend/bd/conexion.php";

$idUsuario = $_SESSION['id_usuario'];
$sqlAlertCount = "SELECT COUNT(*) 
                  FROM alertas 
                  WHERE id_usuario = :id_usuario
                    AND estado = 'nueva'";
$stmtAlert = $pdo->prepare($sqlAlertCount);
$stmtAlert->execute([':id_usuario' => $idUsuario]);
$alertasPendientes = (int)$stmtAlert->fetchColumn();

$sql = "SELECT t.id_tarjeta,
               t.numero_enmascarado,
               t.marca,
               t.estado,
               t.saldo_disponible,
               c.limite_semanal,
               c.gasto_semanal_actual,
               c.limite_mensual,
               c.gasto_mensual_actual
        FROM tarjetas t
        LEFT JOIN config_seguridad_tarjeta c
            ON c.id_tarjeta = t.id_tarjeta
        WHERE t.id_usuario = :id_usuario
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id_usuario' => $idUsuario]);
$tarjeta = $stmt->fetch(PDO::FETCH_ASSOC);

$nombreUsuario   = $_SESSION['nombre'] ?? 'Usuario';
$numeroTarjeta   = $tarjeta['numero_enmascarado']   ?? '**** **** **** 0000';
$marcaTarjeta    = $tarjeta['marca']                ?? 'VISA';
$estadoTarjeta   = $tarjeta['estado']               ?? 'activa';
$saldoDisponible = isset($tarjeta['saldo_disponible']) ? (float)$tarjeta['saldo_disponible'] : 0;

$limiteSemanal = isset($tarjeta['limite_semanal']) ? (float)$tarjeta['limite_semanal'] : 0;
$gastoSemanal  = isset($tarjeta['gasto_semanal_actual']) ? (float)$tarjeta['gasto_semanal_actual'] : 0;

$limiteMensual = isset($tarjeta['limite_mensual']) ? (float)$tarjeta['limite_mensual'] : 0;
$gastoMensual  = isset($tarjeta['gasto_mensual_actual']) ? (float)$tarjeta['gasto_mensual_actual'] : 0;

$porcSemanal = ($limiteSemanal > 0) ? min(100, ($gastoSemanal / $limiteSemanal) * 100) : 0;
$porcMensual = ($limiteMensual > 0) ? min(100, ($gastoMensual / $limiteMensual) * 100) : 0;

if ($limiteSemanal <= 0) {
    $limiteSemanal = 1200.00;
}

$msgOk    = $_SESSION['msg_ok']    ?? '';
$msgError = $_SESSION['msg_error'] ?? '';
unset($_SESSION['msg_ok'], $_SESSION['msg_error']);

$sqlAlertas = "
    SELECT 
        id_alerta,
        titulo,
        mensaje,
        tipo_alerta,
        nivel_riesgo,
        fecha_hora,
        estado
    FROM alertas
    WHERE id_usuario = :id_usuario
      AND estado = 'nueva'
      AND tipo_alerta <> 'destino_nuevo'
    ORDER BY fecha_hora DESC
    LIMIT 20
";

$stmtAlertas = $pdo->prepare($sqlAlertas);
$stmtAlertas->execute([':id_usuario' => $idUsuario]);

$alertasRecientes = $stmtAlertas->fetchAll(PDO::FETCH_ASSOC);


function formatearFechaAlerta($fechaHora)
{
    if (!$fechaHora) return '';
    $dt = new DateTime($fechaHora);
    return $dt->format('d/m/Y H:i');
}


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inicio - SmartShield Interbank</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../backend/css/dashboard.css">
    <link rel="stylesheet" href="../backend/css/modales.css">

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
                <a href="/SmartShield-Interbank/frontend/index.php" class="activo">Inicio</a>
                <a href="/SmartShield-Interbank/frontend/movimientos.php">Movimientos</a>
                <a href="/SmartShield-Interbank/frontend/configuracion.php">Configuracion</a>
                <a href="#">Seguridad</a>
            </nav>
        </div>

        <div class="header-right">
            <div class="header-bell" id="btnAlertas">
                ❕
                <?php if ($alertasPendientes > 0): ?>
                    <span class="badge-alertas">
                        <?php echo $alertasPendientes; ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="user-menu" id="userMenu">
                <div class="user-name">
                    <?php echo htmlspecialchars($nombreUsuario); ?> ▾
                </div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="#">Mi perfil</a>
                    <a href="#">Configuración</a>
                    <a href="#">Claves y seguridad</a>
                    <a href="../backend/controlador/logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="columns">
            <div class="col-left">
                <div class="card-box">
                    <div class="card-header">
                        <div class="card-title">Consulta</div>
                    </div>

                    <div class="card-subtitle">
                        Hola, <?php echo htmlspecialchars($nombreUsuario); ?>.
                        Esta es tu tarjeta protegida con SmartShield Interbank.
                    </div>

                    <div class="saldo-card">
                        <div class="saldo-icono-grande">
                            <img src="../backend/img/alcancia.png" alt="Ahorro">
                        </div>
                        <div class="saldo-textos">
                            <span class="saldo-cuenta">Cuenta Sueldo Soles</span>
                            <span class="saldo-monto-grande">
                                S/ <?php echo number_format($saldoDisponible, 2); ?>
                            </span>
                            <span class="saldo-etiqueta">Saldo disponible</span>
                        </div>
                    </div>

                    <div class="tarjeta-visual">
                        <div class="tarjeta-logo">Interbank</div>
                        <div class="tarjeta-chip"></div>
                        <div class="tarjeta-numero">**** **** **** 3456</div>
                        <div class="tarjeta-nombre"><?php echo htmlspecialchars($nombreUsuario); ?></div>
                        <div class="tarjeta-marca">VISA</div>
                    </div>

                    <button class="btn-estado" id="btnBloquear">
                        Bloquear tarjeta
                    </button>
                </div>
            </div>
            
            <!-- COLUMNA LA DE GASTPS -->

            <div class="col-middle">
                <div class="gasto-box">
                    <div class="card-header">
                        <div class="card-title">Gastos y seguridad</div>
                    </div>
                    <p class="gasto-subtitle">
                        Controla tu gasto semanal y detecta a tiempo consumos inusuales.
                    </p>

                    <div class="gasto-layout">
                        <div class="donut-grande-box">
                            <div class="donut-grande"
                                id="donutGrande"
                                data-porcentaje="<?php echo $porcSemanal; ?>"
                                data-monto="<?php echo $gastoSemanal; ?>">
                                <div class="donut-grande-inner">
                                    <div class="dg-monto" id="dgMonto">S/ 0.00</div>
                                    <div class="dg-porc" id="dgPorc">0.0%</div>
                                </div>
                            </div>

                            <div class="dg-label">Gasto semanal</div>
                            <div class="dg-info">
                                Límite configurado:
                                <strong>S/ <?php echo number_format($limiteSemanal, 2); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="opcion-box" id="btnRecomendaciones">
                        <div class="opcion-icono opcion-icono-seguridad">
                            <img src="../backend/img/recomendacion2.png" alt="Seguridad" class="icono-seguridad-img">
                        </div>
                        <div>
                            <div class="opcion-titulo " >Recomendaciones de seguridad</div>
                            <div class="opcion-texto">
                                Sugerencias personalizadas.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA 3 -->
            <div class="col-right">
                <div class="gasto-box transfer-box">
                    <div class="card-header">
                        <div class="card-title">Transferencias</div>
                    </div>
                    <p class="card-subtitle">
                        Registra transferencias de prueba y mira cómo afecta a tu gasto semanal.
                    </p>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert-msg alert-error">
                            <?php if ($_GET['error'] === 'saldo'): ?>
                                Saldo insuficiente para realizar la transferencia.
                            <?php elseif ($_GET['error'] === 'datos'): ?>
                                Por favor, completa correctamente los datos de la transferencia.
                            <?php else: ?>
                                Ocurrió un error al procesar la transferencia.
                            <?php endif; ?>
                        </div>
                    <?php elseif (isset($_GET['ok'])): ?>
                        <div class="alert-msg alert-success">
                            Transferencia registrada correctamente.
                        </div>
                    <?php endif; ?>


                    <form id="formTransferencia"
                        method="post"
                        action="../backend/controlador/registrar_transferencia.php">

                        <div class="form-group">
                            <label for="destinatario">Transferir a</label>
                            <select name="destinatario" id="destinatario" class="form-control">
                                <option value="">Seleccione un destinatario</option>
                                <option value="1">Cuenta propia</option>
                                <option value="2">Servicio básico</option>
                                <option value="3">Otro beneficiario</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="alias_destino">Alias / nombre del destinatario (opcional)</label>
                            <input type="text"
                                name="alias_destino"
                                id="alias_destino"
                                class="form-control"
                                placeholder="Ej: Luz de la casa, Netflix, Juan Pérez...">
                        </div>

                        <div class="form-group">
                            <label for="cuenta_destino">Número de cuenta / tarjeta</label>
                            <input type="text"
                                name="cuenta_destino"
                                id="cuenta_destino"
                                class="form-control"
                                placeholder="Ej: 123-4567891234"
                                required>
                        </div>

                        <div class="form-row">
                            <div class="form-group half">
                                <label for="moneda">Moneda</label>
                                <select name="moneda" id="moneda" class="form-control">
                                    <option value="PEN">Soles (PEN)</option>
                                    <option value="USD">Dólares (USD)</option>
                                </select>
                            </div>

                            <div class="form-group half">
                                <label for="monto">Monto</label>
                                <input type="number"
                                    step="0.01"
                                    min="0"
                                    name="monto"
                                    id="monto"
                                    class="form-control"
                                    placeholder="0.00"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción (opcional)</label>
                            <input type="text"
                                name="descripcion"
                                id="descripcion"
                                class="form-control"
                                placeholder="Ej: Compra supermercado, pago de servicio...">
                        </div>

                        <button type="submit" class="btn-estado btn-transferir">
                            Transferir
                        </button>
                    </form>

                    <p class="actividad-nota" style="margin-top: 10px;">
                        Cada transferencia realizada se considerará en el cálculo del gasto semanal
                        y permitirá generar alertas inteligentes sobre tus consumos.
                    </p>
                </div>
            </div>

        </div>

    </div>

    <script>
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');

        if (userMenu && userDropdown) {
            userMenu.addEventListener('click', (e) => {
                e.stopPropagation();
                const visible = userDropdown.style.display === 'block';
                userDropdown.style.display = visible ? 'none' : 'block';
            });
        }
        const navToggle = document.getElementById('navToggle');
        const navMain = document.getElementById('navMain');

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


    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const donut = document.getElementById("donutGrande");
            const montoSpan = document.getElementById("dgMonto");
            const porcSpan = document.getElementById("dgPorc");

            if (!donut || !montoSpan || !porcSpan) return;

            const porcentajeFinal = parseFloat(donut.dataset.porcentaje || "0");
            const montoFinal = parseFloat(donut.dataset.monto || "0");

            let inicio = null;
            const duracion = 1200; // ms

            function animar(timestamp) {
                if (!inicio) inicio = timestamp;
                const progreso = Math.min((timestamp - inicio) / duracion, 1); // 0 a 1
                const porcActual = porcentajeFinal * progreso;
                const montoActual = montoFinal * progreso;

                donut.style.setProperty("--porcentaje", porcActual + "%");

                montoSpan.textContent = "S/ " + montoActual.toFixed(2);
                porcSpan.textContent = porcActual.toFixed(1) + "%";

                if (progreso < 1) {
                    requestAnimationFrame(animar);
                }
            }

            requestAnimationFrame(animar);
        });
    </script>

    <div class="modal-overlay" id="modalAlertas">
        <div class="modal-dialog">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Alertas recientes</div>
                    <div class="modal-subtitle">Últimos eventos relacionados con la seguridad de tu tarjeta.</div>
                </div>
                <button class="modal-close" data-close-modal="modalAlertas">✕</button>
            </div>

            <div class="modal-body modal-alertas-body">

                <?php if (empty($alertasRecientes)): ?>
                    <p class="sin-alertas">Por ahora no tienes alertas recientes. 🎉</p>
                <?php else: ?>

                    <div class="alertas-scroll">
                        <?php foreach ($alertasRecientes as $al): ?>
                            <?php
                            $tipo  = $al['tipo_alerta'] ?? '';
                            $fecha = date("d/m/Y H:i", strtotime($al['fecha_hora']));

                            $tituloUI = $al['titulo'] ?? 'Alerta de seguridad';
                            $msgUI    = $al['mensaje'] ?? '';
                            $iconSimbolo = 'i';
                            $iconClase   = 'alerta-default';

                            switch ($tipo) {
                                case 'limite_superado':
                                    $tituloUI    = 'Has superado tu límite semanal';
                                    $msgUI       = 'Tu consumo semanal ha superado el tope configurado. Revisa tus operaciones recientes y, si es necesario, ajusta tu límite.';
                                    $iconSimbolo = '⚠';
                                    $iconClase   = 'alerta-limite';
                                    break;

                                case 'fuera_horario':
                                    $tituloUI    = 'Operación fuera del horario habitual';
                                    $msgUI       = 'Se detectó una transferencia fuera del horario configurado para tus compras habituales. Verifica si fuiste tú.';
                                    $iconSimbolo = '⏰';
                                    $iconClase   = 'alerta-horario';
                                    break;
                                
                                case 'fraude_ml_alto':
                                    $iconSimbolo = '🤖';
                                    $iconClase   = 'alerta-ml-critica';
                                    break;
                                
                                case 'riesgo_ml_detectado':
                                    $iconSimbolo = '🤖';
                                    $iconClase   = 'alerta-ml-moderada';
                                    break;
                            }
                            ?>
                            <div class="alerta-item">
                                <div class="alerta-icono <?php echo $iconClase; ?>">
                                    <?php echo $iconSimbolo; ?>
                                </div>

                                <div class="alerta-contenido">
                                    <div class="alerta-titulo">
                                        <?php echo htmlspecialchars($tituloUI); ?>
                                    </div>
                                    <div class="alerta-texto">
                                        <?php echo htmlspecialchars($msgUI); ?>
                                    </div>
                                    <div class="alerta-meta">
                                        <span class="alerta-fecha"><?php echo $fecha; ?></span>
                                        <?php if (!empty($tipo)): ?>
                                            <span class="alerta-tipo"><?php echo htmlspecialchars($tipo); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="alerta-acciones">
                                    <button
                                        type="button"
                                        class="alerta-ver-btn"
                                        title="Marcar como vista"
                                        data-id-alerta="<?php echo (int)$al['id_alerta']; ?>">
                                        👁
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

            </div>



            <div class="modal-footer">
                <button class="modal-btn-sec" onclick="location.href='movimientos.php'">
                    Ver movimientos
                </button>
                <button id="btnEntendidoAlertas"  class="modal-btn-primary">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalRecomendaciones">
        <div class="modal-dialog">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Recomendaciones de seguridad</div>
                    <div class="modal-subtitle">Acciones sugeridas para mantener tu tarjeta protegida.</div>
                </div>
                <button class="modal-close" data-close-modal="modalRecomendaciones">✕</button>
            </div>

            <div class="modal-body">
                <div class="reco-item">
                    <div class="reco-icon">🔐</div>
                    <div class="reco-content">
                        <div class="reco-title">Actualiza tu clave periódicamente</div>
                        <div class="reco-text">Te sugerimos cambiar tus claves cada 90 días y evitar repetir contraseñas usadas en otras aplicaciones.</div>
                    </div>
                </div>

                <div class="reco-item">
                    <div class="reco-icon">📲</div>
                    <div class="reco-content">
                        <div class="reco-title">Activa alertas por SMS o correo</div>
                        <div class="reco-text">Recibe notificaciones cada vez que se realice un consumo con tu tarjeta para detectar movimientos no reconocidos a tiempo.</div>
                    </div>
                </div>

                <div class="reco-item">
                    <div class="reco-icon">🕒</div>
                    <div class="reco-content">
                        <div class="reco-title">Configura horarios de uso</div>
                        <div class="reco-text">Limita el uso de tu tarjeta a los horarios en los que normalmente realizas compras para reducir el riesgo de fraudes nocturnos.</div>
                    </div>
                </div>

                <div class="reco-item">
                    <div class="reco-icon">🌍</div>
                    <div class="reco-content">
                        <div class="reco-title">Activa el uso internacional solo cuando viajes</div>
                        <div class="reco-text">Mantén desactivadas las compras internacionales y en línea si no las estás utilizando.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="modal-btn-sec" onclick="location.href='configuracion.php'">Ir a Configuración</button>
                <button class="modal-btn-primary" data-close-modal="modalRecomendaciones">Listo</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const btnAlertas = document.getElementById("btnAlertas");
            const btnRecomendaciones = document.getElementById("btnRecomendaciones");
            const modalAlertas = document.getElementById("modalAlertas");
            const modalRecom = document.getElementById("modalRecomendaciones");

            function abrirModal(modal) {
                if (!modal) return;
                modal.classList.add("show");
            }

            function cerrarModal(modal) {
                if (!modal) return;
                modal.classList.remove("show");
            }

            if (btnAlertas && modalAlertas) {
                btnAlertas.addEventListener("click", () => abrirModal(modalAlertas));
            }

            if (btnRecomendaciones && modalRecom) {
                btnRecomendaciones.addEventListener("click", () => abrirModal(modalRecom));
            }

            document.querySelectorAll("[data-close-modal]").forEach(btn => {
                btn.addEventListener("click", () => {
                    const id = btn.getAttribute("data-close-modal");
                    const modal = document.getElementById(id);
                    cerrarModal(modal);
                });
            });

            [modalAlertas, modalRecom].forEach(modal => {
                if (!modal) return;
                modal.addEventListener("click", (e) => {
                    if (e.target === modal) {
                        cerrarModal(modal);
                    }
                });
            });

            document.addEventListener("keydown", (e) => {
                if (e.key === "Escape") {
                    cerrarModal(modalAlertas);
                    cerrarModal(modalRecom);
                }
            });
            document.querySelectorAll(".alerta-ver-btn").forEach(btn => {
                btn.addEventListener("click", async () => {
                    const idAlerta = btn.getAttribute("data-id-alerta");
                    if (!idAlerta) return;

                    try {
                        const resp = await fetch("../backend/controlador/marcar_alerta_vista.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: "id_alerta=" + encodeURIComponent(idAlerta)
                        });

                        const data = await resp.json();
                        if (data.ok) {
                            const item = btn.closest(".alerta-item");
                            if (item) {
                                item.remove();
                            }

                            const badge = document.getElementById("badgeAlertas");
                            if (badge) {
                                let n = parseInt(badge.textContent || "0", 10);
                                n = isNaN(n) ? 0 : Math.max(0, n - 1);
                                if (n <= 0) {
                                    badge.style.display = "none";
                                } else {
                                    badge.textContent = n;
                                }
                            }

                            const contenedor = document.querySelector(".alertas-scroll");
                            if (contenedor && contenedor.children.length === 0) {
                                contenedor.innerHTML = "<p class='sin-alertas'>Por ahora no tienes alertas recientes. 🎉</p>";
                            }
                        } else {
                            console.error("Error al marcar alerta:", data.error);
                        }
                    } catch (err) {
                        console.error("Error de red al marcar alerta:", err);
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const btnEntendido = document.getElementById("btnEntendidoAlertas");
            const modalAlertas = document.getElementById("modalAlertas");

            if (btnEntendido) {
                btnEntendido.addEventListener("click", () => {
                    modalAlertas.classList.remove("show");
                    setTimeout(() => {
                        location.reload();
                    }, 250);
                });
            }

        });
    </script>


    <script src="https://cdn.botpress.cloud/webchat/v3.4/inject.js"></script>
    <script src="https://files.bpcontent.cloud/2025/11/15/08/20251115083512-2553XNUV.js" defer></script>
</body>

</html>