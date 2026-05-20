<?php

require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/UsuarioController.php';
require_once __DIR__ . '/../app/controllers/SalaController.php';
require_once __DIR__ . '/../app/controllers/DocenteController.php';
require_once __DIR__ . '/../app/controllers/CursoController.php';
require_once __DIR__ . '/../app/controllers/CursoModeloController.php';
require_once __DIR__ . '/../app/controllers/UnidadeCurricularController.php';

$page   = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? '';

$rotasPermitidas = ['login', 'home', 'usuarios', 'salas', 'docentes', 'cursos', 'turmas', 'ucs', 'logout'];

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

    if ($page === 'salas' && $action === 'salvar') {
        $controller = new SalaController();
        $controller->salvar();
        exit;
    }

    if ($page === 'salas' && $action === 'atualizar') {
        $controller = new SalaController();
        $controller->atualizar();
        exit;
    }

    if ($page === 'salas' && $action === 'excluir') {
        $controller = new SalaController();
        $controller->excluir();
        exit;
    }

    if ($page === 'docentes' && $action === 'salvar') {
        $controller = new DocenteController();
        $controller->salvar();
        exit;
    }

    if ($page === 'docentes' && $action === 'atualizar') {
        $controller = new DocenteController();
        $controller->atualizar();
        exit;
    }

    if ($page === 'docentes' && $action === 'excluir') {
        $controller = new DocenteController();
        $controller->excluir();
        exit;
    }

    if ($page === 'turmas' && $action === 'salvar') {
        $controller = new CursoController();
        $controller->salvar();
        exit;
    }

    if ($page === 'turmas' && $action === 'atualizar') {
        $controller = new CursoController();
        $controller->atualizar();
        exit;
    }

    if ($page === 'turmas' && $action === 'excluir') {
        $controller = new CursoController();
        $controller->excluir();
        exit;
    }

    if ($page === 'cursos' && $action === 'salvar') {
        $controller = new CursoModeloController();
        $controller->salvar();
        exit;
    }

    if ($page === 'cursos' && $action === 'atualizar') {
        $controller = new CursoModeloController();
        $controller->atualizar();
        exit;
    }

    if ($page === 'cursos' && $action === 'excluir') {
        $controller = new CursoModeloController();
        $controller->excluir();
        exit;
    }

    if ($page === 'ucs' && $action === 'salvar') {
        $controller = new UnidadeCurricularController();
        $controller->salvar();
        exit;
    }

    if ($page === 'ucs' && $action === 'atualizar') {
        $controller = new UnidadeCurricularController();
        $controller->atualizar();
        exit;
    }

    if ($page === 'ucs' && $action === 'excluir') {
        $controller = new UnidadeCurricularController();
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

    case 'salas':
        if ($action === 'cadastrar') {
            require_once __DIR__ . '/../app/views/dashboard/cadastrar_sala.php';
            break;
        }

        if ($action === 'editar') {
            $controller = new SalaController();
            $controller->editar();
            break;
        }

        $controller = new SalaController();
        $controller->index();
        break;

    case 'docentes':
        if ($action === 'cadastrar') {
            $controller = new DocenteController();
            $controller->cadastrar();
            break;
        }

        if ($action === 'editar') {
            $controller = new DocenteController();
            $controller->editar();
            break;
        }

        $controller = new DocenteController();
        $controller->index();
        break;

    case 'cursos':
        if ($action === 'cadastrar') {
            $controller = new CursoModeloController();
            $controller->cadastrar();
            break;
        }

        if ($action === 'editar') {
            $controller = new CursoModeloController();
            $controller->editar();
            break;
        }

        $controller = new CursoModeloController();
        $controller->index();
        break;

    case 'turmas':
        if ($action === 'cadastrar') {
            $controller = new CursoController();
            $controller->cadastrar();
            break;
        }

        if ($action === 'editar') {
            $controller = new CursoController();
            $controller->editar();
            break;
        }

        $controller = new CursoController();
        $controller->index();
        break;

    case 'ucs':
        if ($action === 'cadastrar') {
            $controller = new UnidadeCurricularController();
            $controller->cadastrar();
            break;
        }

        if ($action === 'editar') {
            $controller = new UnidadeCurricularController();
            $controller->editar();
            break;
        }

        $controller = new UnidadeCurricularController();
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
