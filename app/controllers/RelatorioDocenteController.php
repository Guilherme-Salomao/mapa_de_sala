<?php

require_once __DIR__ . '/../models/RelatorioDocente.php';
require_once __DIR__ . '/../models/EducacaoCorporativa.php';
require_once __DIR__ . '/../models/DocenteSubstituicao.php';
require_once __DIR__ . '/../core/AccessControl.php';

class RelatorioDocenteController
{
    private RelatorioDocente $relatorioModel;
    private EducacaoCorporativa $educacaoModel;
    private DocenteSubstituicao $substituicaoModel;

    public function __construct()
    {
        $this->relatorioModel = new RelatorioDocente();
        $this->educacaoModel = new EducacaoCorporativa();
        $this->substituicaoModel = new DocenteSubstituicao();
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
        $substituicoes = $docenteSelecionado ? $this->substituicaoModel->listarPorDocenteMes($docenteId, $mes, $ano) : [];
        $cursosCorporativos = $docenteSelecionado ? $this->educacaoModel->listarPorDocenteMes($docenteId, $mes, $ano) : [];
        $bloqueiosCalendario = $docenteSelecionado ? $this->relatorioModel->listarBloqueiosMensais($mes, $ano) : [];
        $ausencias = $docenteSelecionado ? $this->relatorioModel->listarAusenciasMensais($docenteId, $mes, $ano) : [];
        $eventosPorData = $this->montarEventos(
            $escala,
            $aulas,
            $substituicoes,
            $cursosCorporativos,
            $bloqueiosCalendario,
            $ausencias,
            $mes,
            $ano
        );
        $cargaHorariaMensal = $this->calcularCargaHorariaMensal($escala, $bloqueiosCalendario, $ausencias, $mes, $ano);
        $resumoCarga = $this->calcularResumoCarga($eventosPorData, $cargaHorariaMensal);
        $periodosEscala = $this->periodosDaEscala($escala);

        require_once __DIR__ . '/../views/dashboard/relatorio_docente.php';
    }

    private function montarEventos(
        array $escala,
        array $aulas,
        array $substituicoes,
        array $cursosCorporativos,
        array $bloqueiosCalendario,
        array $ausencias,
        int $mes,
        int $ano
    ): array
    {
        $escalaPorDia = [];
        $aulasPorData = [];
        $substituicoesPorData = [];
        $cursosPorData = [];
        $bloqueiosPorData = [];
        $ausenciasPorData = [];
        $eventosPorData = [];
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));
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

        foreach ($substituicoes as $substituicao) {
            $data = (string) $substituicao['data_aula'];
            $periodoKey = $this->periodoPorHorario(
                (string) ($substituicao['hora_inicio'] ?? ''),
                (string) ($substituicao['hora_fim'] ?? '')
            );
            $substituicao['periodo_key'] = $periodoKey;
            $substituicoesPorData[$data][] = $substituicao;
        }

        foreach ($cursosCorporativos as $curso) {
            $data = (string) ($curso['data'] ?? '');

            if ($data !== '') {
                $cursosPorData[$data][] = $curso;
            }
        }

        foreach ($bloqueiosCalendario as $bloqueio) {
            $dataInicioBloqueio = (string) ($bloqueio['data'] ?? '');
            $dataFimBloqueio = (string) ($bloqueio['data_fim'] ?? $dataInicioBloqueio);

            for ($dataBloqueio = $dataInicioBloqueio; $dataBloqueio !== '' && $dataBloqueio <= $dataFimBloqueio; $dataBloqueio = date('Y-m-d', strtotime($dataBloqueio . ' +1 day'))) {
                if ($dataBloqueio >= $inicio && $dataBloqueio <= $fim) {
                    $bloqueiosPorData[$dataBloqueio][] = $bloqueio;
                }
            }
        }

        foreach ($ausencias as $ausencia) {
            $dataInicioAusencia = max((string) ($ausencia['data_inicio'] ?? ''), $inicio);
            $dataFimAusencia = min((string) ($ausencia['data_fim'] ?? ''), $fim);

            for (
                $dataAusencia = $dataInicioAusencia;
                $dataAusencia !== '' && $dataAusencia <= $dataFimAusencia;
                $dataAusencia = date('Y-m-d', strtotime($dataAusencia . ' +1 day'))
            ) {
                $ausenciasPorData[$dataAusencia][] = $ausencia;
            }
        }

        for ($dia = 1; $dia <= $diasNoMes; $dia++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $diaKey = $this->diaSemanaPorData($data);
            $aulasData = $aulasPorData[$data] ?? [];
            $substituicoesData = $substituicoesPorData[$data] ?? [];
            $cursosData = $cursosPorData[$data] ?? [];
            $escalaData = $escalaPorDia[$diaKey] ?? [];
            $eventosPorData[$data] = [];
            $periodosComAula = [];
            $diaInteiroBloqueado = false;
            $temParadaPedagogica = false;

            if (! empty($ausenciasPorData[$data])) {
                $temFerias = false;

                foreach ($ausenciasPorData[$data] as $ausencia) {
                    $tipoAusencia = (string) ($ausencia['tipo'] ?? '');
                    $horasEscalaDia = array_sum(array_map(
                        static fn(array $itemEscala): float => (float) ($itemEscala['horas'] ?? 0),
                        $escalaData
                    ));
                    $horasAusencia = $tipoAusencia === 'compensacao'
                        ? min($horasEscalaDia, $this->horasCompensacaoAusencia($ausencia, $horasEscalaDia))
                        : 0.0;
                    $periodoCompensacao = $tipoAusencia === 'compensacao'
                        ? $this->periodoPorHorario((string) ($ausencia['hora_inicio'] ?? ''), (string) ($ausencia['hora_fim'] ?? ''))
                        : '';
                    $periodosAusencia = $tipoAusencia === 'compensacao'
                        ? $this->periodosCompensacaoAusencia($escalaData, $periodoCompensacao)
                        : [];

                    if ($tipoAusencia === 'compensacao' && $horasAusencia > 0) {
                        $this->aplicarCompensacaoNosPeriodos($periodosComAula, $escalaData, $horasAusencia, $periodoCompensacao);
                    }

                    $eventosPorData[$data][] = [
                        'tipo' => $tipoAusencia,
                        'periodo' => implode(' / ', $periodosAusencia),
                        'periodo_key' => $tipoAusencia,
                        'hora' => $tipoAusencia === 'compensacao' ? $this->formatarHorarioCompensacao($ausencia, $horasAusencia) : '',
                        'horas_numero' => $horasAusencia,
                        'turma' => $tipoAusencia === 'compensacao' ? 'Compensação' : 'Férias',
                        'uc' => '',
                        'sala' => '',
                        'observacoes' => $ausencia['observacoes'] ?? '',
                    ];

                    $temFerias = $temFerias || $tipoAusencia === 'ferias';
                }

                if ($temFerias) {
                    continue;
                }
            }

            foreach (($bloqueiosPorData[$data] ?? []) as $bloqueio) {
                $horaInicioBloqueio = substr((string) ($bloqueio['hora_inicio'] ?? ''), 0, 5);
                $horaFimBloqueio = substr((string) ($bloqueio['hora_fim'] ?? ''), 0, 5);
                $isParadaPedagogica = (string) ($bloqueio['tipo'] ?? '') === 'Parada Pedagogica';

                $tituloBloqueio = (string) ($bloqueio['titulo'] ?? '');
                $ocultarTipoBloqueio = stripos($tituloBloqueio, 'Ponte de Feriado') !== false;
                $diaInteiroBloqueado = $diaInteiroBloqueado || $horaInicioBloqueio === '' || $horaFimBloqueio === '';
                $eventosPorData[$data][] = [
                    'tipo' => 'calendario',
                    'periodo' => '',
                    'periodo_key' => 'calendario',
                    'hora' => '',
                    'horas_numero' => 0,
                    'turma' => $ocultarTipoBloqueio ? '' : $this->labelTipoBloqueio((string) ($bloqueio['tipo'] ?? 'Evento')),
                    'uc' => '',
                    'sala' => '',
                    'titulo_calendario' => $isParadaPedagogica ? '' : $tituloBloqueio,
                    'subtipo_calendario' => $bloqueio['tipo'] ?? '',
                ];

                $temParadaPedagogica = $temParadaPedagogica || $isParadaPedagogica;
            }

            foreach ($aulasData as $aula) {
                $periodoKey = (string) ($aula['periodo_key'] ?? '');
                $horasEventoAula = 0.0;

                $horasLancadasAula = $this->horasEntre((string) $aula['hora_inicio'], (string) $aula['hora_fim']);

                if ($periodoKey !== '') {
                    $horasEventoAula = $this->horasDisponiveisHorarioPeriodo(
                        $escalaData,
                        $periodoKey,
                        (float) ($periodosComAula[$periodoKey] ?? 0),
                        (string) $aula['hora_inicio'],
                        (string) $aula['hora_fim']
                    );
                    $periodosComAula[$periodoKey] = (float) ($periodosComAula[$periodoKey] ?? 0) + $horasEventoAula;
                }

                $horasBancoHorasAula = max(0.0, $horasLancadasAula - $horasEventoAula);

                if ($horasEventoAula > 0 || $horasBancoHorasAula > 0) {
                    $eventosPorData[$data][] = [
                        'tipo' => 'aula',
                        'periodo' => $this->periodoLabel($periodoKey),
                        'periodo_key' => $periodoKey,
                        'hora' => substr((string) $aula['hora_inicio'], 0, 5) . ' - ' . substr((string) $aula['hora_fim'], 0, 5),
                        'horas_numero' => $horasEventoAula,
                        'banco_horas_numero' => $horasBancoHorasAula,
                        'turma' => $aula['turma_nome'] ?? '',
                        'uc' => trim(($aula['uc_codigo'] ?? '') . ' - ' . ($aula['uc_nome'] ?? '')),
                        'sala' => $aula['sala_nome'] ?? '',
                    ];
                }
            }

            foreach ($substituicoesData as $substituicao) {
                $periodoKey = (string) ($substituicao['periodo_key'] ?? '');
                $horasEventoAula = 0.0;

                $horasLancadasSubstituicao = $this->horasEntre((string) $substituicao['hora_inicio'], (string) $substituicao['hora_fim']);

                if ($periodoKey !== '') {
                    $horasEventoAula = $this->horasDisponiveisHorarioPeriodo(
                        $escalaData,
                        $periodoKey,
                        (float) ($periodosComAula[$periodoKey] ?? 0),
                        (string) $substituicao['hora_inicio'],
                        (string) $substituicao['hora_fim']
                    );
                    $periodosComAula[$periodoKey] = (float) ($periodosComAula[$periodoKey] ?? 0) + $horasEventoAula;
                }

                $horasBancoHorasSubstituicao = max(0.0, $horasLancadasSubstituicao - $horasEventoAula);

                if ($horasEventoAula > 0 || $horasBancoHorasSubstituicao > 0) {
                    $eventosPorData[$data][] = [
                        'tipo' => 'aula',
                        'periodo' => $this->periodoLabel($periodoKey),
                        'periodo_key' => $periodoKey,
                        'hora' => substr((string) $substituicao['hora_inicio'], 0, 5) . ' - ' . substr((string) $substituicao['hora_fim'], 0, 5),
                        'horas_numero' => $horasEventoAula,
                        'banco_horas_numero' => $horasBancoHorasSubstituicao,
                        'turma' => 'Substituição: ' . ($substituicao['turma'] ?? ''),
                        'uc' => $substituicao['unidade_curricular'] ?? '',
                        'sala' => $substituicao['sala_nome'] ?? '',
                        'observacoes' => $substituicao['motivo'] ?? '',
                    ];
                }
            }

            foreach ($cursosData as $cursoData) {
                $cursoTemHorarioLancado = ! empty($cursoData['hora_inicio']) && ! empty($cursoData['hora_fim']);
                $diaInteiroCurso = ! $cursoTemHorarioLancado;
                $periodosCurso = [];
                $horasCurso = 0.0;
                $horasCursoLancadas = 0.0;
                $horaCurso = '';

                if ($diaInteiroCurso) {
                    foreach ($escalaData as $periodoKey => $itemEscala) {
                        $periodosCurso[] = $itemEscala['periodo'];
                        $horasCursoPeriodo = (float) ($itemEscala['horas'] ?? 0);
                        $periodosComAula[$periodoKey] = $horasCursoPeriodo;
                        $horasCurso += $horasCursoPeriodo;
                    }
                } else {
                    $periodoCursoKey = $this->periodoPorHorario(
                        (string) $cursoData['hora_inicio'],
                        (string) $cursoData['hora_fim']
                    );
                    $periodosCurso[] = $this->periodoLabel($periodoCursoKey);
                    $horasCursoLancadas = $this->horasEntre(
                        (string) $cursoData['hora_inicio'],
                        (string) $cursoData['hora_fim']
                    );
                    $horasCurso = $horasCursoLancadas;
                    if ($periodoCursoKey !== '') {
                        $horasCurso = $this->horasDisponiveisHorarioPeriodo(
                            $escalaData,
                            $periodoCursoKey,
                            (float) ($periodosComAula[$periodoCursoKey] ?? 0),
                            (string) $cursoData['hora_inicio'],
                            (string) $cursoData['hora_fim']
                        );
                        $periodosComAula[$periodoCursoKey] = (float) ($periodosComAula[$periodoCursoKey] ?? 0) + $horasCurso;
                    }

                    $horaCurso = substr((string) $cursoData['hora_inicio'], 0, 5)
                        . ' - '
                        . substr((string) $cursoData['hora_fim'], 0, 5);
                }

                $cursoSemEscala = empty($escalaData);
                $cursoSemEscalaSemHorario = $cursoSemEscala && ! $cursoTemHorarioLancado;
                $horasBancoHorasCurso = $cursoSemEscalaSemHorario ? 0.0 : max(0.0, $horasCursoLancadas - $horasCurso);
                $marcarCurso = $cursoSemEscala || $horasBancoHorasCurso > 0;

                if ($horasCurso > 0 || $horasBancoHorasCurso > 0 || $cursoSemEscala) {
                    $eventosPorData[$data][] = [
                        'tipo' => 'curso',
                        'periodo' => implode(' / ', array_unique(array_filter($periodosCurso))) ?: 'Curso',
                        'periodo_key' => 'curso',
                        'hora' => $horaCurso !== '' ? $horaCurso : ($horasCurso > 0 ? $this->formatarHoras($horasCurso) : 'Dia todo'),
                        'horas_numero' => $cursoSemEscala ? 0.0 : $horasCurso,
                        'banco_horas_numero' => $horasBancoHorasCurso,
                        'marcar_asterisco' => $marcarCurso,
                        'turma' => 'Curso: ' . ($cursoData['titulo'] ?? ''),
                        'uc' => '',
                        'sala' => '',
                    ];
                }
            }

            if ($temParadaPedagogica) {
                $horasParadaPedagogicaDisponiveis = $this->horasDisponiveisEscala($escalaData, $periodosComAula);

                foreach ($eventosPorData[$data] as &$evento) {
                    if (($evento['tipo'] ?? '') === 'calendario' && ($evento['subtipo_calendario'] ?? '') === 'Parada Pedagogica') {
                        $evento['horas_numero'] = $horasParadaPedagogicaDisponiveis;
                        break;
                    }
                }
                unset($evento);

                continue;
            }

            if ($diaInteiroBloqueado) {
                continue;
            }

            foreach ($escalaData as $periodoKey => $itemEscala) {
                $horasPlanejamento = max(0, (float) ($itemEscala['horas'] ?? 0) - (float) ($periodosComAula[$periodoKey] ?? 0));

                if ($horasPlanejamento <= 0) {
                    continue;
                }

                $eventosPorData[$data][] = [
                    'tipo' => 'planejamento',
                    'periodo' => $itemEscala['periodo'],
                    'periodo_key' => $periodoKey,
                    'hora' => $this->formatarHoras($horasPlanejamento),
                    'horas_numero' => $horasPlanejamento,
                    'turma' => 'Planejamento',
                    'uc' => '',
                    'sala' => '',
                ];
            }

            usort($eventosPorData[$data], [$this, 'compararEventosRelatorio']);
        }

        return $eventosPorData;
    }


    private function periodosCompensacaoAusencia(array $escalaData, string $periodoCompensacao): array
    {
        if ($periodoCompensacao !== '' && isset($escalaData[$periodoCompensacao])) {
            return [(string) ($escalaData[$periodoCompensacao]['periodo'] ?? $this->periodoLabel($periodoCompensacao))];
        }

        return array_values(array_unique(array_column($escalaData, 'periodo')));
    }

    private function aplicarCompensacaoNosPeriodos(
        array &$periodosComAula,
        array $escalaData,
        float $horasCompensacao,
        string $periodoCompensacao
    ): void {
        if ($periodoCompensacao !== '' && isset($escalaData[$periodoCompensacao])) {
            $horasPeriodo = (float) ($escalaData[$periodoCompensacao]['horas'] ?? 0);
            $horasOcupadas = (float) ($periodosComAula[$periodoCompensacao] ?? 0);
            $periodosComAula[$periodoCompensacao] = $horasOcupadas + min(
                max(0, $horasPeriodo - $horasOcupadas),
                $horasCompensacao
            );

            return;
        }

        $horasRestantesCompensacao = $horasCompensacao;

        foreach ($escalaData as $periodoKey => $itemEscala) {
            if ($horasRestantesCompensacao <= 0) {
                break;
            }

            $horasPeriodo = (float) ($itemEscala['horas'] ?? 0);
            $horasOcupadas = (float) ($periodosComAula[$periodoKey] ?? 0);
            $horasAplicadas = min(max(0, $horasPeriodo - $horasOcupadas), $horasRestantesCompensacao);
            $periodosComAula[$periodoKey] = $horasOcupadas + $horasAplicadas;
            $horasRestantesCompensacao -= $horasAplicadas;
        }
    }

    private function formatarHorarioCompensacao(array $ausencia, float $horas): string
    {
        $horaInicio = substr((string) ($ausencia['hora_inicio'] ?? ''), 0, 5);
        $horaFim = substr((string) ($ausencia['hora_fim'] ?? ''), 0, 5);

        if ($horaInicio !== '' && $horaFim !== '') {
            return $horaInicio . ' - ' . $horaFim;
        }

        return $this->formatarHoras($horas);
    }

    private function horasCompensacaoAusencia(array $ausencia, float $horasPadrao): float
    {
        $inicio = strtotime((string) ($ausencia['hora_inicio'] ?? ''));
        $fim = strtotime((string) ($ausencia['hora_fim'] ?? ''));

        if ($inicio !== false && $fim !== false && $fim > $inicio) {
            return round(($fim - $inicio) / 3600, 2);
        }

        return $horasPadrao;
    }

    private function horasDisponiveisEscala(array $escalaData, array $periodosComAula): float
    {
        $horasDisponiveis = 0.0;

        foreach ($escalaData as $periodoKey => $itemEscala) {
            $horasPeriodo = (float) ($itemEscala['horas'] ?? 0);
            $horasOcupadas = (float) ($periodosComAula[$periodoKey] ?? 0);
            $horasDisponiveis += max(0.0, $horasPeriodo - $horasOcupadas);
        }

        return round($horasDisponiveis, 2);
    }

    private function horasDisponiveisHorarioPeriodo(
        array $escalaData,
        string $periodoKey,
        float $horasOcupadas,
        string $horaInicio,
        string $horaFim
    ): float {
        if (! isset($escalaData[$periodoKey])) {
            return 0.0;
        }

        $intervaloEscala = $this->intervaloEscalaPeriodo($periodoKey, (float) ($escalaData[$periodoKey]['horas'] ?? 0));

        if ($intervaloEscala === null) {
            return 0.0;
        }

        $inicioEvento = $this->minutosHorario($horaInicio);
        $fimEvento = $this->minutosHorario($horaFim);

        if ($inicioEvento === null || $fimEvento === null || $fimEvento <= $inicioEvento) {
            return 0.0;
        }

        $inicioValido = max($inicioEvento, $intervaloEscala['inicio']);
        $fimValido = min($fimEvento, $intervaloEscala['fim']);
        $horasDentroEscala = max(0.0, ($fimValido - $inicioValido) / 60);

        return $this->horasDisponiveisPeriodo($escalaData, $periodoKey, $horasOcupadas, $horasDentroEscala);
    }

    private function intervaloEscalaPeriodo(string $periodoKey, float $horas): ?array
    {
        if ($horas <= 0) {
            return null;
        }

        $minutos = (int) round($horas * 60);

        if ($periodoKey === 'manha') {
            $fim = 12 * 60;
            return ['inicio' => max(0, $fim - $minutos), 'fim' => $fim];
        }

        if ($periodoKey === 'tarde') {
            $fim = (17 * 60) + 30;
            return ['inicio' => max(12 * 60, $fim - $minutos), 'fim' => $fim];
        }

        if ($periodoKey === 'noite') {
            $inicio = 19 * 60;
            return ['inicio' => $inicio, 'fim' => min((24 * 60) - 1, $inicio + $minutos)];
        }

        return null;
    }

    private function minutosHorario(string $hora): ?int
    {
        $hora = substr(trim($hora), 0, 5);

        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora, $matches)) {
            return null;
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }

    private function horasDisponiveisPeriodo(array $escalaData, string $periodoKey, float $horasOcupadas, float $horasEvento): float
    {
        $limitePeriodo = isset($escalaData[$periodoKey])
            ? (float) ($escalaData[$periodoKey]['horas'] ?? 0)
            : 0.0;

        return max(0, min($horasEvento, $limitePeriodo - $horasOcupadas));
    }

    private function compararEventosRelatorio(array $eventoA, array $eventoB): int
    {
        return $this->chaveOrdenacaoEventoRelatorio($eventoA) <=> $this->chaveOrdenacaoEventoRelatorio($eventoB);
    }

    private function chaveOrdenacaoEventoRelatorio(array $evento): array
    {
        $periodo = strtolower((string) ($evento['periodo'] ?? ''));
        $hora = (string) ($evento['hora'] ?? '');

        if (($evento['tipo'] ?? '') === 'calendario') {
            return [0, '00:00', 0];
        }

        if (str_contains($periodo, 'manh')) {
            return [1, $this->horaInicioEventoRelatorio($hora, '00:00'), $this->ordemTipoEventoRelatorio($evento)];
        }

        if (str_contains($periodo, 'tarde')) {
            return [2, $this->horaInicioEventoRelatorio($hora, '12:00'), $this->ordemTipoEventoRelatorio($evento)];
        }

        if (str_contains($periodo, 'noite')) {
            return [3, $this->horaInicioEventoRelatorio($hora, '18:00'), $this->ordemTipoEventoRelatorio($evento)];
        }

        return [4, $this->horaInicioEventoRelatorio($hora, '23:59'), $this->ordemTipoEventoRelatorio($evento)];
    }

    private function horaInicioEventoRelatorio(string $hora, string $padrao): string
    {
        return preg_match('/\d{2}:\d{2}/', $hora, $match) === 1 ? $match[0] : $padrao;
    }

    private function ordemTipoEventoRelatorio(array $evento): int
    {
        return [
            'aula' => 0,
            'curso' => 1,
            'planejamento' => 2,
        ][$evento['tipo'] ?? ''] ?? 3;
    }


    private function calcularCargaHorariaMensal(
        array $escala,
        array $bloqueiosCalendario,
        array $ausencias,
        int $mes,
        int $ano
    ): float {
        $horasPorDiaSemana = [];

        foreach ($escala as $item) {
            $diaKey = $this->normalizarDiaSemana((string) ($item['dia_semana'] ?? ''));

            if ($diaKey !== '') {
                $horasPorDiaSemana[$diaKey] = (float) ($horasPorDiaSemana[$diaKey] ?? 0) + (float) ($item['horas'] ?? 0);
            }
        }

        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));
        $datasBloqueadas = [];
        $datasFerias = [];

        foreach ($bloqueiosCalendario as $bloqueio) {
            if (! empty($bloqueio['hora_inicio']) || ! empty($bloqueio['hora_fim'])) {
                continue;
            }

            $dataAtual = max($inicio, (string) ($bloqueio['data'] ?? ''));
            $dataFim = min($fim, (string) ($bloqueio['data_fim'] ?? $dataAtual));

            while ($dataAtual !== '' && $dataAtual <= $dataFim) {
                $datasBloqueadas[$dataAtual] = true;
                $dataAtual = date('Y-m-d', strtotime($dataAtual . ' +1 day'));
            }
        }

        foreach ($ausencias as $ausencia) {
            if ((string) ($ausencia['tipo'] ?? '') !== 'ferias') {
                continue;
            }

            $dataAtual = max($inicio, (string) ($ausencia['data_inicio'] ?? ''));
            $dataFim = min($fim, (string) ($ausencia['data_fim'] ?? $dataAtual));

            while ($dataAtual !== '' && $dataAtual <= $dataFim) {
                $datasFerias[$dataAtual] = true;
                $dataAtual = date('Y-m-d', strtotime($dataAtual . ' +1 day'));
            }
        }

        $horas = 0.0;

        for ($data = $inicio; $data <= $fim; $data = date('Y-m-d', strtotime($data . ' +1 day'))) {
            if (isset($datasBloqueadas[$data]) || isset($datasFerias[$data])) {
                continue;
            }

            $diaKey = $this->diaSemanaPorData($data);
            $horas += (float) ($horasPorDiaSemana[$diaKey] ?? 0);
        }

        return round($horas, 2);
    }

    private function labelTipoBloqueio(string $tipo): string
    {
        return $tipo === 'Parada Pedagogica' ? 'Parada Pedagógica' : $tipo;
    }

    private function calcularResumoCarga(array $eventosPorData, float $cargaHorariaMensal = 0.0): array
    {
        $horasAula = 0.0;
        $horasPlanejamento = 0.0;
        $horasCurso = 0.0;
        $horasParadaPedagogica = 0.0;
        $horasCompensacao = 0.0;
        $horasBancoHoras = 0.0;

        foreach ($eventosPorData as $eventos) {
            foreach ($eventos as $evento) {
                $horas = (float) ($evento['horas_numero'] ?? 0);
                $horasBancoHoras += (float) ($evento['banco_horas_numero'] ?? 0);

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
                    continue;
                }

                if (($evento['tipo'] ?? '') === 'compensacao') {
                    $horasCompensacao += $horas;
                    continue;
                }


                if (
                    ($evento['tipo'] ?? '') === 'calendario'
                    && ($evento['subtipo_calendario'] ?? '') === 'Parada Pedagogica'
                ) {
                    $horasParadaPedagogica += $horas;
                }
            }
        }

        $totalCalculado = $horasAula + $horasPlanejamento + $horasCurso + $horasParadaPedagogica + $horasCompensacao;

        if ($cargaHorariaMensal > 0) {
            $totalOutrasCategorias = $horasAula + $horasCurso + $horasParadaPedagogica + $horasCompensacao;
            $horasPlanejamento = max(0.0, min($horasPlanejamento, $cargaHorariaMensal - $totalOutrasCategorias));
        }

        $total = $cargaHorariaMensal > 0
            ? $horasAula + $horasPlanejamento + $horasCurso + $horasParadaPedagogica + $horasCompensacao
            : $totalCalculado;
        $percentuais = $this->calcularPercentuaisCarga([
            'aula' => $horasAula,
            'planejamento' => $horasPlanejamento,
            'curso' => $horasCurso,
            'parada_pedagogica' => $horasParadaPedagogica,
            'compensacao' => $horasCompensacao,
        ], $total);

        return [
            'horas_aula' => $horasAula,
            'horas_planejamento' => $horasPlanejamento,
            'horas_curso' => $horasCurso,
            'horas_parada_pedagogica' => $horasParadaPedagogica,
            'horas_compensacao' => $horasCompensacao,
            'horas_banco_horas' => $horasBancoHoras,
            'total_horas' => $total,
            'percentual_aula' => $percentuais['aula'],
            'percentual_planejamento' => $percentuais['planejamento'],
            'percentual_curso' => $percentuais['curso'],
            'percentual_parada_pedagogica' => $percentuais['parada_pedagogica'],
            'percentual_compensacao' => $percentuais['compensacao'],
        ];
    }

    private function calcularPercentuaisCarga(array $horasPorCategoria, float $total): array
    {
        $percentuais = [];

        foreach ($horasPorCategoria as $categoria => $horas) {
            $percentuais[$categoria] = $total > 0 ? round(((float) $horas / $total) * 100, 1) : 0.0;
        }

        if ($total <= 0) {
            return $percentuais;
        }

        $categoriaAjuste = null;

        foreach ($horasPorCategoria as $categoria => $horas) {
            if ((float) $horas > 0) {
                $categoriaAjuste = $categoria;
            }
        }

        if ($categoriaAjuste !== null) {
            $somaSemAjuste = 0.0;

            foreach ($percentuais as $categoria => $percentual) {
                if ($categoria !== $categoriaAjuste) {
                    $somaSemAjuste += $percentual;
                }
            }

            $percentuais[$categoriaAjuste] = max(0.0, round(100 - $somaSemAjuste, 1));
        }

        return $percentuais;
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
            'manha' => 'Manhã',
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






