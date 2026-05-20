<?php

require_once __DIR__ . '/../models/CursoModelo.php';

class CursoModeloController
{
    private CursoModelo $cursoModel;

    public function __construct()
    {
        $this->cursoModel = new CursoModelo();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $busca = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');

        $cursos = $this->cursoModel->listar($busca, $status);
        $totalCursos = $this->cursoModel->contar($busca, $status);

        require_once __DIR__ . '/../views/dashboard/curso_modelos.php';
    }

    public function cadastrar(): void
    {
        $this->exigirLogin();

        require_once __DIR__ . '/../views/dashboard/cadastrar_curso_modelo.php';
    }

    public function editar(): void
    {
        $this->exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=erro&msg=' . urlencode('Curso invalido.'));
        }

        $cursoForm = $this->cursoModel->buscarPorId($id);

        if (! $cursoForm) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=erro&msg=' . urlencode('Curso nao encontrado.'));
        }

        require_once __DIR__ . '/../views/dashboard/editar_curso_modelo.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $queryBase = $this->montarQueryCadastro($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatorios.'));
        }

        if ($this->cursoModel->nomeExiste($dados['nome'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Ja existe um curso com este nome.'));
        }

        if ($this->cursoModel->salvar($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=sucesso&msg=' . urlencode('Curso cadastrado com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar o curso.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        if (! $this->cursoModel->buscarPorId($dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=erro&msg=' . urlencode('Curso nao encontrado.'));
        }

        if ($this->cursoModel->nomeExiste($dados['nome'], $dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Ja existe outro curso com este nome.'));
        }

        if ($this->cursoModel->atualizar($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=sucesso&msg=' . urlencode('Curso atualizado com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=cursos&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar o curso.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=erro&msg=' . urlencode('Curso invalido.'));
        }

        if ($this->cursoModel->excluir($id)) {
            $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=sucesso&msg=' . urlencode('Curso excluido com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=cursos&tipo=erro&msg=' . urlencode('Nao foi possivel excluir o curso. Verifique se existem UCs ou turmas vinculadas.'));
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            $this->redirecionar('/mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        }
    }

    private function obterDadosPost(): array
    {
        return [
            'nome'                => trim($_POST['nome'] ?? ''),
            'carga_horaria_total' => (int) ($_POST['carga_horaria_total'] ?? 0),
            'status'              => trim($_POST['status'] ?? 'Ativo'),
        ];
    }

    private function validarDados(array $dados): bool
    {
        return $dados['nome'] !== ''
            && $dados['carga_horaria_total'] > 0
            && in_array($dados['status'], ['Ativo', 'Inativo'], true);
    }

    private function montarQueryCadastro(array $dados): string
    {
        return http_build_query([
            'page'                => 'cursos',
            'action'              => 'cadastrar',
            'nome'                => $dados['nome'],
            'carga_horaria_total' => $dados['carga_horaria_total'] > 0 ? $dados['carga_horaria_total'] : '',
            'status'              => $dados['status'],
        ]);
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
