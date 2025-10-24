<?php
session_start();
include("../config/db.php");

// Apenas instituições podem acessar
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    header("Location: ../login.php");
    exit();
}

$id_instituicao = $_SESSION['usuario_id'];

// Buscar apenas alunos vinculados à instituição (afiliações ativas)
$sql_alunos = "SELECT a.*, af.status AS afiliacao_status, t.nome AS turma_nome, t.id AS turma_id
               FROM alunos a
               INNER JOIN afiliacoes af 
               ON af.usuario_id = a.id 
               AND af.usuario_tipo='aluno' 
               AND af.instituicao_id = ?
               AND af.status='ativa'
               LEFT JOIN turmas t ON t.id = a.turma_id
               ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql_alunos);
$stmt->bind_param("i", $id_instituicao);
$stmt->execute();
$result_alunos = $stmt->get_result();
$stmt->close();

// Buscar todas as turmas da instituição
$sql_turmas = "SELECT id, nome FROM turmas WHERE instituicao_id = ?";
$stmt = $conn->prepare($sql_turmas);
$stmt->bind_param("i", $id_instituicao);
$stmt->execute();
$result_turmas = $stmt->get_result();
$turmas = [];
while($row = $result_turmas->fetch_assoc()){
    $turmas[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Alunos - Dashboard</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<style>

.page-header .small-muted{
    font-size: 18px;
}
.main-content { padding-bottom:50px; }
.list-container { display:flex; flex-direction:column; gap:16px; }

/* Card moderno */
.item-card { 
    background: linear-gradient(145deg, #fefefe, #f2f2f2);
    padding:16px; 
    border-radius:16px; 
    box-shadow:0 4px 12px rgba(0,0,0,0.12);
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    gap:16px; 
    transition:0.3s;
    position:relative;
}
.item-card:hover {
    transform: translateY(-4px);
    box-shadow:0 8px 20px rgba(0,0,0,0.18);
}

/* Área do perfil e info */
.item-info { 
    display:flex; 
    align-items:center; 
    gap:16px;
}

.item-info img { 
    width:100px; 
    height:100px; 
    object-fit:cover; 
    border-radius:50%; 
    border:3px solid #375569; 
    transition:0.3s;
}
.item-info img:hover { border-color:#1e3b53; }

/* Detalhes do aluno */
.item-details { flex:1; }
.item-details strong { font-size:22px; display:block; color:#222; }
.item-meta { font-size:14px; color:#555; margin-top:4px; line-height:1.4; }

/* Botão 3 pontinhos */
.three-dots { 
    background:#fff; 
    border:1px solid #ccc; 
    border-radius:50%; 
    width:36px; 
    height:36px; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    font-size:20px; 
    cursor:pointer; 
    transition:0.2s;
}
.three-dots:hover { background:#f0f0f0; }

/* Menu de contexto */
#menuContext {
    position:fixed; 
    display:none; 
    z-index:60; 
    background:#fff; 
    box-shadow:0 8px 24px rgba(0,0,0,0.2); 
    border-radius:12px; 
    padding:10px;
}
#menuContext button { 
    display:block; 
    width:100%; 
    text-align:left; 
    padding:8px 12px; 
    border:none; 
    background:#fff; 
    border-radius:8px; 
    cursor:pointer; 
    font-size:14px; 
    transition:0.2s;
}
#menuContext button:hover { background:#f0f0f0; }

/* Modal */
#editModal { 
    display:none; 
    position:fixed; 
    top:50%; left:50%; 
    transform:translate(-50%, -50%);
    background:#fff; 
    padding:24px; 
    border-radius:16px; 
    box-shadow:0 12px 32px rgba(0,0,0,0.25); 
    z-index:100;
    width:320px;
}
#editModal h3 { margin-top:0; margin-bottom:16px; color:#375569; }
#editModal label { font-weight:500; color:#333; margin-bottom:4px; display:block; }
#editModal select { width:100%; padding:6px 8px; border-radius:8px; border:1px solid #ccc; margin-bottom:12px; }
#editModal button { padding:6px 14px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
#editModal button:first-child { background:#375569; color:#fff; margin-right:8px; }
#editModal button:last-child { background:#ccc; color:#000; }
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
                <li><a href="alunos.php" class="active"><i class="bi bi-backpack"></i>Alunos</a></li>
                <li><a href="turmas.php"><i class="bi bi-journal-bookmark"></i>Turmas e Matérias</a></li>
            </ul>
        </div>
    </div>

    <a href="../logout.php" class="logout">Sair</a>
</div>

    <main class="main-content">
        <div class="page-header">
            <h2 class="page-title">👩‍🎓 Alunos</h2>
            <p class="small-muted">Veja todos os alunos da instituição e gerencie suas informações.</p>
        </div>

        <div class="list-container">
            <?php while($aluno = $result_alunos->fetch_assoc()): ?>
            <?php
                // Caminho da foto
                $foto = (!empty($aluno['foto_perfil']) && file_exists("../" . $aluno['foto_perfil'])) ? "../" . $aluno['foto_perfil'] : "../uploads/default.png";
            ?>
            <div class="item-card" data-id="<?= $aluno['id'] ?>">
                <div class="item-info">
                    <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do aluno">
                    <div class="item-details">
                        <strong><?= htmlspecialchars($aluno['nome']) ?></strong>
                        <div class="item-meta">
                            Email: <?= htmlspecialchars($aluno['email']) ?> <br>
                            Telefone: <?= htmlspecialchars($aluno['telefone'] ?? '-') ?> <br>
                            Turma: <?= htmlspecialchars($aluno['turma_nome'] ?? '-') ?> <br>
                            Status: <?= htmlspecialchars($aluno['afiliacao_status'] ?? '-') ?>
                        </div>
                    </div>
                </div>
                <button class="three-dots" onclick="openMenu(event, <?= $aluno['id'] ?>)">⋯</button>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</div>

<!-- Menu contexto -->
<div id="menuContext" style="position:fixed; display:none; z-index:60; background:#fff; box-shadow:0 6px 20px rgba(0,0,0,0.15); border-radius:8px; padding:8px;">
    <button class="btn ghost" onclick="openEditModal()">Editar</button>
    <button class="btn ghost" onclick="removerAluno()">Remover</button>
</div>

<!-- Modal de edição -->
<div id="editModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
    background:#fff; padding:20px; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.25); z-index:100;">
    <h3>Editar Aluno</h3>
    <form id="editForm">
        <input type="hidden" name="id" id="edit_id">
        <label>Turma:</label><br>
        <select name="turma_id" id="edit_turma_id">
            <?php foreach($turmas as $turma): ?>
                <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <button type="button" onclick="salvarEdicao()">Salvar</button>
        <button type="button" onclick="closeEditModal()">Cancelar</button>
    </form>
</div>

<script>
let currentId = null;
let currentTurma = null;

function openMenu(e, id){
    e.stopPropagation();
    currentId = id;
    const menu = document.getElementById('menuContext');
    menu.style.display = 'block';
    
    const menuWidth = menu.offsetWidth;
    const menuHeight = menu.offsetHeight;
    const pageWidth = window.innerWidth;
    const pageHeight = window.innerHeight;

    let left = e.pageX;
    let top = e.pageY + 6;

    // se passar da direita, ajusta
    if(left + menuWidth > pageWidth) {
        left = pageWidth - menuWidth - 10; // 10px de margem
    }

    // se passar da parte inferior, ajusta
    if(top + menuHeight > pageHeight) {
        top = pageHeight - menuHeight - 10;
    }

    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
}

document.addEventListener('click', ()=> document.getElementById('menuContext').style.display='none');

function openEditModal(){
    const card = document.querySelector(`.item-card[data-id='${currentId}']`);
    const turmaNome = card.querySelector('.item-meta').innerText.split("\n")[2].replace("Turma: ","").trim();
    document.getElementById('edit_id').value = currentId;

    const select = document.getElementById('edit_turma_id');
    for(let i=0;i<select.options.length;i++){
        if(select.options[i].text === turmaNome){
            select.selectedIndex = i;
            break;
        }
    }

    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal(){
    document.getElementById('editModal').style.display = 'none';
}

function salvarEdicao(){
    const formData = new FormData(document.getElementById('editForm'));
    fetch('editar_aluno.php', { method:'POST', body: formData })
    .then(res=>res.json())
    .then(data=>{
        if(data.status === 'success'){
            alert('Aluno atualizado!');
            location.reload();
        } else {
            alert('Erro: '+data.message);
        }
    });
}

function removerAluno(){
    if(confirm("Deseja realmente remover este aluno?")){
        fetch('remover_aluno.php', { method:'POST', body: new URLSearchParams({id:currentId}) })
        .then(res=>res.json())
        .then(data=>{
            if(data.status === 'success'){
                alert('Aluno removido!');
                location.reload();
            } else {
                alert('Erro: '+data.message);
            }
        });
    }
}
</script>

</body>
</html>
