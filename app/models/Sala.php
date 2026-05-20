<?php

require_once __DIR__ . '/../core/Database.php';

class Sala
{
    private PDO $conn;

    public function __construct()
    {
        $database   = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $tipo = 'todos', string $status = 'todos'): array
    {
        $sql = "
            SELECT
                s.id,
                s.nome,
                s.tipo,
                s.capacidade,
                s.status,
                s.descricao
            FROM salas s
            WHERE 1 = 1
        ";

        $params = [];

        if (! empty($busca)) {
            $sql              .= " AND (s.nome LIKE :busca OR s.descricao LIKE :busca)";
            $params[':busca']  = '%' . $busca . '%';
        }

        if ($tipo !== 'todos') {
            $sql             .= " AND s.tipo = :tipo";
            $params[':tipo']  = $tipo;
        }

        if ($status !== 'todos') {
            $sql               .= " AND s.status = :status";
            $params[':status']  = $status;
        }

        $sql .= " ORDER BY s.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $salas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($salas as &$sala) {
            $sala['recursos'] = $this->listarRecursosDaSala((int) $sala['id']);
        }

        return $salas;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                nome,
                tipo,
                capacidade,
                status,
                descricao
            FROM salas
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);

        $sala = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $sala) {
            return null;
        }

        $sala['recursos'] = $this->listarIdsRecursosDaSala($id);

        return $sala;
    }

    public function nomeExiste(string $nome, ?int $ignorarId = null): bool
    {
        $sql = "
        SELECT id
        FROM salas
        WHERE nome = :nome
    ";

        $params = [
            ':nome' => $nome,
        ];

        if ($ignorarId !== null) {
            $sql                   .= " AND id != :ignorar_id";
            $params[':ignorar_id']  = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvar(array $dados): bool
    {
        try {
            $this->conn->beginTransaction();

            $sql = "
                INSERT INTO salas (
                    nome,
                    tipo,
                    capacidade,
                    status,
                    descricao
                ) VALUES (
                    :nome,
                    :tipo,
                    :capacidade,
                    :status,
                    :descricao
                )
            ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':nome'       => $dados['nome'],
                ':tipo'       => $dados['tipo'],
                ':capacidade' => $dados['capacidade'],
                ':status'     => $dados['status'],
                ':descricao'  => $dados['descricao'],
            ]);

            $salaId = (int) $this->conn->lastInsertId();

            $this->salvarRecursos($salaId, $dados['recursos'] ?? []);

            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function atualizar(array $dados): bool
    {
        try {
            $this->conn->beginTransaction();

            $sql = "
                UPDATE salas SET
                    nome = :nome,
                    tipo = :tipo,
                    capacidade = :capacidade,
                    status = :status,
                    descricao = :descricao
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':id'         => $dados['id'],
                ':nome'       => $dados['nome'],
                ':tipo'       => $dados['tipo'],
                ':capacidade' => $dados['capacidade'],
                ':status'     => $dados['status'],
                ':descricao'  => $dados['descricao'],
            ]);

            $this->removerRecursosDaSala((int) $dados['id']);
            $this->salvarRecursos((int) $dados['id'], $dados['recursos'] ?? []);

            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function excluir(int $id): bool
    {
        try {
            $sql  = "DELETE FROM salas WHERE id = :id";
            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function listarRecursos(): array
    {
        $sql = "
            SELECT id, nome, descricao
            FROM recursos
            ORDER BY nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listarRecursosDaSala(int $salaId): array
    {
        $sql = "
            SELECT r.nome
            FROM recursos r
            INNER JOIN sala_recursos sr ON sr.recurso_id = r.id
            WHERE sr.sala_id = :sala_id
            ORDER BY r.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':sala_id' => $salaId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function listarIdsRecursosDaSala(int $salaId): array
    {
        $sql = "
            SELECT recurso_id
            FROM sala_recursos
            WHERE sala_id = :sala_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':sala_id' => $salaId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function salvarRecursos(int $salaId, array $recursos): void
    {
        if (empty($recursos)) {
            return;
        }

        $sql = "
            INSERT INTO sala_recursos (
                sala_id,
                recurso_id
            ) VALUES (
                :sala_id,
                :recurso_id
            )
        ";

        $stmt = $this->conn->prepare($sql);

        foreach ($recursos as $recursoId) {
            $stmt->execute([
                ':sala_id'    => $salaId,
                ':recurso_id' => (int) $recursoId,
            ]);
        }
    }

    private function removerRecursosDaSala(int $salaId): void
    {
        $sql = "DELETE FROM sala_recursos WHERE sala_id = :sala_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':sala_id' => $salaId]);
    }
}
