<?php
require('Bd/ligar.php');

// Deletar aluno via POST
if(isset($_POST['deletar'])){
    $nr_proc = intval($_POST['nr_proc']);
    $sql_deletar = "DELETE FROM alunos_teste WHERE nr_proc=$nr_proc";
    $res_deletar = mysqli_query($con, $sql_deletar);
    if($res_deletar){
        $mensagem = '<div class="alert alert-success text-center">Aluno deletado com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-danger text-center">Erro ao deletar o aluno!</div>';
    }
}

// Buscar todos os alunos
$sql = "SELECT * FROM alunos_teste";
$resultado = mysqli_query($con, $sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- CSS do Projeto -->
  <link rel="stylesheet" href="css/loader.css">
  <link rel="stylesheet" href="css/ModoEscuro.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/carrosel.css">
  
  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body, main { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #111; color: #f4f4f4; }
    .container { margin-top: 80px; }
    .table { background-color: #1c1c1c; color: #f4f4f4; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
    .table thead { background-color: #f4b942; color: #111; font-weight: bold; }
    .table tbody tr:hover { background-color: #2a2a2a; }
    hr { border: 1px solid #f4b942; }
  </style>
  <title>AULAS</title>
</head>
<body>
  <div class="container">
    <h1 class="mb-4 text-center" style="color:#f4b942;">Lista de Alunos</h1>

    <?php if(isset($mensagem)) echo $mensagem; ?> <!-- Mensagem de deletar -->

    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th scope="col">Nº PROCESSO</th>
          <th scope="col">NOME</th>
          <th scope="col">ANO</th>
          <th scope="col">TURMA</th>
          <th scope="col">AÇÕES</th>
        </tr>
      </thead>
      <tbody>
        <?php while($registo = mysqli_fetch_array($resultado)) { ?>
          <tr>
            <td><?php echo $registo['nr_proc']; ?></td>
            <td><?php echo $registo['nome']; ?></td>
            <td><?php echo $registo['ano']; ?></td>
            <td><?php echo $registo['turma']; ?></td>
            <td>
              <!-- Botão Editar via POST -->
              <form method="POST" action="editar.php" style="display:inline;">
                  <input type="hidden" name="nr_proc" value="<?= $registo['nr_proc'] ?>">
                  <button type="submit" class="btn btn-sm btn-info">Editar</button>
              </form>

              <!-- Botão Deletar -->
              <form method="POST" action="" style="display:inline;" 
                    onsubmit="return confirm('Deseja realmente deletar este aluno?')">
                <input type="hidden" name="nr_proc" value="<?= $registo['nr_proc'] ?>">
                <button type="submit" name="deletar" class="btn btn-sm btn-danger">Deletar</button>
              </form>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <hr>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
  <script src="js/ModoEscuro.js"></script>
  <script src="js/loader.js"></script>
  <script src="js/carrosel.js"></script>
</body>
</html>
