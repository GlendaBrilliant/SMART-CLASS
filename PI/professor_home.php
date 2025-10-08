<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'professor') {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['id'];
$sql = "SELECT * FROM professores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$dados = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Área do Professor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Bem-vindo(a), Professor <?php echo $dados['nome']; ?>!</h1>
    <p>Email: <?php echo $dados['email']; ?></p>
    <p>Cidade: <?php echo $dados['cidade']; ?> - <?php echo $dados['estado']; ?></p>
    <p><a href="logout.php">Sair</a></p>
</body>
</html>