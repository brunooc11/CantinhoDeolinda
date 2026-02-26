<?php
session_start();

if (!isset($_SESSION['permissoes']) || $_SESSION['permissoes'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="Imagens/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Mesas</title>
    <link rel="stylesheet" href="Css/admin.css">
    <link rel="stylesheet" href="Css/bttlogin.css">
    <link rel="stylesheet" href="Css/admin_mapa.css">
</head>
<body class="cdol-admin cdol-mapa">
    <div class="container">
        <div class="admin-hero">
            <div>
                <h2>Mapa de Mesas</h2>
                <p>Layout visual por zonas: Sala, Sof&aacute;s e Esplanada.</p>
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
                    <p class="mapa-tip">Arrasta para posicionar. Clique alterna estados: Livre &rarr; Reservada &rarr; Ocupada.</p>
                    <button type="button" id="mapaResetBtn" class="mapa-reset-btn">Resetar layout</button>
                </div>
            </div>
            <div class="mapa-merge-toolbar">
                <button type="button" id="mapaMergeModeBtn" class="mapa-merge-btn">Modo juntar mesas: OFF</button>
                <button type="button" id="mapaMergeCreateBtn" class="mapa-merge-btn" disabled>Criar conjunto</button>
                <button type="button" id="mapaMergeClearBtn" class="mapa-merge-btn mapa-merge-btn-danger" disabled>Limpar conjuntos</button>
                <span id="mapaMergeHint" class="mapa-merge-hint">Ativa o modo para selecionar mesas e junt&aacute;-las.</span>
            </div>

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
                        <div class="zona-bar" data-id="sala-balcao" style="left: 3%; top: 20%; width: 16%; height: 28%;">Balc&atilde;o</div>
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
                        <h3 class="mapa-card-title">Sof&aacute;s</h3>
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

        <div class="botoesNav" id="navFim">
            <a href="index.php" id="btnInicio" class="btt-padrao-login">&larr; In&iacute;cio</a>
            <a href="dashboard.php" id="btnDashboard" class="btt-padrao-login">&larr; Dashboard</a>
            <a href="admin.php" id="btnAdmin" class="btt-padrao-login">&larr; Admin</a>
            <a href="Bd/confirmar_reservas.php" id="btnConfirmarReservas" class="btt-padrao-login">&larr; Confirmar Reservas</a>
            <a href="admin_reservas.php" id="btnTodasReservas" class="btt-padrao-login">&larr; Todas as Reservas</a>
            <a href="admin_logs.php" id="btnLogs" class="btt-padrao-login">&larr; Logs</a>
        </div>
    </div>

    <script src="Js/admin_mapa.js"></script>
</body>
</html>
