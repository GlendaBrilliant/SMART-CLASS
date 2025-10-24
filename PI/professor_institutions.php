<?php
session_start();
include("config/db.php");

// validação de sessão professor
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: login.php");
    exit();
}
$id_professor = $_SESSION['usuario_id'];

// processar pedido de afiliação via código
$mensagem = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'solicitar_afiliacao') {
    $codigo = trim($_POST['codigo_instituicao'] ?? '');
    if ($codigo === '') {
        $mensagem = ["type"=>"erro", "text"=>"Código inválido."];
    } else {
        $stmt = $conn->prepare("SELECT id, nome FROM instituicoes WHERE codigo_instituicao = ? LIMIT 1");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $inst = $res->fetch_assoc();
            $inst_id = $inst['id'];
            $chk = $conn->prepare("SELECT id FROM solicitacoes WHERE tipo='professor' AND usuario_id = ? AND instituicao_id = ?");
            $chk->bind_param("ii", $id_professor, $inst_id);
            $chk->execute();
            $rchk = $chk->get_result();
            if ($rchk && $rchk->num_rows > 0) {
                $mensagem = ["type"=>"alert", "text"=>"Você já enviou uma solicitação para essa instituição."];
            } else {
                $ins = $conn->prepare("INSERT INTO solicitacoes (tipo, usuario_id, instituicao_id, status) VALUES ('professor', ?, ?, 'pendente')");
                $ins->bind_param("ii", $id_professor, $inst_id);
                if ($ins->execute()) {
                    $mensagem = ["type"=>"sucesso", "text"=>"Solicitação enviada para {$inst['nome']}"];
                } else {
                    $mensagem = ["type"=>"erro", "text"=>"Erro ao enviar solicitação."];
                }
                $ins->close();
            }
            $chk->close();
        } else {
            $mensagem = ["type"=>"erro", "text"=>"Código não encontrado."];
        }
        $stmt->close();
    }
}

// buscar instituições onde professor já tem AFILIAÇÕES (ativa/pendente/...)
$stmt = $conn->prepare("SELECT i.id, i.nome, i.codigo_instituicao, af.status 
                        FROM instituicoes i
                        JOIN afiliacoes af ON af.instituicao_id = i.id AND af.usuario_tipo='professor' AND af.usuario_id = ?
                        ORDER BY af.created_at DESC");
$stmt->bind_param("i", $id_professor);
$stmt->execute();
$res_af = $stmt->get_result();
$afiliacoes = [];
while ($r = $res_af->fetch_assoc()) $afiliacoes[] = $r;
$stmt->close();
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Minhas Instituições - Professor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
/* BASE */
body, html {
    margin:0;
    padding:0;
    font-family:Poppins, sans-serif;
    background:#f0f4f9;
    color:#222;
}
.container {
    max-width:1200px;
    margin:0 auto;
    padding:28px;
}
h2, h3 { margin:0 0 12px 0; }
h3 { color:#374151; }

/* HEADER */
.header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:24px;
}
.header h2 { font-size:24px; color:#1e293b; }
.header .subtitle { color:#6b7280; font-size:14px; }

/* MENSAGENS */
.msg {
    padding:14px 18px;
    border-radius:10px;
    margin-bottom:20px;
    font-weight:500;
    display:flex;
    align-items:center;
    gap:8px;
}
.msg.sucesso { background:#ecfdf5; color:#065f46; border:1px solid #10b981; }
.msg.erro { background:#fef2f2; color:#991b1b; border:1px solid #ef4444; }
.msg.alert { background:#fffbeb; color:#78350f; border:1px solid #f59e0b; }

/* FORMULÁRIO */
.form-inline {
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    align-items:center;
    margin-bottom:28px;
}
.input {
    flex:1;
    padding:12px 16px;
    border-radius:10px;
    border:1px solid #d1d5db;
    font-size:14px;
    transition:0.2s;
}
.input:focus {
    outline:none;
    border-color:#3b82f6;
    box-shadow:0 0 8px rgba(59,130,246,0.25);
}
.btn {
    padding:12px 18px;
    border-radius:10px;
    border:none;
    cursor:pointer;
    font-weight:500;
    display:flex;
    align-items:center;
    gap:6px;
    transition:0.2s;
}
.btn.primary {
    background:#3b82f6;
    color:#fff;
}
.btn.primary:hover { background:#2563eb; }

/* LISTA DE AFILIAÇÕES */
.card-list {
    display:flex;
    flex-wrap:wrap;
    gap:18px;
}
.card {
    background:#fff;
    border-radius:14px;
    padding:18px;
    width:300px;
    box-shadow:0 8px 20px rgba(0,0,0,0.08);
    transition:0.3s;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
}
.card:hover {
    transform:translateY(-4px);
    box-shadow:0 12px 28px rgba(0,0,0,0.12);
}
.card .top {
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.card strong {
    font-size:16px;
    color:#1f2937;
}
.card small {
    color:#6b7280;
    margin-top:2px;
    display:block;
}
.badge {
    padding:6px 10px;
    border-radius:12px;
    font-weight:600;
    font-size:12px;
    text-transform:capitalize;
    background:#e0f2fe;
    color:#0369a1;
}
.card .bottom {
    margin-top:12px;
    display:flex;
    justify-content:flex-end;
}

/* ICONE ABRIR */
.btn.open {
    background:#10b981;
    color:#fff;
}
.btn.open:hover { background:#059669; }

/* RESPONSIVO */
@media (max-width:900px){
    .card-list { flex-direction:column; }
    .form-inline { flex-direction:column; gap:10px; }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Minhas Instituições</h2>
        <div class="subtitle">Gerencie suas afiliações e solicitações</div>
    </div>

    <?php if($mensagem): ?>
        <div class="msg <?= htmlspecialchars($mensagem['type']) ?>">
            <?php if($mensagem['type']=='sucesso'): ?>
                <i class="bi bi-check-circle-fill"></i>
            <?php elseif($mensagem['type']=='erro'): ?>
                <i class="bi bi-x-circle-fill"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-triangle-fill"></i>
            <?php endif; ?>
            <?= htmlspecialchars($mensagem['text']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-inline">
        <input type="hidden" name="action" value="solicitar_afiliacao">
        <input name="codigo_instituicao" placeholder="Código da instituição (4 dígitos)" maxlength="4" class="input" required>
        <button class="btn primary" type="submit"><i class="bi bi-envelope-plus"></i> Solicitar Acesso</button>
    </form>

    <h3>Minhas Afiliações</h3>
    <div class="card-list">
        <?php if(empty($afiliacoes)): ?>
            <div class="card">
                <div class="top">
                    <strong>Nenhuma afiliação encontrada</strong>
                </div>
                <small>Solicite acesso a uma instituição usando o código acima.</small>
            </div>
        <?php else: ?>
            <?php foreach($afiliacoes as $a): ?>
                <div class="card">
                    <div class="top">
                        <div>
                            <strong><?= htmlspecialchars($a['nome']) ?></strong>
                            <small>Código: <?= htmlspecialchars($a['codigo_instituicao']) ?></small>
                        </div>
                        <div style="text-align:right">
                            <div class="badge"><?= htmlspecialchars($a['status']) ?></div>
                        </div>
                    </div>
                    <div class="bottom">
                        <a href="professor_dashboard.php?inst=<?= intval($a['id']) ?>" class="btn open"><i class="bi bi-box-arrow-in-right"></i> Abrir</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
