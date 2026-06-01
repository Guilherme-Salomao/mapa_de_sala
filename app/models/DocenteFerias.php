<?php

require_once __DIR__ . '/../core/Database.php';

class DocenteFerias
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $dataInicio, string $dataFim, array $escopo, ?int $docenteRestritoId = null): array
    {
        $sql = "
            SELECT
                df.*,
                u.nome AS docente_nome,
                d.area_atuacao
            FROM docente_ferias df
            INNER JOIN docentes d ON d.id = df.docente_id
            INNER JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN areas a ON a.nome = d.area_atuacao
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
            FROM docente_ferias df
            INNER JOIN docentes d ON d.id = df.docente_id
            LEFT JOIN areas a ON a.nome = d.area_atuacao
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
            SELECT d.id, u.nome, d.area_atuacao
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN areas a ON a.nome = d.area_atuacao
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
            FROM docente_ferias
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
            INSERT INTO docente_ferias (
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
            UPDATE docente_ferias SET
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
        $stmt = $this->conn->prepare("DELETE FROM docente_ferias WHERE id = :id");

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

        $sql .= " AND a.id IN (" . implode(',', $placeholders) . ")";
    }
}
