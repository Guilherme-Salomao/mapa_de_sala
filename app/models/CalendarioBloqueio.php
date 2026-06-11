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
            SELECT cb.*, c.nome AS cidade_nome
            FROM calendario_bloqueios cb
            LEFT JOIN cidades c ON c.id = cb.cidade_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (cb.titulo LIKE :busca OR cb.descricao LIKE :busca OR c.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if (in_array($status, ['Ativo', 'Inativo'], true)) {
            $sql .= " AND cb.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY cb.data DESC, COALESCE(cb.data_fim, cb.data) DESC, cb.titulo ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT cb.*, c.nome AS cidade_nome
            FROM calendario_bloqueios cb
            LEFT JOIN cidades c ON c.id = cb.cidade_id
            WHERE cb.id = :id
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
              AND data <= :data_fim
              AND COALESCE(data_fim, data) >= :data_inicio
            ORDER BY data ASC, COALESCE(data_fim, data) ASC, titulo ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarAtivoPorData(string $data, ?array $turma = null, ?string $horaInicio = null, ?string $horaFim = null): ?array
    {
        $sql = "
            SELECT *
            FROM calendario_bloqueios
            WHERE status = 'Ativo'
              AND data <= :data
              AND COALESCE(data_fim, data) >= :data
            ORDER BY data ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':data' => $data]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $bloqueio) {
            if (
                $this->bloqueioAplicaTurma($bloqueio, $turma) &&
                $this->bloqueioConflitaHorario($bloqueio, $horaInicio, $horaFim)
            ) {
                return $bloqueio;
            }
        }

        return null;
    }

    public function bloqueioAplicaTurma(array $bloqueio, ?array $turma = null): bool
    {
        $cidadeBloqueioId = (int) ($bloqueio['cidade_id'] ?? 0);
        $cidadeTurmaId = (int) ($turma['cidade_id'] ?? 0);

        if ($cidadeBloqueioId > 0 && $cidadeBloqueioId !== $cidadeTurmaId) {
            return false;
        }

        $tipo = $bloqueio['tipo'] ?? '';

        if ($tipo === 'Parada Pedagogica') {
            return (int) ($turma['participa_parada_pedagogica'] ?? 1) === 1;
        }

        if ($tipo === 'Recesso') {
            return (int) ($turma['participa_recesso_escolar'] ?? 0) === 1;
        }

        return true;
    }

    public function bloqueioConflitaHorario(array $bloqueio, ?string $horaInicio, ?string $horaFim): bool
    {
        $bloqueioInicio = $this->normalizarHora($bloqueio['hora_inicio'] ?? null);
        $bloqueioFim = $this->normalizarHora($bloqueio['hora_fim'] ?? null);
        $horaInicio = $this->normalizarHora($horaInicio);
        $horaFim = $this->normalizarHora($horaFim);

        if ($bloqueioInicio === '' || $bloqueioFim === '') {
            return true;
        }

        if ($horaInicio === null || $horaFim === null || $horaInicio === '' || $horaFim === '') {
            return true;
        }

        return $bloqueioInicio < $horaFim && $bloqueioFim > $horaInicio;
    }

    private function normalizarHora(?string $hora): string
    {
        $hora = trim((string) $hora);

        return $hora === '' ? '' : substr($hora, 0, 5);
    }

    public function salvar(array $dados): bool
    {
        try {
            $sql = "
                INSERT INTO calendario_bloqueios (
                    data,
                    data_fim,
                    hora_inicio,
                    hora_fim,
                    cidade_id,
                    titulo,
                    tipo,
                    descricao,
                    status
                ) VALUES (
                    :data,
                    :data_fim,
                    :hora_inicio,
                    :hora_fim,
                    :cidade_id,
                    :titulo,
                    :tipo,
                    :descricao,
                    :status
                )
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':data' => $dados['data'],
                ':data_fim' => $dados['data_fim'] ?: null,
                ':hora_inicio' => $dados['hora_inicio'] ?: null,
                ':hora_fim' => $dados['hora_fim'] ?: null,
                ':cidade_id' => $dados['cidade_id'] ?: null,
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
                    data_fim = :data_fim,
                    hora_inicio = :hora_inicio,
                    hora_fim = :hora_fim,
                    cidade_id = :cidade_id,
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
                ':data_fim' => $dados['data_fim'] ?: null,
                ':hora_inicio' => $dados['hora_inicio'] ?: null,
                ':hora_fim' => $dados['hora_fim'] ?: null,
                ':cidade_id' => $dados['cidade_id'] ?: null,
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
