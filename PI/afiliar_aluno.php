<?php
include("config/db.php");
session_start();

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== "aluno") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = trim($_POST['codigo_instituicao']);
    $id_aluno = $_SESSION['usuario_id'];

    $sql = "SELECT id FROM instituicoes WHERE codigo_instituicao = '$codigo'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $instituicao = $result->fetch_assoc();
        $id_instituicao = $instituicao['id'];

        $check = $conn->query("SELECT * FROM solicitacoes 
                               WHERE usuario_id='$id_aluno' 
                               AND instituicao_id='$id_instituicao' 
                               AND tipo='aluno'");

        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO solicitacoes (tipo, usuario_id, instituicao_id, status) 
                          VALUES ('aluno', '$id_aluno', '$id_instituicao', 'pendente')");
            $mensagem = "✅ Pedido enviado! Aguarde a aprovação da instituição.";
        } else {
            $mensagem = "⚠️ Você já fez um pedido para essa instituição.";
        }
    } else {
        $mensagem = "❌ Código da instituição inválido.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Afiliar Aluno</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Afiliar-se a uma instituição (Aluno)</h2>
    <?php if(isset($mensagem)) echo "<p class='mensagem'>$mensagem</p>"; ?>

    <form method="POST" class="afiliar-form">
        <div class="form-group">
            <label for="codigo_instituicao">Código da instituição:</label>
            <input type="text" id="codigo_instituicao" name="codigo_instituicao" maxlength="4" required>
        </div>
        <button type="submit" class="btn">Enviar Pedido</button>
    </form>
</div>
</body>
</html>