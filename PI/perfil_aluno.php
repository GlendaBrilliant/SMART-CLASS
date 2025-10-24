<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../login.php");
    exit();
}

$id_aluno = $_SESSION['usuario_id'];

$sql = "SELECT * FROM alunos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_aluno);
$stmt->execute();
$result = $stmt->get_result();
$aluno = $result->fetch_assoc();
$stmt->close();

// Se não tiver foto, usar padrão
$foto = !empty($aluno['foto_perfil']) ? $aluno['foto_perfil'] : 'uploads/default.png';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Perfil do Aluno</title>
<style>
.container { max-width:600px; margin:50px auto; background:#f9f9f9; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); text-align:center; }
.foto { width:150px; height:150px; border-radius:50%; object-fit:cover; margin-bottom:20px; }
.info { margin-bottom:10px; font-size:16px; }
h2 { margin-bottom:20px; }
</style>
</head>
<body>
<div class="container">
    <h2>Perfil do Aluno</h2>
    <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do Aluno" class="foto">
    <div class="info"><strong>Nome:</strong> <?= htmlspecialchars($aluno['nome']) ?></div>
    <div class="info"><strong>Email:</strong> <?= htmlspecialchars($aluno['email']) ?></div>
    <div class="info"><strong>Telefone:</strong> <?= htmlspecialchars($aluno['telefone'] ?? '-') ?></div>
    <div class="info"><strong>Estado:</strong> <?= htmlspecialchars($aluno['estado'] ?? '-') ?></div>
    <div class="info"><strong>Cidade:</strong> <?= htmlspecialchars($aluno['cidade'] ?? '-') ?></div>
    <div class="info"><strong>Endereço:</strong> <?= htmlspecialchars($aluno['endereco'] ?? '-') ?></div>
</div>
</body>
</html>
