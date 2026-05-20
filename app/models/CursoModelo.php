<?php

require_once __DIR__ . '/../core/Database.php';

class CursoModelo
{
    private PDO $conn;
    private string $table = 'curso_modelos';

    public function __construct()
    {
        $database   = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $status = 'todos'): array
    {
        $sql = "
            SELECT id, nome, carga_horaria_total, status, criado_em, atualizado_em
            FROM {$this->table}
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND nome LIKE :busca";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $busca = '', string $status = 'todos'): int
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE 1 = 1";
        $params = [];

        if ($busca !== '') {
            $sql .= " AND nome LIKE :busca";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT id, nome, carga_horaria_total, status
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    public function nomeExiste(string $nome, ?int $ignorarId = null): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE nome = :nome";
        $params = [':nome' => $nome];

        if ($ignorarId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvar(array $dados): bool
    {
        try {
            $sql = "
                INSERT INTO {$this->table} (
                    nome,
                    carga_horaria_total,
                    status
                ) VALUES (
                    :nome,
                    :carga_horaria_total,
                    :status
                )
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':nome'                => $dados['nome'],
                ':carga_horaria_total' => $dados['carga_horaria_total'],
                ':status'              => $dados['status'],
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function atualizar(array $dados): bool
    {
        try {
            $sql = "
                UPDATE {$this->table} SET
                    nome = :nome,
                    carga_horaria_total = :carga_horaria_total,
                    status = :status
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':id'                  => $dados['id'],
                ':nome'                => $dados['nome'],
                ':carga_horaria_total' => $dados['carga_horaria_total'],
                ':status'              => $dados['status'],
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
}
