<?php
session_start();

if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");
$id_instituicao = $_SESSION['usuario_id'];

if (isset($_POST['acao'])) {
    $solicitacao_id = $_POST['solicitacao_id'];
    $acao = $_POST['acao'];

    $sol = $conn->query("SELECT * FROM solicitacoes WHERE id='$solicitacao_id' AND instituicao_id='$id_instituicao'")->fetch_assoc();
    if ($sol) {
        $tipo = $sol['tipo'];
        $usuario_id = $sol['usuario_id'];

        if ($acao === 'aceitar') {
            if ($tipo === 'aluno') {
                $turma_id = $_POST['turma_id'] ?? null;
                $conn->query("UPDATE alunos SET instituicao_id='$id_instituicao', turma_id='$turma_id' WHERE id='$usuario_id'");
                $conn->query("INSERT INTO afiliacoes (instituicao_id, usuario_tipo, usuario_id, turma_id, status, data_inicio) VALUES ('$id_instituicao','aluno','$usuario_id','$turma_id','ativa',NOW())");
            } elseif ($tipo === 'professor') {
                $disciplina_id = $_POST['disciplina_id'] ?? null;
                $conn->query("INSERT INTO professores_disciplinas (professor_id, disciplina_id, instituicao_id) VALUES ('$usuario_id','$disciplina_id','$id_instituicao')");
                $conn->query("INSERT INTO afiliacoes (instituicao_id, usuario_tipo, usuario_id, status, data_inicio) VALUES ('$id_instituicao','professor','$usuario_id','ativa',NOW())");
            }
            $conn->query("UPDATE solicitacoes SET status='aceito' WHERE id='$solicitacao_id'");
        } elseif ($acao === 'recusar') {
            $conn->query("UPDATE solicitacoes SET status='recusado' WHERE id='$solicitacao_id'");
        }
    }
}

$solicitacoes_alunos = $conn->query("
    SELECT s.id AS solicitacao_id, a.id AS aluno_id, a.nome, a.email
    FROM solicitacoes s
    JOIN alunos a ON s.usuario_id = a.id
    WHERE s.tipo='aluno' AND s.instituicao_id='$id_instituicao' AND s.status='pendente'
");

$solicitacoes_professores = $conn->query("
    SELECT s.id AS solicitacao_id, p.id AS professor_id, p.nome, p.email
    FROM solicitacoes s
    JOIN professores p ON s.usuario_id = p.id
    WHERE s.tipo='professor' AND s.instituicao_id='$id_instituicao' AND s.status='pendente'
");

$turmas = $conn->query("SELECT * FROM turmas WHERE instituicao_id='$id_instituicao'");
$disciplinas = $conn->query("SELECT d.id, d.nome, t.nome AS turma_nome FROM disciplinas d JOIN turmas t ON d.turma_id = t.id WHERE t.instituicao_id='$id_instituicao'");

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Aprovações</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        .aba-container { display: flex; gap: 10px; margin-bottom: 20px; }
        .aba { padding: 10px 20px; background: #eee; border-radius: 8px; cursor: pointer; }
        .aba.ativa { background: #375569; color: #fff; }
        .solicitacoes { display: none; }
        .solicitacoes.ativa { display: block; }
        .solicitacao-card { background: #fff; padding: 15px; border-radius: 10px; margin-bottom: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        select, button { padding: 8px; margin-right: 10px; border-radius: 5px; }
        button { cursor: pointer; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
    <div class="top-section">
        <div class="logo">
            <?php echo $_SESSION['nome']; ?>
        </div>

        <div class="menu">
            <ul>
                <li><a href="visao-geral.php"><i class="bi bi-speedometer2"></i>Visão Geral</a></li>
                <li><a href="aprovacoes.php" class="active"><i class="bi bi-check-circle"></i>Aprovações</a></li>
                <li><a href="professores.php"><i class="bi bi-person-badge"></i>Professores</a></li>
                <li><a href="alunos.php"><i class="bi bi-backpack"></i>Alunos</a></li>
                <li><a href="turmas.php"><i class="bi bi-journal-bookmark"></i>Turmas e Matérias</a></li>
            </ul>
        </div>
    </div>

    <a href="../logout.php" class="logout">Sair</a>
</div>

    <main class="main-content">
        <h2 class="page-title">✅ Aprovações</h2>

        <div class="aba-container">
            <div class="aba ativa" onclick="mostrarAba('alunos')">Alunos</div>
            <div class="aba" onclick="mostrarAba('professores')">Professores</div>
        </div>

        <div id="alunos" class="solicitacoes ativa">
            <?php if($solicitacoes_alunos->num_rows > 0): ?>
                <?php while($row = $solicitacoes_alunos->fetch_assoc()): ?>
                    <div class="solicitacao-card">
                        <p><strong>Nome:</strong> <?= $row['nome'] ?></p>
                        <p><strong>Email:</strong> <?= $row['email'] ?></p>
                        <form method="POST" style="margin-top:10px;">
                            <input type="hidden" name="solicitacao_id" value="<?= $row['solicitacao_id'] ?>">
                            <select name="turma_id" required>
                                <option value="">Selecionar Turma</option>
                                <?php $turmas->data_seek(0); while($t = $turmas->fetch_assoc()): ?>
                                    <option value="<?= $t['id'] ?>"><?= $t['nome'] ?></option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" name="acao" value="aceitar">✅ Aceitar</button>
                            <button type="submit" name="acao" value="recusar">❌ Recusar</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Nenhuma solicitação pendente.</p>
            <?php endif; ?>
        </div>

        <div id="professores" class="solicitacoes">
            <?php if($solicitacoes_professores->num_rows > 0): ?>
                <?php while($row = $solicitacoes_professores->fetch_assoc()): ?>
                    <div class="solicitacao-card">
                        <p><strong>Nome:</strong> <?= $row['nome'] ?></p>
                        <p><strong>Email:</strong> <?= $row['email'] ?></p>
                        <form method="POST" style="margin-top:10px;">
                            <input type="hidden" name="solicitacao_id" value="<?= $row['solicitacao_id'] ?>">
                            <select name="disciplina_id" required>
                                <option value="">Selecionar Disciplina</option>
                                <?php $disciplinas->data_seek(0); while($d = $disciplinas->fetch_assoc()): ?>
                                    <option value="<?= $d['id'] ?>"><?= $d['nome'] ?> (Turma: <?= $d['turma_nome'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" name="acao" value="aceitar">✅ Aceitar</button>
                            <button type="submit" name="acao" value="recusar">❌ Recusar</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Nenhuma solicitação pendente.</p>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
function mostrarAba(id) {
    document.querySelectorAll('.aba').forEach(el => el.classList.remove('ativa'));
    document.querySelectorAll('.solicitacoes').forEach(el => el.classList.remove('ativa'));
    document.querySelector(`[onclick="mostrarAba('${id}')"]`).classList.add('ativa');
    document.getElementById(id).classList.add('ativa');
}
</script>
</body>
</html>
