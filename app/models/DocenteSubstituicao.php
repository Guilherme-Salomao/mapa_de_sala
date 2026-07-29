<?php

require_once __DIR__ . '/../core/Database.php';

class DocenteSubstituicao
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca, string $status, array $escopo, ?int $docenteRestritoId = null): array
    {
        $sql = "
            SELECT
                ds.*,
                u.nome AS docente_nome,
                s.nome AS sala_nome,
                COALESCE((
                    SELECT GROUP_CONCAT(a2.nome ORDER BY a2.nome SEPARATOR ', ')
                    FROM docente_areas da2
                    INNER JOIN areas a2 ON a2.id = da2.area_id
                    WHERE da2.docente_id = d.id
                ), d.area_atuacao) AS area_atuacao
            FROM docente_substituicoes ds
            INNER JOIN docentes d ON d.id = ds.docente_id
            INNER JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN salas s ON s.id = ds.sala_id
            WHERE 1 = 1
        ";
        $params = [];

        $this->aplicarEscopo($sql, $params, $escopo, $docenteRestritoId);

        if ($busca !== '') {
            $sql .= " AND (
                u.nome LIKE :busca
                OR ds.turma LIKE :busca
                OR ds.unidade_curricular LIKE :busca
                OR ds.motivo LIKE :busca
                OR ds.observacoes LIKE :busca
            )";
            $params[':busca'] = '%' . $busca . '%';
        }

        if (in_array($status, ['Ativo', 'Inativo'], true)) {
            $sql .= " AND ds.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY ds.data_aula DESC, ds.hora_inicio ASC, u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id, array $escopo, ?int $docenteRestritoId = null): ?array
    {
        $sql = "
            SELECT ds.*
            FROM docente_substituicoes ds
            INNER JOIN docentes d ON d.id = ds.docente_id
            WHERE ds.id = :id
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
            SELECT
                d.id,
                u.nome,
                COALESCE((
                    SELECT GROUP_CONCAT(a2.nome ORDER BY a2.nome SEPARATOR ', ')
                    FROM docente_areas da2
                    INNER JOIN areas a2 ON a2.id = da2.area_id
                    WHERE da2.docente_id = d.id
                ), d.area_atuacao) AS area_atuacao
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
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

    public function listarSalas(): array
    {
        $stmt = $this->conn->query("
            SELECT id, nome, tipo
            FROM salas
            WHERE LOWER(status) IN ('ativa', 'ativo', 'livre')
            ORDER BY nome ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorDocenteMes(int $docenteId, int $mes, int $ano): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $stmt = $this->conn->prepare("
            SELECT
                ds.*,
                s.nome AS sala_nome
            FROM docente_substituicoes ds
            LEFT JOIN salas s ON s.id = ds.sala_id
            WHERE ds.docente_id = :docente_id
              AND ds.status = 'Ativo'
              AND ds.data_aula BETWEEN :inicio AND :fim
            ORDER BY ds.data_aula ASC, ds.hora_inicio ASC
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvar(array $dados): bool
    {
        $stmt = $this->conn->prepare("
            INSERT INTO docente_substituicoes (
                docente_id,
                sala_id,
                data_aula,
                hora_inicio,
                hora_fim,
                turma,
                unidade_curricular,
                motivo,
                observacoes,
                status
            ) VALUES (
                :docente_id,
                :sala_id,
                :data_aula,
                :hora_inicio,
                :hora_fim,
                :turma,
                :unidade_curricular,
                :motivo,
                :observacoes,
                :status
            )
        ");

        return $stmt->execute($this->paramsSalvar($dados));
    }

    public function atualizar(array $dados): bool
    {
        $params = $this->paramsSalvar($dados);
        $params[':id'] = $dados['id'];

        $stmt = $this->conn->prepare("
            UPDATE docente_substituicoes SET
                docente_id = :docente_id,
                sala_id = :sala_id,
                data_aula = :data_aula,
                hora_inicio = :hora_inicio,
                hora_fim = :hora_fim,
                turma = :turma,
                unidade_curricular = :unidade_curricular,
                motivo = :motivo,
                observacoes = :observacoes,
                status = :status
            WHERE id = :id
        ");

        return $stmt->execute($params);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM docente_substituicoes WHERE id = :id");

        return $stmt->execute([':id' => $id]);
    }

    public function encontrarConflitoSubstituicaoDocente(
        int $docenteId,
        string $dataAula,
        string $horaInicio,
        string $horaFim,
        ?int $ignorarId = null
    ): ?array {
        $sql = "
            SELECT id, turma, hora_inicio, hora_fim
            FROM docente_substituicoes
            WHERE docente_id = :docente_id
              AND data_aula = :data_aula
              AND status = 'Ativo'
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
        ";
        $params = [
            ':docente_id' => $docenteId,
            ':data_aula' => $dataAula,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }

    public function encontrarConflitoSubstituicaoSala(
        int $salaId,
        string $dataAula,
        string $horaInicio,
        string $horaFim,
        ?int $ignorarId = null
    ): ?array {
        if ($salaId <= 0) {
            return null;
        }

        $sql = "
            SELECT id, turma, hora_inicio, hora_fim
            FROM docente_substituicoes
            WHERE sala_id = :sala_id
              AND data_aula = :data_aula
              AND status = 'Ativo'
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
        ";
        $params = [
            ':sala_id' => $salaId,
            ':data_aula' => $dataAula,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }

    private function paramsSalvar(array $dados): array
    {
        return [
            ':docente_id' => $dados['docente_id'],
            ':sala_id' => ! empty($dados['sala_id']) ? $dados['sala_id'] : null,
            ':data_aula' => $dados['data_aula'],
            ':hora_inicio' => $dados['hora_inicio'],
            ':hora_fim' => $dados['hora_fim'],
            ':turma' => $dados['turma'],
            ':unidade_curricular' => $dados['unidade_curricular'],
            ':motivo' => $dados['motivo'],
            ':observacoes' => $dados['observacoes'],
            ':status' => $dados['status'],
        ];
    }

    private function aplicarEscopo(string &$sql, array &$params, array $escopo, ?int $docenteRestritoId): void
    {
        if ($docenteRestritoId !== null) {
            $sql .= " AND ds.docente_id = :docente_restrito_id";
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
            $placeholder = ':substituicao_area_' . $index;
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
}
