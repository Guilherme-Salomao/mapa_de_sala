<?php

require_once __DIR__ . '/../models/RelatorioTurmaSemDocente.php';
require_once __DIR__ . '/../core/AccessControl.php';

class RelatorioTurmaSemDocenteController
{
    private RelatorioTurmaSemDocente $relatorioModel;

    public function __construct()
    {
        $this->relatorioModel = new RelatorioTurmaSemDocente();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $access = new AccessControl();
        $dataInicio = $this->normalizarData($_GET['data_inicio'] ?? '') ?? date('Y-m-d');
        $dataFim = $this->normalizarData($_GET['data_fim'] ?? '') ?? date('Y-m-d', strtotime('+30 days'));
        $turmaId = (int) ($_GET['turma_id'] ?? 0);

        if ($dataFim < $dataInicio) {
            [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
        }

        $escopo = $access->escopoAreaAtuacao();
        $turmas = $this->relatorioModel->listarTurmas($escopo);
        $aulasSemDocente = $this->relatorioModel->listar($dataInicio, $dataFim, $turmaId, $escopo);

        require_once __DIR__ . '/../views/dashboard/relatorio_turmas_sem_docente.php';
    }

    private function normalizarData(string $data): ?string
    {
        $dt = DateTime::createFromFormat('Y-m-d', $data);

        return $dt && $dt->format('Y-m-d') === $data ? $data : null;
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }
    }
}
