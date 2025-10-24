<?php
session_start();
include("../config/db.php");

// Apenas instituições podem acessar
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    http_response_code(403);
    echo json_encode(['error'=>'Acesso negado']);
    exit();
}

$id_instituicao = $_SESSION['usuario_id'];
$turma_id = intval($_GET['turma_id'] ?? 0);

$stmt = $conn->prepare("SELECT id, nome, turno FROM turmas WHERE id = ? AND instituicao_id = ?");
$stmt->bind_param("ii", $turma_id, $id_instituicao);
$stmt->execute();
$res = $stmt->get_result();
$turma = $res->fetch_assoc();
$stmt->close();

if ($turma) {
    echo json_encode($turma);
} else {
    echo json_encode(['error'=>'Turma não encontrada']);
}
