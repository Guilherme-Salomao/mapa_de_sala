<?php

require_once __DIR__ . '/../core/Database.php';

class RelatorioSala
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listarPorSituacao(string $data, string $situacao = 'todas'): array
    {
        $salas = $this->listarSalasBase();
        $ocupacoes = $this->ocupacoesPorSala($data);
        $reservas = $this->reservasPorSala($data);
        $resultado = [];

        foreach ($salas as $sala) {
            $salaId = (int) $sala['id'];
            $status = (string) ($sala['status'] ?? '');
            $ocupada = isset($ocupacoes[$salaId]);
            $reservada = isset($reservas[$salaId]);
            $emManutencao = $reservada && $this->temManutencao($reservas[$salaId]);
            $temReserva = $reservada && $this->temReservaOperacional($reservas[$salaId]);
            $turnos = $this->situacoesPorTurno($status, $ocupacoes[$salaId] ?? [], $reservas[$salaId] ?? []);

            if ($status === 'manutencao' || $emManutencao) {
                $situacaoSala = 'manutencao';
            } elseif ($ocupada) {
                $situacaoSala = 'ocupada';
            } elseif ($temReserva) {
                $situacaoSala = 'reservada';
            } elseif (in_array($status, ['ativa', 'livre', 'uso'], true)) {
                $situacaoSala = 'livre';
            } else {
                $situacaoSala = 'inativa';
            }

            if ($situacao !== 'todas' && ! in_array($situacao, array_column($turnos, 'situacao'), true)) {
                continue;
            }

            $sala['situacao_calculada'] = $situacaoSala;
            $sala['turnos'] = $turnos;
            $sala['aulas'] = $ocupacoes[$salaId] ?? [];
            $sala['reservas'] = $reservas[$salaId] ?? [];
            $resultado[] = $sala;
        }

        return $resultado;
    }

    public function totais(string $data): array
    {
        $totais = [
            'todas' => 0,
            'livre' => 0,
            'ocupada' => 0,
            'reservada' => 0,
            'manutencao' => 0,
            'inativa' => 0,
        ];

        foreach ($this->listarPorSituacao($data, 'todas') as $sala) {
            $situacao = (string) ($sala['situacao_calculada'] ?? 'inativa');
            $totais['todas']++;

            if (isset($totais[$situacao])) {
                $totais[$situacao]++;
            }
        }

        return $totais;
    }

    private function listarSalasBase(): array
    {
        $sql = "
            SELECT id, nome, tipo, capacidade, status, descricao
            FROM salas
            ORDER BY nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ocupacoesPorSala(string $data): array
    {
        $sql = "
            SELECT
                qh.sala_id,
                qh.hora_inicio,
                qh.hora_fim,
                co.nome AS turma_nome,
                uc.codigo AS uc_codigo,
                CASE WHEN COALESCE(cm.sem_uc, 0) = 1 THEN co.nome ELSE uc.nome END AS uc_nome,
                GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') AS docentes
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            LEFT JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            LEFT JOIN docentes d ON d.id = qhd.docente_id
            LEFT JOIN usuarios u ON u.id = d.usuario_id
            WHERE qh.data_aula = :data
              AND qh.status = 'Ativa'
            GROUP BY
                qh.id,
                qh.sala_id,
                qh.hora_inicio,
                qh.hora_fim,
                co.nome,
                cm.sem_uc,
                uc.codigo,
                uc.nome
            ORDER BY qh.hora_inicio ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':data' => $data]);

        $porSala = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $aula) {
            $salaId = (int) ($aula['sala_id'] ?? 0);

            if ($salaId > 0) {
                $porSala[$salaId][] = $aula;
            }
        }

        return $porSala;
    }

    private function reservasPorSala(string $data): array
    {
        $sql = "
            SELECT sala_id, tipo, hora_inicio, hora_fim, solicitante, motivo, descricao
            FROM sala_reservas
            WHERE status = 'Ativo'
              AND data_inicio <= :data
              AND data_fim >= :data
            ORDER BY hora_inicio ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':data' => $data]);

        $porSala = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $reserva) {
            $porSala[(int) $reserva['sala_id']][] = $reserva;
        }

        return $porSala;
    }

    private function temReservaOperacional(array $reservas): bool
    {
        foreach ($reservas as $reserva) {
            if (($reserva['tipo'] ?? '') === 'Reservada') {
                return true;
            }
        }

        return false;
    }

    private function temManutencao(array $reservas): bool
    {
        foreach ($reservas as $reserva) {
            if (($reserva['tipo'] ?? '') === 'Manutencao') {
                return true;
            }
        }

        return false;
    }

    private function situacoesPorTurno(string $status, array $aulas, array $reservas): array
    {
        $turnos = [
            'Manha' => ['label' => 'Manhã', 'inicio' => '00:00:00', 'fim' => '12:00:00'],
            'Tarde' => ['label' => 'Tarde', 'inicio' => '12:00:00', 'fim' => '18:00:00'],
            'Noite' => ['label' => 'Noite', 'inicio' => '18:00:00', 'fim' => '23:59:59'],
        ];
        $resultado = [];

        foreach ($turnos as $chave => $turno) {
            $aulasTurno = $this->filtrarPorSobreposicao($aulas, $turno['inicio'], $turno['fim']);
            $reservasTurno = $this->filtrarPorSobreposicao($reservas, $turno['inicio'], $turno['fim']);
            $manutencoesTurno = array_values(array_filter($reservasTurno, fn ($reserva) => ($reserva['tipo'] ?? '') === 'Manutencao'));
            $reservasOperacionais = array_values(array_filter($reservasTurno, fn ($reserva) => ($reserva['tipo'] ?? '') === 'Reservada'));

            if ($status === 'manutencao' || ! empty($manutencoesTurno)) {
                $situacao = 'manutencao';
            } elseif (! empty($aulasTurno)) {
                $situacao = 'ocupada';
            } elseif (! empty($reservasOperacionais)) {
                $situacao = 'reservada';
            } elseif (in_array($status, ['ativa', 'livre', 'uso'], true)) {
                $situacao = 'livre';
            } else {
                $situacao = 'inativa';
            }

            $resultado[$chave] = [
                'label' => $turno['label'],
                'situacao' => $situacao,
                'aulas' => $aulasTurno,
                'reservas' => $reservasTurno,
            ];
        }

        return $resultado;
    }

    private function filtrarPorSobreposicao(array $itens, string $inicio, string $fim): array
    {
        return array_values(array_filter($itens, function (array $item) use ($inicio, $fim): bool {
            return (string) ($item['hora_inicio'] ?? '') < $fim
                && (string) ($item['hora_fim'] ?? '') > $inicio;
        }));
    }
}
