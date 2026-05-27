<?php

require_once __DIR__ . '/../models/QuadroHorario.php';
require_once __DIR__ . '/../models/CalendarioBloqueio.php';
require_once __DIR__ . '/../models/EducacaoCorporativa.php';
require_once __DIR__ . '/../core/AccessControl.php';

class QuadroHorarioController
{
    private QuadroHorario $quadroModel;
    private CalendarioBloqueio $bloqueioModel;
    private EducacaoCorporativa $educacaoModel;

    public function __construct()
    {
        $this->quadroModel = new QuadroHorario();
        $this->bloqueioModel = new CalendarioBloqueio();
        $this->educacaoModel = new EducacaoCorporativa();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $mes = (int) ($_GET['mes'] ?? date('n'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));
        $cursoOfertaId = (int) ($_GET['curso_oferta_id'] ?? 0);
        $escopo = (new AccessControl())->escopo();

        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }

        if ($ano < 2000 || $ano > 2100) {
            $ano = (int) date('Y');
        }

        $ofertas = $this->quadroModel->listarOfertas($escopo);
        $ofertaSelecionada = $cursoOfertaId > 0 ? $this->quadroModel->buscarOferta($cursoOfertaId) : null;

        $aulas = $ofertaSelecionada ? $this->quadroModel->listarAulasMensais($cursoOfertaId, $mes, $ano) : [];
        $unidadesCurriculares = $ofertaSelecionada ? $this->quadroModel->listarUnidadesCurriculares($cursoOfertaId, $escopo) : [];
        $salas = $ofertaSelecionada ? $this->quadroModel->listarSalas() : [];
        $docentes = $ofertaSelecionada ? $this->quadroModel->listarDocentes() : [];
        $bloqueiosPorData = $ofertaSelecionada ? $this->montarBloqueiosPorData($mes, $ano, $ofertaSelecionada) : [];
        $disponibilidadeMensal = $this->montarDisponibilidadeMensal($salas, $docentes, $ofertaSelecionada, $mes, $ano);
        $salasDisponiveisPorData = $disponibilidadeMensal['salas'];
        $docentesDisponiveisPorData = $disponibilidadeMensal['docentes'];
        $docentesDisponiveisPorBloco = $disponibilidadeMensal['docentes_por_bloco'];
        $blocosOferta = $disponibilidadeMensal['blocos'];
        $disponibilidadeEdicao = $this->montarDisponibilidadeEdicao($aulas, $salas, $docentes);
        $salasDisponiveisPorAula = $disponibilidadeEdicao['salas'];
        $docentesDisponiveisPorAula = $disponibilidadeEdicao['docentes'];

        require_once __DIR__ . '/../views/dashboard/quadro_horario.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $queryBase = $this->queryRetorno($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha os campos obrigatorios da aula.'));
        }

        $erroDia = $this->validarDiaPermitido($dados);

        if ($erroDia !== null) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode($erroDia));
        }

        $blocos = $this->montarBlocos($dados['hora_inicio'], $dados['hora_fim'], $dados['divisao_por_hora'] === 1);

        if (empty($blocos)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Horario invalido para a aula.'));
        }

        if ($dados['divisao_por_hora'] !== 1 && ! $this->quadroModel->unidadePertenceOferta($dados['unidade_curricular_id'], $dados['curso_oferta_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('A UC selecionada nao pertence ao curso da turma.'));
        }

        $erroBlocos = $this->aplicarDadosPorBloco($dados, $blocos);

        if ($erroBlocos !== null) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode($erroBlocos));
        }

        $erroDocenteUc = $this->validarDocentesPorUc($dados, $blocos);

        if ($erroDocenteUc !== null) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode($erroDocenteUc));
        }

        $conflito = $this->validarConflitos($dados, $blocos);

        if ($conflito !== null) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode($conflito));
        }

        if ($this->quadroModel->salvarAulas($dados, $blocos)) {
            $this->redirecionar('/mapa_de_sala/public/?page=quadro_horario&curso_oferta_id=' . $dados['curso_oferta_id'] . '&mes=' . $dados['mes'] . '&ano=' . $dados['ano']);
        }

        $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar a aula.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);
        $queryBase = $this->queryRetorno($dados);

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        $erroDia = $this->validarDiaPermitido($dados);

        if ($erroDia !== null) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode($erroDia));
        }

        if ($dados['divisao_por_hora'] === 1) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('A edicao de uma aula existente nao divide em novos blocos. Cadastre uma nova aula com divisao por hora.'));
        }

        if (! $this->quadroModel->unidadePertenceOferta($dados['unidade_curricular_id'], $dados['curso_oferta_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('A UC selecionada nao pertence ao curso da turma.'));
        }

        $blocos = $this->montarBlocos($dados['hora_inicio'], $dados['hora_fim'], false);
        $erroDocenteUc = $this->validarDocentesPorUc($dados, $blocos);

        if ($erroDocenteUc !== null) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode($erroDocenteUc));
        }

        $conflito = $this->validarConflitos($dados, $blocos, $dados['id']);

        if ($conflito !== null) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode($conflito));
        }

        if ($this->quadroModel->atualizarAula($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=quadro_horario&curso_oferta_id=' . $dados['curso_oferta_id'] . '&mes=' . $dados['mes'] . '&ano=' . $dados['ano']);
        }

        $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar a aula.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);
        $cursoOfertaId = (int) ($_POST['curso_oferta_id'] ?? 0);
        $mes = (int) ($_POST['mes'] ?? date('n'));
        $ano = (int) ($_POST['ano'] ?? date('Y'));

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=quadro_horario&tipo=erro&msg=' . urlencode('Aula invalida.'));
        }

        if ($this->quadroModel->excluirAula($id)) {
            $this->redirecionar('/mapa_de_sala/public/?page=quadro_horario&curso_oferta_id=' . $cursoOfertaId . '&mes=' . $mes . '&ano=' . $ano);
        }

        $this->redirecionar('/mapa_de_sala/public/?page=quadro_horario&curso_oferta_id=' . $cursoOfertaId . '&mes=' . $mes . '&ano=' . $ano . '&tipo=erro&msg=' . urlencode('Nao foi possivel excluir a aula.'));
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            $this->redirecionar('/mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        }
    }

    private function obterDadosPost(): array
    {
        $duplaDocencia = isset($_POST['dupla_docencia']) ? 1 : 0;
        $docentePrincipalId = (int) ($_POST['docente_principal_id'] ?? 0);
        $docente2Id = (int) ($_POST['docente_2_id'] ?? 0);
        $ucsPorBloco = is_array($_POST['ucs_por_bloco'] ?? null) ? $_POST['ucs_por_bloco'] : [];
        $docentesPorBloco = is_array($_POST['docentes_por_bloco'] ?? null) ? $_POST['docentes_por_bloco'] : [];
        $docentes = [];

        if ($docentePrincipalId > 0) {
            $docentes[] = $docentePrincipalId;
        }

        if ($duplaDocencia && $docente2Id > 0 && $docente2Id !== $docentePrincipalId) {
            $docentes[] = $docente2Id;
        }

        $dataAula = trim($_POST['data_aula'] ?? '');
        $mes = $dataAula !== '' ? (int) date('n', strtotime($dataAula)) : (int) ($_POST['mes'] ?? date('n'));
        $ano = $dataAula !== '' ? (int) date('Y', strtotime($dataAula)) : (int) ($_POST['ano'] ?? date('Y'));

        return [
            'curso_oferta_id'       => (int) ($_POST['curso_oferta_id'] ?? 0),
            'unidade_curricular_id' => (int) ($_POST['unidade_curricular_id'] ?? 0),
            'sala_id'               => (int) ($_POST['sala_id'] ?? 0),
            'data_aula'             => $dataAula,
            'hora_inicio'           => trim($_POST['hora_inicio'] ?? ''),
            'hora_fim'              => trim($_POST['hora_fim'] ?? ''),
            'divisao_por_hora'      => isset($_POST['divisao_por_hora']) ? 1 : 0,
            'dupla_docencia'        => $duplaDocencia,
            'visita_tecnica'        => isset($_POST['visita_tecnica']) ? 1 : 0,
            'ead_assincrona'        => isset($_POST['ead_assincrona']) ? 1 : 0,
            'troca_escala'          => isset($_POST['troca_escala']) ? 1 : 0,
            'docente_principal_id'  => $docentePrincipalId,
            'docente_2_id'          => $docente2Id,
            'docentes'              => $docentes,
            'ucs_por_bloco'         => $ucsPorBloco,
            'docentes_por_bloco'    => $docentesPorBloco,
            'status'                => trim($_POST['status'] ?? 'Ativa'),
            'observacoes'           => trim($_POST['observacoes'] ?? ''),
            'mes'                   => $mes,
            'ano'                   => $ano,
        ];
    }

    private function validarDados(array $dados): bool
    {
        if (
            $dados['curso_oferta_id'] <= 0 ||
            $dados['data_aula'] === '' ||
            $dados['hora_inicio'] === '' ||
            $dados['hora_fim'] === '' ||
            ! in_array($dados['status'], ['Ativa', 'Cancelada'], true)
        ) {
            return false;
        }

        if ($dados['sala_id'] <= 0 && $dados['ead_assincrona'] !== 1 && $dados['visita_tecnica'] !== 1) {
            return false;
        }

        if ($dados['divisao_por_hora'] !== 1 && $dados['unidade_curricular_id'] <= 0) {
            return false;
        }

        if ($dados['dupla_docencia'] === 1 && ($dados['docente_principal_id'] <= 0 || $dados['docente_2_id'] <= 0)) {
            return false;
        }

        if ($dados['dupla_docencia'] === 1 && $dados['docente_2_id'] === $dados['docente_principal_id']) {
            return false;
        }

        return strtotime($dados['hora_inicio']) < strtotime($dados['hora_fim']);
    }

    private function validarDiaPermitido(array $dados): ?string
    {
        $timestamp = strtotime($dados['data_aula']);

        if ($timestamp === false) {
            return 'Data da aula invalida.';
        }

        $oferta = $this->quadroModel->buscarOferta((int) $dados['curso_oferta_id']);

        return $this->mensagemDiaBloqueado($dados['data_aula'], $oferta);
    }

    private function mensagemDiaBloqueado(string $dataAula, ?array $oferta): ?string
    {
        $timestamp = strtotime($dataAula);

        if ($timestamp === false) {
            return 'Data da aula invalida.';
        }

        $diaSemana = (int) date('w', $timestamp);

        if ($diaSemana === 0) {
            return 'Domingo nao permite lancamento de aula.';
        }

        if (! $this->turmaTemAulaNoDia($oferta, $diaSemana)) {
            return 'Esta turma nao possui aula configurada para este dia da semana.';
        }

        $periodo = strtolower($this->periodoPorHorario(
            (string) ($oferta['hora_inicio'] ?? ''),
            (string) ($oferta['hora_fim'] ?? '')
        ));

        if ($diaSemana === 6 && in_array($periodo, ['tarde', 'noite'], true)) {
            return 'Turmas dos periodos Tarde e Noite nao permitem lancamento no sabado.';
        }

        $bloqueio = $this->bloqueioModel->buscarAtivoPorData($dataAula, $oferta);

        if ($bloqueio) {
            return 'Data bloqueada: ' . ($bloqueio['titulo'] ?? 'calendario da unidade') . '.';
        }

        return null;
    }

    private function turmaTemAulaNoDia(?array $oferta, int $diaSemana): bool
    {
        if (! $oferta) {
            return false;
        }

        $campos = [
            1 => 'aula_segunda',
            2 => 'aula_terca',
            3 => 'aula_quarta',
            4 => 'aula_quinta',
            5 => 'aula_sexta',
            6 => 'aula_sabado',
        ];

        $campo = $campos[$diaSemana] ?? '';

        return $campo !== '' && (int) ($oferta[$campo] ?? 0) === 1;
    }

    private function montarBloqueiosPorData(int $mes, int $ano, ?array $oferta): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));
        $bloqueios = $this->bloqueioModel->listarPorPeriodo($inicio, $fim);
        $porData = [];

        foreach ($bloqueios as $bloqueio) {
            if (! $this->bloqueioModel->bloqueioAplicaTurma($bloqueio, $oferta)) {
                continue;
            }

            $dataInicioBloqueio = (string) ($bloqueio['data'] ?? '');
            $dataFimBloqueio = (string) ($bloqueio['data_fim'] ?? $dataInicioBloqueio);

            if ($dataInicioBloqueio === '') {
                continue;
            }

            $dataAtual = max($inicio, $dataInicioBloqueio);
            $dataLimite = min($fim, $dataFimBloqueio ?: $dataInicioBloqueio);

            while (strtotime($dataAtual) !== false && strtotime($dataAtual) <= strtotime($dataLimite)) {
                $porData[$dataAtual][] = $bloqueio;
                $dataAtual = date('Y-m-d', strtotime($dataAtual . ' +1 day'));
            }
        }

        return $porData;
    }

    private function periodoPorHorario(string $horaInicio, string $horaFim): string
    {
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return '';
        }

        $periodos = [];
        $faixas = [
            'manha' => ['00:00', '12:00'],
            'tarde' => ['12:00', '18:00'],
            'noite' => ['18:00', '23:59'],
        ];

        foreach ($faixas as $periodo => [$inicioFaixa, $fimFaixa]) {
            $base = date('Y-m-d ', $inicio);
            $faixaInicio = strtotime($base . $inicioFaixa);
            $faixaFim = strtotime($base . $fimFaixa);

            if ($faixaInicio !== false && $faixaFim !== false && $inicio < $faixaFim && $fim > $faixaInicio) {
                $periodos[] = $periodo;
            }
        }

        return count($periodos) > 1 ? 'integral' : ($periodos[0] ?? '');
    }

    private function montarDisponibilidadeMensal(array $salas, array $docentes, ?array $oferta, int $mes, int $ano): array
    {
        $salasPorData = [];
        $docentesPorData = [];
        $docentesPorBloco = [];
        $blocos = [];

        if (! $oferta || empty($oferta['hora_inicio']) || empty($oferta['hora_fim'])) {
            return [
                'salas' => $salasPorData,
                'docentes' => $docentesPorData,
                'docentes_por_bloco' => $docentesPorBloco,
                'blocos' => $blocos,
            ];
        }

        $primeiroDia = sprintf('%04d-%02d-01', $ano, $mes);
        $diasNoMes = (int) date('t', strtotime($primeiroDia));
        $horaInicio = substr((string) $oferta['hora_inicio'], 0, 5);
        $horaFim = substr((string) $oferta['hora_fim'], 0, 5);
        $blocos = $this->montarBlocos($horaInicio, $horaFim, true);

        for ($dia = 1; $dia <= $diasNoMes; $dia++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);

            if ($this->mensagemDiaBloqueado($data, $oferta) !== null) {
                $salasPorData[$data] = [];
                $docentesPorData[$data] = [];
                $docentesPorBloco[$data] = [];
                continue;
            }

            $salasPorData[$data] = array_values(array_filter($salas, function (array $sala) use ($data, $horaInicio, $horaFim): bool {
                return ! $this->quadroModel->encontrarConflitoSala((int) $sala['id'], $data, $horaInicio, $horaFim);
            }));

            $docentesPorData[$data] = $this->docentesDisponiveisParaHorario($docentes, $data, $horaInicio, $horaFim);

            foreach ($blocos as $bloco) {
                $chaveBloco = $this->chaveBloco($bloco);
                $docentesPorBloco[$data][$chaveBloco] = $this->docentesDisponiveisParaHorario($docentes, $data, $bloco['inicio'], $bloco['fim']);
            }
        }

        return [
            'salas' => $salasPorData,
            'docentes' => $docentesPorData,
            'docentes_por_bloco' => $docentesPorBloco,
            'blocos' => $blocos,
        ];
    }

    private function montarDisponibilidadeEdicao(array $aulas, array $salas, array $docentes): array
    {
        $salasPorAula = [];
        $docentesPorAula = [];

        foreach ($aulas as $aula) {
            $aulaId = (int) $aula['id'];
            $data = (string) $aula['data_aula'];
            $horaInicio = substr((string) $aula['hora_inicio'], 0, 5);
            $horaFim = substr((string) $aula['hora_fim'], 0, 5);

            $salasPorAula[$aulaId] = array_values(array_filter($salas, function (array $sala) use ($data, $horaInicio, $horaFim, $aulaId): bool {
                return ! $this->quadroModel->encontrarConflitoSala((int) $sala['id'], $data, $horaInicio, $horaFim, $aulaId);
            }));

            $docentesPorAula[$aulaId] = $this->docentesDisponiveisParaHorario($docentes, $data, $horaInicio, $horaFim, $aulaId);
        }

        return ['salas' => $salasPorAula, 'docentes' => $docentesPorAula];
    }

    private function docentesDisponiveisParaHorario(array $docentes, string $data, string $horaInicio, string $horaFim, ?int $ignorarId = null): array
    {
        $disponiveis = [];

        foreach ($docentes as $docente) {
            $docenteId = (int) ($docente['id'] ?? 0);

            if ($docenteId <= 0 || ! $this->docenteDisponivel($docenteId, $data, $horaInicio, $horaFim, $ignorarId, false)) {
                continue;
            }

            $docente['tem_escala'] = $this->quadroModel->docenteTemEscala($docenteId, $data, $horaInicio, $horaFim) ? 1 : 0;
            $disponiveis[] = $docente;
        }

        return $disponiveis;
    }

    private function montarBlocos(string $horaInicio, string $horaFim, bool $dividirPorHora): array
    {
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $inicio >= $fim) {
            return [];
        }

        if (! $dividirPorHora) {
            return [[
                'inicio' => date('H:i:s', $inicio),
                'fim'    => date('H:i:s', $fim),
            ]];
        }

        $blocos = [];
        $cursor = $inicio;

        while ($cursor < $fim) {
            $proximo = min(strtotime('+1 hour', $cursor), $fim);
            $blocos[] = [
                'inicio' => date('H:i:s', $cursor),
                'fim'    => date('H:i:s', $proximo),
            ];
            $cursor = $proximo;
        }

        return $blocos;
    }

    private function validarConflitos(array $dados, array $blocos, ?int $ignorarId = null): ?string
    {
        foreach ($blocos as $bloco) {
            $conflitoTurma = $this->quadroModel->encontrarConflitoTurma(
                $dados['curso_oferta_id'],
                $dados['data_aula'],
                $bloco['inicio'],
                $bloco['fim'],
                $ignorarId
            );

            if ($conflitoTurma) {
                return 'A turma ja possui aula neste dia e horario.';
            }

            $conflitoSala = $this->quadroModel->encontrarConflitoSala(
                $dados['sala_id'],
                $dados['data_aula'],
                $bloco['inicio'],
                $bloco['fim'],
                $ignorarId
            );

            if ($conflitoSala) {
                return 'A sala ja esta ocupada neste dia e horario.';
            }

            foreach (($bloco['docentes'] ?? $dados['docentes']) as $docenteId) {
                $exigirEscala = (int) ($dados['troca_escala'] ?? 0) !== 1;

                if (! $this->docenteDisponivel((int) $docenteId, $dados['data_aula'], $bloco['inicio'], $bloco['fim'], $ignorarId, $exigirEscala)) {
                    return 'Um dos docentes nao esta disponivel neste dia ou periodo.';
                }
            }
        }

        return null;
    }

    private function docenteDisponivel(int $docenteId, string $data, string $horaInicio, string $horaFim, ?int $ignorarId = null, bool $exigirEscala = true): bool
    {
        return (! $exigirEscala || $this->quadroModel->docenteTemEscala($docenteId, $data, $horaInicio, $horaFim))
            && ! $this->educacaoModel->docenteEmCurso($docenteId, $data)
            && ! $this->quadroModel->encontrarConflitoDocente($docenteId, $data, $horaInicio, $horaFim, $ignorarId);
    }

    private function validarDocentesPorUc(array $dados, array $blocos): ?string
    {
        foreach ($blocos as $bloco) {
            $ucId = (int) ($bloco['unidade_curricular_id'] ?? $dados['unidade_curricular_id']);

            foreach (($bloco['docentes'] ?? $dados['docentes']) as $docenteId) {
                if (! $this->quadroModel->docenteVinculadoUc((int) $docenteId, $ucId)) {
                    return 'Um dos professores nao esta vinculado a UC selecionada.';
                }
            }
        }

        return null;
    }

    private function aplicarDadosPorBloco(array &$dados, array &$blocos): ?string
    {
        if ($dados['divisao_por_hora'] !== 1) {
            return null;
        }

        $docentesSelecionados = [];

        foreach ($blocos as &$bloco) {
            $chave = $this->chaveBloco($bloco);
            $unidadeCurricularId = (int) ($dados['ucs_por_bloco'][$chave] ?? 0);
            $docenteId = (int) ($dados['docentes_por_bloco'][$chave] ?? 0);

            if ($unidadeCurricularId <= 0) {
                return 'Informe a UC de cada horario da divisao por hora.';
            }

            if (! $this->quadroModel->unidadePertenceOferta($unidadeCurricularId, $dados['curso_oferta_id'])) {
                return 'Uma das UCs selecionadas nao pertence ao curso da turma.';
            }

            $bloco['unidade_curricular_id'] = $unidadeCurricularId;
            $bloco['docentes'] = $docenteId > 0 ? [$docenteId] : [];

            if ($docenteId > 0) {
                $docentesSelecionados[] = $docenteId;
            }
        }

        unset($bloco);

        $dados['docentes'] = array_values(array_unique($docentesSelecionados));
        $dados['dupla_docencia'] = 0;
        $dados['visita_tecnica'] = (int) ($dados['visita_tecnica'] ?? 0);
        $dados['ead_assincrona'] = (int) ($dados['ead_assincrona'] ?? 0);
        $dados['docente_principal_id'] = $dados['docentes'][0] ?? 0;
        $dados['docente_2_id'] = 0;

        return null;
    }

    private function chaveBloco(array $bloco): string
    {
        return substr((string) $bloco['inicio'], 0, 5) . '|' . substr((string) $bloco['fim'], 0, 5);
    }

    private function queryRetorno(array $dados): string
    {
        return http_build_query([
            'page'            => 'quadro_horario',
            'curso_oferta_id' => $dados['curso_oferta_id'],
            'mes'             => $dados['mes'],
            'ano'             => $dados['ano'],
            'data_aula'       => $dados['data_aula'],
        ]);
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
