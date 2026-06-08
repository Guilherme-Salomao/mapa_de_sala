<?php

require_once __DIR__ . '/../models/DocenteFerias.php';
require_once __DIR__ . '/../core/AccessControl.php';

class DocenteFeriasController
{
    private DocenteFerias $feriasModel;

    public function __construct()
    {
        $this->feriasModel = new DocenteFerias();
    }

    public function index(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $ano = $this->anoValido($_GET['ano'] ?? null) ? (int) $_GET['ano'] : (int) date('Y');
        $dataInicio = sprintf('%04d-01-01', $ano);
        $dataFim = sprintf('%04d-12-31', $ano);
        $registroForm = null;
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $escopo = $access->escopoAreaAtuacao();

        if ($docenteRestritoId === null && $access->nivel() === 'Professor') {
            $this->redirecionar('./?page=home&tipo=erro&msg=' . urlencode('Seu usuário ainda não está vinculado a um docente ativo.'));
        }

        if (($_GET['action'] ?? '') === 'editar') {
            $id = (int) ($_GET['id'] ?? 0);
            $registroForm = $id > 0 ? $this->feriasModel->buscarPorId($id, $escopo, $docenteRestritoId) : null;

            if (! $registroForm) {
                $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Registro de férias não encontrado.'));
            }
        }

        $docentes = $this->feriasModel->listarDocentes($escopo, $docenteRestritoId);
        $registros = $this->feriasModel->listar($dataInicio, $dataFim, $escopo, $docenteRestritoId);
        $totalRegistros = count($registros);
        $relatorioProprioDocente = $docenteRestritoId !== null;

        require_once __DIR__ . '/../views/dashboard/docente_ferias.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $dados = $this->obterDadosPost($access);
        $this->validarDadosOuRedirecionar($dados);

        if (! $this->docentePermitido($dados['docente_id'], $access)) {
            $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Docente não disponível para este usuário.'));
        }

        if ($dados['status'] === 'Ativo' && $this->feriasModel->existeSobreposicao($dados['docente_id'], $dados['data_inicio'], $dados['data_fim'])) {
            $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Já existe um período de férias ativo para este docente nestas datas.'));
        }

        if ($this->feriasModel->salvar($dados)) {
            $this->redirecionar('./?page=ferias&tipo=sucesso&msg=' . urlencode('Férias cadastradas com sucesso.'));
        }

        $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Não foi possível cadastrar as férias.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $dados = $this->obterDadosPost($access);
        $dados['id'] = (int) ($_POST['id'] ?? 0);
        $this->validarDadosOuRedirecionar($dados);
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $registro = $dados['id'] > 0 ? $this->feriasModel->buscarPorId($dados['id'], $access->escopoAreaAtuacao(), $docenteRestritoId) : null;

        if (! $registro || ! $this->docentePermitido($dados['docente_id'], $access)) {
            $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Registro de férias não encontrado.'));
        }

        if ($dados['status'] === 'Ativo' && $this->feriasModel->existeSobreposicao($dados['docente_id'], $dados['data_inicio'], $dados['data_fim'], $dados['id'])) {
            $this->redirecionar('./?page=ferias&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Já existe outro período de férias ativo para este docente nestas datas.'));
        }

        if ($this->feriasModel->atualizar($dados)) {
            $this->redirecionar('./?page=ferias&tipo=sucesso&msg=' . urlencode('Férias atualizadas com sucesso.'));
        }

        $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Não foi possível atualizar as férias.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $id = (int) ($_POST['id'] ?? 0);
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $registro = $id > 0 ? $this->feriasModel->buscarPorId($id, $access->escopoAreaAtuacao(), $docenteRestritoId) : null;

        if (! $registro) {
            $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Registro de férias não encontrado.'));
        }

        if ($this->feriasModel->excluir($id)) {
            $this->redirecionar('./?page=ferias&tipo=sucesso&msg=' . urlencode('Férias excluídas com sucesso.'));
        }

        $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Não foi possível excluir as férias.'));
    }

    private function obterDadosPost(AccessControl $access): array
    {
        return [
            'docente_id' => $access->nivel() === 'Professor' ? (int) ($access->docenteId() ?? 0) : (int) ($_POST['docente_id'] ?? 0),
            'data_inicio' => trim($_POST['data_inicio'] ?? ''),
            'data_fim' => trim($_POST['data_fim'] ?? ''),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'status' => trim($_POST['status'] ?? 'Ativo'),
        ];
    }

    private function validarDadosOuRedirecionar(array $dados): void
    {
        if (
            $dados['docente_id'] <= 0
            || ! $this->dataValida($dados['data_inicio'])
            || ! $this->dataValida($dados['data_fim'])
            || $dados['data_fim'] < $dados['data_inicio']
            || ! in_array($dados['status'], ['Ativo', 'Inativo'], true)
        ) {
            $this->redirecionar('./?page=ferias&tipo=erro&msg=' . urlencode('Preencha corretamente o docente e o período de férias.'));
        }
    }

    private function docentePermitido(int $docenteId, AccessControl $access): bool
    {
        foreach ($this->feriasModel->listarDocentes($access->escopoAreaAtuacao(), $access->nivel() === 'Professor' ? $access->docenteId() : null) as $docente) {
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

    private function anoValido(mixed $ano): bool
    {
        if (! is_scalar($ano) || ! preg_match('/^\d{4}$/', (string) $ano)) {
            return false;
        }

        $anoInteiro = (int) $ano;

        return $anoInteiro >= 1900 && $anoInteiro <= 2200;
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            $this->redirecionar('./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
        }
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
