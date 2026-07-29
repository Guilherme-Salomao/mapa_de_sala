<?php

require_once __DIR__ . '/../models/DocenteSubstituicao.php';
require_once __DIR__ . '/../models/QuadroHorario.php';
require_once __DIR__ . '/../models/EducacaoCorporativa.php';
require_once __DIR__ . '/../core/AccessControl.php';

class DocenteSubstituicaoController
{
    private DocenteSubstituicao $substituicaoModel;
    private QuadroHorario $quadroModel;
    private EducacaoCorporativa $educacaoModel;

    public function __construct()
    {
        $this->substituicaoModel = new DocenteSubstituicao();
        $this->quadroModel = new QuadroHorario();
        $this->educacaoModel = new EducacaoCorporativa();
    }

    public function index(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $busca = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');
        $escopo = $access->escopoAreaAtuacao();
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $registroForm = null;

        if ($access->nivel() === 'Professor' && $docenteRestritoId === null) {
            $this->redirecionar('./?page=home&tipo=erro&msg=' . urlencode('Seu usuario ainda nao esta vinculado a um docente ativo.'));
        }

        if (($_GET['action'] ?? '') === 'editar') {
            $id = (int) ($_GET['id'] ?? 0);
            $registroForm = $id > 0 ? $this->substituicaoModel->buscarPorId($id, $escopo, $docenteRestritoId) : null;

            if (! $registroForm) {
                $this->redirecionar('./?page=substituicoes&tipo=erro&msg=' . urlencode('Registro de substituicao nao encontrado.'));
            }
        }

        $docentes = $this->substituicaoModel->listarDocentes($escopo, $docenteRestritoId);
        $salas = $this->substituicaoModel->listarSalas();
        $registros = $this->substituicaoModel->listar($busca, $status, $escopo, $docenteRestritoId);
        $totalRegistros = count($registros);

        require_once __DIR__ . '/../views/dashboard/docente_substituicoes.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $dados = $this->obterDadosPost($access);

        if (! $this->validarDados($dados) || ! $this->docentePermitido($dados['docente_id'], $access)) {
            $this->redirecionar('./?page=substituicoes&tipo=erro&msg=' . urlencode('Preencha corretamente os dados da substituicao.'));
        }

        $erro = $this->validarDisponibilidade($dados);

        if ($erro !== null) {
            $this->redirecionar('./?page=substituicoes&tipo=erro&msg=' . urlencode($erro));
        }

        if ($this->substituicaoModel->salvar($dados)) {
            $this->redirecionar('./?page=substituicoes&tipo=sucesso&msg=' . urlencode('Substituicao cadastrada com sucesso.'));
        }

        $this->redirecionar('./?page=substituicoes&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar a substituicao.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $dados = $this->obterDadosPost($access);
        $dados['id'] = (int) ($_POST['id'] ?? 0);
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $registro = $dados['id'] > 0 ? $this->substituicaoModel->buscarPorId($dados['id'], $access->escopoAreaAtuacao(), $docenteRestritoId) : null;

        if (! $registro || ! $this->validarDados($dados) || ! $this->docentePermitido($dados['docente_id'], $access)) {
            $this->redirecionar('./?page=substituicoes&tipo=erro&msg=' . urlencode('Registro de substituicao nao encontrado.'));
        }

        $erro = $this->validarDisponibilidade($dados, $dados['id']);

        if ($erro !== null) {
            $this->redirecionar('./?page=substituicoes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode($erro));
        }

        if ($this->substituicaoModel->atualizar($dados)) {
            $this->redirecionar('./?page=substituicoes&tipo=sucesso&msg=' . urlencode('Substituicao atualizada com sucesso.'));
        }

        $this->redirecionar('./?page=substituicoes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar a substituicao.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $id = (int) ($_POST['id'] ?? 0);
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $registro = $id > 0 ? $this->substituicaoModel->buscarPorId($id, $access->escopoAreaAtuacao(), $docenteRestritoId) : null;

        if (! $registro) {
            $this->redirecionar('./?page=substituicoes&tipo=erro&msg=' . urlencode('Registro de substituicao nao encontrado.'));
        }

        if ($this->substituicaoModel->excluir($id)) {
            $this->redirecionar('./?page=substituicoes&tipo=sucesso&msg=' . urlencode('Substituicao excluida com sucesso.'));
        }

        $this->redirecionar('./?page=substituicoes&tipo=erro&msg=' . urlencode('Nao foi possivel excluir a substituicao.'));
    }

    private function obterDadosPost(AccessControl $access): array
    {
        return [
            'docente_id' => $access->nivel() === 'Professor' ? (int) ($access->docenteId() ?? 0) : (int) ($_POST['docente_id'] ?? 0),
            'sala_id' => (int) ($_POST['sala_id'] ?? 0),
            'data_aula' => trim($_POST['data_aula'] ?? ''),
            'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
            'hora_fim' => trim($_POST['hora_fim'] ?? ''),
            'turma' => trim($_POST['turma'] ?? ''),
            'unidade_curricular' => trim($_POST['unidade_curricular'] ?? ''),
            'motivo' => trim($_POST['motivo'] ?? ''),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'status' => trim($_POST['status'] ?? 'Ativo'),
        ];
    }

    private function validarDados(array $dados): bool
    {
        return $dados['docente_id'] > 0
            && $this->dataValida((string) $dados['data_aula'])
            && preg_match('/^\d{2}:\d{2}$/', (string) $dados['hora_inicio'])
            && preg_match('/^\d{2}:\d{2}$/', (string) $dados['hora_fim'])
            && $dados['hora_fim'] > $dados['hora_inicio']
            && $dados['turma'] !== ''
            && in_array($dados['status'], ['Ativo', 'Inativo'], true);
    }

    private function validarDisponibilidade(array $dados, ?int $ignorarId = null): ?string
    {
        if ($dados['status'] !== 'Ativo') {
            return null;
        }

        $docenteId = (int) $dados['docente_id'];
        $dataAula = (string) $dados['data_aula'];
        $horaInicio = (string) $dados['hora_inicio'];
        $horaFim = (string) $dados['hora_fim'];

        if ($this->quadroModel->docenteEmFerias($docenteId, $dataAula)) {
            return 'Este docente esta de ferias nesta data.';
        }

        if ($this->quadroModel->docenteEmCompensacao($docenteId, $dataAula)) {
            return 'Este docente esta em compensacao nesta data.';
        }

        if ($this->educacaoModel->docenteEmCurso($docenteId, $dataAula, null, $horaInicio, $horaFim)) {
            return 'Este docente ja possui Educacao Corporativa neste horario.';
        }

        if ($this->quadroModel->encontrarConflitoDocente($docenteId, $dataAula, $horaInicio, $horaFim)) {
            return 'Este docente ja possui aula lancada neste horario.';
        }

        if ($this->substituicaoModel->encontrarConflitoSubstituicaoDocente($docenteId, $dataAula, $horaInicio, $horaFim, $ignorarId)) {
            return 'Este docente ja possui substituicao neste horario.';
        }

        $salaId = (int) ($dados['sala_id'] ?? 0);

        if ($salaId > 0 && $this->quadroModel->encontrarConflitoSala($salaId, $dataAula, $horaInicio, $horaFim)) {
            return 'Esta sala ja possui aula lancada neste horario.';
        }

        if ($salaId > 0 && $this->substituicaoModel->encontrarConflitoSubstituicaoSala($salaId, $dataAula, $horaInicio, $horaFim, $ignorarId)) {
            return 'Esta sala ja possui substituicao neste horario.';
        }

        return null;
    }

    private function docentePermitido(int $docenteId, AccessControl $access): bool
    {
        foreach ($this->substituicaoModel->listarDocentes($access->escopoAreaAtuacao(), $access->nivel() === 'Professor' ? $access->docenteId() : null) as $docente) {
            if ((int) ($docente['id'] ?? 0) === $docenteId) {
                return true;
            }
        }

        return false;
    }

    private function dataValida(string $data): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $data);

        return $dt && $dt->format('Y-m-d') === $data;
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
