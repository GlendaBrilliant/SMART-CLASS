<?php
include("config/db.php");
session_start();

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
    echo json_encode(['status'=>'error','message'=>'Acesso negado']);
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$turma_id = $_POST['turma_id'];
$data = $_POST['data'];
$horario = $_POST['horario'];
$sala = $_POST['sala'];
$descricao = $_POST['descricao'] ?? '';
$aula_id = $_POST['aula_id'] ?? null;

// Pegar disciplina do professor
$sql = "SELECT disciplina_id FROM professores_disciplinas pd 
        INNER JOIN disciplinas d ON pd.disciplina_id = d.id 
        WHERE pd.professor_id = ? AND d.turma_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $professor_id, $turma_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$disciplina_id = $result['disciplina_id'] ?? null;
$stmt->close();

if (!$disciplina_id) {
    echo json_encode(['status'=>'error','message'=>'Disciplina não encontrada.']);
    exit();
}

if ($aula_id) {
    // Atualizar aula existente
    $stmt = $conn->prepare("UPDATE aulas SET data=?, horario=?, sala=?, descricao=? WHERE id=? AND professor_id=?");
    $stmt->bind_param("ssssii", $data, $horario, $sala, $descricao, $aula_id, $professor_id);
} else {
    // Criar nova aula
    $stmt = $conn->prepare("INSERT INTO aulas (disciplina_id, professor_id, turma_id, data, horario, sala, descricao) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissss", $disciplina_id, $professor_id, $turma_id, $data, $horario, $sala, $descricao);
}

if ($stmt->execute()) {
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>$conn->error]);
}
$stmt->close();
$conn->close();
