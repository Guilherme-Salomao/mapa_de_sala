<?php

require_once __DIR__ . '/../models/CursoModelo.php';
require_once __DIR__ . '/../core/AccessControl.php';

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
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        $cursos = $this->cursoModel->listar($busca, $status, $escopo);
        $totalCursos = $this->cursoModel->contar($busca, $status, $escopo);

        require_once __DIR__ . '/../views/dashboard/curso_modelos.php';
    }

    public function cadastrar(): void
    {
        $this->exigirLogin();

        $areas = $this->cursoModel->listarAreas((new AccessControl())->escopoAreaAtuacao());

        require_once __DIR__ . '/../views/dashboard/cadastrar_curso_modelo.php';
    }

    public function editar(): void
    {
        $this->exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($id <= 0) {
            $this->redirecionar('./?page=cursos&tipo=erro&msg=' . urlencode('Curso invalido.'));
        }

        $cursoForm = $this->cursoModel->buscarPorId($id);

        if (! $cursoForm || ! $this->cursoModel->cursoPertenceEscopo($id, $escopo)) {
            $this->redirecionar('./?page=cursos&tipo=erro&msg=' . urlencode('Curso nao encontrado.'));
        }

        $areas = $this->cursoModel->listarAreas($escopo);

        require_once __DIR__ . '/../views/dashboard/editar_curso_modelo.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $queryBase = $this->montarQueryCadastro($dados);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if (! $this->validarDados($dados)) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatorios.'));
        }

        if (! $this->cursoModel->areaExiste($dados['area_id'], $escopo)) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Area selecionada nao foi encontrada.'));
        }

        if ($this->cursoModel->nomeExiste($dados['nome'])) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Ja existe um curso com este nome.'));
        }

        if ($this->cursoModel->salvar($dados)) {
            $this->redirecionar('./?page=cursos&tipo=sucesso&msg=' . urlencode('Curso cadastrado com sucesso.'));
        }

        $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar o curso.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('./?page=cursos&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        if (! $this->cursoModel->areaExiste($dados['area_id'], $escopo)) {
            $this->redirecionar('./?page=cursos&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Area selecionada nao foi encontrada.'));
        }

        if (! $this->cursoModel->buscarPorId($dados['id']) || ! $this->cursoModel->cursoPertenceEscopo($dados['id'], $escopo)) {
            $this->redirecionar('./?page=cursos&tipo=erro&msg=' . urlencode('Curso nao encontrado.'));
        }

        if ($this->cursoModel->nomeExiste($dados['nome'], $dados['id'])) {
            $this->redirecionar('./?page=cursos&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Ja existe outro curso com este nome.'));
        }

        if ($this->cursoModel->atualizar($dados)) {
            $this->redirecionar('./?page=cursos&tipo=sucesso&msg=' . urlencode('Curso atualizado com sucesso.'));
        }

        $this->redirecionar('./?page=cursos&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar o curso.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();
        $this->bloquearProfessor();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('./?page=cursos&tipo=erro&msg=' . urlencode('Curso invalido.'));
        }

        if ($this->cursoModel->excluir($id)) {
            $this->redirecionar('./?page=cursos&tipo=sucesso&msg=' . urlencode('Curso excluido com sucesso.'));
        }

        $this->redirecionar('./?page=cursos&tipo=erro&msg=' . urlencode('Nao foi possivel excluir o curso. Verifique se existem UCs ou turmas vinculadas.'));
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            $this->redirecionar('./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        }
    }

    private function bloquearProfessor(): void
    {
        if ((new AccessControl())->nivel() === 'Professor') {
            $this->redirecionar('./?page=cursos&tipo=erro&msg=' . urlencode('Professor pode cadastrar curso, mas nao pode editar ou excluir cursos existentes.'));
        }
    }

    private function obterDadosPost(): array
    {
        return [
            'area_id'             => (int) ($_POST['area_id'] ?? 0),
            'nome'                => trim($_POST['nome'] ?? ''),
            'carga_horaria_total' => $this->normalizarHoras($_POST['carga_horaria_total'] ?? 0),
            'sem_uc'              => isset($_POST['sem_uc']) ? 1 : 0,
            'status'              => trim($_POST['status'] ?? 'Ativo'),
        ];
    }

    private function validarDados(array $dados): bool
    {
        return $dados['nome'] !== ''
            && $dados['area_id'] > 0
            && $dados['carga_horaria_total'] > 0
            && in_array($dados['status'], ['Ativo', 'Inativo'], true);
    }

    private function montarQueryCadastro(array $dados): string
    {
        return http_build_query([
            'page'                => 'cursos',
            'action'              => 'cadastrar',
            'area_id'             => $dados['area_id'] > 0 ? $dados['area_id'] : '',
            'nome'                => $dados['nome'],
            'carga_horaria_total' => $dados['carga_horaria_total'] > 0 ? $dados['carga_horaria_total'] : '',
            'sem_uc'              => $dados['sem_uc'],
            'status'              => $dados['status'],
        ]);
    }

    private function normalizarHoras($valor): float
    {
        $valor = strtolower(trim((string) $valor));

        if (preg_match('/^(\d{1,5})\s*h(?:\s*e)?\s*(\d{1,2})?\s*(?:min)?$/', $valor, $matches)) {
            $horas = (int) $matches[1];
            $minutos = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;

            return $minutos < 60 ? round($horas + ($minutos / 60), 2) : 0.0;
        }

        if (preg_match('/^(\d{1,5})[:,](\d{1,2})$/', $valor, $matches)) {
            $minutos = (int) $matches[2];

            return $minutos < 60 ? round(((int) $matches[1]) + ($minutos / 60), 2) : 0.0;
        }

        return is_numeric($valor) ? round((float) $valor, 2) : 0.0;
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
