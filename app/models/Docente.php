<?php

require_once __DIR__ . '/../core/Database.php';

class Docente
{
    private PDO $conn;

    public function __construct()
    {
        $database   = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $status = 'todos'): array
    {
        $sql = "
            SELECT
                d.id,
                d.usuario_id,
                d.horas_semanais,
                d.area_atuacao,
                d.status,
                d.observacoes,
                d.criado_em,
                u.nome AS usuario_nome,
                u.email AS usuario_email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (u.nome LIKE :busca OR u.email LIKE :busca OR d.area_atuacao LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND d.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $busca = '', string $status = 'todos'): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (u.nome LIKE :busca OR u.email LIKE :busca OR d.area_atuacao LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND d.status = :status";
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
                d.id,
                d.usuario_id,
                d.horas_semanais,
                d.area_atuacao,
                d.status,
                d.observacoes,
                u.nome AS usuario_nome,
                u.email AS usuario_email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);

        $docente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $docente) {
            return null;
        }

        $docente['escala'] = $this->listarEscalaPorDocente($id);
        $docente['unidades_curriculares'] = $this->listarUcsPorDocente($id);

        return $docente;
    }

    public function usuarioJaVinculado(int $usuarioId, ?int $ignorarDocenteId = null): bool
    {
        $sql = "SELECT id FROM docentes WHERE usuario_id = :usuario_id";
        $params = [':usuario_id' => $usuarioId];

        if ($ignorarDocenteId !== null) {
            $sql .= " AND id != :docente_id";
            $params[':docente_id'] = $ignorarDocenteId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function usuarioExiste(int $usuarioId): bool
    {
        $sql = "SELECT id FROM usuarios WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $usuarioId]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarUsuariosDisponiveis(?int $usuarioAtualId = null): array
    {
        $sql = "
            SELECT u.id, u.nome, u.email
            FROM usuarios u
            LEFT JOIN docentes d ON d.usuario_id = u.id
            WHERE (
                u.status = 'Ativo'
                AND u.nivel_acesso = 'Professor'
                AND d.id IS NULL
            )
        ";

        $params = [];

        if ($usuarioAtualId !== null) {
            $sql .= " OR u.id = :usuario_atual_id";
            $params[':usuario_atual_id'] = $usuarioAtualId;
        }

        $sql .= " ORDER BY u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvar(array $dados): bool
    {
        try {
            $this->conn->beginTransaction();

            $sql = "
                INSERT INTO docentes (
                    usuario_id,
                    horas_semanais,
                    area_atuacao,
                    status,
                    observacoes
                ) VALUES (
                    :usuario_id,
                    :horas_semanais,
                    :area_atuacao,
                    :status,
                    :observacoes
                )
            ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':usuario_id'      => $dados['usuario_id'],
                ':horas_semanais' => $dados['horas_semanais'],
                ':area_atuacao'   => $dados['area_atuacao'],
                ':status'         => $dados['status'],
                ':observacoes'    => $dados['observacoes'],
            ]);

            $docenteId = (int) $this->conn->lastInsertId();
            $this->salvarEscala($docenteId, $dados['escala'] ?? []);
            $this->salvarUcsDocente($docenteId, $dados['unidades_curriculares'] ?? []);

            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function atualizar(array $dados): bool
    {
        try {
            $this->conn->beginTransaction();

            $sql = "
                UPDATE docentes SET
                    usuario_id = :usuario_id,
                    horas_semanais = :horas_semanais,
                    area_atuacao = :area_atuacao,
                    status = :status,
                    observacoes = :observacoes
                WHERE id = :id
            ";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':id'              => $dados['id'],
                ':usuario_id'      => $dados['usuario_id'],
                ':horas_semanais' => $dados['horas_semanais'],
                ':area_atuacao'   => $dados['area_atuacao'],
                ':status'         => $dados['status'],
                ':observacoes'    => $dados['observacoes'],
            ]);

            $this->removerEscala((int) $dados['id']);
            $this->salvarEscala((int) $dados['id'], $dados['escala'] ?? []);
            $this->salvarUcsDocente((int) $dados['id'], $dados['unidades_curriculares'] ?? []);

            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function excluir(int $id): bool
    {
        try {
            $sql = "DELETE FROM docentes WHERE id = :id";
            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function listarEscalaPorDocente(int $docenteId): array
    {
        $sql = "
            SELECT id, docente_id, dia_semana, periodo, horas
            FROM docente_escala
            WHERE docente_id = :docente_id
            ORDER BY
                FIELD(dia_semana, 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'),
                FIELD(periodo, 'Manhã', 'Tarde', 'Noite')
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarUnidadesCurriculares(): array
    {
        $sql = "
            SELECT
                uc.id,
                uc.codigo,
                uc.nome,
                cm.nome AS curso_nome,
                a.nome AS area_nome
            FROM unidades_curriculares uc
            INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
            LEFT JOIN areas a ON a.id = cm.area_id
            WHERE uc.status = 'Ativa'
            ORDER BY a.nome ASC, cm.nome ASC, uc.ordem ASC, uc.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarUcsPorDocente(int $docenteId): array
    {
        $sql = "
            SELECT unidade_curricular_id
            FROM docente_unidades_curriculares
            WHERE docente_id = :docente_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);

        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'unidade_curricular_id'));
    }

    private function salvarUcsDocente(int $docenteId, array $ucs): void
    {
        $stmt = $this->conn->prepare("DELETE FROM docente_unidades_curriculares WHERE docente_id = :docente_id");
        $stmt->execute([':docente_id' => $docenteId]);

        $ucs = array_values(array_unique(array_filter(array_map('intval', $ucs))));

        if (empty($ucs)) {
            return;
        }

        $sql = "
            INSERT INTO docente_unidades_curriculares (
                docente_id,
                unidade_curricular_id
            ) VALUES (
                :docente_id,
                :unidade_curricular_id
            )
        ";

        $stmt = $this->conn->prepare($sql);

        foreach ($ucs as $ucId) {
            $stmt->execute([
                ':docente_id' => $docenteId,
                ':unidade_curricular_id' => $ucId,
            ]);
        }
    }

    private function salvarEscala(int $docenteId, array $escala): void
    {
        if (empty($escala)) {
            return;
        }

        $sql = "
            INSERT INTO docente_escala (
                docente_id,
                dia_semana,
                periodo,
                horas
            ) VALUES (
                :docente_id,
                :dia_semana,
                :periodo,
                :horas
            )
        ";

        $stmt = $this->conn->prepare($sql);

        foreach ($escala as $item) {
            $stmt->execute([
                ':docente_id'  => $docenteId,
                ':dia_semana'  => $item['dia_semana'],
                ':periodo'     => $item['periodo'],
                ':horas'       => $item['horas'],
            ]);
        }
    }

    private function removerEscala(int $docenteId): void
    {
        $sql = "DELETE FROM docente_escala WHERE docente_id = :docente_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);
    }
}
