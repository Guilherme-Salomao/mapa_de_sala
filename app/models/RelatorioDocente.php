<?php

require_once __DIR__ . '/../core/Database.php';

class RelatorioDocente
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listarDocentes(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT d.id, u.nome, u.email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.status = 'Ativo'
        ";

        $params = [];
        $this->aplicarEscopo($sql, $params, $escopo);

        $sql .= " ORDER BY u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarDocente(int $id, array $escopo = ['tipo' => 'todos', 'ids' => []]): ?array
    {
        $sql = "
            SELECT d.id, u.nome, u.email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.id = :id
              AND d.status = 'Ativo'
        ";

        $params = [':id' => $id];
        $this->aplicarEscopo($sql, $params, $escopo);
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $docente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $docente ?: null;
    }

    public function listarEscala(int $docenteId): array
    {
        $sql = "
            SELECT dia_semana, periodo, horas
            FROM docente_escala
            WHERE docente_id = :docente_id
            ORDER BY FIELD(dia_semana, 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'),
                     FIELD(periodo, 'Manhã', 'Tarde', 'Noite')
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAulasMensais(int $docenteId, int $mes, int $ano): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $sql = "
            SELECT
                qh.id,
                qh.data_aula,
                qh.hora_inicio,
                qh.hora_fim,
                qh.divisao_por_hora,
                co.nome AS turma_nome,
                co.codigo_oferta,
                co.hora_inicio AS turma_hora_inicio,
                co.hora_fim AS turma_hora_fim,
                uc.codigo AS uc_codigo,
                CASE WHEN COALESCE(cm.sem_uc, 0) = 1 THEN co.nome ELSE uc.nome END AS uc_nome,
                s.nome AS sala_nome
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            LEFT JOIN salas s ON s.id = qh.sala_id
            WHERE qhd.docente_id = :docente_id
              AND qh.status = 'Ativa'
              AND qh.data_aula BETWEEN :inicio AND :fim
            ORDER BY qh.data_aula ASC, qh.hora_inicio ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarBloqueiosMensais(int $mes, int $ano): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $sql = "
            SELECT data, data_fim, hora_inicio, hora_fim, titulo, tipo
            FROM calendario_bloqueios
            WHERE status = 'Ativo'
              AND data <= :fim
              AND COALESCE(data_fim, data) >= :inicio
            ORDER BY data ASC, COALESCE(data_fim, data) ASC, titulo ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAusenciasMensais(int $docenteId, int $mes, int $ano): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $sql = "
            SELECT data_inicio, data_fim, observacoes, 'ferias' AS tipo
            FROM docente_ferias
            WHERE docente_id = :docente_ferias
              AND status = 'Ativo'
              AND data_inicio <= :fim_ferias
              AND data_fim >= :inicio_ferias

            UNION ALL

            SELECT data_inicio, data_fim, observacoes, 'compensacao' AS tipo
            FROM docente_compensacoes
            WHERE docente_id = :docente_compensacao
              AND status = 'Ativo'
              AND data_inicio <= :fim_compensacao
              AND data_fim >= :inicio_compensacao

            ORDER BY data_inicio ASC, data_fim ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':docente_ferias' => $docenteId,
            ':fim_ferias' => $fim,
            ':inicio_ferias' => $inicio,
            ':docente_compensacao' => $docenteId,
            ':fim_compensacao' => $fim,
            ':inicio_compensacao' => $inicio,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $placeholder = ':area_rel_docente_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND EXISTS (
            SELECT 1
            FROM areas a_escopo
            WHERE a_escopo.id IN (" . implode(',', $placeholders) . ")
              AND (
                a_escopo.nome = d.area_atuacao
                OR EXISTS (
                    SELECT 1
                    FROM docente_areas da_escopo
                    WHERE da_escopo.docente_id = d.id
                      AND da_escopo.area_id = a_escopo.id
                )
              )
        )";
    }
}
