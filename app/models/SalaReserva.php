<?php

require_once __DIR__ . '/../core/Database.php';

class SalaReserva
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $dataInicio, string $dataFim, int $salaId = 0, string $tipo = 'todos', string $status = 'Ativo'): array
    {
        $sql = "
            SELECT
                sr.*,
                s.nome AS sala_nome,
                s.tipo AS sala_tipo,
                u.nome AS solicitante_nome,
                u.email AS solicitante_email
            FROM sala_reservas sr
            INNER JOIN salas s ON s.id = sr.sala_id
            LEFT JOIN usuarios u ON u.id = sr.solicitante_usuario_id
            WHERE sr.data_inicio <= :data_fim
              AND sr.data_fim >= :data_inicio
        ";

        $params = [
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ];

        if ($salaId > 0) {
            $sql .= " AND sr.sala_id = :sala_id";
            $params[':sala_id'] = $salaId;
        }

        if (in_array($tipo, ['Reservada', 'Manutencao'], true)) {
            $sql .= " AND sr.tipo = :tipo";
            $params[':tipo'] = $tipo;
        }

        if (in_array($status, ['Ativo', 'Inativo'], true)) {
            $sql .= " AND sr.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY sr.data_inicio DESC, sr.hora_inicio ASC, s.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarSalas(): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, nome, tipo
            FROM salas
            WHERE status IN ('ativa', 'livre', 'uso')
            ORDER BY nome ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarUsuarios(): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, nome, email, nivel_acesso
            FROM usuarios
            WHERE status = 'Ativo'
            ORDER BY nome ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAulas(string $dataInicio, string $dataFim): array
    {
        $sql = "
            SELECT
                qh.id,
                qh.sala_id,
                qh.data_aula,
                qh.hora_inicio,
                qh.hora_fim,
                co.nome AS turma_nome,
                s.nome AS sala_nome,
                uc.codigo AS uc_codigo,
                CASE WHEN COALESCE(cm.sem_uc, 0) = 1 THEN co.nome ELSE uc.nome END AS uc_nome
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            LEFT JOIN salas s ON s.id = qh.sala_id
            WHERE qh.status = 'Ativa'
              AND qh.data_aula BETWEEN :data_inicio AND :data_fim
              AND qh.sala_id IS NOT NULL
            ORDER BY qh.data_aula ASC, qh.hora_inicio ASC, s.nome ASC, co.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvar(array $dados): array
    {
        $conflitoReserva = $this->encontrarConflitoReserva(
            (int) $dados['sala_id'],
            $dados['data_inicio'],
            $dados['data_fim'],
            $dados['hora_inicio'],
            $dados['hora_fim']
        );

        if ($conflitoReserva) {
            return ['sucesso' => false, 'mensagem' => 'A sala ja possui reserva ou manutenção neste periodo.'];
        }

        $conflitosAula = $this->aulasImpactadas(
            (int) $dados['sala_id'],
            $dados['data_inicio'],
            $dados['data_fim'],
            $dados['hora_inicio'],
            $dados['hora_fim']
        );

        if (! empty($conflitosAula) && $dados['tipo'] === 'Reservada') {
            return ['sucesso' => false, 'mensagem' => 'A sala possui aula neste periodo. Faça a troca de sala antes de reservar.'];
        }

        try {
            $this->conn->beginTransaction();

            $sql = "
                INSERT INTO sala_reservas (
                    sala_id, tipo, data_inicio, data_fim, hora_inicio, hora_fim, solicitante_usuario_id, solicitante, motivo, descricao, status
                ) VALUES (
                    :sala_id, :tipo, :data_inicio, :data_fim, :hora_inicio, :hora_fim, :solicitante_usuario_id, :solicitante, :motivo, :descricao, 'Ativo'
                )
            ";

            $stmt = $this->conn->prepare($sql);
            $solicitante = $this->buscarUsuario((int) $dados['solicitante_usuario_id']);
            $stmt->execute([
                ':sala_id' => $dados['sala_id'],
                ':tipo' => $dados['tipo'],
                ':data_inicio' => $dados['data_inicio'],
                ':data_fim' => $dados['data_fim'],
                ':hora_inicio' => $dados['hora_inicio'],
                ':hora_fim' => $dados['hora_fim'],
                ':solicitante_usuario_id' => $dados['solicitante_usuario_id'],
                ':solicitante' => $solicitante['nome'] ?? '',
                ':motivo' => $dados['motivo'],
                ':descricao' => $dados['descricao'],
            ]);

            if ($dados['tipo'] === 'Manutencao') {
                $this->atualizarStatusSala((int) $dados['sala_id'], 'manutencao');
            }

            $this->conn->commit();

            $mensagem = 'Registro salvo com sucesso.';

            if (! empty($conflitosAula)) {
                $mensagem .= ' Existem ' . count($conflitosAula) . ' aula(s) impactadas para troca de sala.';
            }

            return ['sucesso' => true, 'mensagem' => $mensagem];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel salvar o registro: ' . $e->getMessage()];
        }
    }

    public function excluir(int $id): bool
    {
        try {
            $reserva = $this->buscarReserva($id);
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("UPDATE sala_reservas SET status = 'Inativo' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            if ($reserva && ($reserva['tipo'] ?? '') === 'Manutencao') {
                $this->restaurarStatusSalaSeSemManutencao((int) $reserva['sala_id']);
            }

            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function trocarSala(int $aulaId, int $salaDestinoId, bool $permitirPermuta): array
    {
        $aula = $this->buscarAula($aulaId);

        if (! $aula || $salaDestinoId <= 0 || (int) $aula['sala_id'] === $salaDestinoId) {
            return ['sucesso' => false, 'mensagem' => 'Dados invalidos para troca de sala.'];
        }

        if ($this->encontrarConflitoReserva($salaDestinoId, $aula['data_aula'], $aula['data_aula'], $aula['hora_inicio'], $aula['hora_fim'])) {
            return ['sucesso' => false, 'mensagem' => 'A sala destino esta reservada ou em manutenção neste horario.'];
        }

        $aulaDestino = $this->buscarAulaPorSalaHorario($salaDestinoId, $aula['data_aula'], $aula['hora_inicio'], $aula['hora_fim'], $aulaId);

        try {
            $this->garantirTabelaTrocas();
            $this->conn->beginTransaction();

            if ($aulaDestino) {
                if (! $permitirPermuta) {
                    $this->conn->rollBack();
                    return ['sucesso' => false, 'mensagem' => 'A sala destino esta ocupada. Marque permuta para trocar as duas aulas.'];
                }

                $salaOrigemId = (int) $aula['sala_id'];

                $stmt = $this->conn->prepare("UPDATE quadro_horario SET sala_id = :sala_id WHERE id = :id");
                $stmt->execute([':sala_id' => $salaDestinoId, ':id' => $aulaId]);
                $stmt->execute([':sala_id' => $salaOrigemId, ':id' => (int) $aulaDestino['id']]);

                $this->registrarTroca($aulaId, $salaOrigemId, $salaDestinoId, 'Permuta de sala');
                $this->registrarTroca((int) $aulaDestino['id'], $salaDestinoId, $salaOrigemId, 'Permuta de sala');
            } else {
                $stmt = $this->conn->prepare("UPDATE quadro_horario SET sala_id = :sala_id WHERE id = :id");
                $stmt->execute([':sala_id' => $salaDestinoId, ':id' => $aulaId]);
                $this->registrarTroca($aulaId, (int) $aula['sala_id'], $salaDestinoId, 'Troca de sala');
            }

            $this->conn->commit();

            return ['sucesso' => true, 'mensagem' => 'Troca de sala realizada com sucesso.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel trocar a sala: ' . $e->getMessage()];
        }
    }

    public function salaBloqueada(int $salaId, string $data, string $horaInicio, string $horaFim): ?array
    {
        $sql = "
            SELECT *
            FROM sala_reservas
            WHERE sala_id = :sala_id
              AND status = 'Ativo'
              AND data_inicio <= :data
              AND data_fim >= :data
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

        return $reserva ?: null;
    }

    private function encontrarConflitoReserva(int $salaId, string $dataInicio, string $dataFim, string $horaInicio, string $horaFim): ?array
    {
        $sql = "
            SELECT *
            FROM sala_reservas
            WHERE sala_id = :sala_id
              AND status = 'Ativo'
              AND data_inicio <= :data_fim
              AND data_fim >= :data_inicio
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

        return $reserva ?: null;
    }

    private function buscarUsuario(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT id, nome, email
            FROM usuarios
            WHERE id = :id
              AND status = 'Ativo'
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    private function buscarReserva(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM sala_reservas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

        return $reserva ?: null;
    }

    private function atualizarStatusSala(int $salaId, string $status): void
    {
        $stmt = $this->conn->prepare("UPDATE salas SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $status,
            ':id' => $salaId,
        ]);
    }

    private function restaurarStatusSalaSeSemManutencao(int $salaId): void
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM sala_reservas
            WHERE sala_id = :sala_id
              AND tipo = 'Manutencao'
              AND status = 'Ativo'
            LIMIT 1
        ");
        $stmt->execute([':sala_id' => $salaId]);

        if (! $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->atualizarStatusSala($salaId, 'ativa');
        }
    }

    private function aulasImpactadas(int $salaId, string $dataInicio, string $dataFim, string $horaInicio, string $horaFim): array
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM quadro_horario
            WHERE sala_id = :sala_id
              AND status = 'Ativa'
              AND data_aula BETWEEN :data_inicio AND :data_fim
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
        ");
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarAula(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM quadro_horario WHERE id = :id AND status = 'Ativa' LIMIT 1");
        $stmt->execute([':id' => $id]);
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);

        return $aula ?: null;
    }

    private function buscarAulaPorSalaHorario(int $salaId, string $data, string $horaInicio, string $horaFim, int $ignorarId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM quadro_horario
            WHERE sala_id = :sala_id
              AND data_aula = :data
              AND status = 'Ativa'
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
              AND id != :ignorar_id
            LIMIT 1
        ");
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
            ':ignorar_id' => $ignorarId,
        ]);
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);

        return $aula ?: null;
    }

    private function registrarTroca(int $aulaId, int $salaOrigemId, int $salaDestinoId, string $motivo): void
    {
        $usuarioId = (int) ($_SESSION['usuario']['id'] ?? 0);
        $stmt = $this->conn->prepare("
            INSERT INTO sala_trocas (quadro_horario_id, sala_origem_id, sala_destino_id, motivo, usuario_id)
            VALUES (:aula_id, :origem_id, :destino_id, :motivo, :usuario_id)
        ");
        $stmt->execute([
            ':aula_id' => $aulaId,
            ':origem_id' => $salaOrigemId > 0 ? $salaOrigemId : null,
            ':destino_id' => $salaDestinoId,
            ':motivo' => $motivo,
            ':usuario_id' => $usuarioId > 0 ? $usuarioId : null,
        ]);
    }

    private function garantirTabelaTrocas(): void
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS sala_trocas (
              id INT AUTO_INCREMENT PRIMARY KEY,
              quadro_horario_id INT NOT NULL,
              sala_origem_id INT NULL,
              sala_destino_id INT NOT NULL,
              motivo VARCHAR(255) NULL,
              usuario_id INT NULL,
              criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");
    }
}
