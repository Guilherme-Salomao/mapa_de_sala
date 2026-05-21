<?php

require_once __DIR__ . '/../app/controllers/LoginController.php';
require_once __DIR__ . '/../app/controllers/HomeController.php';
require_once __DIR__ . '/../app/controllers/UsuarioController.php';
require_once __DIR__ . '/../app/controllers/SalaController.php';
require_once __DIR__ . '/../app/controllers/DocenteController.php';
require_once __DIR__ . '/../app/controllers/CursoController.php';
require_once __DIR__ . '/../app/controllers/CursoModeloController.php';
require_once __DIR__ . '/../app/controllers/UnidadeCurricularController.php';
require_once __DIR__ . '/../app/controllers/QuadroHorarioController.php';
require_once __DIR__ . '/../app/controllers/RelatorioDocenteController.php';
require_once __DIR__ . '/../app/controllers/RelatorioTurmaController.php';

$page   = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? '';

$rotasPermitidas = ['login', 'cadastro', 'esqueci_senha', 'home', 'usuarios', 'salas', 'docentes', 'cursos', 'turmas', 'ucs', 'quadro_horario', 'relatorio_docente', 'relatorio_turma', 'logout'];

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

    if ($page === 'quadro_horario' && $action === 'salvar') {
        $controller = new QuadroHorarioController();
        $controller->salvar();
        exit;
    }

    if ($page === 'quadro_horario' && $action === 'atualizar') {
        $controller = new QuadroHorarioController();
        $controller->atualizar();
        exit;
    }

    if ($page === 'quadro_horario' && $action === 'excluir') {
        $controller = new QuadroHorarioController();
        $controller->excluir();
        exit;
    }

    $controller = new LoginController();
    if ($page === 'cadastro') {
        $controller->cadastrar();
        exit;
    }

    if ($page === 'esqueci_senha' && $action === 'solicitar') {
        $controller->solicitarRedefinicao();
        exit;
    }

    if ($page === 'esqueci_senha' && $action === 'redefinir') {
        $controller->redefinirSenha();
        exit;
    }

    $controller->autenticar();
    exit;
}

switch ($page) {
    case 'home':
        $controller = new HomeController();
        $controller->index();
        break;

    case 'usuarios':
        if ($action === 'cadastrar') {
            $controller = new UsuarioController();
            $controller->cadastrar();
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

    case 'quadro_horario':
        $controller = new QuadroHorarioController();
        $controller->index();
        break;

    case 'relatorio_docente':
        $controller = new RelatorioDocenteController();
        $controller->index();
        break;

    case 'relatorio_turma':
        $controller = new RelatorioTurmaController();
        $controller->index();
        break;

    case 'logout':
        $controller = new LoginController();
        $controller->logout();
        break;

    case 'cadastro':
        $controller = new LoginController();
        $controller->cadastro();
        break;

    case 'esqueci_senha':
        $controller = new LoginController();
        $controller->esqueciSenha();
        break;

    case 'login':
    default:
        require_once __DIR__ . '/../app/views/auth/login.php';
        break;
}
