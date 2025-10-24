<?php
session_start();
include("../config/db.php");

// Apenas instituições podem acessar
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    echo json_encode(['status'=>'error','message'=>'Acesso negado']);
    exit();
}

if(!isset($_POST['id'], $_POST['turma_id'])){
    echo json_encode(['status'=>'error','message'=>'Dados incompletos']);
    exit();
}

$id_aluno = intval($_POST['id']);
$turma_id = intval($_POST['turma_id']);

// Verificar se o aluno está vinculado à instituição
$sql = "SELECT * FROM afiliacoes WHERE usuario_id=? AND usuario_tipo='aluno' AND instituicao_id=? AND status='ativa'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_aluno, $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0){
    echo json_encode(['status'=>'error','message'=>'Aluno não pertence à instituição']);
    exit();
}
$stmt->close();

// Atualizar apenas a turma do aluno
$sql_update = "UPDATE alunos SET turma_id=? WHERE id=?";
$stmt = $conn->prepare($sql_update);
$stmt->bind_param("ii", $turma_id, $id_aluno);
if($stmt->execute()){
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Erro ao atualizar a turma']);
}
$stmt->close();
?>
