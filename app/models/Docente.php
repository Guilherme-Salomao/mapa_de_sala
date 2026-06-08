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

    public function listar(string $busca = '', string $status = 'todos', array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT
                d.id,
                d.usuario_id,
                d.horas_semanais,
                COALESCE((
                    SELECT GROUP_CONCAT(a2.nome ORDER BY a2.nome SEPARATOR ', ')
                    FROM docente_areas da2
                    INNER JOIN areas a2 ON a2.id = da2.area_id
                    WHERE da2.docente_id = d.id
                ), d.area_atuacao) AS area_atuacao,
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
            $sql .= " AND (
                u.nome LIKE :busca
                OR u.email LIKE :busca
                OR d.area_atuacao LIKE :busca
                OR EXISTS (
                    SELECT 1
                    FROM docente_areas da_busca
                    INNER JOIN areas a_busca ON a_busca.id = da_busca.area_id
                    WHERE da_busca.docente_id = d.id
                      AND a_busca.nome LIKE :busca
                )
            )";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND d.status = :status";
            $params[':status'] = $status;
        }

        $this->aplicarEscopo($sql, $params, $escopo);

        $sql .= " ORDER BY u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $busca = '', string $status = 'todos', array $escopo = ['tipo' => 'todos', 'ids' => []]): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (
                u.nome LIKE :busca
                OR u.email LIKE :busca
                OR d.area_atuacao LIKE :busca
                OR EXISTS (
                    SELECT 1
                    FROM docente_areas da_busca
                    INNER JOIN areas a_busca ON a_busca.id = da_busca.area_id
                    WHERE da_busca.docente_id = d.id
                      AND a_busca.nome LIKE :busca
                )
            )";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($status !== 'todos') {
            $sql .= " AND d.status = :status";
            $params[':status'] = $status;
        }

        $this->aplicarEscopo($sql, $params, $escopo);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    public function buscarPorId(int $id, array $escopo = ['tipo' => 'todos', 'ids' => []]): ?array
    {
        $sql = "
            SELECT
                d.id,
                d.usuario_id,
                d.horas_semanais,
                COALESCE((
                    SELECT GROUP_CONCAT(a2.nome ORDER BY a2.nome SEPARATOR ', ')
                    FROM docente_areas da2
                    INNER JOIN areas a2 ON a2.id = da2.area_id
                    WHERE da2.docente_id = d.id
                ), d.area_atuacao) AS area_atuacao,
                d.status,
                d.observacoes,
                u.nome AS usuario_nome,
                u.email AS usuario_email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.id = :id
        ";

        $params = [':id' => $id];
        $this->aplicarEscopo($sql, $params, $escopo);
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $docente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $docente) {
            return null;
        }

        $docente['escala'] = $this->listarEscalaPorDocente($id);
        $docente['unidades_curriculares'] = $this->listarUcsPorDocente($id);
        $docente['areas_ids'] = $this->listarAreasPorDocente($id);

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
            $this->salvarAreasDocente($docenteId, $dados['areas_ids'] ?? []);
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

            if (! empty($dados['usuario_nome']) && ! empty($dados['usuario_email'])) {
                $stmtUsuario = $this->conn->prepare("
                    UPDATE usuarios
                    SET nome = :nome,
                        email = :email
                    WHERE id = :id
                ");
                $stmtUsuario->execute([
                    ':id' => $dados['usuario_id'],
                    ':nome' => $dados['usuario_nome'],
                    ':email' => $dados['usuario_email'],
                ]);
            }

            if (! empty($dados['senha_hash'])) {
                $stmtSenha = $this->conn->prepare("
                    UPDATE usuarios
                    SET senha = :senha
                    WHERE id = :id
                ");
                $stmtSenha->execute([
                    ':id' => $dados['usuario_id'],
                    ':senha' => $dados['senha_hash'],
                ]);
            }

            $this->removerEscala((int) $dados['id']);
            $this->salvarAreasDocente((int) $dados['id'], $dados['areas_ids'] ?? []);
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
                uc.curso_modelo_id,
                uc.codigo,
                uc.nome,
                cm.nome AS curso_nome,
                a.nome AS area_nome
            FROM unidades_curriculares uc
            INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
            LEFT JOIN areas a ON a.id = cm.area_id
            WHERE uc.status = 'Ativa'
            ORDER BY a.nome ASC, cm.nome ASC, CHAR_LENGTH(uc.codigo) ASC, uc.codigo ASC, uc.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAreas(array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT id, nome, status
            FROM areas
            WHERE status = 'Ativa'
        ";
        $params = [];
        $this->aplicarEscopoAreas($sql, $params, $escopo);
        $sql .= " ORDER BY nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarNomeArea(int $areaId, array $escopo = ['tipo' => 'todos', 'ids' => []]): ?string
    {
        $sql = "
            SELECT id, nome
            FROM areas
            WHERE id = :id
              AND status = 'Ativa'
        ";
        $params = [':id' => $areaId];
        $this->aplicarEscopoAreas($sql, $params, $escopo);
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $area = $stmt->fetch(PDO::FETCH_ASSOC);

        return $area ? (string) $area['nome'] : null;
    }

    public function listarCursoModelosComUc(): array
    {
        $sql = "
            SELECT DISTINCT
                cm.id,
                cm.nome,
                a.nome AS area_nome
            FROM curso_modelos cm
            INNER JOIN unidades_curriculares uc ON uc.curso_modelo_id = cm.id
            LEFT JOIN areas a ON a.id = cm.area_id
            WHERE cm.status = 'Ativo'
              AND uc.status = 'Ativa'
            ORDER BY a.nome ASC, cm.nome ASC
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

    public function listarAreasPorDocente(int $docenteId): array
    {
        $sql = "
            SELECT da.area_id
            FROM docente_areas da
            INNER JOIN areas a ON a.id = da.area_id
            WHERE da.docente_id = :docente_id
              AND a.status = 'Ativa'
            ORDER BY a.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);
        $ids = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'area_id'));

        if (! empty($ids)) {
            return $ids;
        }

        $sqlFallback = "
            SELECT a.id
            FROM docentes d
            INNER JOIN areas a ON a.nome = d.area_atuacao
            WHERE d.id = :docente_id
              AND a.status = 'Ativa'
        ";
        $stmtFallback = $this->conn->prepare($sqlFallback);
        $stmtFallback->execute([':docente_id' => $docenteId]);

        return array_map('intval', array_column($stmtFallback->fetchAll(PDO::FETCH_ASSOC), 'id'));
    }

    private function salvarAreasDocente(int $docenteId, array $areas): void
    {
        $stmt = $this->conn->prepare("DELETE FROM docente_areas WHERE docente_id = :docente_id");
        $stmt->execute([':docente_id' => $docenteId]);

        $areas = array_values(array_unique(array_filter(array_map('intval', $areas))));

        if (empty($areas)) {
            return;
        }

        $sql = "
            INSERT INTO docente_areas (docente_id, area_id)
            VALUES (:docente_id, :area_id)
        ";

        $stmt = $this->conn->prepare($sql);

        foreach ($areas as $areaId) {
            $stmt->execute([
                ':docente_id' => $docenteId,
                ':area_id' => $areaId,
            ]);
        }
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

    private function aplicarEscopo(string &$sql, array &$params, array $escopo): void
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
            $placeholder = ':escopo_docente_area_' . $index;
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
            $placeholder = ':escopo_area_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND id IN (" . implode(',', $placeholders) . ")";
    }
}
