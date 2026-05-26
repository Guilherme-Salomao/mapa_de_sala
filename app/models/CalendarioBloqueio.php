<?php

require_once __DIR__ . '/../core/Database.php';

class CalendarioBloqueio
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $status = 'todos'): array
    {
        $sql = "
            SELECT *
            FROM calendario_bloqueios
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (titulo LIKE :busca OR descricao LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if (in_array($status, ['Ativo', 'Inativo'], true)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY data DESC, titulo ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM calendario_bloqueios
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $bloqueio = $stmt->fetch(PDO::FETCH_ASSOC);

        return $bloqueio ?: null;
    }

    public function listarPorPeriodo(string $dataInicio, string $dataFim): array
    {
        $sql = "
            SELECT *
            FROM calendario_bloqueios
            WHERE status = 'Ativo'
              AND data BETWEEN :data_inicio AND :data_fim
            ORDER BY data ASC, titulo ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarAtivoPorData(string $data): ?array
    {
        $sql = "
            SELECT *
            FROM calendario_bloqueios
            WHERE status = 'Ativo'
              AND data = :data
            ORDER BY data ASC
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':data' => $data]);
        $bloqueio = $stmt->fetch(PDO::FETCH_ASSOC);

        return $bloqueio ?: null;
    }

    public function salvar(array $dados): bool
    {
        try {
            $sql = "
                INSERT INTO calendario_bloqueios (
                    data,
                    titulo,
                    tipo,
                    descricao,
                    status
                ) VALUES (
                    :data,
                    :titulo,
                    :tipo,
                    :descricao,
                    :status
                )
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':data' => $dados['data'],
                ':titulo' => $dados['titulo'],
                ':tipo' => $dados['tipo'],
                ':descricao' => $dados['descricao'],
                ':status' => $dados['status'],
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function atualizar(array $dados): bool
    {
        try {
            $sql = "
                UPDATE calendario_bloqueios SET
                    data = :data,
                    titulo = :titulo,
                    tipo = :tipo,
                    descricao = :descricao,
                    status = :status
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':id' => $dados['id'],
                ':data' => $dados['data'],
                ':titulo' => $dados['titulo'],
                ':tipo' => $dados['tipo'],
                ':descricao' => $dados['descricao'],
                ':status' => $dados['status'],
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function excluir(int $id): bool
    {
        try {
            $sql = "DELETE FROM calendario_bloqueios WHERE id = :id";
            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
