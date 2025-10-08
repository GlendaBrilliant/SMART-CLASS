<?php
include("config/db.php");
session_start();

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== "professor") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = trim($_POST['codigo_instituicao']);
    $id_professor = $_SESSION['usuario_id'];

    $sql = "SELECT id FROM instituicoes WHERE codigo_instituicao = '$codigo'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $instituicao = $result->fetch_assoc();
        $id_instituicao = $instituicao['id'];

        $check = $conn->query("SELECT * FROM solicitacoes 
                               WHERE usuario_id='$id_professor' 
                               AND instituicao_id='$id_instituicao' 
                               AND tipo='professor'");

        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO solicitacoes (tipo, usuario_id, instituicao_id, status) 
                          VALUES ('professor', '$id_professor', '$id_instituicao', 'pendente')");
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
    <title>Afiliar Professor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f4f6f9;
        }

        .container {
            background-color: #fff;
            padding: 40px 50px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            width: 400px;
            max-width: 90%;
        }

        .container h2 {
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
            color: #444;
        }

        .mensagem {
            margin-bottom: 20px;
            font-size: 15px;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
        }

        .mensagem.sucesso {
            background-color: #e6ffed;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }

        .mensagem.alerta {
            background-color: #fff8e1;
            color: #b26a00;
            border: 1px solid #b26a00;
        }

        .mensagem.erro {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #c62828;
        }

        .afiliar-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #5b86e5;
            box-shadow: 0 0 8px rgba(91,134,229,0.4);
        }

        .btn {
            padding: 14px;
            background: #375569;
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #375569;
            transform: translateY(-4px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        @media (max-width: 500px) {
            .container {
                padding: 30px 20px;
            }

            .container h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Afiliar-se a uma instituição</h2>
    <?php 
        if(isset($mensagem)) {
            $classe = "mensagem ";
            if(strpos($mensagem, "✅") !== false) $classe .= "sucesso";
            elseif(strpos($mensagem, "⚠️") !== false) $classe .= "alerta";
            elseif(strpos($mensagem, "❌") !== false) $classe .= "erro";
            echo "<p class='$classe'>$mensagem</p>";
        }
    ?>

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