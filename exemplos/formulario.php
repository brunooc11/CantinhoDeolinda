<?php
require('Bd/ligar.php');

if(isset($_POST['inserir'])){

    $sql_verificar=sprintf("select * from alunos_teste where nr_proc=%d;",$_POST['nr_proc']);
    $res_verificar=mysqli_query($con,$sql_verificar);

    if(mysqli_num_rows($res_verificar)==0){
    $sql_insere=sprintf("insert into alunos_teste (nr_proc,nome,ano,turma) values (%d,'%s',%d,'%s');",$_POST['nr_proc'],$_POST['nome'],$_POST['ano'],$_POST['turma']);
    mysqli_query($con,$sql_insere);
    $existe=1;
    }
    else{
        $existe=0;
    }
}

if(isset($_POST['deletar'])){
    $sql_deletar = sprintf("DELETE FROM alunos_teste WHERE nr_proc=%d;", $_POST['nr_proc']);
    $res_deletar = mysqli_query($con, $sql_deletar);

    if($res_deletar){
        $apagado = 1;
    } else {
        $apagado = 0;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<!-- Bootstrap -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">

<title>Formulário de Alunos</title>
<style>
body { background-color: #111; color: #f4f4f4; font-family: Arial, sans-serif; }
.container { margin-top: 60px; max-width: 600px; }
.form-control, .btn { border-radius: 8px; }
.card { background-color: #1c1c1c; border-radius: 10px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.4); }
.card h2 { color: #f4b942; text-align: center; margin-bottom: 25px; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Formulário de Alunos</h2>

        <?php
        if(isset($existe)){
            if($existe==1){
                echo 'inserido com sucesso!';
            } else {
                echo 'ERRO! O NR-PROC JÁ EXISTE!';
            }
        }

        if(isset($apagado)){
            if($apagado==1){
                echo 'Deletado com sucesso';
            }
        }
        ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nr_proc">Nº Processo</label>
                <input type="text" class="form-control" name="nr_proc" id="nr_proc" required>
            </div>

            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" class="form-control" name="nome" id="nome" required>
            </div>

            <div class="form-group">
                <label for="ano">Ano</label>
                <input type="text" class="form-control" name="ano" id="ano" required>
            </div>

            <div class="form-group">
                <label for="turma">Turma</label>
                <input type="text" class="form-control" name="turma" id="turma" required>
            </div>

        <button type="submit" name="inserir" class="btn btn-warning btn-block mt-3">Adicionar Aluno</button>
        </form>
    </div>
</div>

<!-- Scripts Bootstrap -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
</body>
</html>
