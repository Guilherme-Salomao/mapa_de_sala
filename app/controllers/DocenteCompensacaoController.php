<?php

require_once __DIR__ . '/../models/DocenteCompensacao.php';
require_once __DIR__ . '/../core/AccessControl.php';

class DocenteCompensacaoController
{
    private DocenteCompensacao $compensacaoModel;

    public function __construct()
    {
        $this->compensacaoModel = new DocenteCompensacao();
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
            $registroForm = $id > 0
                ? $this->compensacaoModel->buscarPorId($id, $escopo, $docenteRestritoId)
                : null;

            if (! $registroForm) {
                $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Registro de compensação não encontrado.'));
            }
        }

        $docentes = $this->compensacaoModel->listarDocentes($escopo, $docenteRestritoId);
        $registros = $this->compensacaoModel->listar($dataInicio, $dataFim, $escopo, $docenteRestritoId);
        $totalRegistros = count($registros);
        $relatorioProprioDocente = $docenteRestritoId !== null;
        $paginaPeriodo = 'compensacao';
        $nomePeriodo = 'Compensação';
        $nomePeriodoPlural = 'Compensações';
        $subtituloPeriodo = 'Cadastre e consulte os períodos de compensação dos docentes';

        require_once __DIR__ . '/../views/dashboard/docente_ferias.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $dados = $this->obterDadosPost($access);
        $this->validarDadosOuRedirecionar($dados);

        if (! $this->docentePermitido($dados['docente_id'], $access)) {
            $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Docente não disponível para este usuário.'));
        }

        if ($dados['status'] === 'Ativo' && $this->compensacaoModel->existeSobreposicao(
            $dados['docente_id'],
            $dados['data_inicio'],
            $dados['data_fim']
        )) {
            $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Já existe uma compensação ativa para este docente nestas datas.'));
        }

        if ($this->compensacaoModel->salvar($dados)) {
            $this->redirecionar('./?page=compensacao&tipo=sucesso&msg=' . urlencode('Compensação cadastrada com sucesso.'));
        }

        $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Não foi possível cadastrar a compensação.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $dados = $this->obterDadosPost($access);
        $dados['id'] = (int) ($_POST['id'] ?? 0);
        $this->validarDadosOuRedirecionar($dados);
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $registro = $dados['id'] > 0
            ? $this->compensacaoModel->buscarPorId($dados['id'], $access->escopoAreaAtuacao(), $docenteRestritoId)
            : null;

        if (! $registro || ! $this->docentePermitido($dados['docente_id'], $access)) {
            $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Registro de compensação não encontrado.'));
        }

        if ($dados['status'] === 'Ativo' && $this->compensacaoModel->existeSobreposicao(
            $dados['docente_id'],
            $dados['data_inicio'],
            $dados['data_fim'],
            $dados['id']
        )) {
            $this->redirecionar('./?page=compensacao&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Já existe outra compensação ativa para este docente nestas datas.'));
        }

        if ($this->compensacaoModel->atualizar($dados)) {
            $this->redirecionar('./?page=compensacao&tipo=sucesso&msg=' . urlencode('Compensação atualizada com sucesso.'));
        }

        $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Não foi possível atualizar a compensação.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $id = (int) ($_POST['id'] ?? 0);
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;
        $registro = $id > 0
            ? $this->compensacaoModel->buscarPorId($id, $access->escopoAreaAtuacao(), $docenteRestritoId)
            : null;

        if (! $registro) {
            $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Registro de compensação não encontrado.'));
        }

        if ($this->compensacaoModel->excluir($id)) {
            $this->redirecionar('./?page=compensacao&tipo=sucesso&msg=' . urlencode('Compensação excluída com sucesso.'));
        }

        $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Não foi possível excluir a compensação.'));
    }

    private function obterDadosPost(AccessControl $access): array
    {
        $dataInicio = trim($_POST['data_inicio'] ?? '');
        $dataFim = trim($_POST['data_fim'] ?? '');

        return [
            'docente_id' => $access->nivel() === 'Professor'
                ? (int) ($access->docenteId() ?? 0)
                : (int) ($_POST['docente_id'] ?? 0),
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim !== '' ? $dataFim : $dataInicio,
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
            $this->redirecionar('./?page=compensacao&tipo=erro&msg=' . urlencode('Preencha corretamente o docente e o período de compensação.'));
        }
    }

    private function docentePermitido(int $docenteId, AccessControl $access): bool
    {
        $docenteRestritoId = $access->nivel() === 'Professor' ? $access->docenteId() : null;

        foreach ($this->compensacaoModel->listarDocentes($access->escopoAreaAtuacao(), $docenteRestritoId) as $docente) {
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
