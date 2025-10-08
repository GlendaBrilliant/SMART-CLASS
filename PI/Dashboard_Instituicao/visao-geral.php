<?php
session_start();

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Visão Geral</title>
    <link rel="stylesheet" href="../styleee.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    
    <aside class="sidebar">
        <h2 class="logo">Minha Instituição</h2>
        <nav class="menu">
            <ul>
                <li><a href="visao-geral.php" class="active">📊 Visão Geral</a></li>
                <li><a href="aprovacoes.php">✅ Aprovações</a></li>
                <li><a href="professores.php">👨‍🏫 Professores</a></li>
                <li><a href="alunos.php">👩‍🎓 Alunos</a></li>
                <li><a href="turmas.php">🏫 Turmas e Matérias</a></li>
            </ul>
        </nav>
        <a href="../logout.php" class="logout">🚪 Sair</a>
    </aside>

    <main class="main-content">
        <div class="visao-geral">
            <h2 class="page-title">📊 Visão Geral</h2>

            <div class="cards-container">
                <div class="card">
                    <h3 class="card-title">Agendamentos Hoje</h3>
                    <p class="card-number">
                        <?php
                        include("../config/db.php");
                        $id_instituicao = $_SESSION['usuario_id'];
                        $hoje = date("Y-m-d");

                        $sql = "SELECT COUNT(*) AS total FROM aulas 
                                INNER JOIN turmas ON aulas.turma_id = turmas.id
                                WHERE turmas.instituicao_id = '$id_instituicao'
                                AND aulas.data = '$hoje'";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();
                        echo $row['total'];
                        ?>
                    </p>
                </div>
            </div>

            <div class="charts-container">
                <div class="chart-card">
                    <h3 class="chart-title">Presença Geral por Mês</h3>
                    <canvas id="graficoPresenca"></canvas>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title">Distribuição de Alunos por Disciplinas</h3>
                    <canvas id="graficoDisciplinas"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
new Chart(document.getElementById('graficoPresenca'), {
    type: 'line',
    data: {
        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        datasets: [{
            label: 'Média de Presença (%)',
            data: [85, 90, 88, 92, 87, 91],
            borderColor: 'blue',
            fill: false,
            tension: 0.2
        }]
    },
    options: {
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('graficoDisciplinas'), {
    type: 'pie',
    data: {
        labels: ['Matemática', 'História', 'Engenharia', 'Direito', 'Outros'],
        datasets: [{
            data: [120, 80, 150, 100, 90],
            backgroundColor: ['#ff6384','#36a2eb','#ffce56','#4bc0c0','#9966ff']
        }]
    },
    options: {
        maintainAspectRatio: false
    }
});


</script>

</body>
</html>