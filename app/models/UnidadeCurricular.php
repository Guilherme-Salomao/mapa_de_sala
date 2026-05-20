<?php

require_once __DIR__ . '/../core/Database.php';

class UnidadeCurricular
{
    private PDO $conn;
    private string $table = 'unidades_curriculares';

    public function __construct()
    {
        $database   = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $status = 'todos', int $cursoModeloId = 0): array
    {
        $sql = "
            SELECT
                uc.id,
                uc.curso_modelo_id,
                uc.codigo,
                uc.nome,
                uc.carga_horaria,
                uc.ordem,
                uc.status,
                cm.nome AS curso_modelo_nome
            FROM {$this->table} uc
            INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (uc.codigo LIKE :busca OR uc.nome LIKE :busca OR cm.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND uc.status = :status";
            $params[':status'] = $status;
        }

        if ($cursoModeloId > 0) {
            $sql .= " AND uc.curso_modelo_id = :curso_modelo_id";
            $params[':curso_modelo_id'] = $cursoModeloId;
        }

        $sql .= " ORDER BY cm.nome ASC, uc.ordem ASC, uc.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $busca = '', string $status = 'todos', int $cursoModeloId = 0): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM {$this->table} uc
            INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (uc.codigo LIKE :busca OR uc.nome LIKE :busca OR cm.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND uc.status = :status";
            $params[':status'] = $status;
        }

        if ($cursoModeloId > 0) {
            $sql .= " AND uc.curso_modelo_id = :curso_modelo_id";
            $params[':curso_modelo_id'] = $cursoModeloId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT id, curso_modelo_id, codigo, nome, carga_horaria, ordem, status
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $uc = $stmt->fetch(PDO::FETCH_ASSOC);

        return $uc ?: null;
    }

    public function listarCursoModelos(): array
    {
        $sql = "
            SELECT id, nome, carga_horaria_total, status
            FROM curso_modelos
            ORDER BY nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cursoModeloExiste(int $cursoModeloId): bool
    {
        $sql = "SELECT id FROM curso_modelos WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $cursoModeloId]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function codigoExiste(int $cursoModeloId, string $codigo, ?int $ignorarId = null): bool
    {
        $sql = "
            SELECT id
            FROM {$this->table}
            WHERE curso_modelo_id = :curso_modelo_id
              AND codigo = :codigo
        ";

        $params = [
            ':curso_modelo_id' => $cursoModeloId,
            ':codigo'          => $codigo,
        ];

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
                    curso_modelo_id,
                    codigo,
                    nome,
                    carga_horaria,
                    ordem,
                    status
                ) VALUES (
                    :curso_modelo_id,
                    :codigo,
                    :nome,
                    :carga_horaria,
                    :ordem,
                    :status
                )
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':curso_modelo_id' => $dados['curso_modelo_id'],
                ':codigo'          => $dados['codigo'],
                ':nome'            => $dados['nome'],
                ':carga_horaria'   => $dados['carga_horaria'],
                ':ordem'           => $dados['ordem'],
                ':status'          => $dados['status'],
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
                    curso_modelo_id = :curso_modelo_id,
                    codigo = :codigo,
                    nome = :nome,
                    carga_horaria = :carga_horaria,
                    ordem = :ordem,
                    status = :status
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':id'              => $dados['id'],
                ':curso_modelo_id' => $dados['curso_modelo_id'],
                ':codigo'          => $dados['codigo'],
                ':nome'            => $dados['nome'],
                ':carga_horaria'   => $dados['carga_horaria'],
                ':ordem'           => $dados['ordem'],
                ':status'          => $dados['status'],
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
