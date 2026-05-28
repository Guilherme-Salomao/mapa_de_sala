<?php

require_once __DIR__ . '/../models/RelatorioSala.php';

class RelatorioSalaController
{
    private RelatorioSala $relatorioModel;

    public function __construct()
    {
        $this->relatorioModel = new RelatorioSala();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $data = $_GET['data'] ?? date('Y-m-d');
        $situacao = $_GET['situacao'] ?? 'todas';

        if (strtotime($data) === false) {
            $data = date('Y-m-d');
        }

        if (! in_array($situacao, ['todas', 'livre', 'ocupada', 'reservada', 'manutencao', 'inativa'], true)) {
            $situacao = 'todas';
        }

        $salas = $this->relatorioModel->listarPorSituacao($data, $situacao);
        $totais = $this->relatorioModel->totais($data);

        require_once __DIR__ . '/../views/dashboard/relatorio_salas.php';
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
