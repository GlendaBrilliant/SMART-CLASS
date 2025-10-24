<?php
// professor_dashboard.php
session_start();
include("config/db.php");

// verificação de sessão (professor)
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: login.php");
    exit();
}
$id_professor = $_SESSION['usuario_id'];
$prof_nome = $_SESSION['nome'] ?? 'Professor';

// -----------------------
// Buscar instituições onde o professor tem afiliações (ativa/pendente)
$stmt = $conn->prepare("SELECT a.instituicao_id, a.status, i.nome AS instituicao_nome 
                        FROM afiliacoes a
                        JOIN instituicoes i ON i.id = a.instituicao_id
                        WHERE a.usuario_tipo='professor' AND a.usuario_id = ? AND a.status IN ('ativa','pendente')
                        ORDER BY i.nome ASC");
$stmt->bind_param("i", $id_professor);
$stmt->execute();
$res = $stmt->get_result();
$afiliacoes = [];
while ($r = $res->fetch_assoc()) $afiliacoes[] = $r;
$stmt->close();

if (empty($afiliacoes)) {
    echo "Você não está afiliado a nenhuma instituição ainda.";
    exit();
}

// selecionar instituição atual (GET inst) ou primeira afiliacao
$inst_id = isset($_GET['inst']) ? intval($_GET['inst']) : $afiliacoes[0]['instituicao_id'];
$inst_nome = '';
foreach ($afiliacoes as $a) {
    if ($a['instituicao_id'] == $inst_id) $inst_nome = $a['instituicao_nome'];
}
if (!$inst_nome) $inst_nome = $afiliacoes[0]['instituicao_nome'];

// aba atual (turmas | presencas)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'turmas';

// -----------------------
// Buscar turmas onde the professor leciona NESSA instituição
$sql_turmas = "SELECT DISTINCT t.id, t.nome, t.turno
               FROM turmas t
               JOIN disciplinas d ON d.turma_id = t.id
               JOIN professores_disciplinas pd ON pd.disciplina_id = d.id AND pd.professor_id = ? AND pd.instituicao_id = ?
               WHERE t.instituicao_id = ?
               ORDER BY t.nome ASC";
$stmt = $conn->prepare($sql_turmas);
$stmt->bind_param("iii", $id_professor, $inst_id, $inst_id);
$stmt->execute();
$res_turmas = $stmt->get_result();
$turmas = [];
while ($r = $res_turmas->fetch_assoc()) $turmas[] = $r;
$stmt->close();

// disciplinas do professor por turma (para o modal)
$disciplinas_por_turma = [];
$stmt = $conn->prepare("SELECT d.id, d.nome, d.turma_id, d.cor FROM disciplinas d
                       JOIN professores_disciplinas pd ON pd.disciplina_id = d.id AND pd.professor_id = ? AND pd.instituicao_id = ?");
$stmt->bind_param("ii", $id_professor, $inst_id);
$stmt->execute();
$resd = $stmt->get_result();
while ($d = $resd->fetch_assoc()) {
    $disciplinas_por_turma[$d['turma_id']][] = $d;
}
$stmt->close();

// -----------------------
// Buscar todas as aulas do professor (últimos 365 dias) agrupadas por turma — para usar no calendário e na visualização de detalhes
$aulasPorTurma = [];
$turmaIds = array_column($turmas, 'id');
if (!empty($turmaIds)) {
    // montar placeholders
    $in = implode(',', array_fill(0, count($turmaIds), '?'));
    // prepare dinâmico: we'll use prepared with types
    // But easier: use simple query with professor id and turma IN (...) (we control $turmaIds from DB so safe)
    $sql = "SELECT a.id, a.turma_id, a.data, a.horario, a.sala, a.descricao, d.nome AS disciplina_nome, d.cor AS disciplina_cor
            FROM aulas a
            JOIN disciplinas d ON d.id = a.disciplina_id
            WHERE a.professor_id = ? AND a.turma_id IN ($in)
            AND a.data >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
            ORDER BY a.data ASC, a.horario ASC";
    $stmt = $conn->prepare($sql);
    // bind params: first professor id then each turma id
    $types = str_repeat('i', 1 + count($turmaIds));
    $params = array_merge([$id_professor], $turmaIds);
    // bind dynamically
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res_a = $stmt->get_result();
    while ($rw = $res_a->fetch_assoc()) {
        $tid = $rw['turma_id'];
        $a = [
            'id' => (int)$rw['id'],
            'data' => $rw['data'],
            'horario' => $rw['horario'],
            'sala' => $rw['sala'],
            'descricao' => $rw['descricao'],
            'disciplina_nome' => $rw['disciplina_nome'],
            'disciplina_cor' => $rw['disciplina_cor']
        ];
        $aulasPorTurma[$tid][] = $a;
    }
    $stmt->close();
}

// -----------------------
// AJAX endpoints: editar/excluir aula (retornam JSON)
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['ajax_action'];

    if ($action === 'edit_aula') {
        $aula_id = intval($_POST['aula_id'] ?? 0);
        $data = $_POST['data'] ?? '';
        $horario = $_POST['horario'] ?? '';
        $sala = trim($_POST['sala'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($aula_id <= 0 || !$data || !$horario) {
            echo json_encode(['status'=>'error','message'=>'Campos inválidos']);
            exit();
        }

        // verificar que a aula pertence ao professor (segurança)
        $chk = $conn->prepare("SELECT id FROM aulas WHERE id = ? AND professor_id = ? LIMIT 1");
        $chk->bind_param("ii", $aula_id, $id_professor);
        $chk->execute();
        $rchk = $chk->get_result();
        if (!$rchk || $rchk->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Aula não autorizada.']);
            exit();
        }
        $chk->close();

        $upd = $conn->prepare("UPDATE aulas SET data = ?, horario = ?, sala = ?, descricao = ? WHERE id = ?");
        $upd->bind_param("ssssi", $data, $horario, $sala, $descricao, $aula_id);
        if ($upd->execute()) {
            echo json_encode(['status'=>'ok']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Erro no update.']);
        }
        $upd->close();
        exit();
    }

    if ($action === 'delete_aula') {
        $aula_id = intval($_POST['aula_id'] ?? 0);
        if ($aula_id <= 0) {
            echo json_encode(['status'=>'error','message'=>'ID inválido']);
            exit();
        }
        // verificar ownership
        $chk = $conn->prepare("SELECT id FROM aulas WHERE id = ? AND professor_id = ? LIMIT 1");
        $chk->bind_param("ii", $aula_id, $id_professor);
        $chk->execute();
        $rchk = $chk->get_result();
        if (!$rchk || $rchk->num_rows === 0) {
            echo json_encode(['status'=>'error','message'=>'Aula não autorizada.']);
            exit();
        }
        $chk->close();

        $del = $conn->prepare("DELETE FROM aulas WHERE id = ?");
        $del->bind_param("i", $aula_id);
        if ($del->execute()) echo json_encode(['status'=>'ok']);
        else echo json_encode(['status'=>'error','message'=>'Erro ao deletar.']);
        $del->close();
        exit();
    }

    echo json_encode(['status'=>'error','message'=>'Ação desconhecida']);
    exit();
}

// -----------------------
// PROCESSAR criação de aula (form do modal - formulário normal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_aula') {
    $disciplina_id = intval($_POST['disciplina_id'] ?? 0);
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $data = $_POST['data'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $sala = trim($_POST['sala'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    $msg = null;
    if ($disciplina_id <= 0 || $turma_id <= 0 || !$data || !$horario) {
        $msg = ['type'=>'erro','text'=>'Preencha todos os campos obrigatórios.'];
    } else {
        $chk = $conn->prepare("SELECT d.id FROM disciplinas d
                               JOIN professores_disciplinas pd ON pd.disciplina_id = d.id AND pd.professor_id = ? AND pd.instituicao_id = ?
                               WHERE d.id = ? AND d.turma_id = ? LIMIT 1");
        $chk->bind_param("iiii", $id_professor, $inst_id, $disciplina_id, $turma_id);
        $chk->execute();
        $rchk = $chk->get_result();
        if (!$rchk || $rchk->num_rows === 0) {
            $msg = ['type'=>'erro','text'=>'Disciplina inválida para esta turma / instituição.'];
        } else {
            $ins = $conn->prepare("INSERT INTO aulas (disciplina_id, professor_id, turma_id, data, horario, sala, descricao) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("iiissss", $disciplina_id, $id_professor, $turma_id, $data, $horario, $sala, $descricao);
            if ($ins->execute()) $msg = ['type'=>'sucesso','text'=>'Aula agendada com sucesso.'];
            else $msg = ['type'=>'erro','text'=>'Erro ao criar aula.'];
            $ins->close();
        }
        $chk->close();
    }
    // redirecionar para evitar resubmissao
    $_SESSION['flash_msg'] = $msg;
    $redirect = "professor_dashboard.php?inst={$inst_id}&tab=turmas&turma={$turma_id}";
    header("Location: {$redirect}");
    exit();
}

// recuperar flash msg se houver
if (isset($_SESSION['flash_msg'])) {
    $flash_msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
} else {
    $flash_msg = null;
}

// turma selecionada (via GET turma)
$selected_turma = isset($_GET['turma']) ? intval($_GET['turma']) : ($turmas[0]['id'] ?? 0);

// buscar aulas da turma (a partir de -365 dias para popular o calendário) — já fizemos acima, mas manter compatibilidade:
$aulas = [];
if ($selected_turma) {
    if (isset($aulasPorTurma[$selected_turma])) $aulas = $aulasPorTurma[$selected_turma];
}

// map para JS
$js_disciplinas_por_turma = json_encode($disciplinas_por_turma, JSON_HEX_TAG|JSON_HEX_QUOT);
$js_aulas = json_encode($aulas, JSON_HEX_TAG|JSON_HEX_QUOT);
$js_aulas_por_turma = json_encode($aulasPorTurma, JSON_HEX_TAG|JSON_HEX_QUOT);
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Dashboard Professor — <?= htmlspecialchars($inst_nome) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
/* Layout e cores semelhantes ao dashboard da instituição */
:root {
    --accent: #375569;
    --accent-2: #2d425b;
    --muted: #777;
    --card-bg: #fff;
    --bg: #f4f6f9;
}
* { box-sizing: border-box; }
html,body { height:100%; margin:0; font-family:Poppins, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background: linear-gradient(135deg,#f0f4f9,#dbeafe);; color:#222; }
.app { display:flex; height:100vh; width:100vw; }


    main {
        flex: 1;
        margin-left: 250px;
        height: 100vh;
        overflow-y: auto;
        background: linear-gradient(135deg,#f0f4f9,#dbeafe);
        padding: 30px;
        box-sizing: border-box;
        align-items:left;
    }
.header {
    display:flex; justify-content:space-between; align-items:center; padding:14px 20px; background:#fff; border-radius:8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.16); width: 98%; margin-left: 15px;
}
.header h1 { font-size:18px; margin:0; }
.header .controls { display:flex; gap:8px; align-items:center; }

.content { flex:1; display:flex; gap:18px; padding:18px;  }

/* Left column (turmas list) */
.leftcol { width:320px; max-width:38%; display:flex; flex-direction:column; gap:12px; }
.card { background:var(--card-bg); padding:12px; border-radius:10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }

/* Turmas menu with subitems */
.turma-group { display:flex; flex-direction:column; gap:8px; }
.turma-item { display:flex; justify-content:space-between; align-items:center; padding:10px; border-radius:8px; cursor:pointer; }
.turma-item:hover { background:#f1f6fb; }
.turma-item.active { background: var(--accent); color:#fff; }
.subturmas { display:none; flex-direction:column; margin-top:8px; gap:6px; padding-left:8px; }
.subturmas a { display:block; padding:8px 10px; border-radius:6px; text-decoration:none; color:inherit; background:transparent; }
.subturmas a:hover { background:#eef6ff; }
.subturmas a.active { background:#dfeefc; }

/* Calendar area */
.rightcol { flex:1; min-width:0; display:flex; flex-direction:column; gap:12px;  }
.calendar-header { display:flex; justify-content:space-between; align-items:center; gap:8px; }
.calendar-grid { background:var(--card-bg); padding:12px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); display:flex; flex-direction:column; height:100%; overflow:hidden; }
.calendar-controls { display:flex; gap:8px; align-items:center; margin-bottom:10px; }
.month-title { font-weight:700; }
.grid { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; flex:1; overflow:auto; padding:6px; }
.day { background:#fafafa; min-height:120px; border-radius:8px; padding:8px; border:1px solid #eee; display:flex; flex-direction:column; gap:6px; }
.day .date-num { font-weight:700; font-size:13px; }
.events { margin-top:6px; display:flex; flex-direction:column; gap:6px; overflow:auto; }
.event { padding:6px 8px; border-radius:6px; font-size:13px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); color:#0b2540; cursor:pointer; }

/* ensure readable text on dark event backgrounds */
.event.light-text { color:#fff; }

/* small helpers */
.small-muted { color:var(--muted); font-size:13px; }
.btn { background:#fff; border:1px solid #ddd; padding:8px 10px; border-radius:8px; cursor:pointer; }
.btn.primary { background:var(--accent); color:#fff; border-color:var(--accent); }
.hidden { display:none !important; }

/* modal (neutro branco) */
.modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.45); z-index:9999; }
.modal .card { width:520px; max-width:96%; background:#fff; padding:16px; border-radius:10px; }
.form-row { margin-bottom:10px; }
.form-row label { display:block; font-weight:600; margin-bottom:6px; }
.form-row input, .form-row select, .form-row textarea { width:100%; padding:8px; border-radius:8px; border:1px solid #ccc; }

/* details list */
.details-list { display:flex; flex-direction:column; gap:10px; }
.details-item { background:#fff; border-radius:8px; padding:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); display:flex; justify-content:space-between; gap:8px; align-items:flex-start; }
.details-item .left { max-width:72%; }
.details-item .right { display:flex; flex-direction:column; gap:6px; align-items:flex-end; }

/* responsive */
@media (max-width:900px) {
    .content { flex-direction:column; padding:12px; }
    .leftcol { width:100%; max-width:100%; order:2; }
    .rightcol { order:1; height:60vh; }
}
</style>
</head>
<body>

<div class="app">
    <!-- SIDEBAR (igual estilo do dash da instituição) -->
<div class="sidebar">
    <div class="top-section">
        <div class="logo"><?= $_SESSION['nome'] ?></div>
        <div class="menu">
            <ul>
                <li><a href="professor_dashboard.php" class="active"><i class="bi bi-calendar2-plus"></i>Agendas</a></li>
                <li><a href="professor_presencas.php"><i class="bi bi-check-circle"></i>Presenças</a></li>
                <li><a href="professor_perfil.php"><i class="bi bi-person-circle"></i>Perfil</a></li>
            </ul>
        </div>
    </div>
    <a href="logout.php" class="logout">Sair</a>
</div>
    <!-- MAIN -->
    <main class="main">
        <header class="header">
            <div>
                <h1>Professor — <?= htmlspecialchars($inst_nome) ?></h1>
                <div class="small-muted">Instituição selecionada</div>
            </div>
            <div class="controls">
                <?php if ($tab === 'turmas'): ?>
                    <button class="btn primary" id="btnAddAula" onclick="openModal('modalAddAula')"><i class="bi bi-plus-lg"></i>&nbsp;Agendar Aula</button>
                <?php endif; ?>
            </div>
        </header>

        <div class="content">
            <?php if ($tab === 'turmas'): ?>

            <!-- esquerda: lista de turmas / submenu -->
            <aside class="leftcol">
                <div class="card">
                    <strong>Turmas em que você leciona</strong>
                    <div class="small-muted" style="margin-top:6px">Clique para expandir as turmas e ver o calendário.</div>
                </div>

                <div class="card turma-group">
                    <?php foreach ($turmas as $t):
                        $is_open = ($selected_turma && $selected_turma == $t['id']);
                    ?>
                        <div class="turma-item <?= $is_open ? 'active' : '' ?>" data-tid="<?= $t['id'] ?>" onclick="toggleSub(<?= $t['id'] ?>)">
                            <div>
                                <div style="font-weight:700"><?= htmlspecialchars($t['nome']) ?></div>
                                <div class="small-muted"><?= htmlspecialchars($t['turno'] ?? '-') ?></div>
                            </div>
                            <div style="font-size:18px; opacity:0.8;">▾</div>
                        </div>

                        <div id="sub-<?= $t['id'] ?>" class="subturmas" style="<?= $is_open ? 'display:flex;' : '' ?>">
                            <?php
                            $link = "professor_dashboard.php?inst={$inst_id}&tab=turmas&turma={$t['id']}";
                            ?>
                            <a href="<?= $link ?>" class="<?= $is_open ? 'active' : '' ?>">📅 Ver calendário</a>
                            <!-- ver-detalhes: interceptado por JS para não recarregar -->
                            <a href="#" class="ver-detalhes" data-tid="<?= $t['id'] ?>">🔍 Ver detalhes</a>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($turmas)): ?>
                        <div class="small-muted">Nenhuma turma encontrada para esta instituição.</div>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- direita: calendario / detalhes -->
            <section class="rightcol">
                <?php if ($flash_msg): ?>
                    <div class="card"><?= htmlspecialchars($flash_msg['text']) ?></div>
                <?php endif; ?>

                <?php if (!$selected_turma): ?>
                    <div class="card">Selecione uma turma à esquerda para ver o calendário.</div>
                <?php else: ?>
                    <div class="calendar-header card" id="calendarHeader">
                        <div>
                            <div class="small-muted">Turma</div>
                            <div style="font-weight:700"><?= htmlspecialchars(array_values(array_filter($turmas, function($x) use($selected_turma){ return $x['id']==$selected_turma;}))[0]['nome'] ?? '-') ?></div>
                        </div>
                        <div class="calendar-controls">
                            <button class="btn" id="prevMonth">◀</button>
                            <div class="month-title" id="monthTitle"></div>
                            <button class="btn" id="nextMonth">▶</button>
                            <button class="btn" onclick="goToday()">Hoje</button>
                        </div>
                    </div>

                    <div class="calendar-grid" id="calendarGridWrap">
                        <div class="grid" id="calendarGrid"></div>
                    </div>

                    <!-- container de detalhes (inicialmente escondido) -->
                    <div id="detailsContainer" class="card hidden" style="display:none;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <div>
                                <strong id="detailsTitle">Detalhes da turma</strong>
                                <div class="small-muted" id="detailsSubtitle"></div>
                            </div>
                            <div>
                                <button class="btn" onclick="showCalendarView()">Voltar ao calendário</button>
                            </div>
                        </div>
                        <div id="detailsList" class="details-list"></div>
                    </div>
                <?php endif; ?>
            </section>

            <?php elseif ($tab === 'presencas'): ?>

                <aside class="leftcol">
                    <div class="card"><strong>Presenças</strong><div class="small-muted">Área em construção.</div></div>
                </aside>

                <section class="rightcol">
                    <div class="card"><strong>Presenças</strong><div class="small-muted">Implementação futura — por enquanto esta aba está vazia.</div></div>
                </section>

            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal Agendar Aula (form normal) -->
<div id="modalAddAula" class="modal">
    <div class="card">
        <h3>Agendar Aula</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_aula">
            <div class="form-row">
                <label>Turma</label>
                <select name="turma_id" id="form_turma_id" required>
                    <option value="">Selecione a turma</option>
                    <?php foreach ($turmas as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selected_turma == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label>Disciplina (apenas as suas)</label>
                <select name="disciplina_id" id="form_disciplina_id" required>
                    <option value="">Selecione a turma primeiro</option>
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
                <input type="text" name="sala" placeholder="Ex: Sala 101">
            </div>

            <div class="form-row">
                <label>Descrição (opcional)</label>
                <textarea name="descricao" rows="3" placeholder="Observações..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn" onclick="closeModal('modalAddAula')">Cancelar</button>
                <button type="submit" class="btn primary">Agendar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal editar/excluir aula (neutro branco) -->
<div id="modalEditAula" class="modal">
    <div class="card">
        <h3>Detalhes da Aula</h3>
        <form id="formEditAula">
            <input type="hidden" name="aula_id" id="edit_aula_id">
            <div class="form-row">
                <label>Disciplina</label>
                <input type="text" id="edit_disciplina" disabled>
            </div>
            <div class="form-row">
                <label>Data</label>
                <input type="date" name="data" id="edit_data" required>
            </div>
            <div class="form-row">
                <label>Horário</label>
                <input type="time" name="horario" id="edit_horario" required>
            </div>
            <div class="form-row">
                <label>Sala</label>
                <input type="text" name="sala" id="edit_sala">
            </div>
            <div class="form-row">
                <label>Descrição</label>
                <textarea name="descricao" id="edit_descricao" rows="3"></textarea>
            </div>

            <div style="display:flex; justify-content:space-between; gap:8px;">
                <button type="button" class="btn" onclick="deleteAula()">Excluir</button>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn" onclick="closeModal('modalEditAula')">Fechar</button>
                    <button type="button" class="btn primary" onclick="saveEditAula()">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// dados do PHP para o JS
const disciplinasPorTurma = <?= $js_disciplinas_por_turma ?>;
const aulasData = <?= $js_aulas ?>; // aulas da turma selecionada
const aulasPorTurma = <?= $js_aulas_por_turma ?>; // mapa turma_id -> [aulas]
const selectedTurma = <?= json_encode($selected_turma) ?>;

// helpers do modal: popula select de disciplinas quando trocar turma
document.getElementById('form_turma_id')?.addEventListener('change', function(){
    const tid = this.value;
    const sel = document.getElementById('form_disciplina_id');
    sel.innerHTML = '';
    if (!tid || !disciplinasPorTurma[tid]) {
        sel.innerHTML = '<option value="">Nenhuma disciplina</option>'; return;
    }
    disciplinasPorTurma[tid].forEach(d => {
        const o = document.createElement('option'); o.value = d.id; o.textContent = d.nome; sel.appendChild(o);
    });
});

// abrir / fechar modal
function openModal(id){ document.getElementById(id).style.display = 'flex'; }
function closeModal(id){ document.getElementById(id).style.display = 'none'; }

// persistir disciplinas do selectedTurma no modal se abrir
if (selectedTurma && document.getElementById('form_turma_id')) {
    document.getElementById('form_turma_id').value = selectedTurma;
    const ev = new Event('change'); document.getElementById('form_turma_id').dispatchEvent(ev);
}

// interceptar cliques em "Ver detalhes" para mostrar painel de detalhes sem recarregar
document.querySelectorAll('a.ver-detalhes').forEach(a=>{
    a.addEventListener('click', function(e){
        e.preventDefault();
        const tid = parseInt(this.dataset.tid);
        showDetailsForTurma(tid);
    });
});

// função para mostrar detalhes (lista completa de aulas) de uma turma
function showDetailsForTurma(tid){
    // esconder calendário
    document.getElementById('calendarHeader')?.classList.add('hidden');
    document.getElementById('calendarGridWrap')?.classList.add('hidden');

    // preencher título
    const tObj = <?= json_encode(array_column($turmas, null, 'id'), JSON_HEX_TAG|JSON_HEX_QUOT) ?>;
    const tinfo = tObj[tid] || null;
    document.getElementById('detailsTitle').textContent = 'Aulas — ' + (tinfo ? tinfo.nome : ('Turma ' + tid));
    document.getElementById('detailsSubtitle').textContent = tinfo ? ('Turno: ' + (tinfo.turno || '-')) : '';

    // montar lista
    const listWrap = document.getElementById('detailsList');
    listWrap.innerHTML = '';
    const aulas = aulasPorTurma[tid] || [];
    if (aulas.length === 0) {
        listWrap.innerHTML = '<div class="small-muted">Nenhuma aula cadastrada para esta turma.</div>';
    } else {
        aulas.forEach(a=>{
            const it = document.createElement('div');
            it.className = 'details-item';
            const left = document.createElement('div'); left.className='left';
            const right = document.createElement('div'); right.className='right';

            left.innerHTML = `<div style="font-weight:700">${escapeHtml(a.disciplina_nome)}</div>
                              <div class="small-muted">${formatDateBr(a.data)} — ${a.horario ? a.horario.substring(0,5) : ''} — ${escapeHtml(a.sala || '-')}</div>
                              <div style="margin-top:8px;">${escapeHtml(a.descricao || '')}</div>`;

            right.innerHTML = `<div style="font-size:13px; color:var(--muted)">ID: ${a.id}</div>
                               <div style="display:flex; gap:6px; margin-top:6px;">
                                  <button class="btn" onclick="openEditFromList(${a.id}, ${JSON.stringify(a).replace(/'/g,"\\'")})">Editar</button>
                                  <button class="btn" onclick="confirmDeleteFromList(${a.id})">Excluir</button>
                               </div>`;

            it.appendChild(left);
            it.appendChild(right);
            listWrap.appendChild(it);
        });
    }

    // mostrar container
    const cont = document.getElementById('detailsContainer');
    cont.style.display = 'block';
    cont.classList.remove('hidden');
}

// voltar ao calendário
function showCalendarView(){
    document.getElementById('detailsContainer').style.display = 'none';
    document.getElementById('detailsContainer').classList.add('hidden');
    document.getElementById('calendarHeader')?.classList.remove('hidden');
    document.getElementById('calendarGridWrap')?.classList.remove('hidden');
}

// abrir modal de edição a partir do botão da lista (preenche campos)
function openEditFromList(aulaId, aulaObj){
    // aulaObj já contém os dados (passado inline). Mas para garantir, procuramos no array global também.
    let a = aulaObj;
    if (!a || !a.id) {
        // buscar em aulasPorTurma
        for (let tid in aulasPorTurma) {
            const arr = aulasPorTurma[tid];
            for (let i=0;i<arr.length;i++){
                if (arr[i].id == aulaId) { a = arr[i]; break; }
            }
        }
    }
    if (!a) { alert('Aula não encontrada'); return; }
    document.getElementById('edit_aula_id').value = a.id;
    document.getElementById('edit_disciplina').value = a.disciplina_nome || '';
    document.getElementById('edit_data').value = a.data || '';
    document.getElementById('edit_horario').value = a.horario ? a.horario.substring(0,5) : '';
    document.getElementById('edit_sala').value = a.sala || '';
    document.getElementById('edit_descricao').value = a.descricao || '';
    openModal('modalEditAula');
}

// confirma exclusão a partir da lista
function confirmDeleteFromList(aulaId){
    if (!confirm('Excluir esta aula?')) return;
    // chamar deleteAula com id setado
    document.getElementById('edit_aula_id').value = aulaId;
    deleteAula();
}

// ---- Calendário (render cliente)
// representaremos um calendário mensal completo com navegação (mês/ano)
let current = new Date();
const grid = document.getElementById('calendarGrid');
const monthTitle = document.getElementById('monthTitle');

function startOfMonth(d){ return new Date(d.getFullYear(), d.getMonth(), 1); }
function endOfMonth(d){ return new Date(d.getFullYear(), d.getMonth()+1, 0); }

function renderCalendar(){
    if (!grid) return;
    grid.innerHTML = '';
    const start = startOfMonth(current);
    const end = endOfMonth(current);
    const startWeekday = start.getDay(); // 0..6 (Dom..Sab)
    const totalDays = end.getDate();

    const months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    monthTitle.textContent = months[current.getMonth()] + ' ' + current.getFullYear();

    // dias vazios antes
    for (let i=0;i<startWeekday;i++){
        const empty = document.createElement('div');
        empty.className = 'day'; empty.style.background='#f8f9fb'; empty.innerHTML = '';
        grid.appendChild(empty);
    }

    // criar dias do mês
    for (let d=1; d<=totalDays; d++){
        const dateObj = new Date(current.getFullYear(), current.getMonth(), d);
        const iso = dateObj.toISOString().slice(0,10);
        const dayEl = document.createElement('div');
        dayEl.className = 'day';
        dayEl.innerHTML = `<div class="date-num">${d}</div><div class="events" id="ev-${iso}"></div>`;
        grid.appendChild(dayEl);
    }

    const cells = grid.children.length;
    const rem = (7 - (cells % 7)) % 7;
    for (let i=0;i<rem;i++){
        const empty = document.createElement('div'); empty.className='day'; empty.style.background='#f8f9fb'; grid.appendChild(empty);
    }

    // mapar aulas para os dias (filtrar pelas aulasData ou usar aulasPorTurma[selectedTurma])
    // if selectedTurma has aulas loaded in global aulasData, use that; else try aulasPorTurma[selectedTurma]
    let feed = aulasData && aulasData.length ? aulasData : (aulasPorTurma[selectedTurma] || []);
    feed.forEach(a=>{
        const evContainer = document.getElementById('ev-' + a.data);
        if (evContainer) {
            const ev = document.createElement('div');
            ev.className = 'event';
            ev.dataset.aulaId = a.id;
            ev.dataset.data = a.data;
            ev.dataset.horario = a.horario;
            ev.dataset.sala = a.sala || '';
            ev.dataset.descricao = a.descricao || '';
            ev.dataset.disciplina = a.disciplina_nome || '';
            ev.dataset.cor = a.disciplina_cor || '';

            const bg = a.disciplina_cor || '#e6f2ff';
            ev.style.background = bg;

            if (isDark(bg)) ev.classList.add('light-text');

            ev.innerHTML = `<strong>${escapeHtml(a.disciplina_nome)}</strong><div style="font-size:12px">${a.horario ? a.horario.substring(0,5) : ''} ${a.sala ? '- ' + escapeHtml(a.sala) : ''}</div>`;
            ev.addEventListener('click', function(e){
                e.stopPropagation();
                openEditModalFromEvent(this);
            });
            evContainer.appendChild(ev);
        }
    });
}

document.getElementById('prevMonth')?.addEventListener('click', function(){ current.setMonth(current.getMonth()-1); renderCalendar(); });
document.getElementById('nextMonth')?.addEventListener('click', function(){ current.setMonth(current.getMonth()+1); renderCalendar(); });
function goToday(){ current = new Date(); renderCalendar(); }

function escapeHtml(str){ if(!str) return ''; return String(str).replace(/[&<>"']/g, s=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s])); }
function isDark(hex){
    if(!hex) return false;
    hex = hex.replace('#','');
    if(hex.length === 3) hex = hex.split('').map(c=>c+c).join('');
    const r = parseInt(hex.substr(0,2),16), g = parseInt(hex.substr(2,2),16), b = parseInt(hex.substr(4,2),16);
    const lum = 0.2126*r + 0.7152*g + 0.0722*b;
    return lum < 140;
}

function openEditModalFromEvent(el){
    const id = el.dataset.aulaId;
    document.getElementById('edit_aula_id').value = id;
    document.getElementById('edit_disciplina').value = el.dataset.disciplina || '';
    document.getElementById('edit_data').value = el.dataset.data || '';
    document.getElementById('edit_horario').value = el.dataset.horario ? el.dataset.horario.substring(0,5) : '';
    document.getElementById('edit_sala').value = el.dataset.sala || '';
    document.getElementById('edit_descricao').value = el.dataset.descricao || '';
    openModal('modalEditAula');
}

function saveEditAula(){
    const id = document.getElementById('edit_aula_id').value;
    const data = document.getElementById('edit_data').value;
    const horario = document.getElementById('edit_horario').value;
    const sala = document.getElementById('edit_sala').value;
    const descricao = document.getElementById('edit_descricao').value;

    if(!id || !data || !horario){ alert('Preencha data e horário'); return; }

    const fd = new FormData();
    fd.append('ajax_action','edit_aula');
    fd.append('aula_id', id);
    fd.append('data', data);
    fd.append('horario', horario);
    fd.append('sala', sala);
    fd.append('descricao', descricao);

    fetch('professor_dashboard.php?inst=<?= $inst_id ?>&tab=turmas', { method:'POST', body: fd })
    .then(r=>r.json())
    .then(js=>{
        if(js.status === 'ok'){

            // atualizar dados locais (aulasData e aulasPorTurma)
            for(let t in aulasPorTurma){
                for(let i=0;i<aulasPorTurma[t].length;i++){
                    if(aulasPorTurma[t][i].id == id){
                        aulasPorTurma[t][i].data = data;
                        aulasPorTurma[t][i].horario = horario;
                        aulasPorTurma[t][i].sala = sala;
                        aulasPorTurma[t][i].descricao = descricao;
                    }
                }
            }
            for(let i=0;i<aulasData.length;i++){
                if(aulasData[i].id == id){
                    aulasData[i].data = data;
                    aulasData[i].horario = horario;
                    aulasData[i].sala = sala;
                    aulasData[i].descricao = descricao;
                }
            }

            renderCalendar();
            closeModal('modalEditAula');
        } else {
            alert(js.message || 'Erro ao salvar');
        }
    }).catch(e=>{ alert('Erro de rede'); console.error(e); });
}

function deleteAula(){
    if(!confirm('Excluir esta aula?')) return;
    const id = document.getElementById('edit_aula_id').value;
    const fd = new FormData();
    fd.append('ajax_action','delete_aula');
    fd.append('aula_id', id);

    fetch('professor_dashboard.php?inst=<?= $inst_id ?>&tab=turmas', { method:'POST', body: fd })
    .then(r=>r.json())
    .then(js=>{
        if(js.status === 'ok'){
            // remover localmente
            for(let t in aulasPorTurma){
                for(let i=0;i<aulasPorTurma[t].length;i++){
                    if(aulasPorTurma[t][i].id == id){ aulasPorTurma[t].splice(i,1); break; }
                }
            }
            for(let i=0;i<aulasData.length;i++){
                if(aulasData[i].id == id){ aulasData.splice(i,1); break; }
            }
            renderCalendar();
            closeModal('modalEditAula');
            // também remover do details list se estiver visível
            const el = document.querySelector(`.details-item [onclick*="openEditFromList(${id}"]`);
            if (el) renderDetailsAgainIfOpen();
        } else {
            alert(js.message || 'Erro ao excluir');
        }
    }).catch(e=>{ alert('Erro de rede'); console.error(e); });
}

// helper to refresh details list if currently open
function renderDetailsAgainIfOpen(){
    const details = document.getElementById('detailsContainer');
    if (!details || details.style.display === 'none') return;
    // read title to infer turma id? We'll fallback to re-show selectedTurma details
    // Simpler: find displayed turma id from title: it contains turma name; we can't reliably get id — skip
    // Instead reload page to guarantee consistent state:
    location.reload();
}

// toggle submenu
function toggleSub(id){
    const el = document.getElementById('sub-'+id);
    if(!el) return;
    const all = document.querySelectorAll('.subturmas');
    all.forEach(x => { if(x.id !== 'sub-'+id) x.style.display = 'none'; });
    el.style.display = (el.style.display === 'flex') ? 'none' : 'flex';
}

// util format date
function formatDateBr(iso){
    if(!iso) return '-';
    const d = new Date(iso);
    const dd = String(d.getDate()).padStart(2,'0');
    const mm = String(d.getMonth()+1).padStart(2,'0');
    const yy = d.getFullYear();
    return dd + '/' + mm + '/' + yy;
}

// inicializar calendário
renderCalendar();
</script>
</body>
</html>
