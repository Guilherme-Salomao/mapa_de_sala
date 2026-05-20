<?php

require_once __DIR__ . '/../core/Database.php';

class Curso
{
    private PDO $conn;
    private string $table = 'cursos_ofertas';

    public function __construct()
    {
        $database   = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $status = 'todos'): array
    {
        $sql = "
            SELECT
                co.id,
                co.curso_modelo_id,
                co.nome,
                co.codigo_oferta,
                co.periodo,
                co.carga_horaria_total,
                co.hora_aula,
                co.status,
                co.descricao,
                co.criado_em,
                co.atualizado_em,
                cm.nome AS curso_modelo_nome
            FROM {$this->table} co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (co.nome LIKE :busca OR co.codigo_oferta LIKE :busca OR co.descricao LIKE :busca OR cm.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND co.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY co.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $busca = '', string $status = 'todos'): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM {$this->table} co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE 1 = 1
        ";
        $params = [];

        if ($busca !== '') {
            $sql .= " AND (co.nome LIKE :busca OR co.codigo_oferta LIKE :busca OR co.descricao LIKE :busca OR cm.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND co.status = :status";
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
            SELECT
                id,
                curso_modelo_id,
                nome,
                codigo_oferta,
                periodo,
                carga_horaria_total,
                hora_aula,
                status,
                descricao
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);

        return $curso ?: null;
    }

    public function codigoOfertaExiste(string $codigoOferta, ?int $ignorarId = null): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE codigo_oferta = :codigo_oferta";
        $params = [':codigo_oferta' => $codigoOferta];

        if ($ignorarId !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function salvar(array $dados): bool
    {
        try {
            $sql = "
                INSERT INTO {$this->table} (
                    curso_modelo_id,
                    nome,
                    codigo_oferta,
                    periodo,
                    carga_horaria_total,
                    hora_aula,
                    status,
                    descricao
                ) VALUES (
                    :curso_modelo_id,
                    :nome,
                    :codigo_oferta,
                    :periodo,
                    :carga_horaria_total,
                    :hora_aula,
                    :status,
                    :descricao
                )
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':curso_modelo_id'     => $dados['curso_modelo_id'] ?: null,
                ':nome'                => $dados['nome'],
                ':codigo_oferta'       => $dados['codigo_oferta'],
                ':periodo'             => $dados['periodo'],
                ':carga_horaria_total' => $dados['carga_horaria_total'],
                ':hora_aula'           => $dados['hora_aula'],
                ':status'              => $dados['status'],
                ':descricao'           => $dados['descricao'],
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
                    nome = :nome,
                    codigo_oferta = :codigo_oferta,
                    periodo = :periodo,
                    carga_horaria_total = :carga_horaria_total,
                    hora_aula = :hora_aula,
                    status = :status,
                    descricao = :descricao
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':id'                  => $dados['id'],
                ':curso_modelo_id'     => $dados['curso_modelo_id'] ?: null,
                ':nome'                => $dados['nome'],
                ':codigo_oferta'       => $dados['codigo_oferta'],
                ':periodo'             => $dados['periodo'],
                ':carga_horaria_total' => $dados['carga_horaria_total'],
                ':hora_aula'           => $dados['hora_aula'],
                ':status'              => $dados['status'],
                ':descricao'           => $dados['descricao'],
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
