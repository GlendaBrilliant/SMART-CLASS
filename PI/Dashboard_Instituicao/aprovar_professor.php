<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    header("Location: ../login.php");
    exit();
}

$id_solicitacao = $_GET['id'];
$id_instituicao = $_SESSION['usuario_id'];

$sql = "SELECT s.usuario_id, p.nome 
        FROM solicitacoes s 
        JOIN professores p ON s.usuario_id = p.id
        WHERE s.id='$id_solicitacao' AND s.instituicao_id='$id_instituicao'";
$res = $conn->query($sql);
if ($res->num_rows == 0) { die("Solicitação inválida"); }
$sol = $res->fetch_assoc();

$disciplinas = $conn->query("SELECT d.id, d.nome, t.nome AS turma_nome 
                             FROM disciplinas d
                             JOIN turmas t ON d.turma_id = t.id
                             WHERE t.instituicao_id='$id_instituicao'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $disciplinas_escolhidas = $_POST['disciplinas'] ?? [];

    foreach ($disciplinas_escolhidas as $disciplina_id) {
        $conn->query("INSERT INTO professores_disciplinas (professor_id, disciplina_id, instituicao_id) 
                      VALUES ('{$sol['usuario_id']}','$disciplina_id','$id_instituicao')");
    }

    $conn->query("UPDATE solicitacoes SET status='aceito' WHERE id='$id_solicitacao'");

    $conn->query("INSERT INTO afiliacoes (instituicao_id, usuario_tipo, usuario_id, status, data_inicio) 
                  VALUES ('$id_instituicao','professor','{$sol['usuario_id']}','ativa',CURDATE())");

    header("Location: aprovacoes.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Aprovar Professor</title></head>
<body>
<h2>Aprovar Professor: <?= $sol['nome'] ?></h2>
<form method="post">
    <label>Selecione as disciplinas:</label><br>
    <?php while($d = $disciplinas->fetch_assoc()): ?>
        <input type="checkbox" name="disciplinas[]" value="<?= $d['id'] ?>"> <?= $d['nome'] ?> (Turma: <?= $d['turma_nome'] ?>)<br>
    <?php endwhile; ?>
    <button type="submit">Confirmar</button>
</form>
</body>
</html>
