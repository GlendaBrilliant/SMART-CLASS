<?php
session_start();

if (!isset($_SESSION['tipo']) || !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$tipo = $_SESSION['tipo'];

if ($tipo === 'instituicao') {
    header("Location: instituicao_home.php");
    exit();
} elseif ($tipo === 'professor') {
    header("Location: professor_home.php");
    exit();
} elseif ($tipo === 'aluno') {
    header("Location: aluno_home.php");
    exit();
} else {
    echo "Tipo de usuário inválido!";
}
