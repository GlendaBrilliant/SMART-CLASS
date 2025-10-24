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
$disc_id = intval($_GET['disciplina_id'] ?? 0);

// Buscar disciplina
$stmt = $conn->prepare("SELECT id, nome, cor FROM disciplinas WHERE id = ?");
$stmt->bind_param("i", $disc_id);
$stmt->execute();
$res = $stmt->get_result();
$disc = $res->fetch_assoc();
$stmt->close();

if (!$disc) {
    echo json_encode(['error'=>'Disciplina não encontrada']);
    exit();
}

// Buscar professores associados
$stmt = $conn->prepare("SELECT professor_id FROM professores_disciplinas WHERE disciplina_id = ? AND instituicao_id = ?");
$stmt->bind_param("ii", $disc_id, $id_instituicao);
$stmt->execute();
$res = $stmt->get_result();
$professores = [];
while($r = $res->fetch_assoc()) $professores[] = intval($r['professor_id']);
$stmt->close();

$disc['professores'] = $professores;
echo json_encode($disc);
