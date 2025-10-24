<?php
session_start();
include("config/db.php");

$id_professor = $_SESSION['usuario_id']; // ou o nome certo do campo no banco!

$query = "SELECT id, nome FROM turmas WHERE id_professor = '$id_professor'";
$result = mysqli_query($conexao, $query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Minhas Turmas</title>
    <link rel="stylesheet" href="style_professor.css">
</head>
<body>
    <div class="menu-lateral">
        <ul>
            <li><a href="professor_turmas.php" class="ativo">Turmas</a></li>
            <li><a href="professor_presencas.php">Presenças</a></li>
        </ul>
    </div>

    <div class="conteudo-principal">
        <h1>Minhas Turmas</h1>
        <div class="lista-turmas">
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <a href="professor_turma.php?id=<?php echo $row['id']; ?>" class="turma-card">
                    <?php echo htmlspecialchars($row['nome']); ?>
                </a>
            <?php } ?>
        </div>
    </div>
</body>
</html>
