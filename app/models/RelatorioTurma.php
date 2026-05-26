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
            WHERE 1 = 1
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

    public function buscarTurma(int $id): ?array
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
                cm.nome AS curso_nome
            FROM cursos_ofertas co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE co.id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
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
                uc.nome,
                uc.carga_horaria,
                COALESCE(SUM(
                    CASE
                        WHEN qh.id IS NULL THEN 0
                        ELSE TIME_TO_SEC(TIMEDIFF(qh.hora_fim, qh.hora_inicio)) / 3600
                    END
                ), 0) AS horas_lancadas,
                MIN(qh.data_aula) AS data_inicial,
                MAX(qh.data_aula) AS data_final
            FROM unidades_curriculares uc
            LEFT JOIN quadro_horario qh
                ON qh.unidade_curricular_id = uc.id
                AND qh.curso_oferta_id = :turma_id
                AND qh.status = 'Ativa'
            WHERE uc.curso_modelo_id = :curso_modelo_id
              AND uc.status = 'Ativa'
            GROUP BY uc.id, uc.codigo, uc.nome, uc.carga_horaria
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
                MIN(data_aula) AS data_inicial,
                MAX(data_aula) AS data_final
            FROM quadro_horario
            WHERE curso_oferta_id = :turma_id
              AND status = 'Ativa'
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
