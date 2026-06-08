<?php

require_once __DIR__ . '/../core/Database.php';

class Curso
{
    private PDO $conn;
    private string $table = 'cursos_ofertas';

    public function __construct()
    {
        $database   = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $status = 'todos', array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT
                co.id,
                co.curso_modelo_id,
                co.nome,
                co.codigo_oferta,
                co.integral,
                co.hora_inicio,
                co.hora_fim,
                co.hora_inicio_tarde,
                co.hora_fim_tarde,
                co.participa_parada_pedagogica,
                co.participa_recesso_escolar,
                co.aula_segunda,
                co.aula_terca,
                co.aula_quarta,
                co.aula_quinta,
                co.aula_sexta,
                co.aula_sabado,
                co.status,
                co.descricao,
                co.criado_em,
                co.atualizado_em,
                cm.area_id AS curso_area_id,
                cm.nome AS curso_modelo_nome
            FROM {$this->table} co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (co.nome LIKE :busca OR co.codigo_oferta LIKE :busca OR co.descricao LIKE :busca OR cm.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND co.status = :status";
            $params[':status'] = $status;
        }

        $this->aplicarEscopo($sql, $params, $escopo);

        $sql .= " ORDER BY co.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $busca = '', string $status = 'todos', array $escopo = ['tipo' => 'todos', 'ids' => []]): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM {$this->table} co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE 1 = 1
        ";
        $params = [];

        if ($busca !== '') {
            $sql .= " AND (co.nome LIKE :busca OR co.codigo_oferta LIKE :busca OR co.descricao LIKE :busca OR cm.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND co.status = :status";
            $params[':status'] = $status;
        }

        $this->aplicarEscopo($sql, $params, $escopo);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                curso_modelo_id,
                nome,
                codigo_oferta,
                integral,
                hora_inicio,
                hora_fim,
                hora_inicio_tarde,
                hora_fim_tarde,
                participa_parada_pedagogica,
                participa_recesso_escolar,
                aula_segunda,
                aula_terca,
                aula_quarta,
                aula_quinta,
                aula_sexta,
                aula_sabado,
                status,
                descricao
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    public function codigoOfertaExiste(string $codigoOferta, ?int $ignorarId = null): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE codigo_oferta = :codigo_oferta";
        $params = [':codigo_oferta' => $codigoOferta];

        if ($ignorarId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarCursoModelos(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT id, nome, carga_horaria_total, status
            FROM curso_modelos cm
            WHERE status = 'Ativo'
        ";

        $params = [];
        $this->aplicarEscopoCursoModelo($sql, $params, $escopo);

        $sql .= " ORDER BY nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cursoModeloExiste(int $cursoModeloId, array $escopo = ['tipo' => 'todos', 'ids' => []]): bool
    {
        $sql = "SELECT id FROM curso_modelos cm WHERE id = :id";
        $params = [':id' => $cursoModeloId];
        $this->aplicarEscopoCursoModelo($sql, $params, $escopo);
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function turmaPertenceEscopo(int $turmaId, array $escopo = ['tipo' => 'todos', 'ids' => []]): bool
    {
        $sql = "
            SELECT co.id
            FROM {$this->table} co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE co.id = :turma_id
        ";

        $params = [':turma_id' => $turmaId];
        $this->aplicarEscopo($sql, $params, $escopo);
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarSalasAtivas(): array
    {
        $sql = "
            SELECT id, nome, tipo
            FROM salas
            WHERE status IN ('ativa', 'livre', 'uso')
            ORDER BY nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarUcsPorCursoModelos(array $cursoModeloIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $cursoModeloIds))));

        if (empty($ids)) {
            return [];
        }

        foreach ($ids as $cursoModeloId) {
            if ($this->cursoModeloSemUc($cursoModeloId)) {
                $this->garantirUcPadraoCursoSemUc($cursoModeloId);
            }
        }

        $placeholders = [];
        $params = [];

        foreach ($ids as $index => $id) {
            $placeholder = ':curso_modelo_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql = "
            SELECT id, curso_modelo_id, codigo, nome, carga_horaria
            FROM unidades_curriculares
            WHERE curso_modelo_id IN (" . implode(',', $placeholders) . ")
              AND status = 'Ativa'
            ORDER BY curso_modelo_id ASC, CHAR_LENGTH(codigo) ASC, codigo ASC, nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $ucs = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $uc) {
            $ucs[(int) $uc['curso_modelo_id']][] = $uc;
        }

        return $ucs;
    }

    public function listarDocentesAtivos(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT
                d.id,
                MIN(COALESCE(da.area_id, a.id)) AS area_id,
                GROUP_CONCAT(DISTINCT COALESCE(da.area_id, a.id) ORDER BY COALESCE(da.area_id, a.id) SEPARATOR ',') AS area_ids,
                u.nome,
                u.email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN areas a ON a.nome = d.area_atuacao
            LEFT JOIN docente_areas da ON da.docente_id = d.id
            WHERE d.status = 'Ativo'
              AND u.status = 'Ativo'
        ";
        $params = [];

        $this->aplicarEscopoDocente($sql, $params, $escopo);

        $sql .= " GROUP BY d.id, u.nome, u.email ORDER BY u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function gerarQuadroCompleto(int $turmaId, string $dataInicio, ?int $salaPreferencialId = null, ?int $docentePreferencialId = null): array
    {
        $turma = $this->buscarPorId($turmaId);

        if (! $turma || empty($turma['curso_modelo_id']) || empty($turma['hora_inicio']) || empty($turma['hora_fim'])) {
            return ['sucesso' => false, 'mensagem' => 'Turma sem curso ou horario configurado.'];
        }

        $ucs = $this->listarUcsDaTurma((int) $turma['curso_modelo_id']);

        if (empty($ucs)) {
            return ['sucesso' => false, 'mensagem' => 'O curso da turma nao possui UCs ativas cadastradas.'];
        }

        $diaInicio = strtotime($dataInicio);

        if ($diaInicio === false) {
            return ['sucesso' => false, 'mensagem' => 'Data inicial invalida.'];
        }

        $blocosHorario = $this->blocosHorarioTurma($turma);
        $minutosDia = array_sum(array_map(
            fn(array $bloco): int => $this->minutosEntre($bloco['inicio'], $bloco['fim']),
            $blocosHorario
        ));

        if (empty($blocosHorario) || $minutosDia <= 0) {
            return ['sucesso' => false, 'mensagem' => 'Horario da turma invalido.'];
        }

        $diasPermitidos = $this->diasPermitidosTurma($turma);

        if (empty($diasPermitidos)) {
            return ['sucesso' => false, 'mensagem' => 'Marque ao menos um dia de aula na turma.'];
        }

        $minutosLancados = $this->minutosLancadosPorUc($turmaId);
        $minutosPendentes = array_sum(array_map(
            fn(array $uc): int => $this->minutosPendentesUc($uc, $minutosLancados),
            $ucs
        ));

        if ($minutosPendentes <= 0) {
            return [
                'sucesso' => true,
                'mensagem' => 'O quadro horario ja contempla toda a carga horaria das UCs desta turma.',
            ];
        }

        $diasAulaNecessarios = (int) ceil($minutosPendentes / $minutosDia);
        $limiteDiasCalendario = max(
            366,
            ((int) ceil($diasAulaNecessarios / max(1, count($diasPermitidos))) * 7) + 366
        );

        try {
            $this->conn->beginTransaction();

            $dataAtual = date('Y-m-d', $diaInicio);
            $ucIndex = 0;
            $minutosRestantesUc = 0;
            $aulasCriadas = 0;
            $blocosSemSala = 0;
            $blocosSemDocente = 0;
            $guard = 0;

            $this->avancarParaProximaUcPendente($ucs, $minutosLancados, $ucIndex, $minutosRestantesUc);

            while ($ucIndex < count($ucs) && $guard < $limiteDiasCalendario) {
                $guard++;

                if (
                    ! in_array((int) date('N', strtotime($dataAtual)), $diasPermitidos, true) ||
                    $this->turmaTemAulaEmAlgumBloco($turmaId, $dataAtual, $blocosHorario)
                ) {
                    $dataAtual = date('Y-m-d', strtotime($dataAtual . ' +1 day'));
                    continue;
                }

                foreach ($blocosHorario as $blocoHorario) {
                    if ($ucIndex >= count($ucs)) {
                        break;
                    }

                    $horaInicioBloco = $blocoHorario['inicio'];
                    $horaFimBloco = $blocoHorario['fim'];
                    $intervaloDisponivel = $this->intervaloDisponivelPorBloqueios($dataAtual, $turma, $horaInicioBloco, $horaFimBloco);

                    if ($this->turmaTemAulaNoDia($turmaId, $dataAtual, $horaInicioBloco, $horaFimBloco)) {
                        continue;
                    }

                    if ($intervaloDisponivel === null) {
                        continue;
                    }

                    $horaInicioBloco = $intervaloDisponivel['inicio'];
                    $horaFimBloco = $intervaloDisponivel['fim'];

                    $salaBlocoId = $this->salaDisponivelNoDia($salaPreferencialId, $dataAtual, $horaInicioBloco, $horaFimBloco, $turmaId)
                        ? $salaPreferencialId
                        : null;

                    if ($salaPreferencialId !== null && $salaBlocoId === null) {
                        $blocosSemSala++;
                    }

                    $cursor = $horaInicioBloco;
                    $minutosRestantesBloco = $this->minutosEntre($horaInicioBloco, $horaFimBloco);

                    while ($minutosRestantesBloco > 0 && $ucIndex < count($ucs)) {
                        if ($minutosRestantesUc <= 0) {
                            $ucIndex++;
                            $this->avancarParaProximaUcPendente($ucs, $minutosLancados, $ucIndex, $minutosRestantesUc);

                            if ($ucIndex >= count($ucs)) {
                                break;
                            }
                        }

                        $minutosBloco = min($minutosRestantesUc, $minutosRestantesBloco);
                        $fimBloco = $this->somarMinutos($cursor, $minutosBloco);
                        $ucAtualId = (int) $ucs[$ucIndex]['id'];
                        $docenteBlocoId = $this->docenteDisponivelParaAula($docentePreferencialId, $ucAtualId, $dataAtual, $cursor, $fimBloco)
                            ? $docentePreferencialId
                            : null;

                        if ($docentePreferencialId !== null && $docenteBlocoId === null) {
                            $blocosSemDocente++;
                        }

                        $this->inserirAulaGerada([
                            'curso_oferta_id' => $turmaId,
                            'unidade_curricular_id' => $ucAtualId,
                            'sala_id' => $salaBlocoId,
                            'data_aula' => $dataAtual,
                            'hora_inicio' => $cursor,
                            'hora_fim' => $fimBloco,
                            'divisao_por_hora' => $minutosBloco < $minutosRestantesBloco ? 1 : 0,
                            'docente_id' => $docenteBlocoId,
                        ]);

                        $aulasCriadas++;
                        $cursor = $fimBloco;
                        $minutosRestantesBloco -= $minutosBloco;
                        $minutosRestantesUc -= $minutosBloco;
                    }
                }

                $dataAtual = date('Y-m-d', strtotime($dataAtual . ' +1 day'));
            }

            if ($ucIndex < count($ucs)) {
                $this->conn->rollBack();
                $ucAtual = $ucs[$ucIndex] ?? [];
                $horasRestantesUc = round($minutosRestantesUc / 60, 2);

                return [
                    'sucesso' => false,
                    'mensagem' => 'Nao foi possivel concluir a geracao do quadro. A geracao parou na ' .
                        (($ucAtual['codigo'] ?? 'UC') . ' - ' . ($ucAtual['nome'] ?? '')) .
                        ', faltando aproximadamente ' . $horasRestantesUc . 'h desta UC. Verifique se a turma possui dias de aula suficientes, horario valido e se o calendario nao possui muitos bloqueios.',
                ];
            }

            $this->conn->commit();

            return [
                'sucesso' => true,
                'mensagem' => $aulasCriadas . ' aula(s) geradas para completar o restante do curso. Blocos sem sala: ' . $blocosSemSala . '. Blocos sem docente: ' . $blocosSemDocente . '. Previsao de termino: ' . date('d/m/Y', strtotime($dataAtual . ' -1 day')) . '.',
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel gerar o quadro horario: ' . $e->getMessage()];
        }
    }

    public function gerarQuadroPorUcDia(
        int $turmaId,
        int $unidadeCurricularId,
        array $diasSemana,
        string $dataInicio,
        string $dataFim = '',
        ?string $turnoGeracao = null,
        ?int $salaPreferencialId = null,
        ?int $docentePreferencialId = null
    ): array {
        $turma = $this->buscarPorId($turmaId);

        if (! $turma || empty($turma['curso_modelo_id']) || empty($turma['hora_inicio']) || empty($turma['hora_fim'])) {
            return ['sucesso' => false, 'mensagem' => 'Turma sem curso ou horario configurado.'];
        }

        $diasSemana = array_values(array_unique(array_filter(array_map('intval', $diasSemana), static function ($dia): bool {
            return $dia >= 1 && $dia <= 6;
        })));
        sort($diasSemana);

        if (empty($diasSemana)) {
            return ['sucesso' => false, 'mensagem' => 'Selecione pelo menos um dia da semana valido.'];
        }

        $uc = $this->buscarUcDaTurma((int) $turma['curso_modelo_id'], $unidadeCurricularId);

        if (! $uc) {
            return ['sucesso' => false, 'mensagem' => 'A UC selecionada nao pertence ao curso desta turma.'];
        }

        $codigoUc = strtoupper(str_replace(['-', ' '], '', trim((string) ($uc['codigo'] ?? ''))));
        $uc12Aprendizagem = $codigoUc === 'UC12'
            && strcasecmp(trim((string) ($uc['area_nome'] ?? '')), 'Aprendizagem') === 0;
        $diasPermitidosTurma = $this->diasPermitidosTurma($turma);
        $diasInvalidosTurma = array_diff($diasSemana, $diasPermitidosTurma);

        if (! $uc12Aprendizagem && ! empty($diasInvalidosTurma)) {
            return ['sucesso' => false, 'mensagem' => 'Um ou mais dias selecionados nao estao marcados como dia de aula desta turma.'];
        }

        $diaInicio = strtotime($dataInicio);

        if ($diaInicio === false) {
            return ['sucesso' => false, 'mensagem' => 'Data inicial invalida.'];
        }

        $temDataFim = trim($dataFim) !== '';
        $diaFim = $temDataFim ? strtotime($dataFim) : null;

        if ($temDataFim && ($diaFim === false || $diaFim < $diaInicio)) {
            return ['sucesso' => false, 'mensagem' => 'Data final invalida.'];
        }

        if ((int) ($turma['integral'] ?? 0) === 1 && ! in_array($turnoGeracao, ['primeiro', 'segundo'], true)) {
            return ['sucesso' => false, 'mensagem' => 'Selecione o turno em que a UC sera gerada.'];
        }

        $blocosHorario = $this->blocosHorarioTurma($turma, $turnoGeracao);

        if (empty($blocosHorario)) {
            return ['sucesso' => false, 'mensagem' => 'Horario da turma invalido.'];
        }

        $minutosLancados = $this->minutosLancadosPorUc($turmaId);
        $minutosRestantesUc = $this->minutosPendentesUc($uc, $minutosLancados);

        if ($minutosRestantesUc <= 0) {
            return ['sucesso' => true, 'mensagem' => 'Esta UC ja esta com toda a carga horaria lancada nesta turma.'];
        }

        $limiteDiasCalendario = 730;

        try {
            $this->conn->beginTransaction();

            $dataAtual = date('Y-m-d', $diaInicio);
            $aulasCriadas = 0;
            $blocosSemSala = 0;
            $blocosSemDocente = 0;
            $guard = 0;

            while ($minutosRestantesUc > 0 && (! $temDataFim || strtotime($dataAtual) <= $diaFim) && $guard < $limiteDiasCalendario) {
                $guard++;

                if (! in_array((int) date('N', strtotime($dataAtual)), $diasSemana, true)) {
                    $dataAtual = date('Y-m-d', strtotime($dataAtual . ' +1 day'));
                    continue;
                }

                foreach ($blocosHorario as $blocoHorario) {
                    if ($minutosRestantesUc <= 0) {
                        break;
                    }

                    $horaInicioBloco = $blocoHorario['inicio'];
                    $horaFimBloco = $blocoHorario['fim'];

                    if ($this->turmaTemAulaNoDia($turmaId, $dataAtual, $horaInicioBloco, $horaFimBloco)) {
                        continue;
                    }

                    $intervaloDisponivel = $this->intervaloDisponivelPorBloqueios($dataAtual, $turma, $horaInicioBloco, $horaFimBloco);

                    if ($intervaloDisponivel === null) {
                        continue;
                    }

                    $horaInicioBloco = $intervaloDisponivel['inicio'];
                    $horaFimBloco = $intervaloDisponivel['fim'];

                    $salaBlocoId = $this->salaDisponivelNoDia($salaPreferencialId, $dataAtual, $horaInicioBloco, $horaFimBloco, $turmaId)
                        ? $salaPreferencialId
                        : null;

                    if ($salaPreferencialId !== null && $salaBlocoId === null) {
                        $blocosSemSala++;
                    }

                    $minutosDisponiveis = $this->minutosEntre($horaInicioBloco, $horaFimBloco);
                    $minutosBloco = min($minutosRestantesUc, $minutosDisponiveis);
                    $horaFimAula = $this->somarMinutos($horaInicioBloco, $minutosBloco);
                    $docenteBlocoId = $this->docenteDisponivelParaAula($docentePreferencialId, $unidadeCurricularId, $dataAtual, $horaInicioBloco, $horaFimAula)
                        ? $docentePreferencialId
                        : null;

                    if ($docentePreferencialId !== null && $docenteBlocoId === null) {
                        $blocosSemDocente++;
                    }

                    $this->inserirAulaGerada([
                        'curso_oferta_id' => $turmaId,
                        'unidade_curricular_id' => $unidadeCurricularId,
                        'sala_id' => $salaBlocoId,
                        'data_aula' => $dataAtual,
                        'hora_inicio' => $horaInicioBloco,
                        'hora_fim' => $horaFimAula,
                        'divisao_por_hora' => $minutosBloco < $minutosDisponiveis ? 1 : 0,
                        'docente_id' => $docenteBlocoId,
                    ]);

                    $aulasCriadas++;
                    $minutosRestantesUc -= $minutosBloco;
                }

                $dataAtual = date('Y-m-d', strtotime($dataAtual . ' +1 day'));
            }

            if ($minutosRestantesUc > 0 && $temDataFim) {
                if ($aulasCriadas === 0) {
                    $this->conn->rollBack();

                    return [
                        'sucesso' => false,
                        'mensagem' => 'Nenhuma aula foi gerada para a UC no periodo informado. Verifique os dias selecionados, calendario e aulas ja lancadas.',
                    ];
                }

                $this->conn->commit();

                return [
                    'sucesso' => true,
                    'mensagem' => $aulasCriadas . ' aula(s) geradas para ' . ($uc['codigo'] ?? 'UC') .
                        ' ate ' . date('d/m/Y', (int) $diaFim) . '. Ainda restam aproximadamente ' .
                        round($minutosRestantesUc / 60, 2) . 'h desta UC. Blocos sem sala: ' .
                        $blocosSemSala . '. Blocos sem docente: ' . $blocosSemDocente . '.',
                ];
            }

            if ($minutosRestantesUc > 0) {
                $this->conn->rollBack();

                return [
                    'sucesso' => false,
                    'mensagem' => 'Nao foi possivel concluir a geracao da UC. Faltam aproximadamente ' .
                        round($minutosRestantesUc / 60, 2) . 'h. Verifique dias de aula, calendario e aulas ja lancadas.',
                ];
            }

            $this->conn->commit();

            return [
                'sucesso' => true,
                'mensagem' => $aulasCriadas . ' aula(s) geradas para ' . ($uc['codigo'] ?? 'UC') .
                    '. Blocos sem sala: ' . $blocosSemSala . '. Blocos sem docente: ' . $blocosSemDocente . '. Ultima aula: ' . date('d/m/Y', strtotime($dataAtual . ' -1 day')) . '.',
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel gerar a UC: ' . $e->getMessage()];
        }
    }

    public function salvar(array $dados)
    {
        try {
            $sql = "
                INSERT INTO {$this->table} (
                    curso_modelo_id,
                    nome,
                    codigo_oferta,
                    integral,
                    hora_inicio,
                    hora_fim,
                    hora_inicio_tarde,
                    hora_fim_tarde,
                    participa_parada_pedagogica,
                    participa_recesso_escolar,
                    aula_segunda,
                    aula_terca,
                    aula_quarta,
                    aula_quinta,
                    aula_sexta,
                    aula_sabado,
                    status,
                    descricao
                ) VALUES (
                    :curso_modelo_id,
                    :nome,
                    :codigo_oferta,
                    :integral,
                    :hora_inicio,
                    :hora_fim,
                    :hora_inicio_tarde,
                    :hora_fim_tarde,
                    :participa_parada_pedagogica,
                    :participa_recesso_escolar,
                    :aula_segunda,
                    :aula_terca,
                    :aula_quarta,
                    :aula_quinta,
                    :aula_sexta,
                    :aula_sabado,
                    :status,
                    :descricao
                )
            ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':curso_modelo_id'     => $dados['curso_modelo_id'] ?: null,
                ':nome'                => $dados['nome'],
                ':codigo_oferta'       => $dados['codigo_oferta'],
                ':integral'            => (int) $dados['integral'],
                ':hora_inicio'         => $dados['hora_inicio'] ?: null,
                ':hora_fim'            => $dados['hora_fim'] ?: null,
                ':hora_inicio_tarde'   => ! empty($dados['integral']) ? ($dados['hora_inicio_tarde'] ?: null) : null,
                ':hora_fim_tarde'      => ! empty($dados['integral']) ? ($dados['hora_fim_tarde'] ?: null) : null,
                ':participa_parada_pedagogica' => (int) $dados['participa_parada_pedagogica'],
                ':participa_recesso_escolar' => (int) $dados['participa_recesso_escolar'],
                ':aula_segunda'        => (int) $dados['aula_segunda'],
                ':aula_terca'          => (int) $dados['aula_terca'],
                ':aula_quarta'         => (int) $dados['aula_quarta'],
                ':aula_quinta'         => (int) $dados['aula_quinta'],
                ':aula_sexta'          => (int) $dados['aula_sexta'],
                ':aula_sabado'         => (int) $dados['aula_sabado'],
                ':status'              => $dados['status'],
                ':descricao'           => $dados['descricao'],
            ]);

            return (int) $this->conn->lastInsertId();
        } catch (Throwable $e) {
            return false;
        }
    }

    private function listarUcsDaTurma(int $cursoModeloId): array
    {
        $sql = "
            SELECT id, codigo, nome, carga_horaria
            FROM unidades_curriculares
            WHERE curso_modelo_id = :curso_modelo_id
              AND status = 'Ativa'
            ORDER BY CHAR_LENGTH(codigo) ASC, codigo ASC, nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':curso_modelo_id' => $cursoModeloId]);

        $ucs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($ucs) && $this->cursoModeloSemUc($cursoModeloId)) {
            $this->garantirUcPadraoCursoSemUc($cursoModeloId);

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':curso_modelo_id' => $cursoModeloId]);
            $ucs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $ucs;
    }

    private function cursoModeloSemUc(int $cursoModeloId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM curso_modelos
            WHERE id = :id
              AND COALESCE(sem_uc, 0) = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $cursoModeloId]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function garantirUcPadraoCursoSemUc(int $cursoModeloId): void
    {
        $stmt = $this->conn->prepare("
            SELECT nome, carga_horaria_total
            FROM curso_modelos
            WHERE id = :id
              AND COALESCE(sem_uc, 0) = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $cursoModeloId]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $curso) {
            return;
        }

        $insert = $this->conn->prepare("
            INSERT IGNORE INTO unidades_curriculares (
                curso_modelo_id,
                codigo,
                nome,
                carga_horaria,
                status
            ) VALUES (
                :curso_modelo_id,
                'TURMA',
                :nome,
                :carga_horaria,
                'Ativa'
            )
        ");
        $insert->execute([
            ':curso_modelo_id' => $cursoModeloId,
            ':nome' => (string) ($curso['nome'] ?? ''),
            ':carga_horaria' => (float) ($curso['carga_horaria_total'] ?? 0),
        ]);
    }

    private function buscarUcDaTurma(int $cursoModeloId, int $unidadeCurricularId): ?array
    {
        $sql = "
            SELECT
                uc.id,
                uc.codigo,
                uc.nome,
                uc.carga_horaria,
                a.nome AS area_nome
            FROM unidades_curriculares uc
            INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
            LEFT JOIN areas a ON a.id = cm.area_id
            WHERE uc.id = :id
              AND uc.curso_modelo_id = :curso_modelo_id
              AND uc.status = 'Ativa'
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $unidadeCurricularId,
            ':curso_modelo_id' => $cursoModeloId,
        ]);

        $uc = $stmt->fetch(PDO::FETCH_ASSOC);

        return $uc ?: null;
    }

    private function minutosLancadosPorUc(int $turmaId): array
    {
        $sql = "
            SELECT
                unidade_curricular_id,
                COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(hora_fim, hora_inicio)) / 60), 0) AS minutos
            FROM quadro_horario
            WHERE curso_oferta_id = :turma_id
              AND status = 'Ativa'
              AND unidade_curricular_id IS NOT NULL
            GROUP BY unidade_curricular_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':turma_id' => $turmaId]);

        $minutos = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $minutos[(int) $linha['unidade_curricular_id']] = (int) round((float) $linha['minutos']);
        }

        return $minutos;
    }

    private function minutosPendentesUc(array $uc, array $minutosLancados): int
    {
        $total = (int) round(((float) ($uc['carga_horaria'] ?? 0)) * 60);
        $lancado = (int) ($minutosLancados[(int) $uc['id']] ?? 0);

        return max(0, $total - $lancado);
    }

    private function avancarParaProximaUcPendente(array $ucs, array $minutosLancados, int &$ucIndex, int &$minutosRestantesUc): void
    {
        while ($ucIndex < count($ucs)) {
            $minutosRestantesUc = $this->minutosPendentesUc($ucs[$ucIndex], $minutosLancados);

            if ($minutosRestantesUc > 0) {
                return;
            }

            $ucIndex++;
        }

        $minutosRestantesUc = 0;
    }

    private function inserirAulaGerada(array $dados): void
    {
        $sql = "
            INSERT INTO quadro_horario (
                curso_oferta_id,
                unidade_curricular_id,
                sala_id,
                data_aula,
                hora_inicio,
                hora_fim,
                divisao_por_hora,
                dupla_docencia,
                visita_tecnica,
                ead_assincrona,
                status,
                observacoes
            ) VALUES (
                :curso_oferta_id,
                :unidade_curricular_id,
                :sala_id,
                :data_aula,
                :hora_inicio,
                :hora_fim,
                :divisao_por_hora,
                0,
                0,
                0,
                'Ativa',
                ''
            )
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':curso_oferta_id' => $dados['curso_oferta_id'],
            ':unidade_curricular_id' => $dados['unidade_curricular_id'],
            ':sala_id' => $dados['sala_id'],
            ':data_aula' => $dados['data_aula'],
            ':hora_inicio' => $dados['hora_inicio'],
            ':hora_fim' => $dados['hora_fim'],
            ':divisao_por_hora' => $dados['divisao_por_hora'],
        ]);

        $aulaId = (int) $this->conn->lastInsertId();
        $docenteId = (int) ($dados['docente_id'] ?? 0);

        if ($aulaId > 0 && $docenteId > 0) {
            $sqlDocente = "
                INSERT INTO quadro_horario_docentes (
                    quadro_horario_id,
                    docente_id
                ) VALUES (
                    :quadro_horario_id,
                    :docente_id
                )
            ";

            $stmtDocente = $this->conn->prepare($sqlDocente);
            $stmtDocente->execute([
                ':quadro_horario_id' => $aulaId,
                ':docente_id' => $docenteId,
            ]);
        }
    }

    private function docenteDisponivelParaAula(?int $docenteId, int $unidadeCurricularId, string $data, string $horaInicio, string $horaFim): bool
    {
        if ($docenteId === null || $docenteId <= 0) {
            return false;
        }

        return $this->docenteVinculadoUc($docenteId, $unidadeCurricularId)
            && $this->docenteTemEscala($docenteId, $data, $horaInicio, $horaFim)
            && ! $this->docenteEmFerias($docenteId, $data)
            && ! $this->docenteEmCompensacao($docenteId, $data)
            && ! $this->docenteTemConflito($docenteId, $data, $horaInicio, $horaFim);
    }

    private function docenteVinculadoUc(int $docenteId, int $unidadeCurricularId): bool
    {
        if ($this->unidadeDeCursoSemUc($unidadeCurricularId)) {
            return true;
        }

        $sql = "
            SELECT docente_id
            FROM docente_unidades_curriculares
            WHERE docente_id = :docente_id
              AND unidade_curricular_id = :unidade_curricular_id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':unidade_curricular_id' => $unidadeCurricularId,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function unidadeDeCursoSemUc(int $unidadeCurricularId): bool
    {
        $sql = "
            SELECT cm.id
            FROM unidades_curriculares uc
            INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
            WHERE uc.id = :id
              AND COALESCE(cm.sem_uc, 0) = 1
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $unidadeCurricularId]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteTemConflito(int $docenteId, string $data, string $horaInicio, string $horaFim): bool
    {
        $sql = "
            SELECT qh.id
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            WHERE qhd.docente_id = :docente_id
              AND qh.data_aula = :data
              AND qh.status = 'Ativa'
              AND qh.hora_inicio < :hora_fim
              AND qh.hora_fim > :hora_inicio
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteEmFerias(int $docenteId, string $data): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM docente_ferias
            WHERE docente_id = :docente_id
              AND status = 'Ativo'
              AND data_inicio <= :data
              AND data_fim >= :data
            LIMIT 1
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':data' => $data,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteEmCompensacao(int $docenteId, string $data): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM docente_compensacoes
            WHERE docente_id = :docente_id
              AND status = 'Ativo'
              AND data_inicio <= :data
              AND data_fim >= :data
            LIMIT 1
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':data' => $data,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteTemEscala(int $docenteId, string $data, string $horaInicio, string $horaFim): bool
    {
        $diaSemana = $this->diaSemanaPortugues($data);
        $periodos = $this->periodosPorHorario($horaInicio, $horaFim);

        if ($diaSemana === '' || empty($periodos)) {
            return false;
        }

        $placeholders = [];
        $params = [
            ':docente_id' => $docenteId,
            ':dia_semana' => $diaSemana,
        ];

        foreach ($periodos as $index => $periodo) {
            $placeholder = ':periodo_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $periodo;
        }

        $sql = "
            SELECT id
            FROM docente_escala
            WHERE docente_id = :docente_id
              AND dia_semana = :dia_semana
              AND periodo IN (" . implode(',', $placeholders) . ")
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function diaSemanaPortugues(string $data): string
    {
        $timestamp = strtotime($data);

        if ($timestamp === false) {
            return '';
        }

        return [
            1 => 'Segunda',
            2 => 'Terça',
            3 => 'Quarta',
            4 => 'Quinta',
            5 => 'Sexta',
            6 => 'Sábado',
            7 => 'Domingo',
        ][(int) date('N', $timestamp)] ?? '';
    }

    private function periodosPorHorario(string $horaInicio, string $horaFim): array
    {
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return [];
        }

        $periodos = [];
        $faixas = [
            'Manhã' => ['00:00', '12:00'],
            'Tarde' => ['12:00', '18:00'],
            'Noite' => ['18:00', '23:59'],
        ];

        foreach ($faixas as $periodo => [$inicioFaixa, $fimFaixa]) {
            $base = date('Y-m-d ', $inicio);
            $faixaInicio = strtotime($base . $inicioFaixa);
            $faixaFim = strtotime($base . $fimFaixa);

            if ($faixaInicio !== false && $faixaFim !== false && $inicio < $faixaFim && $fim > $faixaInicio) {
                $periodos[] = $periodo;
            }
        }

        return array_values(array_unique($periodos));
    }

    private function salaDisponivelNoDia(?int $salaId, string $data, string $horaInicio, string $horaFim, int $turmaId): bool
    {
        if ($salaId === null || $salaId <= 0) {
            return false;
        }

        $sql = "
            SELECT id
            FROM quadro_horario
            WHERE sala_id = :sala_id
              AND data_aula = :data
              AND status = 'Ativa'
              AND curso_oferta_id != :turma_id
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data' => $data,
            ':turma_id' => $turmaId,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $sql = "
            SELECT id
            FROM sala_reservas
            WHERE sala_id = :sala_id
              AND status = 'Ativo'
              AND data_inicio <= :data
              AND data_fim >= :data
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        return ! $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function turmaTemAulaNoDia(int $turmaId, string $data, string $horaInicio, string $horaFim): bool
    {
        $sql = "
            SELECT id
            FROM quadro_horario
            WHERE curso_oferta_id = :turma_id
              AND data_aula = :data
              AND status = 'Ativa'
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':turma_id' => $turmaId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function turmaTemAulaEmAlgumBloco(int $turmaId, string $data, array $blocosHorario): bool
    {
        foreach ($blocosHorario as $bloco) {
            if (! $this->turmaTemAulaNoDia($turmaId, $data, $bloco['inicio'], $bloco['fim'])) {
                return false;
            }
        }

        return true;
    }

    private function bloqueioConflitaHorario(array $bloqueio, string $horaInicio, string $horaFim): bool
    {
        $bloqueioInicio = $this->normalizarHora($bloqueio['hora_inicio'] ?? null);
        $bloqueioFim = $this->normalizarHora($bloqueio['hora_fim'] ?? null);
        $horaInicio = $this->normalizarHora($horaInicio);
        $horaFim = $this->normalizarHora($horaFim);

        if ($bloqueioInicio === '' || $bloqueioFim === '') {
            return true;
        }

        return $bloqueioInicio < $horaFim && $bloqueioFim > $horaInicio;
    }

    private function normalizarHora(?string $hora): string
    {
        $hora = trim((string) $hora);

        return $hora === '' ? '' : substr($hora, 0, 5);
    }

    private function intervaloDisponivelPorBloqueios(string $data, array $turma, string $horaInicio, string $horaFim): ?array
    {
        $inicioDisponivel = $horaInicio;
        $fimDisponivel = $horaFim;

        $stmt = $this->conn->prepare("
            SELECT tipo, hora_inicio, hora_fim
            FROM calendario_bloqueios
            WHERE status = 'Ativo'
              AND data <= :data
              AND COALESCE(data_fim, data) >= :data
        ");
        $stmt->execute([':data' => $data]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $bloqueio) {
            if (! $this->bloqueioConflitaHorario($bloqueio, $inicioDisponivel, $fimDisponivel)) {
                continue;
            }

            if (($bloqueio['tipo'] ?? '') === 'Parada Pedagogica' && (int) ($turma['participa_parada_pedagogica'] ?? 1) !== 1) {
                continue;
            }

            if (($bloqueio['tipo'] ?? '') === 'Recesso' && (int) ($turma['participa_recesso_escolar'] ?? 0) !== 1) {
                continue;
            }

            $bloqueioInicio = substr((string) ($bloqueio['hora_inicio'] ?? ''), 0, 5);
            $bloqueioFim = substr((string) ($bloqueio['hora_fim'] ?? ''), 0, 5);

            if ($bloqueioInicio === '' || $bloqueioFim === '') {
                return null;
            }

            if ($bloqueioInicio <= $inicioDisponivel && $bloqueioFim >= $fimDisponivel) {
                return null;
            }

            if ($bloqueioInicio <= $inicioDisponivel && $bloqueioFim > $inicioDisponivel) {
                $inicioDisponivel = max($inicioDisponivel, $bloqueioFim);
                continue;
            }

            if ($bloqueioInicio < $fimDisponivel && $bloqueioFim >= $fimDisponivel) {
                $fimDisponivel = min($fimDisponivel, $bloqueioInicio);
                continue;
            }

            if ($bloqueioInicio > $inicioDisponivel && $bloqueioFim < $fimDisponivel) {
                $minutosAntes = $this->minutosEntre($inicioDisponivel, $bloqueioInicio);
                $minutosDepois = $this->minutosEntre($bloqueioFim, $fimDisponivel);

                if ($minutosAntes >= $minutosDepois) {
                    $fimDisponivel = $bloqueioInicio;
                } else {
                    $inicioDisponivel = $bloqueioFim;
                }
            }
        }

        if (strtotime($inicioDisponivel) === false || strtotime($fimDisponivel) === false || strtotime($fimDisponivel) <= strtotime($inicioDisponivel)) {
            return null;
        }

        return ['inicio' => $inicioDisponivel, 'fim' => $fimDisponivel];
    }

    private function diasPermitidosTurma(array $turma): array
    {
        $mapa = [
            1 => 'aula_segunda',
            2 => 'aula_terca',
            3 => 'aula_quarta',
            4 => 'aula_quinta',
            5 => 'aula_sexta',
            6 => 'aula_sabado',
        ];

        $dias = [];

        foreach ($mapa as $dia => $campo) {
            if ((int) ($turma[$campo] ?? 0) === 1) {
                $dias[] = $dia;
            }
        }

        return $dias;
    }

    private function minutosEntre(string $horaInicio, string $horaFim): int
    {
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return 0;
        }

        return (int) (($fim - $inicio) / 60);
    }

    private function blocosHorarioTurma(array $turma, ?string $turnoGeracao = null): array
    {
        $blocos = [];
        $horaInicio = substr((string) ($turma['hora_inicio'] ?? ''), 0, 5);
        $horaFim = substr((string) ($turma['hora_fim'] ?? ''), 0, 5);

        if ($turnoGeracao !== 'segundo' && $this->minutosEntre($horaInicio, $horaFim) > 0) {
            $blocos[] = ['inicio' => $horaInicio, 'fim' => $horaFim];
        }

        if ((int) ($turma['integral'] ?? 0) === 1 && $turnoGeracao !== 'primeiro') {
            $horaInicioTarde = substr((string) ($turma['hora_inicio_tarde'] ?? ''), 0, 5);
            $horaFimTarde = substr((string) ($turma['hora_fim_tarde'] ?? ''), 0, 5);

            if ($this->minutosEntre($horaInicioTarde, $horaFimTarde) > 0) {
                $blocos[] = ['inicio' => $horaInicioTarde, 'fim' => $horaFimTarde];
            }
        }

        return $blocos;
    }

    private function somarMinutos(string $hora, int $minutos): string
    {
        return date('H:i:s', strtotime($hora) + ($minutos * 60));
    }

    public function atualizar(array $dados): bool
    {
        try {
            $sql = "
                UPDATE {$this->table} SET
                    curso_modelo_id = :curso_modelo_id,
                    nome = :nome,
                    codigo_oferta = :codigo_oferta,
                    integral = :integral,
                    hora_inicio = :hora_inicio,
                    hora_fim = :hora_fim,
                    hora_inicio_tarde = :hora_inicio_tarde,
                    hora_fim_tarde = :hora_fim_tarde,
                    participa_parada_pedagogica = :participa_parada_pedagogica,
                    participa_recesso_escolar = :participa_recesso_escolar,
                    aula_segunda = :aula_segunda,
                    aula_terca = :aula_terca,
                    aula_quarta = :aula_quarta,
                    aula_quinta = :aula_quinta,
                    aula_sexta = :aula_sexta,
                    aula_sabado = :aula_sabado,
                    status = :status,
                    descricao = :descricao
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':id'                  => $dados['id'],
                ':curso_modelo_id'     => $dados['curso_modelo_id'] ?: null,
                ':nome'                => $dados['nome'],
                ':codigo_oferta'       => $dados['codigo_oferta'],
                ':integral'            => (int) $dados['integral'],
                ':hora_inicio'         => $dados['hora_inicio'] ?: null,
                ':hora_fim'            => $dados['hora_fim'] ?: null,
                ':hora_inicio_tarde'   => ! empty($dados['integral']) ? ($dados['hora_inicio_tarde'] ?: null) : null,
                ':hora_fim_tarde'      => ! empty($dados['integral']) ? ($dados['hora_fim_tarde'] ?: null) : null,
                ':participa_parada_pedagogica' => (int) $dados['participa_parada_pedagogica'],
                ':participa_recesso_escolar' => (int) $dados['participa_recesso_escolar'],
                ':aula_segunda'        => (int) $dados['aula_segunda'],
                ':aula_terca'          => (int) $dados['aula_terca'],
                ':aula_quarta'         => (int) $dados['aula_quarta'],
                ':aula_quinta'         => (int) $dados['aula_quinta'],
                ':aula_sexta'          => (int) $dados['aula_sexta'],
                ':aula_sabado'         => (int) $dados['aula_sabado'],
                ':status'              => $dados['status'],
                ':descricao'           => $dados['descricao'],
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function excluir(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function aplicarEscopo(string &$sql, array &$params, array $escopo): void
    {
        $tipo = $escopo['tipo'] ?? 'todos';
        $ids = array_values(array_filter(array_map('intval', $escopo['ids'] ?? [])));

        if ($tipo === 'todos') {
            return;
        }

        if (empty($ids)) {
            $sql .= " AND 1 = 0";
            return;
        }

        $placeholders = [];

        foreach ($ids as $index => $id) {
            $placeholder = ':escopo_' . $tipo . '_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        if ($tipo === 'areas') {
            $sql .= " AND cm.area_id IN (" . implode(',', $placeholders) . ")";
            return;
        }

        if ($tipo === 'ucs') {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM unidades_curriculares uc_escopo
                WHERE uc_escopo.curso_modelo_id = co.curso_modelo_id
                  AND uc_escopo.id IN (" . implode(',', $placeholders) . ")
            )";
        }
    }

    private function aplicarEscopoCursoModelo(string &$sql, array &$params, array $escopo): void
    {
        $tipo = $escopo['tipo'] ?? 'todos';
        $ids = array_values(array_filter(array_map('intval', $escopo['ids'] ?? [])));

        if ($tipo === 'todos') {
            return;
        }

        if (empty($ids)) {
            $sql .= " AND 1 = 0";
            return;
        }

        $placeholders = [];

        foreach ($ids as $index => $id) {
            $placeholder = ':escopo_modelo_' . $tipo . '_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        if ($tipo === 'areas') {
            $sql .= " AND cm.area_id IN (" . implode(',', $placeholders) . ")";
            return;
        }

        if ($tipo === 'ucs') {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM unidades_curriculares uc_escopo
                WHERE uc_escopo.curso_modelo_id = cm.id
                  AND uc_escopo.id IN (" . implode(',', $placeholders) . ")
            )";
        }
    }

    private function aplicarEscopoDocente(string &$sql, array &$params, array $escopo): void
    {
        $tipo = $escopo['tipo'] ?? 'todos';
        $ids = array_values(array_filter(array_map('intval', $escopo['ids'] ?? [])));

        if ($tipo === 'todos') {
            return;
        }

        if ($tipo !== 'areas' || empty($ids)) {
            $sql .= " AND 1 = 0";
            return;
        }

        $placeholders = [];

        foreach ($ids as $index => $id) {
            $placeholder = ':escopo_docente_area_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND COALESCE(da.area_id, a.id) IN (" . implode(',', $placeholders) . ")";
    }

}
