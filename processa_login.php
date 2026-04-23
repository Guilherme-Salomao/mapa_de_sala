<?php
require_once __DIR__ . '/assets/config/conexao.php';

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if ($conexao) {
    header('Location: index.php?tipo=sucesso&msg=' . urlencode('Conectado ao banco com sucesso!'));
    exit;
} else {
    header('Location: index.php?tipo=erro&msg=' . urlencode('Falha ao conectar com o banco.'));
    exit;
}