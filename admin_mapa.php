<?php
session_start();
require_once("Bd/ligar.php");
require_once("Bd/mesa_status_helper.php");

if (!isset($_SESSION['permissoes']) || $_SESSION['permissoes'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function cd_mapa_csrf(): string
{
    return (string)($_SESSION['csrf_token'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['libertar_mesa_mapa'], $_POST['reserva_id'])) {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token !== '' && hash_equals(cd_mapa_csrf(), $token) && isset($con) && $con instanceof mysqli) {
        $idReserva = (int)$_POST['reserva_id'];
        if ($idReserva > 0) {
            mysqli_begin_transaction($con);
            try {
                $stmtLivre = mysqli_prepare(
                    $con,
                    "UPDATE mesas m
                     JOIN reserva_mesas rm ON rm.mesa_id = m.id
                     SET m.estado = 'livre'
                     WHERE rm.reserva_id = ?"
                );
                if ($stmtLivre) {
                    mysqli_stmt_bind_param($stmtLivre, 'i', $idReserva);
                    mysqli_stmt_execute($stmtLivre);
                    mysqli_stmt_close($stmtLivre);
                }

                $stmtDelete = mysqli_prepare($con, "DELETE FROM reserva_mesas WHERE reserva_id = ?");
                if ($stmtDelete) {
                    mysqli_stmt_bind_param($stmtDelete, 'i', $idReserva);
                    mysqli_stmt_execute($stmtDelete);
                    mysqli_stmt_close($stmtDelete);
                }

                mysqli_commit($con);
            } catch (Throwable $e) {
                mysqli_rollback($con);
            }
        }
    }

    header("Location: admin_mapa.php");
    exit();
}

$mesaEstados = [];
$mesaLocks = [];
$mesaLayout = [];
if (isset($con) && $con instanceof mysqli) {
    cd_sync_mesa_states($con);
    $mesaLocks = cd_get_mesa_lock_map($con);
    $hasPosLeft = false;
    $hasPosTop = false;
    $hasPosRight = false;
    $hasGrupo = false;

    $colRes = mysqli_query($con, "SHOW COLUMNS FROM mesas");
    if ($colRes) {
        while ($colRow = mysqli_fetch_assoc($colRes)) {
            $field = (string)($colRow['Field'] ?? '');
            if ($field === 'pos_left') {
                $hasPosLeft = true;
            } elseif ($field === 'pos_top') {
                $hasPosTop = true;
            } elseif ($field === 'pos_right') {
                $hasPosRight = true;
            } elseif ($field === 'grupo') {
                $hasGrupo = true;
            }
        }
    }

    $hasTipoMesaRows = $hasTipo = false;
    $hasAtivaRows = false;

    if ($colRes) {
        mysqli_data_seek($colRes, 0);
        while ($colRow = mysqli_fetch_assoc($colRes)) {
            $field = (string)($colRow['Field'] ?? '');
            if ($field === 'tipo') {
                $hasTipo = true;
            } elseif ($field === 'ativa') {
                $hasAtivaRows = true;
            }
        }
    }

    $hasTipoMesaRows = $hasTipo && cd_has_rows_for_condition_safe($con, 'mesas', "tipo = 'mesa'");
    $hasAtivaRows = $hasAtivaRows && cd_has_rows_for_condition_safe($con, 'mesas', "ativa = 1");

    $posTopColumn = $hasPosTop ? 'pos_top' : ($hasPosRight ? 'pos_right' : null);
    $selectParts = ["id", "estado"];
    $selectParts[] = $hasPosLeft ? "pos_left AS pos_left" : "NULL AS pos_left";
    $selectParts[] = $posTopColumn !== null ? "{$posTopColumn} AS pos_top" : "NULL AS pos_top";
    $selectParts[] = $hasGrupo ? "grupo AS grupo" : "NULL AS grupo";

    $whereParts = [];
    if ($hasTipoMesaRows) {
        $whereParts[] = "tipo = 'mesa'";
    }
    if ($hasAtivaRows) {
        $whereParts[] = "ativa = 1";
    }
    $whereSql = count($whereParts) > 0 ? " WHERE " . implode(' AND ', $whereParts) : '';

    $sqlMesas = "SELECT " . implode(', ', $selectParts) . " FROM mesas" . $whereSql;
    $resMesas = mysqli_query($con, $sqlMesas);
    if ($resMesas) {
        while ($row = mysqli_fetch_assoc($resMesas)) {
            $id = (string)($row['id'] ?? '');
            $estado = strtolower(trim((string)($row['estado'] ?? '')));
            if ($id === '') {
                continue;
            }
            if (!in_array($estado, ['livre', 'reservada', 'ocupada'], true)) {
                $estado = 'livre';
            }
            $mesaEstados[$id] = $estado;

            $posLeft = trim((string)($row['pos_left'] ?? ''));
            $posTop = trim((string)($row['pos_top'] ?? ''));
            $grupo = trim((string)($row['grupo'] ?? ''));
            $layoutItem = [];
            if ($posLeft !== '') {
                $layoutItem['left'] = $posLeft;
            }
            if ($posTop !== '') {
                $layoutItem['top'] = $posTop;
            }
            if ($grupo !== '') {
                $layoutItem['group'] = $grupo;
            }
            if (!empty($layoutItem)) {
                $mesaLayout[$id] = $layoutItem;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="Imagens/logo_atual.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Mesas</title>
    <link rel="stylesheet" href="Css/admin.css?v=<?php echo filemtime(__DIR__ . '/Css/admin.css'); ?>">
    <link rel="stylesheet" href="Css/bttlogin.css">
    <link rel="stylesheet" href="Css/admin_mapa.css">
</head>
<body class="cdol-admin cdol-admin-home cdol-mapa">
    <script>
        document.documentElement.classList.add('admin-home-page');
    </script>
    <button type="button" class="admin-home-menu-toggle" aria-label="Abrir menu admin" aria-expanded="false" aria-controls="adminHomeSidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <button type="button" class="admin-home-menu-overlay" aria-label="Fechar menu admin"></button>
    <aside class="admin-home-sidebar" id="adminHomeSidebar" aria-label="Menu administrativo">
        <div class="admin-home-sidebar-brand">
            <span class="admin-home-kicker">Cantinho Deolinda</span>
            <strong>Painel Admin</strong>
            <p>Centro de gestão do backoffice.</p>
        </div>
        <nav class="admin-home-nav">
            <a href="admin.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 11.2 12 4l8 7.2V20a1 1 0 0 1-1 1h-4.8v-5.5H9.8V21H5a1 1 0 0 1-1-1z"/></svg></span><span class="admin-home-link-copy"><strong>Visão geral</strong><small>Painel principal</small></span></a>
            <a href="Bd/confirmar_reservas.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 12.5 10.2 16 17 8.8"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg></span><span class="admin-home-link-copy"><strong>Confirmar reservas</strong><small>Entradas pendentes</small></span></a>
            <a href="admin_reservas.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="5" width="6" height="6" rx="1.5"/><rect x="14" y="5" width="6" height="6" rx="1.5"/><rect x="4" y="13" width="6" height="6" rx="1.5"/><rect x="14" y="13" width="6" height="6" rx="1.5"/></svg></span><span class="admin-home-link-copy"><strong>Todas as reservas</strong><small>Lista completa</small></span></a>
            <a href="admin_logs.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 7h10M7 12h10M7 17h10"/><rect x="4" y="4" width="16" height="16" rx="4"/></svg></span><span class="admin-home-link-copy"><strong>Logs</strong><small>Atividade do sistema</small></span></a>
            <a href="admin_mapa.php" class="is-active" aria-current="page"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M8 6.5 4.5 8v10L8 16.5l4 1.5 3.5-1.5L19.5 18V8l-4 1.5L12 8 8 9.5z"/><path d="M8 6.5v10M12 8v10M15.5 9.5v10"/></svg></span><span class="admin-home-link-copy"><strong>Mapa de mesas</strong><small>Disposição da sala</small></span></a>
            <a href="admin_feedback.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M7 17.5 4.5 20V7a2 2 0 0 1 2-2h11A2.5 2.5 0 0 1 20 7.5v7a2.5 2.5 0 0 1-2.5 2.5z"/><path d="M8 10h8M8 13h5"/></svg></span><span class="admin-home-link-copy"><strong>Feedback</strong><small>Opiniões dos clientes</small></span></a>
        </nav>
        <div class="admin-home-sidebar-footer">
            <a href="dashboard.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 19V9.5L12 5l7 4.5V19z"/><path d="M9 19v-5h6v5"/></svg></span><span class="admin-home-link-copy"><strong>Dashboard</strong><small>Vista do utilizador</small></span></a>
            <a href="index.php"><span class="admin-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M10 7 5 12l5 5"/><path d="M6 12h9a4 4 0 1 0 0 8"/></svg></span><span class="admin-home-link-copy"><strong>Voltar ao site</strong><small>Regressar à homepage</small></span></a>
        </div>
    </aside>
    <div class="container">
        <div class="admin-hero">
            <div>
                <h2>Mapa de Mesas</h2>
                <p>Layout visual por zonas: Sala, Sofás e Esplanada.</p>
            </div>
            <div class="admin-kpis">
                <div class="admin-kpi-card">
                    <span>Total Mesas</span>
                    <strong id="kpiTotalMesas">31</strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Lugares Livres</span>
                    <strong id="kpiLivre">112</strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Lug. Reservados</span>
                    <strong id="kpiReservada">0</strong>
                </div>
                <div class="admin-kpi-card">
                    <span>Lug. Ocupados</span>
                    <strong id="kpiOcupada">0</strong>
                </div>
            </div>
        </div>

        <section class="admin-section mapa-wrap">
            <div class="mapa-toolbar">
                <div class="mapa-legenda">
                    <span class="legenda-item"><i class="dot livre"></i>Livre</span>
                    <span class="legenda-item"><i class="dot reservada"></i>Reservada</span>
                    <span class="legenda-item"><i class="dot ocupada"></i>Ocupada</span>
                </div>
                <div class="mapa-actions">
                    <p class="mapa-tip">Arrasta para posicionar. Clique alterna estados: Livre → Reservada → Ocupada.</p>
                    <button type="button" id="mapaResetBtn" class="mapa-reset-btn">Resetar layout</button>
                </div>
            </div>
            <div class="mapa-merge-toolbar">
                <button type="button" id="mapaMoveModeBtn" class="mapa-merge-btn">Modo mover: OFF</button>
                <button type="button" id="mapaMergeModeBtn" class="mapa-merge-btn">Modo juntar mesas: OFF</button>
                <button type="button" id="mapaMergeCreateBtn" class="mapa-merge-btn" disabled>Criar conjunto</button>
                <button type="button" id="mapaMergeClearBtn" class="mapa-merge-btn mapa-merge-btn-danger" disabled>Limpar conjuntos</button>
                <span id="mapaMergeHint" class="mapa-merge-hint">Ativa o modo para selecionar mesas e juntá-las.</span>
            </div>
            <form method="post" id="mapaReleaseBar" class="mapa-release-bar" hidden>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(cd_mapa_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="reserva_id" id="mapaReleaseReservaId" value="">
                <div class="mapa-release-copy">
                    <strong id="mapaReleaseMesa">Mesa</strong>
                    <span id="mapaReleaseInfo">Seleciona uma mesa reservada ou ocupada para ver os detalhes.</span>
                </div>
                <div class="mapa-release-meta">
                    <span><b>Cliente</b><strong id="mapaReleaseCliente">-</strong></span>
                    <span><b>Pessoas</b><strong id="mapaReleasePessoas">-</strong></span>
                    <span><b>Reserva</b><strong id="mapaReleaseReservaHora">-</strong></span>
                    <span><b>Estado</b><strong id="mapaReleaseEstado">-</strong></span>
                    <span><b>Liberta</b><strong id="mapaReleaseFim">-</strong></span>
                </div>
                <button type="submit" class="mapa-release-btn" id="mapaReleaseBtn" name="libertar_mesa_mapa" value="1" hidden>Libertar mesa</button>
            </form>

            <div class="mapa-grid">
                <article class="mapa-card">
                    <div class="mapa-card-head">
                        <h3 class="mapa-card-title">Sala</h3>
                        <div class="mapa-room-meta">
                            <span>2p x5</span>
                            <span>4p x6</span>
                            <span>6p x1</span>
                        </div>
                    </div>
                    <div class="restaurante-canvas" id="mapaSala">
                        <div class="zona-bar" data-id="sala-balcao" style="left: 3%; top: 20%; width: 16%; height: 28%;">Balcão</div>
                        <div class="zona-entrada" data-id="sala-entrada" style="left: 4%; top: 84%; width: 18%; height: 12%;">Entrada</div>

                        <button class="mesa livre redonda cap-2" data-id="s1" style="left: 34%; top: 76%;">1</button>
                        <button class="mesa livre redonda cap-2" data-id="s2" style="left: 64%; top: 60%;">2</button>
                        <button class="mesa livre redonda cap-2" data-id="s3" style="left: 64%; top: 44%;">3</button>
                        <button class="mesa livre redonda cap-2" data-id="s4" style="left: 46%; top: 12%;">4</button>
                        <button class="mesa livre redonda cap-2" data-id="s5" style="left: 64%; top: 28%;">5</button>

                        <button class="mesa livre quadrada cap-4" data-id="s6" style="left: 48%; top: 76%;">6</button>
                        <button class="mesa livre quadrada cap-4" data-id="s7" style="left: 66%; top: 76%;">7</button>
                        <button class="mesa livre quadrada cap-4" data-id="s8" style="left: 82%; top: 76%;">8</button>
                        <button class="mesa livre quadrada cap-4" data-id="s9" style="left: 82%; top: 60%;">9</button>
                        <button class="mesa livre quadrada cap-4" data-id="s10" style="left: 82%; top: 44%;">10</button>
                        <button class="mesa livre quadrada cap-4" data-id="s11" style="left: 82%; top: 28%;">11</button>

                        <button class="mesa livre retangular cap-6" data-id="s12" style="left: 82%; top: 12%;">12</button>
                    </div>
                </article>

                <article class="mapa-card">
                    <div class="mapa-card-head">
                        <h3 class="mapa-card-title">Sofás</h3>
                        <div class="mapa-room-meta">
                            <span>2p x5</span>
                            <span>4p x5</span>
                        </div>
                    </div>
                    <div class="restaurante-canvas" id="mapaSofas">
                        <button class="mesa livre quadrada cap-4" data-id="f1" style="left: 22%; top: 74%;">1</button>
                        <button class="mesa livre quadrada cap-4" data-id="f2" style="left: 22%; top: 58%;">2</button>
                        <button class="mesa livre quadrada cap-4" data-id="f3" style="left: 72%; top: 58%;">3</button>
                        <button class="mesa livre quadrada cap-4" data-id="f4" style="left: 22%; top: 42%;">4</button>
                        <button class="mesa livre quadrada cap-4" data-id="f5" style="left: 72%; top: 74%;">5</button>

                        <button class="mesa livre redonda cap-2" data-id="f6" style="left: 22%; top: 26%;">6</button>
                        <button class="mesa livre redonda cap-2" data-id="f7" style="left: 22%; top: 12%;">7</button>
                        <button class="mesa livre redonda cap-2" data-id="f8" style="left: 72%; top: 42%;">8</button>
                        <button class="mesa livre redonda cap-2" data-id="f9" style="left: 72%; top: 26%;">9</button>
                        <button class="mesa livre redonda cap-2" data-id="f10" style="left: 72%; top: 12%;">10</button>
                    </div>
                </article>

                <article class="mapa-card">
                    <div class="mapa-card-head">
                        <h3 class="mapa-card-title">Esplanada</h3>
                        <div class="mapa-room-meta">
                            <span>2p x2</span>
                            <span>4p x6</span>
                            <span>6p x1</span>
                            <span>8p x1</span>
                        </div>
                    </div>
                    <div class="restaurante-canvas" id="mapaEsplanada">
                        <button class="mesa livre quadrada cap-4" data-id="e1" style="left: 18%; top: 16%;">1</button>
                        <button class="mesa livre quadrada cap-4" data-id="e2" style="left: 18%; top: 36%;">2</button>
                        <button class="mesa livre quadrada cap-4" data-id="e3" style="left: 66%; top: 16%;">3</button>
                        <button class="mesa livre quadrada cap-4" data-id="e4" style="left: 66%; top: 36%;">4</button>
                        <button class="mesa livre retangular cap-6" data-id="e5" style="left: 86%; top: 58%;">5</button>
                        <button class="mesa livre quadrada cap-4" data-id="e6" style="left: 86%; top: 82%;">6</button>

                        <button class="mesa livre retangular cap-8" data-id="e7" style="left: 26%; top: 82%;">7</button>

                        <button class="mesa livre redonda cap-2" data-id="e8" style="left: 16%; top: 58%;">8</button>
                        <button class="mesa livre redonda cap-2" data-id="e9" style="left: 28%; top: 58%;">9</button>
                    </div>
                </article>
            </div>
        </section>
    </div>

    <script>
        window.CDOL_MESA_STATES = <?php echo json_encode($mesaEstados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.CDOL_MESA_LOCKS = <?php echo json_encode($mesaLocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.CDOL_MESA_LAYOUT = <?php echo json_encode($mesaLayout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.CDOL_MESA_SAVE_URL = <?php echo json_encode('Bd/mesa_layout_api.php'); ?>;
        window.CDOL_CSRF_TOKEN = <?php echo json_encode(cd_mapa_csrf()); ?>;
    </script>
    <script src="Js/admin_mapa.js?v=<?php echo filemtime(__DIR__ . '/Js/admin_mapa.js'); ?>"></script>
    <script>
        (function () {
            var body = document.body;
            var toggle = document.querySelector('.admin-home-menu-toggle');
            var overlay = document.querySelector('.admin-home-menu-overlay');
            var sidebar = document.querySelector('.admin-home-sidebar');
            if (!toggle || !overlay || !sidebar) return;
            function closeMenu() {
                body.classList.remove('admin-home-menu-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
            function openMenu() {
                body.classList.add('admin-home-menu-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
            toggle.addEventListener('click', function () {
                if (body.classList.contains('admin-home-menu-open')) closeMenu();
                else openMenu();
            });
            overlay.addEventListener('click', closeMenu);
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeMenu();
            });
            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });
        })();
    </script>
</body>
</html>
