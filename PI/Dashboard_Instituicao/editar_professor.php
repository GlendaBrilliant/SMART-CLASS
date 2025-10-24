<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    echo json_encode(['status'=>'error','message'=>'Acesso negado']);
    exit();
}

$id_professor = intval($_POST['id']);
$disciplinas = trim($_POST['disciplinas']);
$id_instituicao = $_SESSION['usuario_id'];

// Remover disciplinas atuais do professor para esta instituição
$stmt = $conn->prepare("DELETE FROM professores_disciplinas WHERE professor_id=? AND instituicao_id=?");
$stmt->bind_param("ii",$id_professor,$id_instituicao);
$stmt->execute();
$stmt->close();

// Inserir novas disciplinas
if(!empty($disciplinas)){
    $lista = explode(",",$disciplinas);
    foreach($lista as $d){
        $d = trim($d);
        if($d !== ""){
            $stmt = $conn->prepare("INSERT INTO professores_disciplinas (professor_id,instituicao_id,nome) VALUES (?,?,?)");
            $stmt->bind_param("iis",$id_professor,$id_instituicao,$d);
            $stmt->execute();
            $stmt->close();
        }
    }
}

echo json_encode(['status'=>'success']);
?>
