<?php

require_once __DIR__ . '/../models/RelatorioDocente.php';

class RelatorioDocenteController
{
    private RelatorioDocente $relatorioModel;

    public function __construct()
    {
        $this->relatorioModel = new RelatorioDocente();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $mes = (int) ($_GET['mes'] ?? date('n'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));

        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }

        if ($ano < 2000 || $ano > 2100) {
            $ano = (int) date('Y');
        }

        $docentes = $this->relatorioModel->listarDocentes();
        $docenteId = (int) ($_GET['docente_id'] ?? ($docentes[0]['id'] ?? 0));
        $docenteSelecionado = $docenteId > 0 ? $this->relatorioModel->buscarDocente($docenteId) : null;
        $escala = $docenteSelecionado ? $this->relatorioModel->listarEscala($docenteId) : [];
        $aulas = $docenteSelecionado ? $this->relatorioModel->listarAulasMensais($docenteId, $mes, $ano) : [];
        $eventosPorData = $this->montarEventos($escala, $aulas, $mes, $ano);
        $resumoCarga = $this->calcularResumoCarga($eventosPorData);
        $periodosEscala = $this->periodosDaEscala($escala);

        require_once __DIR__ . '/../views/dashboard/relatorio_docente.php';
    }

    private function montarEventos(array $escala, array $aulas, int $mes, int $ano): array
    {
        $escalaPorDia = [];
        $aulasPorData = [];
        $eventosPorData = [];
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $diasNoMes = (int) date('t', strtotime($inicio));

        foreach ($escala as $item) {
            $diaKey = $this->normalizarDiaSemana((string) ($item['dia_semana'] ?? ''));
            $periodoKey = $this->normalizarPeriodo((string) ($item['periodo'] ?? ''));

            if ($diaKey !== '' && $periodoKey !== '') {
                $escalaPorDia[$diaKey][$periodoKey] = [
                    'periodo' => $this->periodoLabel($periodoKey),
                    'horas' => (int) ($item['horas'] ?? 0),
                ];
            }
        }

        foreach ($aulas as $aula) {
            $data = (string) $aula['data_aula'];
            $periodoKey = $this->normalizarPeriodo((string) ($aula['periodo'] ?? ''));
            $aula['periodo_key'] = $periodoKey;
            $aulasPorData[$data][] = $aula;
        }

        for ($dia = 1; $dia <= $diasNoMes; $dia++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $diaKey = $this->diaSemanaPorData($data);
            $aulasData = $aulasPorData[$data] ?? [];
            $periodosComAula = [];

            foreach ($aulasData as $aula) {
                $periodoKey = (string) ($aula['periodo_key'] ?? '');

                if ($periodoKey !== '') {
                    $periodosComAula[$periodoKey] = true;
                }

                $eventosPorData[$data][] = [
                    'tipo' => 'aula',
                    'periodo' => $this->periodoLabel($periodoKey),
                    'periodo_key' => $periodoKey,
                    'hora' => substr((string) $aula['hora_inicio'], 0, 5) . ' - ' . substr((string) $aula['hora_fim'], 0, 5),
                    'horas_numero' => $this->horasEntre((string) $aula['hora_inicio'], (string) $aula['hora_fim']),
                    'turma' => $aula['turma_nome'] ?? '',
                    'uc' => trim(($aula['uc_codigo'] ?? '') . ' - ' . ($aula['uc_nome'] ?? '')),
                    'sala' => $aula['sala_nome'] ?? '',
                ];
            }

            foreach (($escalaPorDia[$diaKey] ?? []) as $periodoKey => $itemEscala) {
                if (isset($periodosComAula[$periodoKey])) {
                    continue;
                }

                $eventosPorData[$data][] = [
                    'tipo' => 'planejamento',
                    'periodo' => $itemEscala['periodo'],
                    'periodo_key' => $periodoKey,
                    'hora' => $itemEscala['horas'] . 'h',
                    'horas_numero' => (float) $itemEscala['horas'],
                    'turma' => 'Planejamento',
                    'uc' => '',
                    'sala' => '',
                ];
            }
        }

        return $eventosPorData;
    }

    private function calcularResumoCarga(array $eventosPorData): array
    {
        $horasAula = 0.0;
        $horasPlanejamento = 0.0;

        foreach ($eventosPorData as $eventos) {
            foreach ($eventos as $evento) {
                $horas = (float) ($evento['horas_numero'] ?? 0);

                if (($evento['tipo'] ?? '') === 'aula') {
                    $horasAula += $horas;
                    continue;
                }

                if (($evento['tipo'] ?? '') === 'planejamento') {
                    $horasPlanejamento += $horas;
                }
            }
        }

        $total = $horasAula + $horasPlanejamento;

        return [
            'horas_aula' => $horasAula,
            'horas_planejamento' => $horasPlanejamento,
            'total_horas' => $total,
            'percentual_aula' => $total > 0 ? round(($horasAula / $total) * 100, 1) : 0,
            'percentual_planejamento' => $total > 0 ? round(($horasPlanejamento / $total) * 100, 1) : 0,
        ];
    }

    private function periodosDaEscala(array $escala): array
    {
        $periodos = [];

        foreach ($escala as $item) {
            $periodoKey = $this->normalizarPeriodo((string) ($item['periodo'] ?? ''));

            if ($periodoKey !== '') {
                $periodos[$periodoKey] = $this->periodoLabel($periodoKey);
            }
        }

        $ordem = ['manha', 'tarde', 'noite'];
        $ordenados = [];

        foreach ($ordem as $periodoKey) {
            if (isset($periodos[$periodoKey])) {
                $ordenados[$periodoKey] = $periodos[$periodoKey];
            }
        }

        return $ordenados;
    }

    private function horasEntre(string $horaInicio, string $horaFim): float
    {
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return 0.0;
        }

        return ($fim - $inicio) / 3600;
    }

    private function diaSemanaPorData(string $data): string
    {
        return [
            1 => 'segunda',
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado',
            7 => 'domingo',
        ][(int) date('N', strtotime($data))] ?? '';
    }

    private function normalizarDiaSemana(string $dia): string
    {
        $dia = $this->normalizarTexto($dia);

        if (str_contains($dia, 'segunda')) {
            return 'segunda';
        }

        if (str_contains($dia, 'ter')) {
            return 'terca';
        }

        if (str_contains($dia, 'quarta')) {
            return 'quarta';
        }

        if (str_contains($dia, 'quinta')) {
            return 'quinta';
        }

        if (str_contains($dia, 'sexta')) {
            return 'sexta';
        }

        if (str_contains($dia, 'sab')) {
            return 'sabado';
        }

        return '';
    }

    private function normalizarPeriodo(string $periodo): string
    {
        $periodo = $this->normalizarTexto($periodo);

        if (str_contains($periodo, 'manh')) {
            return 'manha';
        }

        if (str_contains($periodo, 'tarde')) {
            return 'tarde';
        }

        if (str_contains($periodo, 'noite')) {
            return 'noite';
        }

        return '';
    }

    private function periodoLabel(string $periodo): string
    {
        return [
            'manha' => 'Manha',
            'tarde' => 'Tarde',
            'noite' => 'Noite',
        ][$periodo] ?? '';
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = strtolower($texto);
        $texto = str_replace(
            ['á', 'à', 'ã', 'â', 'ä', 'é', 'ê', 'í', 'ó', 'õ', 'ô', 'ú', 'ç', '‡', ' ', 'æ', 'Æ'],
            ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c', 'c', 'a', 'a', 'a'],
            $texto
        );

        return $texto;
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
