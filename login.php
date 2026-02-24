<?php
session_start();
require('Bd/ligar.php');
require_once('Bd/popup_helper.php');
//require("config.php");
date_default_timezone_set('Europe/Lisbon');

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("phpmailer/src/PHPMailer.php");
require("phpmailer/src/SMTP.php");
require("phpmailer/src/Exception.php");

$env = parse_ini_file("Seguranca/config.env");
$signup_inline_error = '';

// --- SIGN-UP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {

    if (!isset($_POST['termos'])) {

        cd_popup('Para criar a conta, é necessário aceitar os Termos de Uso e a Política de Privacidade!', 'error');
    } else {

        $nome     = $_POST['name'];
        $email    = $_POST['email'];
        $codigo_pais = $_POST['codigo_pais'] ?? '';
        $telefone_local = $_POST['telefone'] ?? '';
        $password_raw = $_POST['password'] ?? '';
        $data     = date('Y-m-d H:i:s');
        $token    = bin2hex(random_bytes(16)); // 🔥 token único

        $codigo_pais = preg_replace('/\D+/', '', $codigo_pais);
        $telefone_local = preg_replace('/\D+/', '', $telefone_local);
        $telefone_completo = $codigo_pais . $telefone_local;
        $regras_telefone = [
            '351' => ['min' => 9, 'max' => 9],   // Portugal
            '34'  => ['min' => 9, 'max' => 9],   // Espanha
            '33'  => ['min' => 9, 'max' => 9],   // Franca
            '49'  => ['min' => 10, 'max' => 11], // Alemanha
            '44'  => ['min' => 10, 'max' => 10], // Reino Unido
            '1'   => ['min' => 10, 'max' => 10], // EUA/Canada
            '55'  => ['min' => 10, 'max' => 11], // Brasil
            '244' => ['min' => 9, 'max' => 9],   // Angola
            '258' => ['min' => 9, 'max' => 9],   // Mocambique
            '238' => ['min' => 7, 'max' => 7],   // Cabo Verde
            '245' => ['min' => 7, 'max' => 7],   // Guine-Bissau
            '239' => ['min' => 7, 'max' => 7],   // Sao Tome e Principe
            '670' => ['min' => 7, 'max' => 8],   // Timor-Leste
            '39'  => ['min' => 9, 'max' => 10],  // Italia
            '31'  => ['min' => 9, 'max' => 9],   // Paises Baixos
            '32'  => ['min' => 8, 'max' => 9],   // Belgica
            '41'  => ['min' => 9, 'max' => 9],   // Suica
            '43'  => ['min' => 10, 'max' => 13], // Austria
            '352' => ['min' => 9, 'max' => 9],   // Luxemburgo
            '353' => ['min' => 9, 'max' => 9],   // Irlanda
            '52'  => ['min' => 10, 'max' => 10], // Mexico
            '54'  => ['min' => 10, 'max' => 11], // Argentina
            '56'  => ['min' => 9, 'max' => 9],   // Chile
            '57'  => ['min' => 10, 'max' => 10], // Colombia
            '58'  => ['min' => 10, 'max' => 10], // Venezuela
            '51'  => ['min' => 9, 'max' => 9],   // Peru
            '61'  => ['min' => 9, 'max' => 9],   // Australia
            '27'  => ['min' => 9, 'max' => 9],   // Africa do Sul
        ];
        $regra = $regras_telefone[$codigo_pais] ?? ['min' => 4, 'max' => 14];
        $min_local = $regra['min'];
        $max_local = $regra['max'];

        if (!preg_match('/^\d{1,4}$/', $codigo_pais)) {

            cd_popup('Indicativo de país inválido.', 'error');
        } elseif (!preg_match('/^\d+$/', $telefone_local)) {

            cd_popup('Número de telefone inválido.', 'error');
        } elseif (strlen($password_raw) < 8
            || !preg_match('/[A-Z]/', $password_raw)
            || !preg_match('/[a-z]/', $password_raw)
            || !preg_match('/[0-9]/', $password_raw)
            || !preg_match('/[^A-Za-z0-9]/', $password_raw)) {

            $signup_inline_error = 'A password deve ter minimo 8 caracteres, 1 maiuscula, 1 minuscula, 1 numero e 1 simbolo.';
        } elseif (strlen($telefone_local) < $min_local || strlen($telefone_local) > $max_local) {

            cd_popup("Número local inválido para este país. Deve ter entre {$min_local} e {$max_local} dígitos.", 'error');
        } elseif (strlen($telefone_completo) < 8 || strlen($telefone_completo) > 15) {

            cd_popup('Telefone inválido (deve ter entre 8 e 15 dígitos no total).', 'error');
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $telefone = '+' . $telefone_completo;

            // Verificar se o e-mail já existe
            $checkQuery = "SELECT id FROM Cliente WHERE email = ?";
            $stmt = mysqli_prepare($con, $checkQuery);
            if (!$stmt) {
                die("Erro na query: " . mysqli_error($con));
            }

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {

                cd_popup('Email já cadastrado!', 'error');
            } else {

                // Inserir novo utilizador com verificação pendente
                $insertQuery = "
                    INSERT INTO Cliente 
                    (nome, email, telefone, password, data, verificado, token_verificacao_conta, permissoes, estado)
                    VALUES (?, ?, ?, ?, ?, 0, ?, 'cliente', 1)
                ";

                $insertStmt = mysqli_prepare($con, $insertQuery);
                if (!$insertStmt) {
                    die("Erro na query (insert): " . mysqli_error($con));
                }

                mysqli_stmt_bind_param(
                    $insertStmt,
                    "ssssss",
                    $nome,
                    $email,
                    $telefone,
                    $password,
                    $data,
                    $token
                );

                if (mysqli_stmt_execute($insertStmt)) {

                    // Envia e-mail de verificação
                    $link = "https://aluno15696.damiaodegoes.pt/verificar_conta.php?token=$token";
                    $assunto = "Verifique a sua conta";

                    $mensagem = "
                        <html>
                        <body>
                            <h3>Olá, $nome 👋</h3>
                            <p>Obrigado por se registar! Confirme o seu e-mail clicando no link abaixo:</p>
                            <p><a href='$link'>Verificar Conta</a></p>
                            <br>
                            <p>Se não criou esta conta, ignore este e-mail.</p>
                        </body>
                        </html>
                    ";

                    $mail = new PHPMailer(true);

                    /*
                    $mail->SMTPDebug = 2;
                    $mail->Debugoutput = 'html';
                    */

                    $mail->isSMTP();
                    $mail->Host       = $env['SMTP_HOST'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $env['SMTP_USER'];
                    $mail->Password   = $env['SMTP_PASS'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom($env['SMTP_FROM'], $env['SMTP_FROM_NAME']);
                    $mail->addAddress($email, $nome);

                    $mail->isHTML(true);
                    $mail->Subject = $assunto;
                    $mail->Body    = $mensagem;
                    $mail->AltBody = 'Confirme a sua conta através do link enviado por email.';

                    if ($mail->send()) {
                        cd_popup('Conta criada! Verifique o seu e-mail para ativar a conta.', 'success');
                    } else {
                        cd_popup("Erro ao enviar email: {$mail->ErrorInfo}", 'error');
                    }

                    mysqli_stmt_close($insertStmt);
                }
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// --- SIGN-IN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {

    $email    = $_POST['email'];
    $password = $_POST['password'];

    $checkQuery = "
        SELECT id, nome, email, telefone, data, password, verificado, permissoes, estado
        FROM Cliente
        WHERE email = ?
    ";

    $stmt = mysqli_prepare($con, $checkQuery);
    if (!$stmt) {
        die("Erro na query: " . mysqli_error($con));
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {

        mysqli_stmt_bind_result(
            $stmt,
            $id,
            $nome,
            $email,
            $telefone,
            $data,
            $hashedPassword,
            $verificado,
            $permissoes,
            $estado
        );

        mysqli_stmt_fetch($stmt);

        if ($estado == 0) {

            cd_popup('A sua conta está bloqueada pelo administrador.', 'error', 'index.php');
            exit();
        }

        if ($verificado == 0) {

            cd_popup('Por favor, verifique o seu e-mail antes de fazer login.', 'error');
        } elseif (!is_string($hashedPassword)) {

            cd_popup('Erro na password da conta.', 'error');
        } elseif (password_verify($password, $hashedPassword)) {

            $_SESSION['id']         = $id;
            $_SESSION['nome']       = $nome;
            $_SESSION['email']      = $email;
            $_SESSION['data']       = $data;
            $_SESSION['telefone']   = $telefone;
            $_SESSION['permissoes'] = $permissoes;

            mysqli_stmt_close($stmt);
            mysqli_close($con);

            if ($permissoes === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {

            cd_popup('Password incorreta.', 'error');
        }
    } else {

        cd_popup('Email não encontrado!', 'error');
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
}
?>
<link rel="stylesheet" href="Css/login.css">
<link rel="stylesheet" href="Css/bttlogin.css">

<?php
if (isset($_GET['pw_alterada']) && $_GET['pw_alterada'] == 1) {
    cd_popup('Password alterada com sucesso. Faça login novamente.', 'success');
}
?>

<a href="index.php" class="btn-voltar">← Voltar</a>

<div class="container" id="container">

    <div class="form-container sign-up-container">
        <form action="" method="POST">
            <h1>Criar Conta</h1>

            <input type="text" name="name" placeholder="Nome" required>
            <input type="email" name="email" placeholder="Email" required>
            <div class="phone-country-row">
                <div class="country-code-box">
                    <img id="countryFlag" class="country-flag" src="https://flagcdn.com/w20/un.png" alt="Selecionar pais">
                    <input
                        type="text"
                        id="codigoPaisInput"
                        name="codigo_pais"
                        list="listaCodigosPais"
                        placeholder="+351"
                        maxlength="5"
                        autocomplete="off"
                        pattern="\+[0-9]{1,4}"
                        title="Indicativo no formato +351"
                        required>
                    <datalist id="listaCodigosPais">
                        <option value="+93">Afeganistao (+93)</option>
                        <option value="+27">Africa do Sul (+27)</option>
                        <option value="+355">Albania (+355)</option>
                        <option value="+49">Alemanha (+49)</option>
                        <option value="+376">Andorra (+376)</option>
                        <option value="+244">Angola (+244)</option>
                        <option value="+966">Arabia Saudita (+966)</option>
                        <option value="+213">Argelia (+213)</option>
                        <option value="+54">Argentina (+54)</option>
                        <option value="+374">Armenia (+374)</option>
                        <option value="+61">Australia (+61)</option>
                        <option value="+43">Austria (+43)</option>
                        <option value="+994">Azerbaijao (+994)</option>
                        <option value="+973">Bahrein (+973)</option>
                        <option value="+880">Bangladesh (+880)</option>
                        <option value="+375">Bielorrussia (+375)</option>
                        <option value="+32">Belgica (+32)</option>
                        <option value="+229">Benim (+229)</option>
                        <option value="+591">Bolivia (+591)</option>
                        <option value="+387">Bosnia e Herzegovina (+387)</option>
                        <option value="+267">Botswana (+267)</option>
                        <option value="+55">Brasil (+55)</option>
                        <option value="+673">Brunei (+673)</option>
                        <option value="+359">Bulgaria (+359)</option>
                        <option value="+226">Burkina Faso (+226)</option>
                        <option value="+257">Burundi (+257)</option>
                        <option value="+975">Butao (+975)</option>
                        <option value="+238">Cabo Verde (+238)</option>
                        <option value="+237">Camaroes (+237)</option>
                        <option value="+855">Camboja (+855)</option>
                        <option value="+1">Canada (+1)</option>
                        <option value="+974">Catar (+974)</option>
                        <option value="+235">Chade (+235)</option>
                        <option value="+56">Chile (+56)</option>
                        <option value="+86">China (+86)</option>
                        <option value="+357">Chipre (+357)</option>
                        <option value="+57">Colombia (+57)</option>
                        <option value="+269">Comores (+269)</option>
                        <option value="+242">Congo (+242)</option>
                        <option value="+243">Congo (RDC) (+243)</option>
                        <option value="+850">Coreia do Norte (+850)</option>
                        <option value="+82">Coreia do Sul (+82)</option>
                        <option value="+506">Costa Rica (+506)</option>
                        <option value="+225">Costa do Marfim (+225)</option>
                        <option value="+385">Croacia (+385)</option>
                        <option value="+53">Cuba (+53)</option>
                        <option value="+45">Dinamarca (+45)</option>
                        <option value="+253">Djibouti (+253)</option>
                        <option value="+20">Egito (+20)</option>
                        <option value="+503">El Salvador (+503)</option>
                        <option value="+971">Emirados Arabes Unidos (+971)</option>
                        <option value="+593">Equador (+593)</option>
                        <option value="+291">Eritreia (+291)</option>
                        <option value="+421">Eslovaquia (+421)</option>
                        <option value="+386">Eslovenia (+386)</option>
                        <option value="+34">Espanha (+34)</option>
                        <option value="+1">Estados Unidos (+1)</option>
                        <option value="+372">Estonia (+372)</option>
                        <option value="+251">Etiopia (+251)</option>
                        <option value="+679">Fiji (+679)</option>
                        <option value="+63">Filipinas (+63)</option>
                        <option value="+358">Finlandia (+358)</option>
                        <option value="+33">Franca (+33)</option>
                        <option value="+241">Gabao (+241)</option>
                        <option value="+220">Gambia (+220)</option>
                        <option value="+995">Georgia (+995)</option>
                        <option value="+233">Gana (+233)</option>
                        <option value="+30">Grecia (+30)</option>
                        <option value="+502">Guatemala (+502)</option>
                        <option value="+224">Guine (+224)</option>
                        <option value="+245">Guine-Bissau (+245)</option>
                        <option value="+240">Guine Equatorial (+240)</option>
                        <option value="+592">Guiana (+592)</option>
                        <option value="+509">Haiti (+509)</option>
                        <option value="+504">Honduras (+504)</option>
                        <option value="+36">Hungria (+36)</option>
                        <option value="+91">India (+91)</option>
                        <option value="+62">Indonesia (+62)</option>
                        <option value="+98">Irao (+98)</option>
                        <option value="+964">Iraque (+964)</option>
                        <option value="+353">Irlanda (+353)</option>
                        <option value="+354">Islandia (+354)</option>
                        <option value="+972">Israel (+972)</option>
                        <option value="+39">Italia (+39)</option>
                        <option value="+1">Jamaica (+1)</option>
                        <option value="+81">Japao (+81)</option>
                        <option value="+962">Jordania (+962)</option>
                        <option value="+254">Kenya (+254)</option>
                        <option value="+965">Kuwait (+965)</option>
                        <option value="+856">Laos (+856)</option>
                        <option value="+371">Letonia (+371)</option>
                        <option value="+961">Libano (+961)</option>
                        <option value="+231">Liberia (+231)</option>
                        <option value="+218">Libia (+218)</option>
                        <option value="+423">Liechtenstein (+423)</option>
                        <option value="+370">Lituania (+370)</option>
                        <option value="+352">Luxemburgo (+352)</option>
                        <option value="+389">Macedonia do Norte (+389)</option>
                        <option value="+261">Madagascar (+261)</option>
                        <option value="+60">Malasia (+60)</option>
                        <option value="+265">Malawi (+265)</option>
                        <option value="+960">Maldivas (+960)</option>
                        <option value="+223">Mali (+223)</option>
                        <option value="+356">Malta (+356)</option>
                        <option value="+212">Marrocos (+212)</option>
                        <option value="+230">Mauricia (+230)</option>
                        <option value="+222">Mauritania (+222)</option>
                        <option value="+52">Mexico (+52)</option>
                        <option value="+373">Moldavia (+373)</option>
                        <option value="+377">Monaco (+377)</option>
                        <option value="+976">Mongolia (+976)</option>
                        <option value="+382">Montenegro (+382)</option>
                        <option value="+258">Mocambique (+258)</option>
                        <option value="+95">Myanmar (+95)</option>
                        <option value="+264">Namibia (+264)</option>
                        <option value="+977">Nepal (+977)</option>
                        <option value="+505">Nicaragua (+505)</option>
                        <option value="+234">Nigeria (+234)</option>
                        <option value="+47">Noruega (+47)</option>
                        <option value="+64">Nova Zelandia (+64)</option>
                        <option value="+968">Oma (+968)</option>
                        <option value="+31">Paises Baixos (+31)</option>
                        <option value="+92">Paquistao (+92)</option>
                        <option value="+507">Panama (+507)</option>
                        <option value="+675">Papua-Nova Guine (+675)</option>
                        <option value="+595">Paraguai (+595)</option>
                        <option value="+51">Peru (+51)</option>
                        <option value="+48">Polonia (+48)</option>
                        <option value="+351">Portugal (+351)</option>
                        <option value="+44">Reino Unido (+44)</option>
                        <option value="+236">Republica Centro-Africana (+236)</option>
                        <option value="+420">Republica Checa (+420)</option>
                        <option value="+40">Romania (+40)</option>
                        <option value="+250">Ruanda (+250)</option>
                        <option value="+7">Russia (+7)</option>
                        <option value="+685">Samoa (+685)</option>
                        <option value="+378">San Marino (+378)</option>
                        <option value="+239">Sao Tome e Principe (+239)</option>
                        <option value="+221">Senegal (+221)</option>
                        <option value="+232">Serra Leoa (+232)</option>
                        <option value="+381">Servia (+381)</option>
                        <option value="+65">Singapura (+65)</option>
                        <option value="+963">Siria (+963)</option>
                        <option value="+252">Somalia (+252)</option>
                        <option value="+94">Sri Lanka (+94)</option>
                        <option value="+249">Sudao (+249)</option>
                        <option value="+211">Sudao do Sul (+211)</option>
                        <option value="+46">Suecia (+46)</option>
                        <option value="+41">Suica (+41)</option>
                        <option value="+597">Suriname (+597)</option>
                        <option value="+268">Eswatini (+268)</option>
                        <option value="+66">Tailandia (+66)</option>
                        <option value="+886">Taiwan (+886)</option>
                        <option value="+255">Tanzania (+255)</option>
                        <option value="+670">Timor-Leste (+670)</option>
                        <option value="+228">Togo (+228)</option>
                        <option value="+216">Tunisia (+216)</option>
                        <option value="+90">Turquia (+90)</option>
                        <option value="+380">Ucrania (+380)</option>
                        <option value="+256">Uganda (+256)</option>
                        <option value="+598">Uruguai (+598)</option>
                        <option value="+58">Venezuela (+58)</option>
                        <option value="+84">Vietname (+84)</option>
                        <option value="+967">Iemen (+967)</option>
                        <option value="+260">Zambia (+260)</option>
                        <option value="+263">Zimbabwe (+263)</option>
                    </datalist>
                </div>
                <input
                    type="text"
                    id="telefoneInput"
                    name="telefone"
                    placeholder="Numero de telemovel"
                    pattern="[0-9]{4,14}"
                    title="Introduza apenas digitos do numero local (4 a 14)"
                    required>
            </div>
            <div class="password-wrapper">
                <input
                    type="password"
                    id="signupPassword"
                    name="password"
                    placeholder="Password"
                    minlength="8"
                    pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                    title="Minimo 8 caracteres, 1 maiuscula, 1 minuscula, 1 numero e 1 simbolo."
                    required>
                <button type="button" class="pass-info-icon" aria-label="Regras da password">?</button>

                <button type="button"
                    class="toggle-pass"
                    data-target="signupPassword"
                    aria-label="Mostrar password">

                    <!-- olho aberto -->
                    <svg class="eye-open" viewBox="0 0 24 24">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="12" cy="12" r="3.5"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                    </svg>

                    <!-- olho fechado -->
                    <svg class="eye-closed" viewBox="0 0 24 24" style="display:none">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="12" cy="12" r="3.5"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <line x1="3" y1="3" x2="21" y2="21"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>

                </button>

                <div class="pass-tooltip" role="tooltip">
                    <strong>Regras da password</strong>
                    <ul>
                        <li>Minimo 8 caracteres</li>
                        <li>Pelo menos 1 maiuscula</li>
                        <li>Pelo menos 1 minuscula</li>
                        <li>Pelo menos 1 numero</li>
                        <li>Pelo menos 1 simbolo (!@#...)</li>
                    </ul>
                </div>
            </div>


            <div class="terms">
                <label>
                    <input type="checkbox" name="termos" required>
                    <span>
                        Li e aceito os
                        <a href="Recursos/termos.php">Termos de Uso</a>
                        e a
                        <a href="Recursos/politica.php">Política de Privacidade</a>.
                    </span>
                </label>
            </div>


            <button type="submit" name="signup">Sign Up</button>
        </form>
    </div>

    <div class="form-container sign-in-container">
        <form action="" method="POST">
            <h1>Iniciar Sessão</h1>

            <input type="email" name="email" placeholder="Email" required>
            <div class="password-wrapper">
                <input type="password" id="signinPassword" name="password" placeholder="Password" required>

                <button type="button"
                    class="toggle-pass"
                    data-target="signinPassword"
                    aria-label="Mostrar password">

                    <!-- olho aberto -->
                    <svg class="eye-open" viewBox="0 0 24 24">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="12" cy="12" r="3.5"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                    </svg>

                    <!-- olho fechado -->
                    <svg class="eye-closed" viewBox="0 0 24 24" style="display:none">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="12" cy="12" r="3.5"
                            fill="none" stroke="currentColor" stroke-width="1.8" />
                        <line x1="3" y1="3" x2="21" y2="21"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>

                </button>

            </div>

            <a href="recuperacao/forgot_password.php">Esqueci-me da palavra-passe?</a>

            <button type="submit" name="signin">Entrar</button>
        </form>
    </div>

    <div class="overlay-container">
        <div class="overlay">

            <div class="overlay-panel overlay-left">
                <h1>Olá Novamente!</h1>
                <p>Para se manter ligado a nós, inicie sessão com os seus dados pessoais.</p>
                <button class="ghost" id="signIn">Entrar</button>
            </div>

            <div class="overlay-panel overlay-right">
                <h1>Bem-vindo!</h1>
                <p>Introduza os seus dados pessoais e comece a sua jornada connosco.</p>
                <button class="ghost" id="signUp">Criar conta</button>
            </div>

        </div>
    </div>

</div>

<?php if ($signup_inline_error !== ''): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('container');
        const signupPassword = document.getElementById('signupPassword');
        if (container) {
            container.classList.add('right-panel-active');
        }
        if (signupPassword) {
            signupPassword.setCustomValidity(<?php echo json_encode($signup_inline_error); ?>);
            signupPassword.reportValidity();
            signupPassword.addEventListener('input', function() {
                signupPassword.setCustomValidity('');
            }, { once: true });
        }
    });
</script>
<?php endif; ?>

<footer>
    <p>© 2025 Cantinho Deolinda — Todos os direitos reservados</p>
</footer>

<!-- defer garante que o JavaScript só executa depois do HTML estar totalmente carregado -->
<script src="Js/popup_alert.js"></script>
<script src="Js/login.js" defer></script>
