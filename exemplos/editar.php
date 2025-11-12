<?php
require('Bd/ligar.php');

if(isset($_POST['nr_proc'])){
    $nr_proc = intval($_POST['nr_proc']);

    $res = mysqli_query($con, "SELECT * FROM alunos_teste WHERE nr_proc=$nr_proc");
    $aluno = mysqli_fetch_assoc($res);

    if(!$aluno){
        die("Aluno não encontrado!");
    }
}

$mensagem_sucesso = "";
if(isset($_POST['atualizar'])){
    $nr_proc = intval($_POST['nr_proc']); // não pode ser alterado
    $nome = mysqli_real_escape_string($con, $_POST['nome']);
    $ano = intval($_POST['ano']);
    $turma = mysqli_real_escape_string($con, $_POST['turma']);

    $sql_update = sprintf(
        "UPDATE alunos_teste SET nome='%s', ano=%d, turma='%s' WHERE nr_proc=%d",
        $nome, $ano, $turma, $nr_proc
    );

    $res_update = mysqli_query($con, $sql_update);

    if($res_update){
        $mensagem_sucesso = "Aluno alterado com sucesso! A retornar para a lista.";
        echo '<meta http-equiv="refresh" content="3;url=lista1.php">'; // volta para a tabela depois de 3 segundos
    } else {
        $erro = "Erro ao atualizar o aluno!";
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

<title>Editar Aluno</title>
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
        <h2>Editar Aluno</h2>

        <?php
        if(!empty($erro)){
            echo '<div class="alert alert-danger text-center">'.$erro.'</div>';
        }

        if(!empty($mensagem_sucesso)){
            echo '<div class="alert alert-success text-center">'.$mensagem_sucesso.'</div>';
        }
        ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nr_proc">Nº Processo</label>
                <input type="text" class="form-control" name="nr_proc" id="nr_proc" 
                    value="<?= $aluno['nr_proc'] ?>" readonly> <!-- Nº Processo não pode ser alterado , readonly-->
            </div>

            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" class="form-control" name="nome" id="nome" 
                    value="<?= htmlspecialchars($aluno['nome']) ?>" required>
            </div>

            <div class="form-group">
                <label for="ano">Ano</label>
                <input type="text" class="form-control" name="ano" id="ano" 
                    value="<?= $aluno['ano'] ?>" required>
            </div>

            <div class="form-group">
                <label for="turma">Turma</label>
                <input type="text" class="form-control" name="turma" id="turma" 
                    value="<?= htmlspecialchars($aluno['turma']) ?>" required>
            </div>

            <button type="submit" name="atualizar" class="btn btn-warning btn-block mt-3">Atualizar Aluno</button>
        </form>
    </div>
</div>

<!-- Scripts Bootstrap -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
</body>
</html>
