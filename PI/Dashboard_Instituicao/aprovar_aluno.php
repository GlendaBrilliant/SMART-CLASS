<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    header("Location: ../login.php");
    exit();
}

$id_solicitacao = $_GET['id'];
$id_instituicao = $_SESSION['usuario_id'];

$sql = "SELECT s.usuario_id, a.nome 
        FROM solicitacoes s 
        JOIN alunos a ON s.usuario_id = a.id
        WHERE s.id='$id_solicitacao' AND s.instituicao_id='$id_instituicao'";
$res = $conn->query($sql);
if ($res->num_rows == 0) { die("Solicitação inválida"); }
$sol = $res->fetch_assoc();

$turmas = $conn->query("SELECT id, nome FROM turmas WHERE instituicao_id='$id_instituicao'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $turma_id = $_POST['turma_id'];

    $conn->query("UPDATE alunos SET instituicao_id='$id_instituicao', turma_id='$turma_id' WHERE id='{$sol['usuario_id']}'");

    $conn->query("UPDATE solicitacoes SET status='aceito' WHERE id='$id_solicitacao'");

    $conn->query("INSERT INTO afiliacoes (instituicao_id, usuario_tipo, usuario_id, turma_id, status, data_inicio) 
                  VALUES ('$id_instituicao','aluno','{$sol['usuario_id']}','$turma_id','ativa',CURDATE())");

    header("Location: aprovacoes.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Aprovar Aluno</title></head>
<body>
<h2>Aprovar Aluno: <?= $sol['nome'] ?></h2>
<form method="post">
    <label>Turma:</label>
    <select name="turma_id" required>
        <?php while($t = $turmas->fetch_assoc()): ?>
            <option value="<?= $t['id'] ?>"><?= $t['nome'] ?></option>
        <?php endwhile; ?>
    </select>
    <button type="submit">Confirmar</button>
</form>
</body>
</html>
