<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
    http_response_code(403);
    exit;
}
$professor_id = $_SESSION['usuario_id'];
$action = $_GET['action'] ?? '';

if ($action === 'disciplinas') {
    $turma_id = intval($_GET['turma']);
    $stmt = $conn->prepare("SELECT d.id, d.nome FROM disciplinas d 
                            JOIN professores_disciplinas pd ON pd.disciplina_id = d.id
                            WHERE d.turma_id=? AND pd.professor_id=?");
    $stmt->bind_param("ii", $turma_id, $professor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $disciplinas = [];
    while ($r = $res->fetch_assoc()) $disciplinas[] = $r;
    echo json_encode($disciplinas);
    exit;
}

if ($action === 'aulas') {
    $disciplina_id = intval($_GET['disciplina']);
    $turma_id = intval($_GET['turma']);
    $stmt = $conn->prepare("SELECT id, DATE_FORMAT(data,'%d/%m/%Y') AS data_formatada, horario, data FROM aulas 
                            WHERE disciplina_id=? AND turma_id=? AND professor_id=? ORDER BY data DESC");
    $stmt->bind_param("iii", $disciplina_id, $turma_id, $professor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $aulas = [];
    while ($r = $res->fetch_assoc()) $aulas[] = $r;
    echo json_encode($aulas);
    exit;
}

if ($action === 'alunos') {
    $aula_id = intval($_GET['aula']);
    $turma_id = intval($_GET['turma']);
    $stmt = $conn->prepare("SELECT a.id, a.nome, IFNULL(p.status,'falta') AS status
                            FROM alunos a
                            LEFT JOIN presencas p ON p.aluno_id = a.id AND p.aula_id=?
                            WHERE a.turma_id=?");
    $stmt->bind_param("ii", $aula_id, $turma_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $alunos = [];
    while ($r = $res->fetch_assoc()) $alunos[] = $r;
    echo json_encode($alunos);
    exit;
}
