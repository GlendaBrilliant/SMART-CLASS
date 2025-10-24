<?php
session_start();
include("config/db.php");

// Validação de login
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: login.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Buscar turmas que o professor leciona
$query = $conn->prepare("
    SELECT DISTINCT t.id, t.nome, t.turno, i.nome AS instituicao_nome
    FROM turmas t
    JOIN disciplinas d ON d.turma_id = t.id
    JOIN professores_disciplinas pd ON pd.disciplina_id = d.id
    JOIN instituicoes i ON i.id = t.instituicao_id
    WHERE pd.professor_id = ?
    ORDER BY i.nome, t.nome
");
$query->bind_param("i", $professor_id);
$query->execute();
$res_turmas = $query->get_result();
$turmas = [];
while ($r = $res_turmas->fetch_assoc()) $turmas[] = $r;
$query->close();

// Processar envio de presenças
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_presencas'])) {
    $aula_id = intval($_POST['aula_id']);
    foreach ($_POST['presenca'] as $aluno_id => $status) {
        // verifica se já existe
        $check = $conn->prepare("SELECT id FROM presencas WHERE aula_id=? AND aluno_id=?");
        $check->bind_param("ii", $aula_id, $aluno_id);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows > 0) {
            $upd = $conn->prepare("UPDATE presencas SET status=? WHERE aula_id=? AND aluno_id=?");
            $upd->bind_param("sii", $status, $aula_id, $aluno_id);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $conn->prepare("INSERT INTO presencas (aula_id, aluno_id, status) VALUES (?, ?, ?)");
            $ins->bind_param("iis", $aula_id, $aluno_id, $status);
            $ins->execute();
            $ins->close();
        }
        $check->close();
    }
    $msg = ["type"=>"sucesso","text"=>"Presenças salvas com sucesso!"];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Presenças — Professor</title>
<link rel="stylesheet" href="style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg,#f0f4f9,#dbeafe);
    display: flex;
    height: 100vh;
}

.main {
    flex: 1;
    padding: 30px;
    overflow-y: auto;
    margin-left: 250px; /* 👈 adiciona isso */
}

.section {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
h2 {
    margin-top: 0;
    color: #002b5c;
}
.list-item {
    border: 1px solid #ddd;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: 0.2s;
}
.list-item:hover {
    background: #eef4ff;
}
.back-btn {
    background: none;
    border: none;
    color: #004080;
    font-weight: bold;
    margin-bottom: 15px;
    cursor: pointer;
}
.presenca-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}
.presenca-item:last-child {
    border-bottom: none;
}
.radio-group label {
    margin-right: 10px;
}
button.salvar {
    background: #004080;
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
}
.msg.sucesso {
    background: #d4edda;
    color: #155724;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="top-section">
        <div class="logo"><?= $_SESSION['nome'] ?></div>
        <div class="menu">
            <ul>
                <li><a href="professor_dashboard.php"><i class="bi bi-calendar2-plus"></i>Agendas</a></li>
                <li><a href="professor_presencas.php" class="active"><i class="bi bi-check-circle"></i>Presenças</a></li>
                <li><a href="professor_perfil.php"><i class="bi bi-person-circle"></i>Perfil</a></li>
            </ul>
        </div>
    </div>
    <a href="logout.php" class="logout">Sair</a>
</div>

<div class="main">
    <h2>Controle de Presenças</h2>

    <?php if(!empty($msg)): ?>
        <div class="msg <?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <div class="section" id="section-turmas">
        <h3>Selecione uma Turma</h3>
        <?php foreach($turmas as $t): ?>
            <div class="list-item" onclick="carregarDisciplinas(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nome'])) ?>')">
                <strong><?= htmlspecialchars($t['nome']) ?></strong>
                <div style="font-size:0.9em;color:#666;">Instituição: <?= htmlspecialchars($t['instituicao_nome']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section" id="section-disciplinas" style="display:none;"></div>
    <div class="section" id="section-aulas" style="display:none;"></div>
    <div class="section" id="section-presencas" style="display:none;"></div>
</div>

<script>
function carregarDisciplinas(turmaId, turmaNome){
    fetch('professor_presencas_api.php?action=disciplinas&turma='+turmaId)
        .then(r=>r.json())
        .then(data=>{
            let html = `<button class="back-btn" onclick="voltar('turmas')">← Voltar</button>`;
            html += `<h3>Disciplinas da Turma: ${turmaNome}</h3>`;
            if(data.length===0){html += `<p>Nenhuma disciplina encontrada.</p>`;}
            else data.forEach(d=>{
                html += `<div class="list-item" onclick="carregarAulas(${d.id}, '${d.nome}', ${turmaId})">${d.nome}</div>`;
            });
            document.getElementById('section-turmas').style.display='none';
            document.getElementById('section-disciplinas').innerHTML=html;
            document.getElementById('section-disciplinas').style.display='block';
        });
}

function carregarAulas(disciplinaId, disciplinaNome, turmaId){
    fetch('professor_presencas_api.php?action=aulas&disciplina='+disciplinaId+'&turma='+turmaId)
        .then(r=>r.json())
        .then(data=>{
            let html = `<button class="back-btn" onclick="voltar('disciplinas')">← Voltar</button>`;
            html += `<h3>Aulas de ${disciplinaNome}</h3>`;
            if(data.length===0){html += `<p>Nenhuma aula marcada.</p>`;}
            else data.forEach(a=>{
                html += `<div class="list-item" onclick="carregarPresencas(${a.id}, '${a.data}', ${turmaId})">${a.data_formatada} - ${a.horario}</div>`;
            });
            document.getElementById('section-disciplinas').style.display='none';
            document.getElementById('section-aulas').innerHTML=html;
            document.getElementById('section-aulas').style.display='block';
        });
}

function carregarPresencas(aulaId, data, turmaId){
    fetch('professor_presencas_api.php?action=alunos&aula='+aulaId+'&turma='+turmaId)
        .then(r=>r.json())
        .then(data=>{
            let html = `<button class="back-btn" onclick="voltar('aulas')">← Voltar</button>`;
            html += `<h3>Presenças - Aula de ${data}</h3>`;
            html += `<form method="POST"><input type="hidden" name="aula_id" value="${aulaId}">`;
            data.forEach(al=>{
                html += `<div class="presenca-item">
                            <span>${al.nome}</span>
                            <div class="radio-group">
                                <label><input type="radio" name="presenca[${al.id}]" value="presente" ${al.status==='presente'?'checked':''}> ✅</label>
                                <label><input type="radio" name="presenca[${al.id}]" value="falta" ${al.status==='falta'?'checked':''}> ❌</label>
                                <label><input type="radio" name="presenca[${al.id}]" value="justificada" ${al.status==='justificada'?'checked':''}> 📄</label>
                            </div>
                        </div>`;
            });
            html += `<br><button type="submit" name="salvar_presencas" class="salvar">Salvar Presenças</button></form>`;
            document.getElementById('section-aulas').style.display='none';
            document.getElementById('section-presencas').innerHTML=html;
            document.getElementById('section-presencas').style.display='block';
        });
}

function voltar(sec){
    document.querySelectorAll('.section').forEach(s=>s.style.display='none');
    if(sec==='turmas') document.getElementById('section-turmas').style.display='block';
    if(sec==='disciplinas') document.getElementById('section-disciplinas').style.display='block';
    if(sec==='aulas') document.getElementById('section-aulas').style.display='block';
}
</script>

</body>
</html>
