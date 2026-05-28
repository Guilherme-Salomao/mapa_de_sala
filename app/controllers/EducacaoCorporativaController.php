<?php

require_once __DIR__ . '/../models/EducacaoCorporativa.php';
require_once __DIR__ . '/../models/QuadroHorario.php';

class EducacaoCorporativaController
{
    private EducacaoCorporativa $educacaoModel;
    private QuadroHorario $quadroModel;

    public function __construct()
    {
        $this->educacaoModel = new EducacaoCorporativa();
        $this->quadroModel = new QuadroHorario();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $busca = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');
        $registroForm = null;

        if (($_GET['action'] ?? '') === 'editar') {
            $id = (int) ($_GET['id'] ?? 0);
            $registroForm = $id > 0 ? $this->educacaoModel->buscarPorId($id) : null;

            if (! $registroForm) {
                $this->redirecionar('./?page=educacao_corporativa&tipo=erro&msg=' . urlencode('Registro nao encontrado.'));
            }
        }

        $docentes = $this->educacaoModel->listarDocentes();
        $registros = $this->educacaoModel->listar($busca, $status);
        $totalRegistros = count($registros);

        require_once __DIR__ . '/../views/dashboard/educacao_corporativa.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $queryBase = $this->queryCadastro($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha os campos obrigatorios.'));
        }

        $erro = $this->validarDisponibilidade($dados);

        if ($erro !== null) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode($erro));
        }

        if ($this->educacaoModel->salvar($dados)) {
            $this->redirecionar('./?page=educacao_corporativa&tipo=sucesso&msg=' . urlencode('Curso cadastrado com sucesso.'));
        }

        $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar o curso.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('./?page=educacao_corporativa&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        $erro = $this->validarDisponibilidade($dados, $dados['id']);

        if ($erro !== null) {
            $this->redirecionar('./?page=educacao_corporativa&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode($erro));
        }

        if ($this->educacaoModel->atualizar($dados)) {
            $this->redirecionar('./?page=educacao_corporativa&tipo=sucesso&msg=' . urlencode('Curso atualizado com sucesso.'));
        }

        $this->redirecionar('./?page=educacao_corporativa&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar o curso.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('./?page=educacao_corporativa&tipo=erro&msg=' . urlencode('Registro invalido.'));
        }

        if ($this->educacaoModel->excluir($id)) {
            $this->redirecionar('./?page=educacao_corporativa&tipo=sucesso&msg=' . urlencode('Curso excluido com sucesso.'));
        }

        $this->redirecionar('./?page=educacao_corporativa&tipo=erro&msg=' . urlencode('Nao foi possivel excluir o curso.'));
    }

    private function obterDadosPost(): array
    {
        return [
            'docente_id' => (int) ($_POST['docente_id'] ?? 0),
            'data' => trim($_POST['data'] ?? ''),
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'status' => trim($_POST['status'] ?? 'Ativo'),
        ];
    }

    private function validarDados(array $dados): bool
    {
        return $dados['docente_id'] > 0
            && $dados['data'] !== ''
            && strtotime($dados['data']) !== false
            && $dados['titulo'] !== ''
            && in_array($dados['status'], ['Ativo', 'Inativo'], true);
    }

    private function validarDisponibilidade(array $dados, ?int $ignorarId = null): ?string
    {
        if ($dados['status'] !== 'Ativo') {
            return null;
        }

        $curso = $this->educacaoModel->docenteEmCurso((int) $dados['docente_id'], (string) $dados['data'], $ignorarId);

        if ($curso) {
            return 'Este docente ja possui curso de Educacao Corporativa nesta data.';
        }

        $conflito = $this->quadroModel->encontrarConflitoDocente(
            (int) $dados['docente_id'],
            (string) $dados['data'],
            '00:00:00',
            '23:59:59'
        );

        if ($conflito) {
            return 'Este docente ja possui aula lancada nesta data.';
        }

        return null;
    }

    private function queryCadastro(array $dados): string
    {
        return http_build_query([
            'page' => 'educacao_corporativa',
            'docente_id' => $dados['docente_id'] > 0 ? $dados['docente_id'] : '',
            'data' => $dados['data'],
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'],
            'status_registro' => $dados['status'],
        ]);
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

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
