<?php

require_once __DIR__ . '/../core/Database.php';

class EducacaoCorporativa
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $status = 'todos', ?int $docenteRestritoId = null): array
    {
        $sql = "
            SELECT
                ec.*,
                u.nome AS docente_nome
            FROM educacao_corporativa_docentes ec
            INNER JOIN docentes d ON d.id = ec.docente_id
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($docenteRestritoId !== null) {
            $sql .= " AND ec.docente_id = :docente_restrito_id";
            $params[':docente_restrito_id'] = $docenteRestritoId;
        }

        if ($busca !== '') {
            $sql .= " AND (ec.titulo LIKE :busca OR ec.descricao LIKE :busca OR u.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if (in_array($status, ['Ativo', 'Inativo'], true)) {
            $sql .= " AND ec.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY ec.data DESC, u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id, ?int $docenteRestritoId = null): ?array
    {
        $sql = "
            SELECT *
            FROM educacao_corporativa_docentes
            WHERE id = :id
        ";

        $params = [':id' => $id];

        if ($docenteRestritoId !== null) {
            $sql .= " AND docente_id = :docente_restrito_id";
            $params[':docente_restrito_id'] = $docenteRestritoId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }

    public function listarDocentes(?int $docenteRestritoId = null): array
    {
        $sql = "
            SELECT d.id, u.nome, u.email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.status = 'Ativo'
        ";

        $params = [];

        if ($docenteRestritoId !== null) {
            $sql .= " AND d.id = :docente_restrito_id";
            $params[':docente_restrito_id'] = $docenteRestritoId;
        }

        $sql .= " ORDER BY u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorDocenteMes(int $docenteId, int $mes, int $ano): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $sql = "
            SELECT *
            FROM educacao_corporativa_docentes
            WHERE docente_id = :docente_id
              AND status = 'Ativo'
              AND data BETWEEN :inicio AND :fim
            ORDER BY data ASC, titulo ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function docenteEmCurso(
        int $docenteId,
        string $data,
        ?int $ignorarId = null,
        ?string $horaInicio = null,
        ?string $horaFim = null
    ): ?array
    {
        $sql = "
            SELECT *
            FROM educacao_corporativa_docentes
            WHERE docente_id = :docente_id
              AND data = :data
              AND status = 'Ativo'
        ";

        $params = [
            ':docente_id' => $docenteId,
            ':data' => $data,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        if ($horaInicio !== null && $horaFim !== null) {
            $sql .= " AND (
                dia_inteiro = 1
                OR hora_inicio IS NULL
                OR hora_fim IS NULL
                OR (hora_inicio < :hora_fim AND hora_fim > :hora_inicio)
            )";
            $params[':hora_inicio'] = $horaInicio;
            $params[':hora_fim'] = $horaFim;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }

    public function salvar(array $dados): bool
    {
        try {
            $sql = "
                INSERT INTO educacao_corporativa_docentes (
                    docente_id,
                    data,
                    dia_inteiro,
                    hora_inicio,
                    hora_fim,
                    titulo,
                    descricao,
                    status
                ) VALUES (
                    :docente_id,
                    :data,
                    :dia_inteiro,
                    :hora_inicio,
                    :hora_fim,
                    :titulo,
                    :descricao,
                    :status
                )
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':docente_id' => $dados['docente_id'],
                ':data' => $dados['data'],
                ':dia_inteiro' => $dados['dia_inteiro'],
                ':hora_inicio' => $dados['hora_inicio'],
                ':hora_fim' => $dados['hora_fim'],
                ':titulo' => $dados['titulo'],
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
                UPDATE educacao_corporativa_docentes SET
                    docente_id = :docente_id,
                    data = :data,
                    dia_inteiro = :dia_inteiro,
                    hora_inicio = :hora_inicio,
                    hora_fim = :hora_fim,
                    titulo = :titulo,
                    descricao = :descricao,
                    status = :status
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':id' => $dados['id'],
                ':docente_id' => $dados['docente_id'],
                ':data' => $dados['data'],
                ':dia_inteiro' => $dados['dia_inteiro'],
                ':hora_inicio' => $dados['hora_inicio'],
                ':hora_fim' => $dados['hora_fim'],
                ':titulo' => $dados['titulo'],
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
            $sql = "DELETE FROM educacao_corporativa_docentes WHERE id = :id";
            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
