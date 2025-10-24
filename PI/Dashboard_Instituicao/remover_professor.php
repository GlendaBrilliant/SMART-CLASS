<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    echo json_encode(['status'=>'error','message'=>'Acesso negado']);
    exit();
}

$id_professor = intval($_POST['id']);
$id_instituicao = $_SESSION['usuario_id'];

// Verificar se o professor está vinculado à instituição
$stmt = $conn->prepare("SELECT * FROM afiliacoes WHERE usuario_id=? AND usuario_tipo='professor' AND instituicao_id=?");
$stmt->bind_param("ii",$id_professor,$id_instituicao);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0){
    echo json_encode(['status'=>'error','message'=>'Professor não pertence à instituição']);
    exit();
}
$stmt->close();

// Remover apenas a afiliação
$stmt = $conn->prepare("DELETE FROM afiliacoes WHERE usuario_id=? AND usuario_tipo='professor' AND instituicao_id=?");
$stmt->bind_param("ii",$id_professor,$id_instituicao);
if($stmt->execute()){
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Erro ao remover afiliação']);
}
$stmt->close();
?>
