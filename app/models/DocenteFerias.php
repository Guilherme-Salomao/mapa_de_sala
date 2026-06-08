<?php

require_once __DIR__ . '/../core/Database.php';

class DocenteFerias
{
    private PDO $conn;
    private string $table;

    public function __construct(string $table = 'docente_ferias')
    {
        if (! in_array($table, ['docente_ferias', 'docente_compensacoes'], true)) {
            throw new InvalidArgumentException('Tabela de período docente inválida.');
        }

        $this->table = $table;
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $dataInicio, string $dataFim, array $escopo, ?int $docenteRestritoId = null): array
    {
        $sql = "
            SELECT
                df.*,
                DATEDIFF(df.data_fim, df.data_inicio) + 1 AS quantidade_dias,
                u.nome AS docente_nome,
                COALESCE((
                    SELECT GROUP_CONCAT(a2.nome ORDER BY a2.nome SEPARATOR ', ')
                    FROM docente_areas da2
                    INNER JOIN areas a2 ON a2.id = da2.area_id
                    WHERE da2.docente_id = d.id
                ), d.area_atuacao) AS area_atuacao
            FROM {$this->table} df
            INNER JOIN docentes d ON d.id = df.docente_id
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE df.data_inicio <= :data_fim
              AND df.data_fim >= :data_inicio
        ";
        $params = [
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ];

        $this->aplicarEscopo($sql, $params, $escopo, $docenteRestritoId);
        $sql .= " ORDER BY df.data_inicio ASC, u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id, array $escopo, ?int $docenteRestritoId = null): ?array
    {
        $sql = "
            SELECT df.*
            FROM {$this->table} df
            INNER JOIN docentes d ON d.id = df.docente_id
            WHERE df.id = :id
        ";
        $params = [':id' => $id];
        $this->aplicarEscopo($sql, $params, $escopo, $docenteRestritoId);
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }

    public function listarDocentes(array $escopo, ?int $docenteRestritoId = null): array
    {
        $sql = "
            SELECT
                d.id,
                u.nome,
                COALESCE((
                    SELECT GROUP_CONCAT(a2.nome ORDER BY a2.nome SEPARATOR ', ')
                    FROM docente_areas da2
                    INNER JOIN areas a2 ON a2.id = da2.area_id
                    WHERE da2.docente_id = d.id
                ), d.area_atuacao) AS area_atuacao
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.status = 'Ativo'
              AND u.status = 'Ativo'
        ";
        $params = [];

        if ($docenteRestritoId !== null) {
            $sql .= " AND d.id = :docente_restrito_id";
            $params[':docente_restrito_id'] = $docenteRestritoId;
        } else {
            $this->aplicarEscopoAreas($sql, $params, $escopo);
        }

        $sql .= " ORDER BY u.nome ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeSobreposicao(int $docenteId, string $dataInicio, string $dataFim, ?int $ignorarId = null): bool
    {
        $sql = "
            SELECT id
            FROM {$this->table}
            WHERE docente_id = :docente_id
              AND status = 'Ativo'
              AND data_inicio <= :data_fim
              AND data_fim >= :data_inicio
        ";
        $params = [
            ':docente_id' => $docenteId,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvar(array $dados): bool
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table} (
                docente_id,
                data_inicio,
                data_fim,
                observacoes,
                status
            ) VALUES (
                :docente_id,
                :data_inicio,
                :data_fim,
                :observacoes,
                :status
            )
        ");

        return $stmt->execute([
            ':docente_id' => $dados['docente_id'],
            ':data_inicio' => $dados['data_inicio'],
            ':data_fim' => $dados['data_fim'],
            ':observacoes' => $dados['observacoes'],
            ':status' => $dados['status'],
        ]);
    }

    public function atualizar(array $dados): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} SET
                docente_id = :docente_id,
                data_inicio = :data_inicio,
                data_fim = :data_fim,
                observacoes = :observacoes,
                status = :status
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $dados['id'],
            ':docente_id' => $dados['docente_id'],
            ':data_inicio' => $dados['data_inicio'],
            ':data_fim' => $dados['data_fim'],
            ':observacoes' => $dados['observacoes'],
            ':status' => $dados['status'],
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");

        return $stmt->execute([':id' => $id]);
    }

    private function aplicarEscopo(string &$sql, array &$params, array $escopo, ?int $docenteRestritoId): void
    {
        if ($docenteRestritoId !== null) {
            $sql .= " AND df.docente_id = :docente_restrito_id";
            $params[':docente_restrito_id'] = $docenteRestritoId;
            return;
        }

        $this->aplicarEscopoAreas($sql, $params, $escopo);
    }

    private function aplicarEscopoAreas(string &$sql, array &$params, array $escopo): void
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
            $placeholder = ':ferias_area_' . $index;
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
