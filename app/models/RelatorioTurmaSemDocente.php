<?php

require_once __DIR__ . '/../core/Database.php';

class RelatorioTurmaSemDocente
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = (new Database())->connect();
    }

    public function listar(
        string $dataInicio,
        string $dataFim,
        int $turmaId,
        array $escopo = ['tipo' => 'todos', 'ids' => []]
    ): array {
        $sql = "
            SELECT
                qh.id,
                qh.data_aula,
                qh.hora_inicio,
                qh.hora_fim,
                qh.curso_oferta_id,
                qh.visita_tecnica,
                qh.ead_assincrona,
                co.nome AS turma_nome,
                co.codigo_oferta,
                cm.nome AS curso_nome,
                a.nome AS area_nome,
                uc.codigo AS uc_codigo,
                CASE WHEN COALESCE(cm.sem_uc, 0) = 1 THEN co.nome ELSE uc.nome END AS uc_nome,
                s.nome AS sala_nome
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            LEFT JOIN areas a ON a.id = cm.area_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            LEFT JOIN salas s ON s.id = qh.sala_id
            WHERE qh.status = 'Ativa'
              AND qh.data_aula BETWEEN :data_inicio AND :data_fim
              AND NOT EXISTS (
                  SELECT 1
                  FROM quadro_horario_docentes qhd
                  WHERE qhd.quadro_horario_id = qh.id
              )
        ";
        $params = [
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ];

        if ($turmaId > 0) {
            $sql .= " AND co.id = :turma_id";
            $params[':turma_id'] = $turmaId;
        }

        $this->aplicarEscopo($sql, $params, $escopo);
        $sql .= " ORDER BY qh.data_aula ASC, qh.hora_inicio ASC, co.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($aulas as &$aula) {
            $aula['possiveis_docentes'] = $this->listarPossiveisDocentes($aula);
        }

        return $aulas;
    }

    public function listarTurmas(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT co.id, co.nome, co.codigo_oferta
            FROM cursos_ofertas co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE co.status = 'Em andamento'
        ";
        $params = [];
        $this->aplicarEscopo($sql, $params, $escopo);
        $sql .= " ORDER BY co.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listarPossiveisDocentes(array $aula): array
    {
        $diaSemana = $this->diaSemanaPortugues((string) ($aula['data_aula'] ?? ''));
        $periodos = $this->periodosPorHorario(
            (string) ($aula['hora_inicio'] ?? ''),
            (string) ($aula['hora_fim'] ?? '')
        );
        $areaNome = trim((string) ($aula['area_nome'] ?? ''));

        if ($diaSemana === '' || empty($periodos) || $areaNome === '') {
            return [];
        }

        $placeholdersPeriodo = [];
        $params = [
            ':area_nome' => $areaNome,
            ':dia_semana' => $diaSemana,
            ':data_aula' => $aula['data_aula'],
            ':hora_inicio' => $aula['hora_inicio'],
            ':hora_fim' => $aula['hora_fim'],
        ];

        foreach ($periodos as $index => $periodo) {
            $placeholder = ':periodo_candidato_' . $index;
            $placeholdersPeriodo[] = $placeholder;
            $params[$placeholder] = $periodo;
        }

        $sql = "
            SELECT DISTINCT
                d.id,
                u.nome,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM docente_escala de_periodo
                        WHERE de_periodo.docente_id = d.id
                          AND de_periodo.dia_semana = :dia_semana
                          AND de_periodo.periodo IN (" . implode(',', $placeholdersPeriodo) . ")
                    ) THEN 'escala'
                    ELSE 'troca_escala'
                END AS disponibilidade
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.status = 'Ativo'
              AND u.status = 'Ativo'
              AND (
                  d.area_atuacao = :area_nome
                  OR EXISTS (
                      SELECT 1
                      FROM docente_areas da
                      INNER JOIN areas a_docente ON a_docente.id = da.area_id
                      WHERE da.docente_id = d.id
                        AND a_docente.nome = :area_nome
                        AND a_docente.status = 'Ativa'
                  )
              )
              AND (
                  EXISTS (
                      SELECT 1
                      FROM docente_escala de_periodo
                      WHERE de_periodo.docente_id = d.id
                        AND de_periodo.dia_semana = :dia_semana
                        AND de_periodo.periodo IN (" . implode(',', $placeholdersPeriodo) . ")
                  )
                  OR (
                      EXISTS (
                          SELECT 1
                          FROM docente_escala de_troca
                          WHERE de_troca.docente_id = d.id
                            AND de_troca.dia_semana = :dia_semana
                            AND de_troca.periodo NOT IN (" . implode(',', $placeholdersPeriodo) . ")
                      )
                      AND NOT EXISTS (
                          SELECT 1
                          FROM quadro_horario_docentes qhd_dia
                          INNER JOIN quadro_horario qh_dia
                              ON qh_dia.id = qhd_dia.quadro_horario_id
                          WHERE qhd_dia.docente_id = d.id
                            AND qh_dia.status = 'Ativa'
                            AND qh_dia.data_aula = :data_aula
                      )
                  )
              )
              AND NOT EXISTS (
                  SELECT 1
                  FROM quadro_horario_docentes qhd_conflito
                  INNER JOIN quadro_horario qh_conflito
                      ON qh_conflito.id = qhd_conflito.quadro_horario_id
                  WHERE qhd_conflito.docente_id = d.id
                    AND qh_conflito.status = 'Ativa'
                    AND qh_conflito.data_aula = :data_aula
                    AND qh_conflito.hora_inicio < :hora_fim
                    AND qh_conflito.hora_fim > :hora_inicio
              )
              AND NOT EXISTS (
                  SELECT 1
                  FROM docente_ferias df
                  WHERE df.docente_id = d.id
                    AND df.status = 'Ativo'
                    AND df.data_inicio <= :data_aula
                    AND df.data_fim >= :data_aula
              )
              AND NOT EXISTS (
                  SELECT 1
                  FROM docente_compensacoes dc
                  WHERE dc.docente_id = d.id
                    AND dc.status = 'Ativo'
                    AND dc.data_inicio <= :data_aula
                    AND dc.data_fim >= :data_aula
              )
              AND NOT EXISTS (
                  SELECT 1
                  FROM educacao_corporativa_docentes ec
                  WHERE ec.docente_id = d.id
                    AND ec.status = 'Ativo'
                    AND ec.data = :data_aula
                    AND (
                        ec.dia_inteiro = 1
                        OR ec.hora_inicio IS NULL
                        OR ec.hora_fim IS NULL
                        OR (ec.hora_inicio < :hora_fim AND ec.hora_fim > :hora_inicio)
                    )
              )
            ORDER BY
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM docente_escala de_ordem
                        WHERE de_ordem.docente_id = d.id
                          AND de_ordem.dia_semana = :dia_semana
                          AND de_ordem.periodo IN (" . implode(',', $placeholdersPeriodo) . ")
                    ) THEN 0
                    ELSE 1
                END,
                u.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        foreach ([
            'Manhã' => ['00:00', '12:00'],
            'Tarde' => ['12:00', '18:00'],
            'Noite' => ['18:00', '23:59'],
        ] as $periodo => [$inicioFaixa, $fimFaixa]) {
            $faixaInicio = strtotime(date('Y-m-d ', $inicio) . $inicioFaixa);
            $faixaFim = strtotime(date('Y-m-d ', $inicio) . $fimFaixa);

            if ($faixaInicio !== false && $faixaFim !== false && $inicio < $faixaFim && $fim > $faixaInicio) {
                $periodos[] = $periodo;
            }
        }

        return $periodos;
    }

    private function aplicarEscopo(string &$sql, array &$params, array $escopo): void
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
            $placeholder = ':area_sem_docente_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND cm.area_id IN (" . implode(',', $placeholders) . ")";
    }
}
