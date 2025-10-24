<?php
session_start();
include("../config/db.php");

// Apenas instituições podem acessar
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    header("Location: ../login.php");
    exit();
}

$id_instituicao = $_SESSION['usuario_id'];

// ---------------------------
// Processamento de formulários
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Adicionar Turma
    if (isset($_POST['action']) && $_POST['action'] === 'add_turma') {
        $nome = trim($_POST['nome'] ?? '');
        $turno = trim($_POST['turno'] ?? '');

        if ($nome !== '') {
            $stmt = $conn->prepare("INSERT INTO turmas (instituicao_id, nome, turno) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id_instituicao, $nome, $turno);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: turmas.php");
        exit();
    }

    // Editar Turma
    if (isset($_POST['action']) && $_POST['action'] === 'edit_turma') {
        $turma_id = intval($_POST['turma_id']);
        $nome = trim($_POST['nome'] ?? '');
        $turno = trim($_POST['turno'] ?? '');

        if ($nome !== '') {
            $stmt = $conn->prepare("UPDATE turmas SET nome = ?, turno = ? WHERE id = ? AND instituicao_id = ?");
            $stmt->bind_param("ssii", $nome, $turno, $turma_id, $id_instituicao);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: turmas.php");
        exit();
    }

    // Excluir Turma
    if (isset($_POST['action']) && $_POST['action'] === 'delete_turma') {
        $turma_id = intval($_POST['turma_id']);

        // Remover disciplinas e relações
        $stmtDelDisc = $conn->prepare("SELECT id FROM disciplinas WHERE turma_id = ?");
        $stmtDelDisc->bind_param("i", $turma_id);
        $stmtDelDisc->execute();
        $res = $stmtDelDisc->get_result();
        $discIds = [];
        while ($d = $res->fetch_assoc()) $discIds[] = $d['id'];
        $stmtDelDisc->close();

        if (!empty($discIds)) {
            $in = implode(',', array_fill(0, count($discIds), '?'));
            $types = str_repeat('i', count($discIds));

            $sql = "DELETE FROM professores_disciplinas WHERE disciplina_id IN ($in)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$discIds);
            $stmt->execute();
            $stmt->close();

            $sql = "DELETE FROM disciplinas WHERE id IN ($in)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$discIds);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("UPDATE alunos SET turma_id = NULL WHERE turma_id = ?");
        $stmt->bind_param("i", $turma_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM turmas WHERE id = ? AND instituicao_id = ?");
        $stmt->bind_param("ii", $turma_id, $id_instituicao);
        $stmt->execute();
        $stmt->close();

        header("Location: turmas.php");
        exit();
    }

    // Adicionar Disciplina
    if (isset($_POST['action']) && $_POST['action'] === 'add_disciplina') {
        $turma_id = intval($_POST['turma_id']);
        $nome_disc = trim($_POST['nome_disciplina'] ?? '');
        $cor = $_POST['cor'] ?? '#f0f0f0';

        if ($nome_disc !== '') {
            $stmt = $conn->prepare("INSERT INTO disciplinas (turma_id, nome, cor) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $turma_id, $nome_disc, $cor);
            $stmt->execute();
            $disciplina_id = $stmt->insert_id;
            $stmt->close();

            if (!empty($_POST['professores'])) {
                $professores = $_POST['professores'];
                $stmtIns = $conn->prepare("INSERT INTO professores_disciplinas (professor_id, disciplina_id, instituicao_id) VALUES (?, ?, ?)");
                foreach ($professores as $pid) {
                    $pid = intval($pid);
                    $stmtIns->bind_param("iii", $pid, $disciplina_id, $id_instituicao);
                    $stmtIns->execute();
                }
                $stmtIns->close();
            }
        }
        header("Location: turmas.php");
        exit();
    }

    // Editar Disciplina
    if (isset($_POST['action']) && $_POST['action'] === 'edit_disciplina') {
        $disc_id = intval($_POST['disciplina_id']);
        $nome_disc = trim($_POST['nome_disciplina'] ?? '');
        $cor = $_POST['cor'] ?? '#f0f0f0';

        if ($nome_disc !== '') {
            $stmt = $conn->prepare("UPDATE disciplinas SET nome = ?, cor = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nome_disc, $cor, $disc_id);
            $stmt->execute();
            $stmt->close();

            // Atualizar professores vinculados
            $stmt = $conn->prepare("DELETE FROM professores_disciplinas WHERE disciplina_id = ?");
            $stmt->bind_param("i", $disc_id);
            $stmt->execute();
            $stmt->close();

            if (!empty($_POST['professores'])) {
                $professores = $_POST['professores'];
                $stmtIns = $conn->prepare("INSERT INTO professores_disciplinas (professor_id, disciplina_id, instituicao_id) VALUES (?, ?, ?)");
                foreach ($professores as $pid) {
                    $pid = intval($pid);
                    $stmtIns->bind_param("iii", $pid, $disc_id, $id_instituicao);
                    $stmtIns->execute();
                }
                $stmtIns->close();
            }
        }
        header("Location: turmas.php");
        exit();
    }

    // Remover Disciplina
    if (isset($_POST['action']) && $_POST['action'] === 'delete_disciplina') {
        $disc_id = intval($_POST['disciplina_id']);
        $stmt = $conn->prepare("DELETE FROM professores_disciplinas WHERE disciplina_id = ?");
        $stmt->bind_param("i", $disc_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM disciplinas WHERE id = ?");
        $stmt->bind_param("i", $disc_id);
        $stmt->execute();
        $stmt->close();

        header("Location: turmas.php");
        exit();
    }
}

// ---------------------------
// Buscar dados para exibição
// ---------------------------
$sql_turmas = "SELECT t.*, 
               (SELECT COUNT(*) FROM alunos a WHERE a.turma_id = t.id) AS alunos_count
               FROM turmas t
               WHERE t.instituicao_id = ?
               ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql_turmas);
$stmt->bind_param("i", $id_instituicao);
$stmt->execute();
$result_turmas = $stmt->get_result();
$stmt->close();

$sql_professores_afiliados = "SELECT p.id, p.nome FROM professores p
    JOIN afiliacoes af ON af.usuario_tipo='professor' AND af.usuario_id = p.id
    WHERE af.instituicao_id = ? AND af.status = 'ativa'";
$stmt = $conn->prepare($sql_professores_afiliados);
$stmt->bind_param("i", $id_instituicao);
$stmt->execute();
$result_prof_aff = $stmt->get_result();
$professores_afiliados = [];
while ($r = $result_prof_aff->fetch_assoc()) $professores_afiliados[] = $r;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Turmas e Matérias - Dashboard</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<style>
.turma-card.expanded .turma-actions button {
    color: #000 !important;
}

.turma-card.expanded {
    background-color: #375569;
    color: #fff;
}

.turma-card.expanded .turma-info strong,
.turma-card.expanded .turma-meta,
.turma-card.expanded .turma-actions button,
.turma-card.expanded .disciplinas-area {
    color: #fff;
}

.disc-item { padding:6px; 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    border-radius:6px; 
    margin-bottom:6px; 
}

.disc-item div:first-child { 
    background:#fff; 
    padding:6px 10px; 
    border-radius:4px; 
    flex:1; 
    margin-right:8px; 
}

.btn { 
    padding:4px 8px; 
    border-radius:4px; 
    cursor:pointer; 
}

.btn.ghost { 
    background:#fff; 
    border:1px solid #ccc; 
    color:#333; 
}
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
                <li><a href="aprovacoes.php"><i class="bi bi-check-circle"></i>Aprovações</a></li>
                <li><a href="professores.php"><i class="bi bi-person-badge"></i>Professores</a></li>
                <li><a href="alunos.php"><i class="bi bi-backpack"></i>Alunos</a></li>
                <li><a href="turmas.php" class="active"><i class="bi bi-journal-bookmark"></i>Turmas e Matérias</a></li>
            </ul>
        </div>
    </div>

    <a href="../logout.php" class="logout">Sair</a>
</div>

<main class="main-content">
    <div class="page-header">
        <h2 class="page-title">🏫 Turmas e Matérias</h2>
        <p class="small-muted">Gerencie as turmas, veja quantos alunos, adicione matérias e relacione professores.</p>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin:18px 0;">
        <div class="small-muted">Total de turmas: <strong><?= $result_turmas->num_rows ?></strong></div>
        <div><button class="btn primary" onclick="openModal('modalAddTurma')">+ Adicionar Turma</button></div>
    </div>

    <div class="turma-list">
    <?php while($turma = $result_turmas->fetch_assoc()): ?>
        <?php
            $stmt = $conn->prepare("SELECT d.*, 
                (SELECT GROUP_CONCAT(p.nome SEPARATOR ', ') FROM professores p
                 JOIN professores_disciplinas pd ON pd.professor_id = p.id
                 WHERE pd.disciplina_id = d.id AND pd.instituicao_id = ?) AS profs
                FROM disciplinas d WHERE d.turma_id = ? ORDER BY d.nome ASC");
            $stmt->bind_param("ii", $id_instituicao, $turma['id']);
            $stmt->execute();
            $res_disc = $stmt->get_result();
            $stmt->close();
        ?>
        <div class="turma-card" data-turma="<?= $turma['id'] ?>">
            <div class="turma-left" onclick="toggleExpand(<?= $turma['id'] ?>)">
                <div class="turma-info">
                    <strong><?= htmlspecialchars($turma['nome']) ?></strong>
                    <div class="turma-meta">Turno: <?= htmlspecialchars($turma['turno'] ?? '-') ?> • <span class="badge"><?= $turma['alunos_count'] ?> alunos</span></div>
                </div>
            </div>
            <div class="turma-actions">
                <button class="btn ghost" onclick="openAddDisc(<?= $turma['id'] ?>)">+ Matéria</button>
                <button class="three-dots" title="Mais" onclick="openMenu(event, <?= $turma['id'] ?>)">⋯</button>
            </div>
        </div>

        <div id="expand-<?= $turma['id'] ?>" class="disciplinas-area" style="display:none; margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <strong>Matérias da turma</strong>
                <span class="small-muted"><?= $res_disc->num_rows ?> disciplina(s)</span>
            </div>

            <?php if($res_disc->num_rows>0): while($d=$res_disc->fetch_assoc()): ?>
            <div class="disc-item" style="background-color: <?= htmlspecialchars($d['cor'] ?? '#f0f0f0') ?>;">
                <div>
                    <div><strong><?= htmlspecialchars($d['nome']) ?></strong></div>
                    <div class="small">Professores: <?= htmlspecialchars($d['profs'] ?? '—') ?></div>
                </div>
                <div style="display:flex; gap:6px;">
                    <button class="btn ghost" type="button" onclick="openEditDisc(<?= $d['id'] ?>)">Editar</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Remover esta disciplina?')">
                        <input type="hidden" name="action" value="delete_disciplina">
                        <input type="hidden" name="disciplina_id" value="<?= $d['id'] ?>">
                        <button class="btn ghost" type="submit">Excluir</button>
                    </form>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="small-muted">Nenhuma disciplina criada para esta turma.</div>
            <?php endif; ?>

            <?php
                $stmt_alunos = $conn->prepare("SELECT nome, email FROM alunos WHERE turma_id = ? ORDER BY nome ASC");
                $stmt_alunos->bind_param("i", $turma['id']);
                $stmt_alunos->execute();
                $res_alunos = $stmt_alunos->get_result();
                $stmt_alunos->close();
            ?>
            <div style="margin-top:12px;">
                <strong>Alunos da turma</strong>
                <?php if ($res_alunos->num_rows > 0): ?>
                    <ul style="margin-top:6px; padding-left:18px;">
                        <?php while($aluno = $res_alunos->fetch_assoc()): ?>
                            <li><?= htmlspecialchars($aluno['nome']) ?> <?= $aluno['email'] ? '(' . htmlspecialchars($aluno['email']) . ')' : '' ?></li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="small-muted">Nenhum aluno cadastrado nesta turma.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
    </div>

</main>
</div>

<!-- Menu contexto -->
<div id="menuContext" style="position:fixed; display:none; z-index:60; background:#fff; box-shadow:0 6px 20px rgba(0,0,0,0.15); border-radius:8px; padding:8px;">
    <button class="btn ghost" id="btnEditTurma" onclick="openEditTurma()">Editar Turma</button>
    <form id="formDeleteTurma" method="POST" style="display:inline;">
        <input type="hidden" name="action" value="delete_turma">
        <input type="hidden" name="turma_id" id="deleteTurmaId">
        <button class="btn" type="submit" style="background:#c62828; color:#fff; border:none;">Excluir Turma</button>
    </form>
</div>

<!-- Modal Adicionar Turma -->
<div id="modalAddTurma" class="modal">
    <div class="card">
        <h3>Adicionar Turma</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_turma">
            <div class="form-row">
                <label>Nome da Turma</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-row">
                <label>Turno</label>
                <select name="turno" required>
                    <option value="Manhã">Manhã</option>
                    <option value="Tarde">Tarde</option>
                    <option value="Noite">Noite</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn ghost" onclick="closeModal('modalAddTurma')">Cancelar</button>
                <button type="submit" class="btn primary">Criar Turma</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Turma -->
<div id="modalEditTurma" class="modal">
    <div class="card">
        <h3>Editar Turma</h3>
        <form method="POST" id="formEditTurma">
            <input type="hidden" name="action" value="edit_turma">
            <input type="hidden" name="turma_id" id="edit_turma_id">
            <div class="form-row">
                <label>Nome da Turma</label>
                <input type="text" name="nome" id="edit_turma_nome" required>
            </div>
            <div class="form-row">
                <label>Turno</label>
                <select name="turno" id="edit_turma_turno" required>
                    <option value="Manhã">Manhã</option>
                    <option value="Tarde">Tarde</option>
                    <option value="Noite">Noite</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn ghost" onclick="closeModal('modalEditTurma')">Cancelar</button>
                <button type="submit" class="btn primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Adicionar Disciplina -->
<div id="modalAddDisc" class="modal">
    <div class="card">
        <h3>Adicionar Disciplina</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_disciplina">
            <input type="hidden" name="turma_id" id="addDiscTurmaId">
            <div class="form-row">
                <label>Nome da Disciplina</label>
                <input type="text" name="nome_disciplina" required>
            </div>
            <div class="form-row">
                <label>Professores</label>
                <select name="professores[]" multiple style="height:120px;">
                    <?php foreach($professores_afiliados as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>Cor da Disciplina</label>
                <input type="color" name="cor" value="#<?= substr(md5(rand()), 0,6) ?>">
            </div>
            <div class="form-actions">
                <button type="button" class="btn ghost" onclick="closeModal('modalAddDisc')">Cancelar</button>
                <button type="submit" class="btn primary">Adicionar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Disciplina -->
<div id="modalEditDisc" class="modal">
    <div class="card">
        <h3>Editar Disciplina</h3>
        <form method="POST" id="formEditDisc">
            <input type="hidden" name="action" value="edit_disciplina">
            <input type="hidden" name="disciplina_id" id="edit_disc_id">
            <div class="form-row">
                <label>Nome da Disciplina</label>
                <input type="text" name="nome_disciplina" id="edit_disc_nome" required>
            </div>
            <div class="form-row">
                <label>Professores</label>
                <select name="professores[]" id="edit_disc_professores" multiple style="height:120px;">
                    <?php foreach($professores_afiliados as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>Cor da Disciplina</label>
                <input type="color" name="cor" id="edit_disc_cor" value="#f0f0f0">
            </div>
            <div class="form-actions">
                <button type="button" class="btn ghost" onclick="closeModal('modalEditDisc')">Cancelar</button>
                <button type="submit" class="btn primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleExpand(id) {
    const el = document.getElementById('expand-' + id);
    const card = document.querySelector('[data-turma="'+id+'"]');
    
    if (el.style.display === 'none') {
        el.style.display = 'block';
        card.classList.add('expanded'); // adiciona o background
    } else {
        el.style.display = 'none';
        card.classList.remove('expanded'); // remove o background
    }
}


function openModal(id){ document.getElementById(id).style.display='flex'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }

function openAddDisc(tid){ document.getElementById('addDiscTurmaId').value = tid; openModal('modalAddDisc'); }

function openMenu(e, turmaId) {
    e.stopPropagation();
    currentTurmaMenu = turmaId;
    const menu = document.getElementById('menuContext');

    // Deixa visível temporariamente para medir largura
    menu.style.display = 'block';
    menu.style.left = '0px';
    menu.style.top = '0px';

    const rect = e.target.getBoundingClientRect();
    const menuWidth = menu.offsetWidth;
    const screenWidth = window.innerWidth;

    let leftPos = rect.left;
    let topPos = rect.bottom;

    if (leftPos + menuWidth > screenWidth) {
        leftPos = screenWidth - menuWidth - 10; // recua 10px da borda
    }

    menu.style.left = leftPos + 'px';
    menu.style.top = topPos + 'px';
    menu.style.display = 'block';
}


document.addEventListener('click',()=>{ document.getElementById('menuContext').style.display='none'; });

function openEditTurma(){
    const tid = window.currentEditTurmaId;
    fetch('turmas_get.php?turma_id='+tid)
    .then(res=>res.json())
    .then(data=>{
        document.getElementById('edit_turma_id').value = data.id;
        document.getElementById('edit_turma_nome').value = data.nome;
        document.getElementById('edit_turma_turno').value = data.turno;
        openModal('modalEditTurma');
    });
}

function openEditDisc(did){
    fetch('disciplinas_get.php?disciplina_id='+did)
    .then(res=>res.json())
    .then(data=>{
        document.getElementById('edit_disc_id').value = data.id;
        document.getElementById('edit_disc_nome').value = data.nome;
        document.getElementById('edit_disc_cor').value = data.cor;
        const sel = document.getElementById('edit_disc_professores');
        Array.from(sel.options).forEach(o=>o.selected = data.professores.includes(parseInt(o.value)));
        openModal('modalEditDisc');
    });
}
</script>
</body>
</html>
