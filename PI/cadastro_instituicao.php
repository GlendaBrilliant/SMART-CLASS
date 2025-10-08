<?php
include("config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $estado = $_POST['estado'];
    $cidade = $_POST['cidade'];
    $endereco = $_POST['endereco'];
    $telefone = $_POST['telefone'];
    $codigo = $_POST['codigo'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    
    $logo = "";
    if (!empty($_FILES["logo"]["name"])) {
        $targetDir = "uploads/";
        if(!is_dir($targetDir)) mkdir($targetDir);
        $fileName = time() . "_" . basename($_FILES["logo"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFilePath);
        $logo = $targetFilePath;
    }

    $sql = "INSERT INTO instituicoes (nome, email, estado, cidade, endereco, telefone, logo, senha, codigo_instituicao) 
            VALUES ('$nome','$email','$estado','$cidade','$endereco','$telefone','$logo','$senha','$codigo')";

    if ($conn->query($sql) === TRUE) {
        header("Location: login.php?success=instituicao");
        exit();
    } else {
        echo "Erro: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro Instituição</title>
    <link rel="stylesheet" href="styleee.css">
</head>
<body>
<center>
<div class="cadastro">
    <h2>Cadastro de Instituição</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="nome" placeholder="Nome da Instituição" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="text" name="estado" placeholder="Estado"><br>
        <input type="text" name="cidade" placeholder="Cidade"><br>
        <input type="text" name="endereco" placeholder="Endereço"><br>
        <input type="text" name="telefone" placeholder="Telefone"><br>
        <input type="file" name="logo"><br>
        <input type="text" name="codigo" placeholder="Código (4 dígitos)" maxlength="4" required><br>
        <input type="password" name="senha" placeholder="Senha" required><br>
        <div class="cadastroCheckBox">
            <input type="checkbox" required> Eu aceito os termos<br>
        </div>
        <button type="submit">Cadastrar</button>
    </form>
    <p>Já tem cadastro? <a href="login.php">Login</a></p>
</div>
</center>
</body>
</html>
