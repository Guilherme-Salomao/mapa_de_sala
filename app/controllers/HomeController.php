<?php

require_once __DIR__ . '/../models/Home.php';
require_once __DIR__ . '/../core/AccessControl.php';

class HomeController
{
    private Home $homeModel;

    public function __construct()
    {
        $this->homeModel = new Home();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $timezone = new DateTimeZone('America/Sao_Paulo');
        $hoje = new DateTime('now', $timezone);
        $dataSelecionada = $_GET['data'] ?? $hoje->format('Y-m-d');

        if (! $this->dataValida($dataSelecionada)) {
            $dataSelecionada = $hoje->format('Y-m-d');
        }

        $escopo = (new AccessControl())->escopo();
        $access = new AccessControl();
        $dashboardGestor = $access->nivel() === 'Gestor';
        $indicadores = $this->homeModel->indicadores($dataSelecionada);
        $aulasPorTurno = $this->homeModel->aulasPorTurno($dataSelecionada, $escopo);
        $indicadoresGestor = [];
        $resumoDocentesGestor = [];
        $dashboardDocente = $access->nivel() === 'Professor';
        $minhaSemana = [];
        $indicadoresDocente = [
            'horas_semana' => 0,
            'horas_mes' => 0,
            'percentual_aula' => 0,
            'percentual_planejamento' => 0,
            'percentual_curso' => 0,
            'percentual_parada_pedagogica' => 0,
        ];

        if ($dashboardDocente) {
            $docenteId = $access->docenteId();
            if ($docenteId !== null) {
                $minhaSemana = $this->homeModel->semanaDocente($docenteId, $dataSelecionada);
                $indicadoresDocente = $this->homeModel->indicadoresDocente($docenteId, $dataSelecionada);
            }
        }

        if ($dashboardGestor) {
            $mes = (int) date('n', strtotime($dataSelecionada));
            $ano = (int) date('Y', strtotime($dataSelecionada));
            $escopoGestor = $access->escopoAreaAtuacao();
            $indicadoresGestor = $this->homeModel->indicadoresGestor($dataSelecionada, $escopoGestor);
            $resumoDocentesGestor = $this->homeModel->resumoDocentesGestor($mes, $ano, $escopoGestor);
        }

        $dataHoje = $dataSelecionada;

        require_once __DIR__ . '/../views/dashboard/home.php';
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
            header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
            exit;
        }
    }
}
