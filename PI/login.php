<?php
include("config/db.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo = $_POST['tipo'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    if ($tipo == "instituicao") {
        $sql = "SELECT * FROM instituicoes WHERE email='$email'";
    } elseif ($tipo == "professor") {
        $sql = "SELECT * FROM professores WHERE email='$email'";
    } else {
        $sql = "SELECT * FROM alunos WHERE email='$email'";
    }

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_tipo'] = $tipo;
            $_SESSION['usuario_id']   = $user['id'];
            $_SESSION['nome']         = $user['nome'];

            if ($tipo == "instituicao") {
                header("Location: Dashboard_Instituicao/visao-geral.php");
            } elseif ($tipo == "professor") {
                header("Location: afiliar_professor.php");
            } else {
                header("Location: afiliar_aluno.php");
            }
            exit();
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Usuário não encontrado!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="styleee.css">
</head>
<body>
    <div class="login-page-container">
        <div class="login-box">
            <h2 class="login-title">Login</h2>
            <?php if(isset($erro)) echo "<p class='login-error'>$erro</p>"; ?>

            <form method="POST" class="login-form">
                <div class="login-form-group">
                    
                    <select name="tipo" required>
                        <option value="">Selecione</option>
                        <option value="instituicao">Instituição</option>
                        <option value="professor">Professor</option>
                        <option value="aluno">Aluno</option>
                    </select>
                </div>

                <div class="login-form-group">
                    
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="login-form-group">
                    
                    <input type="password" name="senha" placeholder="Senha" required>
                </div>

                <button type="submit" class="login-btn">Entrar</button>
            </form>

            <p class="login-register-text">Ainda não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
        </div>
    </div>
</body>
</html>