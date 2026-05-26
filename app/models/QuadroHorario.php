<?php

require_once __DIR__ . '/../core/Database.php';

class QuadroHorario
{
    private PDO $conn;

    public function __construct()
    {
        $database   = new Database();
        $this->conn = $database->connect();
    }

    public function listarOfertas(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT
                co.id,
                co.nome,
                co.codigo_oferta,
                co.hora_inicio,
                co.hora_fim,
                co.aula_segunda,
                co.aula_terca,
                co.aula_quarta,
                co.aula_quinta,
                co.aula_sexta,
                co.aula_sabado,
                co.curso_modelo_id,
                cm.nome AS curso_nome
            FROM cursos_ofertas co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE 1 = 1
        ";

        $params = [];
        $this->aplicarEscopoOferta($sql, $params, $escopo);

        $sql .= "
            ORDER BY co.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarOferta(int $id): ?array
    {
        $sql = "
            SELECT
                co.id,
                co.nome,
                co.codigo_oferta,
                co.hora_inicio,
                co.hora_fim,
                co.aula_segunda,
                co.aula_terca,
                co.aula_quarta,
                co.aula_quinta,
                co.aula_sexta,
                co.aula_sabado,
                co.curso_modelo_id,
                cm.nome AS curso_nome
            FROM cursos_ofertas co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE co.id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $oferta = $stmt->fetch(PDO::FETCH_ASSOC);

        return $oferta ?: null;
    }

    public function listarUnidadesCurriculares(int $cursoOfertaId, array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $oferta = $this->buscarOferta($cursoOfertaId);

        if (! $oferta || empty($oferta['curso_modelo_id'])) {
            return [];
        }

        $sql = "
            SELECT uc.id, uc.codigo, uc.nome, uc.carga_horaria
            FROM unidades_curriculares uc
            WHERE uc.curso_modelo_id = :curso_modelo_id
              AND uc.status = 'Ativa'
        ";

        $params = [':curso_modelo_id' => (int) $oferta['curso_modelo_id']];
        $this->aplicarEscopoUc($sql, $params, $escopo);

        $sql .= " ORDER BY CHAR_LENGTH(uc.codigo) ASC, uc.codigo ASC, uc.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarSalas(): array
    {
        $sql = "
            SELECT id, nome, tipo, capacidade, status
            FROM salas
            WHERE status IN ('ativa', 'livre', 'uso')
            ORDER BY nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function docenteVinculadoUc(int $docenteId, int $unidadeCurricularId): bool
    {
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

    public function listarDocentes(): array
    {
        $sql = "
            SELECT
                d.id,
                d.area_atuacao,
                u.nome,
                u.email,
                GROUP_CONCAT(duc.unidade_curricular_id) AS uc_ids
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN docente_unidades_curriculares duc ON duc.docente_id = d.id
            WHERE d.status = 'Ativo'
            GROUP BY d.id, d.area_atuacao, u.nome, u.email
            ORDER BY u.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAulasMensais(int $cursoOfertaId, int $mes, int $ano): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $sql = "
            SELECT
                qh.id,
                qh.aprendizagem_quadro_id,
                qh.curso_oferta_id,
                qh.unidade_curricular_id,
                qh.sala_id,
                qh.data_aula,
                qh.hora_inicio,
                qh.hora_fim,
                qh.divisao_por_hora,
                qh.dupla_docencia,
                qh.visita_tecnica,
                qh.ead_assincrona,
                qh.status,
                qh.observacoes,
                uc.codigo AS uc_codigo,
                uc.nome AS uc_nome,
                s.nome AS sala_nome
            FROM quadro_horario qh
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            LEFT JOIN salas s ON s.id = qh.sala_id
            WHERE qh.curso_oferta_id = :curso_oferta_id
              AND qh.data_aula BETWEEN :inicio AND :fim
            ORDER BY qh.data_aula ASC, qh.hora_inicio ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':curso_oferta_id' => $cursoOfertaId,
            ':inicio'          => $inicio,
            ':fim'             => $fim,
        ]);

        $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($aulas as &$aula) {
            $aula['docentes'] = $this->listarDocentesDaAula((int) $aula['id']);
        }

        return $aulas;
    }

    public function buscarAula(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM quadro_horario
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $aula) {
            return null;
        }

        $aula['docentes'] = $this->listarDocentesDaAula($id);

        return $aula;
    }

    public function unidadePertenceOferta(int $unidadeCurricularId, int $cursoOfertaId): bool
    {
        $oferta = $this->buscarOferta($cursoOfertaId);

        if (! $oferta || empty($oferta['curso_modelo_id'])) {
            return false;
        }

        $sql = "
            SELECT id
            FROM unidades_curriculares uc
            WHERE uc.id = :id
              AND uc.curso_modelo_id = :curso_modelo_id
              AND uc.status = 'Ativa'
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id'              => $unidadeCurricularId,
            ':curso_modelo_id' => (int) $oferta['curso_modelo_id'],
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvarAulas(array $dados, array $blocos): bool
    {
        try {
            $this->conn->beginTransaction();

            foreach ($blocos as $bloco) {
                $dadosBloco = $dados;

                if (isset($bloco['unidade_curricular_id'])) {
                    $dadosBloco['unidade_curricular_id'] = (int) $bloco['unidade_curricular_id'];
                }

                $aulaId = $this->inserirAula($dadosBloco, $bloco['inicio'], $bloco['fim']);
                $this->salvarDocentesDaAula($aulaId, $bloco['docentes'] ?? $dados['docentes']);
            }

            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function atualizarAula(array $dados): bool
    {
        try {
            $this->conn->beginTransaction();

            $sql = "
                UPDATE quadro_horario SET
                    curso_oferta_id = :curso_oferta_id,
                    unidade_curricular_id = :unidade_curricular_id,
                    sala_id = :sala_id,
                    data_aula = :data_aula,
                    hora_inicio = :hora_inicio,
                    hora_fim = :hora_fim,
                    divisao_por_hora = :divisao_por_hora,
                    dupla_docencia = :dupla_docencia,
                    visita_tecnica = :visita_tecnica,
                    ead_assincrona = :ead_assincrona,
                    status = :status,
                    observacoes = :observacoes
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id'                    => $dados['id'],
                ':curso_oferta_id'       => $dados['curso_oferta_id'],
                ':unidade_curricular_id' => $dados['unidade_curricular_id'],
                ':sala_id'               => $dados['sala_id'] > 0 ? $dados['sala_id'] : null,
                ':data_aula'             => $dados['data_aula'],
                ':hora_inicio'           => $dados['hora_inicio'],
                ':hora_fim'              => $dados['hora_fim'],
                ':divisao_por_hora'      => $dados['divisao_por_hora'],
                ':dupla_docencia'        => $dados['dupla_docencia'],
                ':visita_tecnica'        => $dados['visita_tecnica'],
                ':ead_assincrona'        => $dados['ead_assincrona'],
                ':status'                => $dados['status'],
                ':observacoes'           => $dados['observacoes'],
            ]);

            $this->removerDocentesDaAula((int) $dados['id']);
            $this->salvarDocentesDaAula((int) $dados['id'], $dados['docentes']);

            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function excluirAula(int $id): bool
    {
        try {
            $sql = "DELETE FROM quadro_horario WHERE id = :id";
            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function encontrarConflitoSala(int $salaId, string $dataAula, string $horaInicio, string $horaFim, ?int $ignorarId = null): ?array
    {
        if ($salaId <= 0) {
            return null;
        }

        $sql = "
            SELECT qh.id, qh.hora_inicio, qh.hora_fim, co.nome AS turma_nome
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            WHERE qh.sala_id = :sala_id
              AND qh.data_aula = :data_aula
              AND qh.status = 'Ativa'
              AND qh.hora_inicio < :hora_fim
              AND qh.hora_fim > :hora_inicio
        ";

        $params = [
            ':sala_id'     => $salaId,
            ':data_aula'   => $dataAula,
            ':hora_inicio' => $horaInicio,
            ':hora_fim'    => $horaFim,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND qh.id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $conflito = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conflito) {
            return $conflito;
        }

        return $this->encontrarBloqueioSala($salaId, $dataAula, $horaInicio, $horaFim);
    }

    private function encontrarBloqueioSala(int $salaId, string $dataAula, string $horaInicio, string $horaFim): ?array
    {
        $sql = "
            SELECT id, hora_inicio, hora_fim, tipo
            FROM sala_reservas
            WHERE sala_id = :sala_id
              AND status = 'Ativo'
              AND data_inicio <= :data_aula
              AND data_fim >= :data_aula
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data_aula' => $dataAula,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);
        $bloqueio = $stmt->fetch(PDO::FETCH_ASSOC);

        return $bloqueio ?: null;
    }

    public function encontrarConflitoTurma(int $cursoOfertaId, string $dataAula, string $horaInicio, string $horaFim, ?int $ignorarId = null): ?array
    {
        $sql = "
            SELECT id, hora_inicio, hora_fim
            FROM quadro_horario
            WHERE curso_oferta_id = :curso_oferta_id
              AND data_aula = :data_aula
              AND status = 'Ativa'
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
        ";

        $params = [
            ':curso_oferta_id' => $cursoOfertaId,
            ':data_aula'       => $dataAula,
            ':hora_inicio'     => $horaInicio,
            ':hora_fim'        => $horaFim,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $conflito = $stmt->fetch(PDO::FETCH_ASSOC);

        return $conflito ?: null;
    }

    public function encontrarConflitoDocente(int $docenteId, string $dataAula, string $horaInicio, string $horaFim, ?int $ignorarId = null): ?array
    {
        $sql = "
            SELECT qh.id, qh.hora_inicio, qh.hora_fim, co.nome AS turma_nome
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            WHERE qhd.docente_id = :docente_id
              AND qh.data_aula = :data_aula
              AND qh.status = 'Ativa'
              AND qh.hora_inicio < :hora_fim
              AND qh.hora_fim > :hora_inicio
        ";

        $params = [
            ':docente_id'  => $docenteId,
            ':data_aula'   => $dataAula,
            ':hora_inicio' => $horaInicio,
            ':hora_fim'    => $horaFim,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND qh.id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $conflito = $stmt->fetch(PDO::FETCH_ASSOC);

        return $conflito ?: null;
    }

    public function encontrarAulaDocenteNoDia(int $docenteId, string $dataAula, ?int $ignorarId = null): ?array
    {
        $sql = "
            SELECT qh.id, qh.hora_inicio, qh.hora_fim, co.nome AS turma_nome, s.nome AS sala_nome
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            LEFT JOIN salas s ON s.id = qh.sala_id
            WHERE qhd.docente_id = :docente_id
              AND qh.data_aula = :data_aula
              AND qh.status = 'Ativa'
        ";

        $params = [
            ':docente_id' => $docenteId,
            ':data_aula' => $dataAula,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND qh.id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);

        return $aula ?: null;
    }

    public function docenteTemEscala(int $docenteId, string $dataAula, string $horaInicio, string $horaFim): bool
    {
        $diaSemana = $this->diaSemanaPortugues($dataAula);
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
            $faixaInicio = strtotime(date('Y-m-d ', $inicio) . $inicioFaixa);
            $faixaFim = strtotime(date('Y-m-d ', $inicio) . $fimFaixa);

            if ($faixaInicio !== false && $faixaFim !== false && $inicio < $faixaFim && $fim > $faixaInicio) {
                $periodos[] = $periodo;
            }
        }

        return $periodos;
    }

    private function inserirAula(array $dados, string $horaInicio, string $horaFim): int
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
                :dupla_docencia,
                :visita_tecnica,
                :ead_assincrona,
                :status,
                :observacoes
            )
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':curso_oferta_id'       => $dados['curso_oferta_id'],
            ':unidade_curricular_id' => $dados['unidade_curricular_id'],
            ':sala_id'               => $dados['sala_id'] > 0 ? $dados['sala_id'] : null,
            ':data_aula'             => $dados['data_aula'],
            ':hora_inicio'           => $horaInicio,
            ':hora_fim'              => $horaFim,
            ':divisao_por_hora'      => $dados['divisao_por_hora'],
            ':dupla_docencia'        => $dados['dupla_docencia'],
            ':visita_tecnica'        => $dados['visita_tecnica'],
            ':ead_assincrona'        => $dados['ead_assincrona'],
            ':status'                => $dados['status'],
            ':observacoes'           => $dados['observacoes'],
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function salvarDocentesDaAula(int $aulaId, array $docentes): void
    {
        $sql = "
            INSERT INTO quadro_horario_docentes (
                quadro_horario_id,
                docente_id
            ) VALUES (
                :quadro_horario_id,
                :docente_id
            )
        ";

        $stmt = $this->conn->prepare($sql);

        foreach (array_unique($docentes) as $docenteId) {
            $stmt->execute([
                ':quadro_horario_id' => $aulaId,
                ':docente_id'        => (int) $docenteId,
            ]);
        }
    }

    private function removerDocentesDaAula(int $aulaId): void
    {
        $sql = "DELETE FROM quadro_horario_docentes WHERE quadro_horario_id = :quadro_horario_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':quadro_horario_id' => $aulaId]);
    }

    private function listarDocentesDaAula(int $aulaId): array
    {
        $sql = "
            SELECT d.id, u.nome, u.email
            FROM quadro_horario_docentes qhd
            INNER JOIN docentes d ON d.id = qhd.docente_id
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE qhd.quadro_horario_id = :aula_id
            ORDER BY u.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':aula_id' => $aulaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function aplicarEscopoOferta(string &$sql, array &$params, array $escopo): void
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
            $placeholder = ':escopo_oferta_' . $index;
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

    private function aplicarEscopoUc(string &$sql, array &$params, array $escopo): void
    {
        $tipo = $escopo['tipo'] ?? 'todos';
        $ids = array_values(array_filter(array_map('intval', $escopo['ids'] ?? [])));

        if ($tipo !== 'ucs') {
            return;
        }

        if (empty($ids)) {
            $sql .= " AND 1 = 0";
            return;
        }

        $placeholders = [];

        foreach ($ids as $index => $id) {
            $placeholder = ':escopo_uc_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND uc.id IN (" . implode(',', $placeholders) . ")";
    }
}
