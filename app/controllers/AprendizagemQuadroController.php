<?php

require_once __DIR__ . '/../models/AprendizagemQuadro.php';

class AprendizagemQuadroController
{
    private AprendizagemQuadro $aprendizagemModel;

    public function __construct()
    {
        $this->aprendizagemModel = new AprendizagemQuadro();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $busca = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');

        $registros = $this->aprendizagemModel->listar($busca, $status);
        $turmas = $this->aprendizagemModel->listarTurmas();
        $ucs = $this->aprendizagemModel->listarUnidadesCurriculares();
        $salas = $this->aprendizagemModel->listarSalas();
        $docentes = $this->aprendizagemModel->listarDocentes();
        $totalRegistros = count($registros);

        require_once __DIR__ . '/../views/dashboard/aprendizagem_quadros.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $queryBase = $this->queryCadastro($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatorios.'));
        }

        $resultado = $this->aprendizagemModel->salvarEGerar($dados);
        $tipo = ! empty($resultado['sucesso']) ? 'sucesso' : 'erro';

        $this->redirecionar('./?page=aceleracao&tipo=' . $tipo . '&msg=' . urlencode($resultado['mensagem'] ?? 'Processo concluido.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('./?page=aceleracao&tipo=erro&msg=' . urlencode('Registro invalido.'));
        }

        if ($this->aprendizagemModel->excluir($id)) {
            $this->redirecionar('./?page=aceleracao&tipo=sucesso&msg=' . urlencode('Programação de Aceleração excluida do quadro horario.'));
        }

        $this->redirecionar('./?page=aceleracao&tipo=erro&msg=' . urlencode('Nao foi possivel excluir a programação de Aceleração.'));
    }

    private function obterDadosPost(): array
    {
        return [
            'curso_oferta_id' => (int) ($_POST['curso_oferta_id'] ?? 0),
            'unidade_curricular_id' => (int) ($_POST['unidade_curricular_id'] ?? 0),
            'sala_id' => (int) ($_POST['sala_id'] ?? 0),
            'docente_id' => (int) ($_POST['docente_id'] ?? 0),
            'data_inicio' => trim($_POST['data_inicio'] ?? ''),
            'data_fim' => trim($_POST['data_fim'] ?? ''),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
    }

    private function validarDados(array $dados): bool
    {
        if (
            $dados['curso_oferta_id'] <= 0 ||
            $dados['unidade_curricular_id'] <= 0 ||
            $dados['sala_id'] <= 0 ||
            $dados['docente_id'] <= 0 ||
            $dados['data_inicio'] === '' ||
            $dados['data_fim'] === ''
        ) {
            return false;
        }

        $inicio = strtotime($dados['data_inicio']);
        $fim = strtotime($dados['data_fim']);

        return $inicio !== false && $fim !== false && $fim >= $inicio;
    }

    private function queryCadastro(array $dados): string
    {
        return http_build_query([
            'page' => 'aceleracao',
            'curso_oferta_id' => $dados['curso_oferta_id'] > 0 ? $dados['curso_oferta_id'] : '',
            'unidade_curricular_id' => $dados['unidade_curricular_id'] > 0 ? $dados['unidade_curricular_id'] : '',
            'sala_id' => $dados['sala_id'] > 0 ? $dados['sala_id'] : '',
            'docente_id' => $dados['docente_id'] > 0 ? $dados['docente_id'] : '',
            'data_inicio' => $dados['data_inicio'],
            'data_fim' => $dados['data_fim'],
            'observacoes' => $dados['observacoes'],
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
