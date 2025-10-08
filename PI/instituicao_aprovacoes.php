<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'instituicao') {
    header("Location: login.php");
    exit;
}

$instituicao_id = $_SESSION['user_id'];

if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $acao = $_GET['acao'];

    $stmt = $pdo->prepare("SELECT * FROM solicitacoes WHERE id = ? AND instituicao_id = ?");
    $stmt->execute([$id, $instituicao_id]);
    $sol = $stmt->fetch();

    if ($sol) {
        if ($acao === 'aceitar') {
            $pdo->prepare("UPDATE solicitacoes SET status = 'aceito' WHERE id = ?")->execute([$id]);

            if ($sol['tipo'] === 'aluno') {
                $pdo->prepare("UPDATE afiliacoes SET status = 'cancelada', data_fim = CURDATE() WHERE usuario_tipo = 'aluno' AND usuario_id = ? AND status = 'ativa'")
                    ->execute([$sol['usuario_id']]);

                $pdo->prepare("UPDATE alunos SET instituicao_id = ?, turma_id = NULL WHERE id = ?")
                    ->execute([$instituicao_id, $sol['usuario_id']]);
            }

            $pdo->prepare("INSERT INTO afiliacoes (instituicao_id, usuario_tipo, usuario_id, status, data_inicio) VALUES (?, ?, ?, 'ativa', CURDATE())")
                ->execute([$instituicao_id, $sol['tipo'], $sol['usuario_id']]);
        } elseif ($acao === 'recusar') {
            $pdo->prepare("UPDATE solicitacoes SET status = 'recusado' WHERE id = ?")->execute([$id]);
        }
    }

    header("Location: instituicao_aprovacoes.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM solicitacoes WHERE instituicao_id = ? AND status = 'pendente'");
$stmt->execute([$instituicao_id]);
$solicitacoes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Aprovações de Acesso</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h2>Solicitações Pendentes</h2>
  <?php if (!$solicitacoes): ?>
    <p>Não há solicitações pendentes.</p>
  <?php else: ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Usuário</th>
        <th>Tipo</th>
        <th>Ações</th>
      </tr>
      <?php foreach ($solicitacoes as $s): ?>
        <tr>
          <td><?= $s['id'] ?></td>
          <td><?= $s['usuario_id'] ?></td>
          <td><?= ucfirst($s['tipo']) ?></td>
          <td>
            <a href="?acao=aceitar&id=<?= $s['id'] ?>">Aceitar</a> |
            <a href="?acao=recusar&id=<?= $s['id'] ?>">Recusar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
</body>
</html>