<?php

require_once __DIR__ . '/../models/SistemaLog.php';

class SistemaLogController
{
    private SistemaLog $logModel;

    public function __construct()
    {
        $this->logModel = new SistemaLog();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $busca = trim($_GET['busca'] ?? '');
        $pagina = trim($_GET['pagina_log'] ?? 'todos');
        $dataInicio = trim($_GET['data_inicio'] ?? '');
        $dataFim = trim($_GET['data_fim'] ?? '');

        $logs = $this->logModel->listar($busca, $pagina, $dataInicio, $dataFim);
        $paginas = $this->logModel->listarPaginas();
        $totalLogs = count($logs);

        require_once __DIR__ . '/../views/dashboard/sistema_logs.php';
    }

    public function limpar(): void
    {
        $this->exigirLogin();

        $dias = (int) ($_POST['dias'] ?? 0);
        $opcoesPermitidas = [30, 60, 90, 180, 365];

        if (! in_array($dias, $opcoesPermitidas, true)) {
            $this->redirecionar('./?page=logs&tipo=erro&msg=' . urlencode('Selecione um periodo valido para limpar os logs.'));
        }

        $removidos = $this->logModel->excluirMaisAntigosQue($dias);
        $mensagem = $removidos === 1
            ? '1 log antigo foi excluido com sucesso.'
            : $removidos . ' logs antigos foram excluidos com sucesso.';

        $this->redirecionar('./?page=logs&tipo=sucesso&msg=' . urlencode($mensagem));
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
            exit;
        }
    }
}
