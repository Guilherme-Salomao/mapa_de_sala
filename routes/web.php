<?php

require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/UsuarioController.php';

$page   = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? '';

$rotasPermitidas = ['login', 'home', 'usuarios', 'logout'];

if (! in_array($page, $rotasPermitidas, true)) {
    $page = 'login';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page === 'usuarios' && $action === 'salvar') {
        $controller = new UsuarioController();
        $controller->salvar();
        exit;
    }

    if ($page === 'usuarios' && $action === 'atualizar') {
        $controller = new UsuarioController();
        $controller->atualizar();
        exit;
    }

    if ($page === 'usuarios' && $action === 'excluir') {
        $controller = new UsuarioController();
        $controller->excluir();
        exit;
    }

    $controller = new LoginController();
    $controller->autenticar();
    exit;
}

switch ($page) {
    case 'home':
        require_once __DIR__ . '/../app/views/dashboard/home.php';
        break;

    case 'usuarios':
        if ($action === 'cadastrar') {
            require_once __DIR__ . '/../app/views/dashboard/cadastrar_usuario.php';
            break;
        }

        if ($action === 'editar') {
            $controller = new UsuarioController();
            $controller->editar();
            break;
        }

        $controller = new UsuarioController();
        $controller->index();
        break;

    case 'logout':
        $controller = new LoginController();
        $controller->logout();
        break;

    case 'login':
    default:
        require_once __DIR__ . '/../app/views/auth/login.php';
        break;
}