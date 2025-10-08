<?php
session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $acao = $_POST['acao'];
    $solicitacao_id = $_POST['solicitacao_id'];
    $usuario_id = $_POST['usuario_id'];
    $id_instituicao = $_SESSION['usuario_id'];

    if ($acao === "aprovar") {
        if ($tipo === "aluno") {
            $turma_id = $_POST['turma_id'];

            $conn->query("UPDATE alunos SET instituicao_id='$id_instituicao', turma_id='$turma_id' WHERE id='$usuario_id'");

            $conn->query("INSERT INTO afiliacoes (instituicao_id, usuario_tipo, usuario_id, turma_id, status, data_inicio)
                          VALUES ('$id_instituicao','aluno','$usuario_id','$turma_id','ativa',CURDATE())");

        } elseif ($tipo === "professor") {
            $disciplina_id = $_POST['disciplina_id'];

            $conn->query("INSERT INTO professores_disciplinas (professor_id, disciplina_id, instituicao_id)
                          VALUES ('$usuario_id','$disciplina_id','$id_instituicao')");

            $conn->query("INSERT INTO afiliacoes (instituicao_id, usuario_tipo, usuario_id, status, data_inicio)
                          VALUES ('$id_instituicao','professor','$usuario_id','ativa',CURDATE())");
        }

        $conn->query("UPDATE solicitacoes SET status='aceito' WHERE id='$solicitacao_id'");

    } elseif ($acao === "recusar") {
        $conn->query("UPDATE solicitacoes SET status='recusado' WHERE id='$solicitacao_id'");
    }

    header("Location: aprovacoes.php");
    exit();
}
?>
