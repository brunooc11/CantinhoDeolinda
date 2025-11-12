<?php
$dotenv = parse_ini_file(__DIR__ . '/seguranca/config.env');

if ($dotenv === false) {
    echo "❌ ERRO: o ficheiro config.env não foi encontrado ou não pode ser lido.";
} else {
    echo "✅ O ficheiro config.env foi lido com sucesso.<br><br>";
    echo "<pre>";
    print_r($dotenv);
    echo "</pre>";
}
?>
