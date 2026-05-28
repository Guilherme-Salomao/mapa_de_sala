<?php

require_once __DIR__ . '/../models/CalendarioBloqueio.php';

class CalendarioBloqueioController
{
    private CalendarioBloqueio $bloqueioModel;

    public function __construct()
    {
        $this->bloqueioModel = new CalendarioBloqueio();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $busca = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');
        $bloqueioForm = null;

        if (($_GET['action'] ?? '') === 'editar') {
            $id = (int) ($_GET['id'] ?? 0);
            $bloqueioForm = $id > 0 ? $this->bloqueioModel->buscarPorId($id) : null;

            if (! $bloqueioForm) {
                $this->redirecionar('./?page=calendario&tipo=erro&msg=' . urlencode('Registro nao encontrado.'));
            }
        }

        $bloqueios = $this->bloqueioModel->listar($busca, $status);
        $totalBloqueios = count($bloqueios);

        require_once __DIR__ . '/../views/dashboard/calendario_bloqueios.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $queryBase = $this->queryCadastro($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha os campos obrigatorios.'));
        }

        if ($this->bloqueioModel->salvar($dados)) {
            $this->redirecionar('./?page=calendario&tipo=sucesso&msg=' . urlencode('Data cadastrada com sucesso.'));
        }

        $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar a data.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('./?page=calendario&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        if ($this->bloqueioModel->atualizar($dados)) {
            $this->redirecionar('./?page=calendario&tipo=sucesso&msg=' . urlencode('Data atualizada com sucesso.'));
        }

        $this->redirecionar('./?page=calendario&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar a data.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('./?page=calendario&tipo=erro&msg=' . urlencode('Registro invalido.'));
        }

        if ($this->bloqueioModel->excluir($id)) {
            $this->redirecionar('./?page=calendario&tipo=sucesso&msg=' . urlencode('Data excluida com sucesso.'));
        }

        $this->redirecionar('./?page=calendario&tipo=erro&msg=' . urlencode('Nao foi possivel excluir a data.'));
    }

    private function obterDadosPost(): array
    {
        $tipo = trim($_POST['tipo'] ?? 'Feriado');

        return [
            'data' => trim($_POST['data'] ?? ''),
            'data_fim' => $tipo === 'Recesso' ? trim($_POST['data_fim'] ?? '') : '',
            'titulo' => trim($_POST['titulo'] ?? ''),
            'tipo' => $tipo,
            'descricao' => trim($_POST['descricao'] ?? ''),
            'status' => trim($_POST['status'] ?? 'Ativo'),
        ];
    }

    private function validarDados(array $dados): bool
    {
        if (
            $dados['data'] === '' ||
            $dados['titulo'] === '' ||
            ! in_array($dados['tipo'], ['Feriado', 'Recesso', 'Parada Pedagogica'], true) ||
            ! in_array($dados['status'], ['Ativo', 'Inativo'], true)
        ) {
            return false;
        }

        if (strtotime($dados['data']) === false) {
            return false;
        }

        if ($dados['tipo'] !== 'Recesso') {
            return true;
        }

        return $dados['data_fim'] !== ''
            && strtotime($dados['data_fim']) !== false
            && strtotime($dados['data_fim']) >= strtotime($dados['data']);
    }

    private function queryCadastro(array $dados): string
    {
        return http_build_query([
            'page' => 'calendario',
            'data' => $dados['data'],
            'data_fim' => $dados['data_fim'],
            'titulo' => $dados['titulo'],
            'tipo_bloqueio' => $dados['tipo'],
            'descricao' => $dados['descricao'],
            'status_bloqueio' => $dados['status'],
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
