<?php
session_start();
include("config/db.php"); // garanta que esse caminho está certo

if (!isset($_GET['id'])) {
    die("Erro: ID da turma não informado.");
}

$id_turma = $_GET['id'];

// Evita SQL Injection
$id_turma = mysqli_real_escape_string($conexao, $id_turma);

$query = "SELECT nome FROM turmas WHERE id = '$id_turma'";
$result = mysqli_query($conexao, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Turma não encontrada.");
}

$turma = mysqli_fetch_assoc($result);
$nome_turma = $turma['nome'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($nome_turma); ?></title>
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
        <h1><?php echo htmlspecialchars($nome_turma); ?></h1>
        <div id="calendar"></div>
    </div>

    <script>
        const calendar = document.getElementById("calendar");

        const renderCalendar = (year, month) => {
            const date = new Date(year, month);
            const monthName = date.toLocaleString('pt-BR', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1).getDay();
            const lastDate = new Date(year, month + 1, 0).getDate();

            let html = `
                <div class='calendar-header'>
                    <button id='prev'>&lt;</button>
                    <h2>${monthName}</h2>
                    <button id='next'>&gt;</button>
                </div>
                <div class='calendar-grid'>
                    <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
            `;

            for (let i = 0; i < firstDay; i++) html += "<div class='empty'></div>";
            for (let d = 1; d <= lastDate; d++) html += `<div class='day'>${d}</div>`;

            html += "</div>";
            calendar.innerHTML = html;

            document.getElementById("prev").onclick = () => renderCalendar(year, month - 1);
            document.getElementById("next").onclick = () => renderCalendar(year, month + 1);
        };

        const today = new Date();
        renderCalendar(today.getFullYear(), today.getMonth());
    </script>
</body>
</html>
