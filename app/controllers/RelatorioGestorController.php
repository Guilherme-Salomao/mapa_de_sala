<?php

require_once __DIR__ . '/../models/RelatorioGestor.php';
require_once __DIR__ . '/../core/AccessControl.php';

class RelatorioGestorController
{
    private RelatorioGestor $relatorioModel;

    public function __construct()
    {
        $this->relatorioModel = new RelatorioGestor();
    }

    public function index(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();

        if ($access->nivel() === 'Professor') {
            $this->redirecionar('./?page=home&tipo=erro&msg=' . urlencode('Voce nao tem permissao para acessar esta tela.'));
        }

        $mes = (int) ($_GET['mes'] ?? date('n'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));

        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }

        if ($ano < 2000 || $ano > 2100) {
            $ano = (int) date('Y');
        }

        $resumos = $this->relatorioModel->listarResumoMensal($mes, $ano, $access->escopoAreaAtuacao());
        $totais = $this->calcularTotais($resumos);

        require_once __DIR__ . '/../views/dashboard/relatorio_gestor.php';
    }

    private function calcularTotais(array $resumos): array
    {
        $horasAula = 0.0;
        $horasCurso = 0.0;
        $horasPlanejamento = 0.0;
        $horasParadaPedagogica = 0.0;
        $horasCompensacao = 0.0;

        foreach ($resumos as $resumo) {
            $horasAula += (float) ($resumo['horas_aula'] ?? 0);
            $horasCurso += (float) ($resumo['horas_curso'] ?? 0);
            $horasPlanejamento += (float) ($resumo['horas_planejamento'] ?? 0);
            $horasParadaPedagogica += (float) ($resumo['horas_parada_pedagogica'] ?? 0);
            $horasCompensacao += (float) ($resumo['horas_compensacao'] ?? 0);
        }

        $total = $horasAula + $horasCurso + $horasPlanejamento + $horasParadaPedagogica + $horasCompensacao;
        $percentualAula = $total > 0 ? round(($horasAula / $total) * 100, 1) : 0;
        $percentualCurso = $total > 0 ? round(($horasCurso / $total) * 100, 1) : 0;
        $percentualPlanejamento = $total > 0 ? round(($horasPlanejamento / $total) * 100, 1) : 0;
        $percentualParada = $total > 0 ? round(($horasParadaPedagogica / $total) * 100, 1) : 0;
        $percentualCompensacao = $total > 0
            ? max(0, round(100 - $percentualAula - $percentualCurso - $percentualPlanejamento - $percentualParada, 1))
            : 0;

        return [
            'docentes' => count($resumos),
            'horas_aula' => $horasAula,
            'horas_curso' => $horasCurso,
            'horas_planejamento' => $horasPlanejamento,
            'horas_parada_pedagogica' => $horasParadaPedagogica,
            'horas_compensacao' => $horasCompensacao,
            'total_horas' => $total,
            'percentual_aula' => $percentualAula,
            'percentual_curso' => $percentualCurso,
            'percentual_planejamento' => $percentualPlanejamento,
            'percentual_parada_pedagogica' => $percentualParada,
            'percentual_compensacao' => $percentualCompensacao,
        ];
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
