<?php
session_start();
include("config/db.php");

// validação de sessão professor
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: login.php");
    exit();
}
$id_professor = $_SESSION['usuario_id'];

// buscar informações do professor
$stmt = $conn->prepare("SELECT nome, email, telefone, foto_perfil, senha, created_at FROM professores WHERE id=?");
$stmt->bind_param("i", $id_professor);
$stmt->execute();
$res = $stmt->get_result();
$professor = $res->fetch_assoc();
$stmt->close();

// buscar estatísticas rápidas
$stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM aulas WHERE professor_id=?) as total_aulas,
        (SELECT COUNT(DISTINCT turma_id) FROM aulas WHERE professor_id=?) as total_turmas
");
$stmt->bind_param("ii", $id_professor, $id_professor);
$stmt->execute();
$res = $stmt->get_result();
$stats = $res->fetch_assoc();
$stmt->close();

$mensagem = null;

// processar atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'atualizar_perfil') {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $telefone = trim($_POST['telefone']);
        $senha_atual = trim($_POST['senha_atual'] ?? '');
        $nova_senha = trim($_POST['nova_senha'] ?? '');
        
        // tratar upload de foto
        $foto_perfil = $professor['foto_perfil']; // mantém a anterior por padrão
        if(isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK){
            $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $novo_nome = 'prof_'.$id_professor.'_'.time().'.'.$ext;
            $destino = 'uploads/'.$novo_nome;
            if(move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino)){
                $foto_perfil = $destino;
            } else {
                $mensagem = ["type"=>"erro","text"=>"Erro ao enviar a foto."];
            }
        }

        // alterar senha, se informada
        if($nova_senha !== ''){
            if(!password_verify($senha_atual, $professor['senha'])){
                $mensagem = ["type"=>"erro","text"=>"Senha atual incorreta"];
            } else {
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE professores SET nome=?, email=?, telefone=?, foto_perfil=?, senha=? WHERE id=?");
                $stmt->bind_param("sssssi", $nome, $email, $telefone, $foto_perfil, $senha_hash, $id_professor);
                $stmt->execute();
                $stmt->close();
                $mensagem = ["type"=>"sucesso","text"=>"Perfil atualizado com sucesso"];
            }
        } else {
            $stmt = $conn->prepare("UPDATE professores SET nome=?, email=?, telefone=?, foto_perfil=? WHERE id=?");
            $stmt->bind_param("ssssi", $nome, $email, $telefone, $foto_perfil, $id_professor);
            $stmt->execute();
            $stmt->close();
            $mensagem = ["type"=>"sucesso","text"=>"Perfil atualizado com sucesso"];
        }

        $professor['nome'] = $nome;
        $professor['email'] = $email;
        $professor['telefone'] = $telefone;
        $professor['foto_perfil'] = $foto_perfil;
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Perfil do Professor</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
body, html {margin:0; padding:0; font-family:Poppins,sans-serif; background: linear-gradient(135deg,#f0f4f9,#dbeafe); color:#1e293b;}
.containerr { max-width:1000px; margin:0 auto; padding:30px; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
.header h2 { margin:0; font-size:26px; font-weight:600; }
.msg { padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-weight:500; }
.msg.sucesso { background:#ecfdf5; color:#065f46; border:1px solid #10b981; }
.msg.erro { background:#fef2f2; color:#991b1b; border:1px solid #ef4444; }
.profile-card { background:#fff; border-radius:20px; padding:30px; box-shadow:0 15px 35px rgba(0,0,0,0.12); display:flex; gap:30px; align-items:center; transition:0.3s; }
.profile-card:hover { transform:translateY(-5px); box-shadow:0 20px 50px rgba(0,0,0,0.15); }
.profile-photo { width:140px; height:140px; border-radius:50%; object-fit:cover; border:4px solid #3b82f6; }
.profile-info { flex:1; }
.profile-info h3 { margin:0; font-size:24px; font-weight:600; color:#1e293b; }
.profile-info p { margin:6px 0; color:#475569; font-size:15px; }
.stats { display:flex; gap:20px; margin-top:12px; }
.stats div { background:#f1f5f9; padding:12px 20px; border-radius:12px; font-weight:600; color:#1e293b; text-align:center; flex:1; }
.stats div span { display:block; font-size:20px; font-weight:700; color:#3b82f6; }
.btn { padding:12px 18px; border-radius:12px; border:none; cursor:pointer; font-weight:500; display:flex; align-items:center; gap:8px; transition:0.2s; }
.btn.primary { background:#3b82f6; color:#fff; }
.btn.primary:hover { background:#2563eb; }
.modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.45); z-index:9999; }
.modal .card { width:550px; max-width:95%; background:#fff; padding:30px; border-radius:18px; box-shadow:0 15px 40px rgba(0,0,0,0.2); position:relative; }
.modal h3 { margin-top:0; font-size:22px; }
.modal .form-row { margin-bottom:15px; display:flex; flex-direction:column; }
.modal label { font-weight:600; margin-bottom:5px; color:#1e293b; }
.modal input[type="text"], .modal input[type="email"], .modal input[type="password"] { padding:12px 14px; border-radius:10px; border:1px solid #d1d5db; transition:0.2s; }
.modal input[type="file"] { margin-top:5px; }
.modal input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 8px rgba(59,130,246,0.25); }
.modal .modal-close { position:absolute; top:15px; right:15px; cursor:pointer; font-size:20px; color:#475569; }
.modal .btn.submit { margin-top:15px; width:100%; justify-content:center; }
.preview-img { width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid #3b82f6; margin-top:10px; }

.details { background:#fff; border-radius:20px; padding:25px; box-shadow:0 10px 30px rgba(0,0,0,0.08); margin-top:25px; }
.details h4 { margin-top:0; font-size:20px; font-weight:600; }
@media(max-width:700px){ .profile-card { flex-direction:column; text-align:center; } .profile-info { width:100%; } .stats { flex-direction:column; gap:12px; } .preview-img { margin:10px auto; } }
</style>
</head>
<body>
<div class="sidebar">
    <div class="top-section">
        <div class="logo"><?= $_SESSION['nome'] ?></div>
        <div class="menu">
            <ul>
                <li><a href="professor_dashboard.php"><i class="bi bi-calendar2-plus"></i>Agendas</a></li>
                <li><a href="professor_presencas.php"><i class="bi bi-check-circle"></i>Presenças</a></li>
                <li><a href="professor_perfil.php" class="active"><i class="bi bi-person-circle"></i>Perfil</a></li>
            </ul>
        </div>
    </div>
    <a href="logout.php" class="logout">Sair</a>
</div>

<div class="containerr">
    <div class="header">
        <h2>Meu Perfil</h2>
        <button class="btn primary" onclick="openModal('modalPerfil')"><i class="bi bi-gear-fill"></i> Configurações</button>
    </div>

    <?php if($mensagem): ?>
        <div class="msg <?= htmlspecialchars($mensagem['type']) ?>"><?= htmlspecialchars($mensagem['text']) ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <img src="<?= htmlspecialchars($professor['foto_perfil'] ?: 'https://via.placeholder.com/140') ?>" class="profile-photo" alt="Foto do Professor">
        <div class="profile-info">
            <h3><?= htmlspecialchars($professor['nome']) ?></h3>
            <p><i class="bi bi-envelope"></i> <?= htmlspecialchars($professor['email']) ?></p>
            <p><i class="bi bi-telephone"></i> <?= htmlspecialchars($professor['telefone'] ?: '-') ?></p>
            <div class="stats">
                <div><span><?= intval($stats['total_aulas']) ?></span>Aulas</div>
                <div><span><?= intval($stats['total_turmas']) ?></span>Turmas</div>
            </div>
        </div>
    </div>

    <div class="details">
        <p><strong>Observação:</strong> Você pode atualizar seus dados e foto de perfil nas configurações acima.</p>
    </div>
</div>

<!-- MODAL CONFIGURAÇÕES -->
<div class="modal" id="modalPerfil">
    <div class="card">
        <span class="modal-close" onclick="closeModal('modalPerfil')">&times;</span>
        <h3>Editar Perfil</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="atualizar_perfil">
            
            <div class="form-row">
                <label>Nome</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($professor['nome']) ?>" required>
            </div>
            <div class="form-row">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($professor['email']) ?>" required>
            </div>
            <div class="form-row">
                <label>Telefone</label>
                <input type="text" name="telefone" value="<?= htmlspecialchars($professor['telefone']) ?>">
            </div>
            <div class="form-row">
                <label>Foto de Perfil</label>
                <input type="file" name="foto_perfil" accept="image/*" onchange="previewImage(event)">
                <img id="preview" class="preview-img" src="<?= htmlspecialchars($professor['foto_perfil'] ?: 'https://via.placeholder.com/120') ?>" alt="Preview Foto">
            </div>
            <hr style="margin:12px 0;">
            <div class="form-row">
                <label>Senha Atual (necessário para alterar senha)</label>
                <input type="password" name="senha_atual" placeholder="Senha atual">
            </div>
            <div class="form-row">
                <label>Nova Senha</label>
                <input type="password" name="nova_senha" placeholder="Nova senha">
            </div>
            <button type="submit" class="btn primary submit"><i class="bi bi-save-fill"></i> Salvar Alterações</button>
        </form>
    </div>
</div>

<script>
function openModal(id){ document.getElementById(id).style.display='flex'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }
window.onclick = function(event){ if(event.target.classList.contains('modal')) event.target.style.display='none'; }

function previewImage(event){
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('preview');
        output.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>
