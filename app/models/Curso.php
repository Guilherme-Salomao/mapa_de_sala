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

    public function gerarQuadroCompleto(int $turmaId, string $dataInicio, ?int $salaPreferencialId = null): array
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

                        $this->inserirAulaGerada([
                            'curso_oferta_id' => $turmaId,
                            'unidade_curricular_id' => (int) $ucs[$ucIndex]['id'],
                            'sala_id' => $salaBlocoId,
                            'data_aula' => $dataAtual,
                            'hora_inicio' => $cursor,
                            'hora_fim' => $fimBloco,
                            'divisao_por_hora' => $minutosBloco < $minutosRestantesBloco ? 1 : 0,
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
                'mensagem' => $aulasCriadas . ' aula(s) geradas para completar o restante do curso. Blocos sem sala: ' . $blocosSemSala . '. Previsao de termino: ' . date('d/m/Y', strtotime($dataAtual . ' -1 day')) . '.',
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel gerar o quadro horario: ' . $e->getMessage()];
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $total = ((int) ($uc['carga_horaria'] ?? 0)) * 60;
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

    private function blocosHorarioTurma(array $turma): array
    {
        $blocos = [];
        $horaInicio = substr((string) ($turma['hora_inicio'] ?? ''), 0, 5);
        $horaFim = substr((string) ($turma['hora_fim'] ?? ''), 0, 5);

        if ($this->minutosEntre($horaInicio, $horaFim) > 0) {
            $blocos[] = ['inicio' => $horaInicio, 'fim' => $horaFim];
        }

        if ((int) ($turma['integral'] ?? 0) === 1) {
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

}
