<?php
// Conexão à base de dados
$con = mysqli_connect('localhost', 'aluno15696', '967549529@aluno15696.7', 'aluno15696');

// Verifica se a ligação falhou
if (!$con) {
    // Para o script imediatamente sem enviar output extra
    die("Erro de ligação à base de dados");
}
?>
