<?php

require_once __DIR__ . '/../core/Database.php';

class RelatorioTurma
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listarTurmas(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT co.id, co.nome, co.codigo_oferta, co.hora_inicio, co.hora_fim, co.status
            FROM cursos_ofertas co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE co.status = 'Em andamento'
        ";

        $params = [];
        $this->aplicarEscopo($sql, $params, $escopo);

        $sql .= "
            ORDER BY nome ASC, codigo_oferta ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resumoTurmas(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT
                co.id,
                co.nome,
                co.codigo_oferta,
                co.status,
                cm.nome AS curso_nome,
                a.nome AS area_nome,
                CASE
                    WHEN LOWER(COALESCE(a.nome, '')) = 'aprendizagem' THEN (
                        SELECT COALESCE(SUM(uc_total.carga_horaria), 0)
                        FROM unidades_curriculares uc_total
                        WHERE uc_total.curso_modelo_id = cm.id
                          AND uc_total.status = 'Ativa'
                          AND UPPER(REPLACE(REPLACE(TRIM(uc_total.codigo), '-', ''), ' ', '')) <> 'UC12'
                    )
                    ELSE COALESCE(
                        NULLIF(cm.carga_horaria_total, 0),
                        (
                            SELECT COALESCE(SUM(uc_total.carga_horaria), 0)
                            FROM unidades_curriculares uc_total
                            WHERE uc_total.curso_modelo_id = cm.id
                              AND uc_total.status = 'Ativa'
                        )
                    )
                END AS carga_horaria_total,
                CASE
                    WHEN LOWER(COALESCE(a.nome, '')) = 'aprendizagem' THEN COALESCE((
                        SELECT SUM(uc12_total.carga_horaria * 60)
                        FROM unidades_curriculares uc12_total
                        WHERE uc12_total.curso_modelo_id = cm.id
                          AND uc12_total.status = 'Ativa'
                          AND UPPER(REPLACE(REPLACE(TRIM(uc12_total.codigo), '-', ''), ' ', '')) = 'UC12'
                    ), 0)
                    ELSE 0
                END AS uc12_carga_minutos,
                (
                    SELECT COUNT(*)
                    FROM unidades_curriculares uc_pendente
                    WHERE uc_pendente.curso_modelo_id = cm.id
                      AND uc_pendente.status = 'Ativa'
                      AND NOT (
                          LOWER(COALESCE(a.nome, '')) = 'aprendizagem'
                          AND UPPER(REPLACE(REPLACE(TRIM(uc_pendente.codigo), '-', ''), ' ', '')) = 'UC12'
                      )
                      AND COALESCE((
                          SELECT SUM(TIMESTAMPDIFF(MINUTE, qh_pendente.hora_inicio, qh_pendente.hora_fim))
                          FROM quadro_horario qh_pendente
                          WHERE qh_pendente.curso_oferta_id = co.id
                            AND qh_pendente.unidade_curricular_id = uc_pendente.id
                            AND qh_pendente.status = 'Ativa'
                      ), 0) < ROUND(
                          CASE
                              WHEN COALESCE(cm.sem_uc, 0) = 1
                              THEN cm.carga_horaria_total
                              ELSE uc_pendente.carga_horaria
                          END * 60
                      )
                ) AS ucs_pendentes_conclusao,
                COALESCE(SUM(
                    CASE
                        WHEN qh.id IS NULL THEN 0
                        WHEN LOWER(COALESCE(a.nome, '')) = 'aprendizagem'
                          AND UPPER(REPLACE(REPLACE(TRIM(qh_uc.codigo), '-', ''), ' ', '')) = 'UC12'
                        THEN 0
                        ELSE TIMESTAMPDIFF(MINUTE, qh.hora_inicio, qh.hora_fim)
                    END
                ), 0) AS minutos_lancados,
                COALESCE(SUM(
                    CASE
                        WHEN LOWER(COALESCE(a.nome, '')) = 'aprendizagem'
                          AND UPPER(REPLACE(REPLACE(TRIM(qh_uc.codigo), '-', ''), ' ', '')) = 'UC12'
                        THEN TIMESTAMPDIFF(MINUTE, qh.hora_inicio, qh.hora_fim)
                        ELSE 0
                    END
                ), 0) AS uc12_minutos_lancados,
                MIN(qh.data_aula) AS data_inicio,
                MAX(
                    CASE
                        WHEN LOWER(COALESCE(a.nome, '')) = 'aprendizagem'
                          AND UPPER(REPLACE(REPLACE(TRIM(qh_uc.codigo), '-', ''), ' ', '')) = 'UC12'
                        THEN NULL
                        ELSE qh.data_aula
                    END
                ) AS ultima_aula
            FROM cursos_ofertas co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            LEFT JOIN areas a ON a.id = cm.area_id
            LEFT JOIN quadro_horario qh
                ON qh.curso_oferta_id = co.id
                AND qh.status = 'Ativa'
            LEFT JOIN unidades_curriculares qh_uc ON qh_uc.id = qh.unidade_curricular_id
            WHERE co.status = 'Em andamento'
        ";

        $params = [];
        $this->aplicarEscopo($sql, $params, $escopo);

        $sql .= "
            GROUP BY co.id, co.nome, co.codigo_oferta, co.status, cm.nome, a.nome, cm.carga_horaria_total, cm.id
            ORDER BY
                CASE co.status WHEN 'Em andamento' THEN 0 ELSE 1 END,
                co.nome ASC,
                co.codigo_oferta ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarTurma(int $id, array $escopo = ['tipo' => 'todos', 'ids' => []]): ?array
    {
        $sql = "
            SELECT
                co.id,
                co.curso_modelo_id,
                co.nome,
                co.codigo_oferta,
                co.hora_inicio,
                co.hora_fim,
                co.status,
                cm.nome AS curso_nome,
                a.nome AS area_nome,
                COALESCE(cm.sem_uc, 0) AS curso_sem_uc
            FROM cursos_ofertas co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            LEFT JOIN areas a ON a.id = cm.area_id
            WHERE co.id = :id
              AND co.status = 'Em andamento'
        ";

        $params = [':id' => $id];
        $this->aplicarEscopo($sql, $params, $escopo);
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $turma = $stmt->fetch(PDO::FETCH_ASSOC);

        return $turma ?: null;
    }

    public function relatorioPorUc(int $turmaId): array
    {
        $turma = $this->buscarTurma($turmaId);

        if (! $turma || empty($turma['curso_modelo_id'])) {
            return [];
        }

        $sql = "
            SELECT
                uc.id,
                uc.codigo,
                CASE WHEN COALESCE(cm.sem_uc, 0) = 1 THEN co.nome ELSE uc.nome END AS nome,
                CASE WHEN COALESCE(cm.sem_uc, 0) = 1 THEN cm.carga_horaria_total ELSE uc.carga_horaria END AS carga_horaria,
                CASE
                    WHEN LOWER(COALESCE(a.nome, '')) = 'aprendizagem'
                      AND UPPER(REPLACE(REPLACE(TRIM(uc.codigo), '-', ''), ' ', '')) = 'UC12'
                    THEN 0
                    ELSE 1
                END AS conta_conclusao,
                COALESCE(SUM(
                    CASE
                        WHEN qh.id IS NULL THEN 0
                        ELSE TIMESTAMPDIFF(MINUTE, qh.hora_inicio, qh.hora_fim)
                    END
                ), 0) AS minutos_lancados,
                MIN(qh.data_aula) AS data_inicial,
                MAX(qh.data_aula) AS data_final
            FROM unidades_curriculares uc
            INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
            LEFT JOIN areas a ON a.id = cm.area_id
            INNER JOIN cursos_ofertas co ON co.id = :turma_id
            LEFT JOIN quadro_horario qh
                ON qh.unidade_curricular_id = uc.id
                AND qh.curso_oferta_id = :turma_id
                AND qh.status = 'Ativa'
            WHERE uc.curso_modelo_id = :curso_modelo_id
              AND uc.status = 'Ativa'
            GROUP BY uc.id, uc.codigo, uc.nome, uc.carga_horaria, cm.sem_uc, cm.carga_horaria_total, co.nome, a.nome
            ORDER BY CHAR_LENGTH(uc.codigo) ASC, uc.codigo ASC, uc.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':turma_id' => $turmaId,
            ':curso_modelo_id' => (int) $turma['curso_modelo_id'],
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function datasTurma(int $turmaId): array
    {
        $sql = "
            SELECT
                MIN(qh.data_aula) AS data_inicial,
                MAX(
                    CASE
                        WHEN LOWER(COALESCE(a.nome, '')) = 'aprendizagem'
                          AND UPPER(REPLACE(REPLACE(TRIM(uc.codigo), '-', ''), ' ', '')) = 'UC12'
                        THEN NULL
                        ELSE qh.data_aula
                    END
                ) AS data_final
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            LEFT JOIN areas a ON a.id = cm.area_id
            LEFT JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            WHERE qh.curso_oferta_id = :turma_id
              AND qh.status = 'Ativa'
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':turma_id' => $turmaId]);
        $datas = $stmt->fetch(PDO::FETCH_ASSOC);

        return $datas ?: ['data_inicial' => null, 'data_final' => null];
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
            $placeholder = ':escopo_rel_turma_' . $index;
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
}
