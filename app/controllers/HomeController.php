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
        $indicadores = $this->homeModel->indicadores($dataSelecionada);
        $aulasPorTurno = $this->homeModel->aulasPorTurno($dataSelecionada, $escopo);
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
            header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
            exit;
        }
    }
}
