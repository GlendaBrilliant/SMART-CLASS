<?php
session_start();
include("../config/db.php");

// Apenas instituições podem acessar
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'instituicao') {
    header("Location: ../login.php");
    exit();
}

$id_instituicao = $_SESSION['usuario_id'];

// Buscar todos os professores vinculados à instituição
$sql_professores = "SELECT p.*, a.status AS afiliacao_status,
                    GROUP_CONCAT(d.nome SEPARATOR ', ') AS disciplinas
                    FROM professores p
                    LEFT JOIN professores_disciplinas pd ON pd.professor_id = p.id AND pd.instituicao_id = ?
                    LEFT JOIN disciplinas d ON d.id = pd.disciplina_id
                    LEFT JOIN afiliacoes a ON a.usuario_id = p.id AND a.usuario_tipo='professor' AND a.instituicao_id = ?
                    GROUP BY p.id
                    ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql_professores);
$stmt->bind_param("ii", $id_instituicao, $id_instituicao);
$stmt->execute();
$result_professores = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Professores - Dashboard</title>
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
                <li><a href="professores.php" class="active"><i class="bi bi-person-badge"></i>Professores</a></li>
                <li><a href="alunos.php"><i class="bi bi-backpack"></i>Alunos</a></li>
                <li><a href="turmas.php"><i class="bi bi-journal-bookmark"></i>Turmas e Matérias</a></li>
            </ul>
        </div>
    </div>

    <a href="../logout.php" class="logout">Sair</a>
</div>

    <main class="main-content">
        <div class="page-header">
            <h2 class="page-title">👨‍🏫 Professores</h2>
            <p class="small-muted">Veja todos os professores da instituição e gerencie suas informações.</p>
        </div>

        <div class="list-container">
            <?php while($prof = $result_professores->fetch_assoc()): ?>
            <?php
                $foto = $prof['foto_perfil'];
                if (!empty($foto) && file_exists("../" . $foto)) {
                    $foto_path = "../" . $foto;
                } else {
                    $foto_path = "../uploads/default.png";
                }
            ?>
            <div class="item-card" data-id="<?= $prof['id'] ?>">
                <div class="item-info">
                    <img src="<?= $foto_path ?>" alt="Foto do professor">
                    <div class="item-details">
                        <strong><?= htmlspecialchars($prof['nome']) ?></strong>
                        <div class="item-meta">
                            Email: <?= htmlspecialchars($prof['email']) ?> <br>
                            Telefone: <?= htmlspecialchars($prof['telefone'] ?? '-') ?> <br>
                            Disciplinas: <?= htmlspecialchars($prof['disciplinas'] ?? '-') ?> <br>
                            Status: <?= htmlspecialchars($prof['afiliacao_status'] ?? '-') ?>
                        </div>
                    </div>
                </div>
                <button class="three-dots" onclick="openMenu(event, <?= $prof['id'] ?>)">⋯</button>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</div>

<!-- Menu contexto -->
<div id="menuContext" style="position:fixed; display:none; z-index:60; background:#fff; box-shadow:0 6px 20px rgba(0,0,0,0.15); border-radius:8px; padding:8px;">
    <button class="btn ghost" onclick="openEditModal()">Editar</button>
    <button class="btn ghost" onclick="removerProfessor()">Remover</button>
</div>

<!-- Modal de edição -->
<div id="editModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
    background:#fff; padding:20px; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.25); z-index:100;">
    <h3>Editar Professor</h3>
    <form id="editForm">
        <input type="hidden" name="id" id="edit_id">
        <label>Disciplinas (separadas por vírgula):</label><br>
        <input type="text" name="disciplinas" id="edit_disciplinas"><br><br>
        <button type="button" onclick="salvarEdicao()">Salvar</button>
        <button type="button" onclick="closeEditModal()">Cancelar</button>
    </form>
</div>

<script>
let currentId = null;

// Função para abrir menu contexto
function openMenu(e, id){
    e.stopPropagation();
    currentId = id;
    const menu = document.getElementById('menuContext');
    menu.style.display = 'block';
    menu.style.left = (e.pageX - 10) + 'px';
    menu.style.top = (e.pageY + 6) + 'px';
}
document.addEventListener('click', ()=> document.getElementById('menuContext').style.display='none');

// Abrir modal de edição preenchido
function openEditModal(){
    const card = document.querySelector(`.item-card[data-id='${currentId}']`);
    document.getElementById('edit_id').value = currentId;
    const meta = card.querySelector('.item-meta').innerText.split("\n");
    document.getElementById('edit_disciplinas').value = meta[2].replace("Disciplinas: ","").trim();
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal(){
    document.getElementById('editModal').style.display = 'none';
}

// Salvar edição via AJAX
function salvarEdicao(){
    const formData = new FormData(document.getElementById('editForm'));
    fetch('editar_professor.php', {
        method:'POST',
        body: formData
    }).then(res=>res.json())
    .then(data=>{
        if(data.status === 'success'){
            alert('Professor atualizado!');
            location.reload(); // atualiza a lista
        } else {
            alert('Erro: '+data.message);
        }
    });
}

// Remover professor (apenas afiliação)
function removerProfessor(){
    if(confirm("Deseja realmente remover este professor da instituição?")){
        fetch('remover_professor.php', {
            method:'POST',
            body: new URLSearchParams({id:currentId})
        }).then(res=>res.json())
        .then(data=>{
            if(data.status === 'success'){
                alert('Professor removido da instituição!');
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
