<?php
// Teste 1: verificar se index.php existe
$arquivo = __DIR__ . '/../index.php';

if (file_exists($arquivo)) {
    echo "TESTE 1 OK - index.php encontrado\n";
    exit(0);
} else {
    echo "TESTE 1 FALHOU - index.php NÃO encontrado\n";
    exit(1);
}
