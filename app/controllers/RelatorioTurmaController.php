<?php

require_once __DIR__ . '/../models/RelatorioTurma.php';
require_once __DIR__ . '/../core/AccessControl.php';

class RelatorioTurmaController
{
    private RelatorioTurma $relatorioModel;

    public function __construct()
    {
        $this->relatorioModel = new RelatorioTurma();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $turmas = $this->relatorioModel->listarTurmas((new AccessControl())->escopo());
        $turmaId = (int) ($_GET['turma_id'] ?? 0);

        $turmaSelecionada = $turmaId > 0 ? $this->relatorioModel->buscarTurma($turmaId) : null;
        $linhas = $turmaSelecionada ? $this->relatorioModel->relatorioPorUc($turmaId) : [];
        $datasTurma = $turmaSelecionada ? $this->relatorioModel->datasTurma($turmaId) : ['data_inicial' => null, 'data_final' => null];

        require_once __DIR__ . '/../views/dashboard/relatorio_turma.php';
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
            exit;
        }
    }

}
