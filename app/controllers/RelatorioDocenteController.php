<?php

require_once __DIR__ . '/../models/RelatorioDocente.php';
require_once __DIR__ . '/../models/EducacaoCorporativa.php';
require_once __DIR__ . '/../core/AccessControl.php';

class RelatorioDocenteController
{
    private RelatorioDocente $relatorioModel;
    private EducacaoCorporativa $educacaoModel;

    public function __construct()
    {
        $this->relatorioModel = new RelatorioDocente();
        $this->educacaoModel = new EducacaoCorporativa();
    }

    public function index(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();
        $relatorioProprioDocente = $access->nivel() === 'Professor';

        $mes = (int) ($_GET['mes'] ?? date('n'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));

        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }

        if ($ano < 2000 || $ano > 2100) {
            $ano = (int) date('Y');
        }

        if ($relatorioProprioDocente) {
            $docenteId = $access->docenteId();

            if ($docenteId === null) {
                $this->redirecionar('./?page=home&tipo=erro&msg=' . urlencode('Seu usuario ainda nao esta vinculado a um docente ativo.'));
            }

            $docenteSelecionado = $this->relatorioModel->buscarDocente($docenteId);
            $docentes = $docenteSelecionado ? [$docenteSelecionado] : [];
        } else {
            $escopo = $access->escopoAreaAtuacao();
            $docentes = $this->relatorioModel->listarDocentes($escopo);
            $docenteId = (int) ($_GET['docente_id'] ?? ($docentes[0]['id'] ?? 0));
            $docenteSelecionado = $docenteId > 0 ? $this->relatorioModel->buscarDocente($docenteId, $escopo) : null;
        }

        if (! $docenteSelecionado) {
            $docenteId = 0;
        }

        $escala = $docenteSelecionado ? $this->relatorioModel->listarEscala($docenteId) : [];
        $aulas = $docenteSelecionado ? $this->relatorioModel->listarAulasMensais($docenteId, $mes, $ano) : [];
        $cursosCorporativos = $docenteSelecionado ? $this->educacaoModel->listarPorDocenteMes($docenteId, $mes, $ano) : [];
        $eventosPorData = $this->montarEventos($escala, $aulas, $cursosCorporativos, $mes, $ano);
        $resumoCarga = $this->calcularResumoCarga($eventosPorData);
        $periodosEscala = $this->periodosDaEscala($escala);

        require_once __DIR__ . '/../views/dashboard/relatorio_docente.php';
    }

    private function montarEventos(array $escala, array $aulas, array $cursosCorporativos, int $mes, int $ano): array
    {
        $escalaPorDia = [];
        $aulasPorData = [];
        $cursosPorData = [];
        $eventosPorData = [];
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $diasNoMes = (int) date('t', strtotime($inicio));

        foreach ($escala as $item) {
            $diaKey = $this->normalizarDiaSemana((string) ($item['dia_semana'] ?? ''));
            $periodoKey = $this->normalizarPeriodo((string) ($item['periodo'] ?? ''));

            if ($diaKey !== '' && $periodoKey !== '') {
                $escalaPorDia[$diaKey][$periodoKey] = [
                    'periodo' => $this->periodoLabel($periodoKey),
                    'horas' => (float) ($item['horas'] ?? 0),
                ];
            }
        }

        foreach ($aulas as $aula) {
            $data = (string) $aula['data_aula'];
            $periodoKey = $this->periodoPorHorario(
                (string) ($aula['hora_inicio'] ?? ''),
                (string) ($aula['hora_fim'] ?? '')
            );
            $aula['periodo_key'] = $periodoKey;
            $aulasPorData[$data][] = $aula;
        }

        foreach ($cursosCorporativos as $curso) {
            $data = (string) ($curso['data'] ?? '');

            if ($data !== '') {
                $cursosPorData[$data][] = $curso;
            }
        }

        for ($dia = 1; $dia <= $diasNoMes; $dia++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $diaKey = $this->diaSemanaPorData($data);
            $aulasData = $aulasPorData[$data] ?? [];
            $cursoData = $cursosPorData[$data][0] ?? null;
            $escalaData = $escalaPorDia[$diaKey] ?? [];
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

            if ($cursoData && ! empty($escalaData)) {
                $periodosCurso = [];
                $horasCurso = 0.0;

                foreach ($escalaData as $itemEscala) {
                    $periodosCurso[] = $itemEscala['periodo'];
                    $horasCurso += (float) ($itemEscala['horas'] ?? 0);
                }

                $eventosPorData[$data][] = [
                    'tipo' => 'curso',
                    'periodo' => implode(' / ', array_unique($periodosCurso)),
                    'periodo_key' => 'curso',
                    'hora' => $this->formatarHoras($horasCurso),
                    'horas_numero' => $horasCurso,
                    'turma' => 'Curso: ' . ($cursoData['titulo'] ?? ''),
                    'uc' => '',
                    'sala' => '',
                ];

                continue;
            }

            foreach ($escalaData as $periodoKey => $itemEscala) {
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
        $horasCurso = 0.0;

        foreach ($eventosPorData as $eventos) {
            foreach ($eventos as $evento) {
                $horas = (float) ($evento['horas_numero'] ?? 0);

                if (($evento['tipo'] ?? '') === 'aula') {
                    $horasAula += $horas;
                    continue;
                }

                if (($evento['tipo'] ?? '') === 'planejamento') {
                    $horasPlanejamento += $horas;
                    continue;
                }

                if (($evento['tipo'] ?? '') === 'curso') {
                    $horasCurso += $horas;
                }
            }
        }

        $total = $horasAula + $horasPlanejamento + $horasCurso;

        return [
            'horas_aula' => $horasAula,
            'horas_planejamento' => $horasPlanejamento,
            'horas_curso' => $horasCurso,
            'total_horas' => $total,
            'percentual_aula' => $total > 0 ? round(($horasAula / $total) * 100, 1) : 0,
            'percentual_planejamento' => $total > 0 ? round(($horasPlanejamento / $total) * 100, 1) : 0,
            'percentual_curso' => $total > 0 ? round(($horasCurso / $total) * 100, 1) : 0,
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

    private function formatarHoras(float $horas): string
    {
        if (fmod($horas, 1.0) === 0.0) {
            return (int) $horas . 'h';
        }

        $horasInteiras = (int) floor($horas);
        $minutos = (int) round(($horas - $horasInteiras) * 60);

        return $horasInteiras . 'h' . str_pad((string) $minutos, 2, '0', STR_PAD_LEFT);
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

    private function periodoPorHorario(string $horaInicio, string $horaFim): string
    {
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return '';
        }

        if (date('H:i', $inicio) < '12:00') {
            return 'manha';
        }

        if (date('H:i', $inicio) < '18:00') {
            return 'tarde';
        }

        return 'noite';
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
            ['á', 'à', 'ã', 'â', 'ä', 'é', 'ê', 'í', 'ó', 'õ', 'ô', 'ú', 'ç', ' ', 'æ', 'Æ'],
            ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c', 'a', 'a', 'a'],
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
            header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
            exit;
        }
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

}
