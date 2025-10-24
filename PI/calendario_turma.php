<?php
session_start();
include("config/db.php");

// validação de sessão professor
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../login.php");
    exit();
}
$id_professor = $_SESSION['usuario_id'];

// obter instituição e turma selecionadas
$inst_id = isset($_GET['inst']) ? intval($_GET['inst']) : 0;
$turma_id = isset($_GET['turma']) ? intval($_GET['turma']) : 0;

if ($inst_id <= 0 || $turma_id <= 0) {
    echo "Instituição ou turma inválida.";
    exit();
}

// verificar se professor está afiliado a essa instituição
$stmt = $conn->prepare("SELECT status FROM afiliacoes WHERE instituicao_id=? AND usuario_tipo='professor' AND usuario_id=? LIMIT 1");
$stmt->bind_param("ii", $inst_id, $id_professor);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo "Você não está afiliado a esta instituição.";
    exit();
}
$af = $res->fetch_assoc();
$stmt->close();

// buscar nome da turma
$stmt = $conn->prepare("SELECT nome FROM turmas WHERE id=? AND instituicao_id=? LIMIT 1");
$stmt->bind_param("ii", $turma_id, $inst_id);
$stmt->execute();
$res_t = $stmt->get_result();
if ($res_t && $res_t->num_rows > 0) {
    $turma_row = $res_t->fetch_assoc();
} else {
    echo "Turma não encontrada.";
    exit();
}
$stmt->close();

// PROCESSAMENTO: criar nova aula
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_aula') {
    $disciplina_id = intval($_POST['disciplina_id']);
    $data = $_POST['data'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $sala = trim($_POST['sala'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($disciplina_id <= 0 || !$data || !$horario) {
        $msg = ["type"=>"erro","text"=>"Preencha todos os campos obrigatórios."];
    } else {
        $chk = $conn->prepare("SELECT d.id FROM disciplinas d
                               JOIN professores_disciplinas pd ON pd.disciplina_id=d.id AND pd.professor_id=? AND pd.instituicao_id=?
                               WHERE d.id=? AND d.turma_id=? LIMIT 1");
        $chk->bind_param("iiii", $id_professor, $inst_id, $disciplina_id, $turma_id);
        $chk->execute();
        $rchk = $chk->get_result();
        if (!$rchk || $rchk->num_rows === 0) {
            $msg = ["type"=>"erro","text"=>"Disciplina inválida para esta turma / instituição."];
        } else {
            $ins = $conn->prepare("INSERT INTO aulas (disciplina_id, professor_id, turma_id, data, horario, sala, descricao)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("iiissss", $disciplina_id, $id_professor, $turma_id, $data, $horario, $sala, $descricao);
            if ($ins->execute()) {
                $msg = ["type"=>"sucesso","text"=>"Aula agendada com sucesso."];
            } else {
                $msg = ["type"=>"erro","text"=>"Erro ao criar aula."];
            }
            $ins->close();
        }
        $chk->close();
    }
}

// buscar disciplinas do professor para a turma
$disciplinas = [];
$stmt = $conn->prepare("SELECT d.id, d.nome FROM disciplinas d
                       JOIN professores_disciplinas pd ON pd.disciplina_id=d.id AND pd.professor_id=? AND pd.instituicao_id=?
                       WHERE d.turma_id=?");
$stmt->bind_param("iii", $id_professor, $inst_id, $turma_id);
$stmt->execute();
$resd = $stmt->get_result();
while ($d = $resd->fetch_assoc()) $disciplinas[] = $d;
$stmt->close();

// buscar aulas da turma a partir de hoje
$aulas = [];
$stmt = $conn->prepare("SELECT a.*, d.nome AS disciplina_nome
                        FROM aulas a
                        JOIN disciplinas d ON d.id=a.disciplina_id
                        WHERE a.professor_id=? AND a.turma_id=? AND a.data >= CURDATE()
                        ORDER BY a.data ASC, a.horario ASC");
$stmt->bind_param("ii", $id_professor, $turma_id);
$stmt->execute();
$res_aulas = $stmt->get_result();
while ($r = $res_aulas->fetch_assoc()) $aulas[] = $r;
$stmt->close();
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Calendário - <?= htmlspecialchars($turma_row['nome']) ?></title>
<link rel="stylesheet" href="style.css">
<style>
.container { padding:22px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.calendario { display:grid; grid-template-columns: repeat(7, 1fr); gap:8px; }
.dia { background:#fff; padding:10px; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.1); min-height:80px; position:relative; cursor:pointer; }
.dia h4 { margin:0 0 6px 0; font-size:14px; }
.aula-badge { background:#375569; color:#fff; font-size:12px; padding:2px 4px; border-radius:4px; margin-top:2px; display:block; }
.btn { padding:8px 12px; border-radius:6px; border:none; cursor:pointer; }
.btn.primary { background:#375569; color:#fff; }
.modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.45); z-index:2000; }
.modal .card { width:400px; max-width:95%; padding:16px; background:#fff; border-radius:10px; }
.form-row { margin-bottom:10px; }
.form-row label { display:block; margin-bottom:6px; font-weight:600; }
.form-row input, .form-row select, .form-row textarea { width:100%; padding:8px 10px; border-radius:6px; border:1px solid #ccc; }
.msg { padding:10px; border-radius:8px; margin-bottom:12px; }
.msg.sucesso { background:#e6ffed; color:#166534; border:1px solid #166534; }
.msg.erro { background:#ffebee; color:#b91c1c; border:1px solid #b91c1c; }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Calendário da Turma: <?= htmlspecialchars($turma_row['nome']) ?></h2>
        <div style="display:flex; gap:8px;">
            <a href="professor_dashboard.php?inst=<?= $inst_id ?>" class="btn">Voltar</a>
            <button class="btn primary" onclick="openModal('modalAddAula')">+ Agendar Aula</button>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="msg <?= htmlspecialchars($msg['type']) ?>"><?= htmlspecialchars($msg['text']) ?></div>
    <?php endif; ?>

    <div class="calendario" id="calendario"></div>
</div>

<!-- Modal Agendar Aula -->
<div id="modalAddAula" class="modal">
    <div class="card">
        <h3>Agendar Aula</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_aula">
            <input type="hidden" name="turma_id" value="<?= $turma_id ?>">
            <div class="form-row">
                <label>Disciplina</label>
                <select name="disciplina_id" required>
                    <option value="">Selecione</option>
                    <?php foreach($disciplinas as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>Data</label>
                <input type="date" name="data" required>
            </div>
            <div class="form-row">
                <label>Horário</label>
                <input type="time" name="horario" required>
            </div>
            <div class="form-row">
                <label>Sala (opcional)</label>
                <input type="text" name="sala" placeholder="Sala / Local">
            </div>
            <div class="form-row">
                <label>Descrição (opcional)</label>
                <textarea name="descricao" rows="3" placeholder="Descrição"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn" onclick="closeModal('modalAddAula')">Cancelar</button>
                <button type="submit" class="btn primary">Agendar</button>
            </div>
        </form>
    </div>
</div>

<script>
// modal
function openModal(id){ document.getElementById(id).style.display='flex'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }

// calendário
const aulas = <?= json_encode($aulas, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

// gerar calendário simples (próximo mês)
const calendarioEl = document.getElementById('calendario');
const hoje = new Date();
const ano = hoje.getFullYear();
const mes = hoje.getMonth();

const primeiroDia = new Date(ano, mes, 1);
const ultimoDia = new Date(ano, mes + 1, 0);
const diasNoMes = ultimoDia.getDate();

for(let i=1;i<=diasNoMes;i++){
    const dataStr = `${ano}-${String(mes+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
    const diaEl = document.createElement('div');
    diaEl.className='dia';
    diaEl.innerHTML=`<h4>${i}/${mes+1}</h4>`;
    const aulasDoDia = aulas.filter(a => a.data===dataStr);
    aulasDoDia.forEach(a=>{
        const b = document.createElement('span');
        b.className='aula-badge';
        b.textContent=a.disciplina_nome+' — '+a.horario.substr(0,5);
        diaEl.appendChild(b);
    });
    calendarioEl.appendChild(diaEl);
}
</script>
</body>
</html>
