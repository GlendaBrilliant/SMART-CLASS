<?php
session_start();
include("../config/db.php");

// Apenas instituições podem acessar
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    echo json_encode(['status'=>'error','message'=>'Acesso negado']);
    exit();
}

if(!isset($_POST['id'])){
    echo json_encode(['status'=>'error','message'=>'ID do aluno não informado']);
    exit();
}

$id_aluno = intval($_POST['id']);
$id_instituicao = $_SESSION['usuario_id'];

// Verificar se o aluno está vinculado à instituição
$sql = "SELECT * FROM afiliacoes WHERE usuario_id=? AND usuario_tipo='aluno' AND instituicao_id=? AND status='ativa'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_aluno, $id_instituicao);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0){
    echo json_encode(['status'=>'error','message'=>'Aluno não pertence à instituição']);
    exit();
}
$stmt->close();

// Remover apenas a afiliação do aluno com esta instituição
$sql_delete = "DELETE FROM afiliacoes WHERE usuario_id=? AND usuario_tipo='aluno' AND instituicao_id=?";
$stmt = $conn->prepare($sql_delete);
$stmt->bind_param("ii", $id_aluno, $id_instituicao);
if($stmt->execute()){
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Erro ao remover afiliação']);
}
$stmt->close();
?>
