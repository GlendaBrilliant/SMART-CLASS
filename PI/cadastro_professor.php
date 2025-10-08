<?php
include("config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $estado = $_POST['estado'];
    $cidade = $_POST['cidade'];
    $endereco = $_POST['endereco'];
    $telefone = $_POST['telefone'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $foto = "";
    if (!empty($_FILES["foto"]["name"])) {
        $targetDir = "uploads/";
        if(!is_dir($targetDir)) mkdir($targetDir);
        $fileName = time() . "_" . basename($_FILES["foto"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFilePath);
        $foto = $targetFilePath;
    }

    $sql = "INSERT INTO professores (nome, email, estado, cidade, endereco, telefone, foto_perfil, senha) 
            VALUES ('$nome','$email','$estado','$cidade','$endereco','$telefone','$foto','$senha')";

    if ($conn->query($sql) === TRUE) {
        header("Location: login.php?success=professor");
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
    <title>Cadastro Professor</title>
    <link rel="stylesheet" href="styleee.css">
</head>
<body>
<center>
    <div class="cadastro">
    <h2>Cadastro de Professor</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="nome" placeholder="Nome" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="text" name="estado" placeholder="Estado"><br>
        <input type="text" name="cidade" placeholder="Cidade"><br>
        <input type="text" name="endereco" placeholder="Endereço"><br>
        <input type="text" name="telefone" placeholder="Telefone"><br>
        <input type="file" name="foto"><br>
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
