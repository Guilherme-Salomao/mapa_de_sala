<?php

require_once __DIR__ . '/../models/EducacaoCorporativa.php';
require_once __DIR__ . '/../models/QuadroHorario.php';
require_once __DIR__ . '/../core/AccessControl.php';

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
        $access = new AccessControl();
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;

        if ($access->nivel() === 'Professor' && $docenteRestritoId === null) {
            $this->redirecionar('./?page=home&tipo=erro&msg=' . urlencode('Seu usuário ainda não está vinculado a um docente ativo.'));
        }

        if (($_GET['action'] ?? '') === 'editar') {
            $id = (int) ($_GET['id'] ?? 0);
            $registroForm = $id > 0 ? $this->educacaoModel->buscarPorId($id, $docenteRestritoId) : null;

            if (! $registroForm) {
                $this->redirecionar('./?page=educacao_corporativa&tipo=erro&msg=' . urlencode('Registro nao encontrado.'));
            }
        }

        $docentes = $this->educacaoModel->listarDocentes($docenteRestritoId);
        $registros = $this->educacaoModel->listar($busca, $status, $docenteRestritoId);
        $totalRegistros = count($registros);

        require_once __DIR__ . '/../views/dashboard/educacao_corporativa.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $access = new AccessControl();
        $dados = $this->obterDadosPost($access);
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

        $access = new AccessControl();
        $dados = $this->obterDadosPost($access);
        $dados['id'] = (int) ($_POST['id'] ?? 0);
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $registro = $dados['id'] > 0 ? $this->educacaoModel->buscarPorId($dados['id'], $docenteRestritoId) : null;

        if (! $registro || ! $this->validarDados($dados)) {
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
        $access = new AccessControl();
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;

        if ($id <= 0 || ! $this->educacaoModel->buscarPorId($id, $docenteRestritoId)) {
            $this->redirecionar('./?page=educacao_corporativa&tipo=erro&msg=' . urlencode('Registro invalido.'));
        }

        if ($this->educacaoModel->excluir($id)) {
            $this->redirecionar('./?page=educacao_corporativa&tipo=sucesso&msg=' . urlencode('Curso excluido com sucesso.'));
        }

        $this->redirecionar('./?page=educacao_corporativa&tipo=erro&msg=' . urlencode('Nao foi possivel excluir o curso.'));
    }

    private function obterDadosPost(AccessControl $access): array
    {
        return [
            'docente_id' => $access->nivel() === 'Professor'
                ? (int) ($access->docenteId() ?? 0)
                : (int) ($_POST['docente_id'] ?? 0),
            'data' => trim($_POST['data'] ?? ''),
            'dia_inteiro' => isset($_POST['dia_inteiro']) ? 1 : 0,
            'hora_inicio' => isset($_POST['dia_inteiro']) ? null : trim($_POST['hora_inicio'] ?? ''),
            'hora_fim' => isset($_POST['dia_inteiro']) ? null : trim($_POST['hora_fim'] ?? ''),
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
            && (
                (int) $dados['dia_inteiro'] === 1
                || (
                    preg_match('/^\d{2}:\d{2}$/', (string) $dados['hora_inicio'])
                    && preg_match('/^\d{2}:\d{2}$/', (string) $dados['hora_fim'])
                    && $dados['hora_fim'] > $dados['hora_inicio']
                )
            )
            && $dados['titulo'] !== ''
            && in_array($dados['status'], ['Ativo', 'Inativo'], true);
    }

    private function validarDisponibilidade(array $dados, ?int $ignorarId = null): ?string
    {
        if ($dados['status'] !== 'Ativo') {
            return null;
        }

        $horaInicio = (int) $dados['dia_inteiro'] === 1 ? '00:00:00' : (string) $dados['hora_inicio'];
        $horaFim = (int) $dados['dia_inteiro'] === 1 ? '23:59:59' : (string) $dados['hora_fim'];
        $curso = $this->educacaoModel->docenteEmCurso(
            (int) $dados['docente_id'],
            (string) $dados['data'],
            $ignorarId,
            $horaInicio,
            $horaFim
        );

        if ($curso) {
            return 'Este docente já possui Educação Corporativa neste horário.';
        }

        $conflito = $this->quadroModel->encontrarConflitoDocente(
            (int) $dados['docente_id'],
            (string) $dados['data'],
            $horaInicio,
            $horaFim
        );

        if ($conflito) {
            return 'Este docente já possui aula lançada neste horário.';
        }

        return null;
    }

    private function queryCadastro(array $dados): string
    {
        return http_build_query([
            'page' => 'educacao_corporativa',
            'docente_id' => $dados['docente_id'] > 0 ? $dados['docente_id'] : '',
            'data' => $dados['data'],
            'dia_inteiro' => $dados['dia_inteiro'],
            'hora_inicio' => $dados['hora_inicio'],
            'hora_fim' => $dados['hora_fim'],
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
