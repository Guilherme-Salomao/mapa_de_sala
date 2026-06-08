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

    public function listar(string $busca = '', string $status = 'todos', array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT
                cm.id,
                cm.area_id,
                cm.nome,
                cm.carga_horaria_total,
                COALESCE(cm.sem_uc, 0) AS sem_uc,
                cm.status,
                cm.criado_em,
                cm.atualizado_em,
                a.nome AS area_nome
            FROM {$this->table} cm
            LEFT JOIN areas a ON a.id = cm.area_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (cm.nome LIKE :busca OR a.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND cm.status = :status";
            $params[':status'] = $status;
        }

        $this->aplicarEscopo($sql, $params, $escopo);

        $sql .= " ORDER BY cm.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $busca = '', string $status = 'todos', array $escopo = ['tipo' => 'todos', 'ids' => []]): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM {$this->table} cm
            LEFT JOIN areas a ON a.id = cm.area_id
            WHERE 1 = 1
        ";
        $params = [];

        if ($busca !== '') {
            $sql .= " AND (cm.nome LIKE :busca OR a.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND cm.status = :status";
            $params[':status'] = $status;
        }

        $this->aplicarEscopo($sql, $params, $escopo);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT id, area_id, nome, carga_horaria_total, COALESCE(sem_uc, 0) AS sem_uc, status
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

    public function listarAreas(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT id, nome, status
            FROM areas
            WHERE status = 'Ativa'
        ";

        $params = [];
        $this->aplicarEscopoArea($sql, $params, $escopo);

        $sql .= " ORDER BY nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function areaExiste(int $areaId, array $escopo = ['tipo' => 'todos', 'ids' => []]): bool
    {
        $sql = "SELECT id FROM areas WHERE id = :id AND status = 'Ativa'";
        $params = [':id' => $areaId];
        $this->aplicarEscopoArea($sql, $params, $escopo);
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cursoPertenceEscopo(int $cursoId, array $escopo = ['tipo' => 'todos', 'ids' => []]): bool
    {
        $sql = "
            SELECT cm.id
            FROM {$this->table} cm
            WHERE cm.id = :id
        ";

        $params = [':id' => $cursoId];
        $this->aplicarEscopo($sql, $params, $escopo);
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
                    area_id,
                    nome,
                    carga_horaria_total,
                    sem_uc,
                    status
                ) VALUES (
                    :area_id,
                    :nome,
                    :carga_horaria_total,
                    :sem_uc,
                    :status
                )
            ";

            $stmt = $this->conn->prepare($sql);

            $salvou = $stmt->execute([
                ':area_id'             => $dados['area_id'],
                ':nome'                => $dados['nome'],
                ':carga_horaria_total' => $dados['carga_horaria_total'],
                ':sem_uc'              => $dados['sem_uc'],
                ':status'              => $dados['status'],
            ]);

            if ($salvou && (int) ($dados['sem_uc'] ?? 0) === 1) {
                $this->garantirUcPadrao((int) $this->conn->lastInsertId(), $dados);
            }

            return $salvou;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function atualizar(array $dados): bool
    {
        try {
            $sql = "
                UPDATE {$this->table} SET
                    area_id = :area_id,
                    nome = :nome,
                    carga_horaria_total = :carga_horaria_total,
                    sem_uc = :sem_uc,
                    status = :status
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            $salvou = $stmt->execute([
                ':id'                  => $dados['id'],
                ':area_id'             => $dados['area_id'],
                ':nome'                => $dados['nome'],
                ':carga_horaria_total' => $dados['carga_horaria_total'],
                ':sem_uc'              => $dados['sem_uc'],
                ':status'              => $dados['status'],
            ]);

            if ($salvou && (int) ($dados['sem_uc'] ?? 0) === 1) {
                $this->garantirUcPadrao((int) $dados['id'], $dados);
            }

            return $salvou;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function garantirUcPadrao(int $cursoModeloId, array $dados): void
    {
        if ($cursoModeloId <= 0) {
            return;
        }

        $stmt = $this->conn->prepare("
            SELECT id
            FROM unidades_curriculares
            WHERE curso_modelo_id = :curso_modelo_id
              AND codigo = 'TURMA'
            LIMIT 1
        ");
        $stmt->execute([':curso_modelo_id' => $cursoModeloId]);
        $uc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($uc) {
            $update = $this->conn->prepare("
                UPDATE unidades_curriculares
                SET nome = :nome,
                    carga_horaria = :carga_horaria,
                    status = 'Ativa'
                WHERE id = :id
            ");
            $update->execute([
                ':id' => (int) $uc['id'],
                ':nome' => (string) $dados['nome'],
                ':carga_horaria' => (float) $dados['carga_horaria_total'],
            ]);
            return;
        }

        $insert = $this->conn->prepare("
            INSERT INTO unidades_curriculares (
                curso_modelo_id,
                codigo,
                nome,
                carga_horaria,
                status
            ) VALUES (
                :curso_modelo_id,
                'TURMA',
                :nome,
                :carga_horaria,
                'Ativa'
            )
        ");
        $insert->execute([
            ':curso_modelo_id' => $cursoModeloId,
            ':nome' => (string) $dados['nome'],
            ':carga_horaria' => (float) $dados['carga_horaria_total'],
        ]);
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
            $placeholder = ':escopo_' . $tipo . '_' . $index;
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
                WHERE uc_escopo.curso_modelo_id = cm.id
                  AND uc_escopo.id IN (" . implode(',', $placeholders) . ")
            )";
        }
    }

    private function aplicarEscopoArea(string &$sql, array &$params, array $escopo): void
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
            $placeholder = ':escopo_area_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND id IN (" . implode(',', $placeholders) . ")";
    }
}
