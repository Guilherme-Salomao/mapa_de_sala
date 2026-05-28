<?php

require_once __DIR__ . '/../core/Database.php';

class AprendizagemQuadro
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
            SELECT
                aq.*,
                co.nome AS turma_nome,
                co.codigo_oferta,
                uc.codigo AS uc_codigo,
                uc.nome AS uc_nome,
                s.nome AS sala_nome,
                u.nome AS docente_nome,
                COUNT(qh.id) AS aulas_geradas
            FROM aprendizagem_quadros aq
            INNER JOIN cursos_ofertas co ON co.id = aq.curso_oferta_id
            INNER JOIN unidades_curriculares uc ON uc.id = aq.unidade_curricular_id
            INNER JOIN salas s ON s.id = aq.sala_id
            INNER JOIN docentes d ON d.id = aq.docente_id
            INNER JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN quadro_horario qh ON qh.aprendizagem_quadro_id = aq.id
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (co.nome LIKE :busca OR co.codigo_oferta LIKE :busca OR uc.nome LIKE :busca OR u.nome LIKE :busca OR s.nome LIKE :busca)";
            $params[':busca'] = '%' . $busca . '%';
        }

        if (in_array($status, ['Ativo', 'Inativo'], true)) {
            $sql .= " AND aq.status = :status";
            $params[':status'] = $status;
        }

        $sql .= "
            GROUP BY aq.id
            ORDER BY aq.data_inicio DESC, co.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTurmas(): array
    {
        $sql = "
            SELECT co.id, co.curso_modelo_id, co.nome, co.codigo_oferta, co.hora_inicio, co.hora_fim
            FROM cursos_ofertas co
            INNER JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE co.status = 'Em andamento'
              AND co.hora_inicio IS NOT NULL
              AND co.hora_fim IS NOT NULL
              AND (co.nome LIKE '%Aprendizagem%' OR cm.nome LIKE '%Aprendizagem%')
            ORDER BY co.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

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
                cm.nome AS curso_nome
            FROM unidades_curriculares uc
            INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
            WHERE uc.status = 'Ativa'
              AND cm.status = 'Ativo'
              AND cm.nome LIKE '%Aprendizagem%'
            ORDER BY cm.nome ASC, CHAR_LENGTH(uc.codigo) ASC, uc.codigo ASC, uc.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarSalas(): array
    {
        $sql = "
            SELECT id, nome, tipo
            FROM salas
            WHERE status IN ('ativa', 'livre', 'uso')
            ORDER BY nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDocentes(): array
    {
        $sql = "
            SELECT
                d.id,
                u.nome,
                d.area_atuacao,
                GROUP_CONCAT(duc.unidade_curricular_id ORDER BY duc.unidade_curricular_id SEPARATOR ',') AS ucs_vinculadas
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            INNER JOIN docente_unidades_curriculares duc ON duc.docente_id = d.id
            WHERE d.status = 'Ativo'
              AND d.area_atuacao = 'Aprendizagem'
            GROUP BY d.id, u.nome, d.area_atuacao
            ORDER BY u.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvarEGerar(array $dados): array
    {
        $turma = $this->buscarTurma((int) $dados['curso_oferta_id']);
        $uc = $this->buscarUc((int) $dados['unidade_curricular_id']);

        if (! $turma || ! $uc || (int) $uc['curso_modelo_id'] !== (int) $turma['curso_modelo_id']) {
            return ['sucesso' => false, 'mensagem' => 'Turma ou UC invalida para Aceleração.'];
        }

        if (! $this->docenteVinculadoUc((int) $dados['docente_id'], (int) $dados['unidade_curricular_id'])) {
            return ['sucesso' => false, 'mensagem' => 'Docente sem vinculo com a UC selecionada.'];
        }

        if (! $this->docenteAreaAprendizagem((int) $dados['docente_id'])) {
            return ['sucesso' => false, 'mensagem' => 'Docente precisa ser da area Aprendizagem para Aceleracao.'];
        }

        $horaInicio = substr((string) $turma['hora_inicio'], 0, 5);
        $horaFim = substr((string) $turma['hora_fim'], 0, 5);

        if ($horaInicio === '' || $horaFim === '' || strtotime($horaFim) <= strtotime($horaInicio)) {
            return ['sucesso' => false, 'mensagem' => 'A turma precisa ter hora inicial e final validas.'];
        }

        $datas = $this->datasUteisPorPeriodo($dados['data_inicio'], $dados['data_fim']);

        if (empty($datas)) {
            return ['sucesso' => false, 'mensagem' => 'Nao existe dia util no periodo informado.'];
        }

        try {
            $this->conn->beginTransaction();

            $aprendizagemId = $this->inserirProgramacao($dados);
            $geradas = 0;
            $puladas = [];

            foreach ($datas as $data) {
                $motivo = $this->motivoBloqueio(
                    (int) $dados['curso_oferta_id'],
                    (int) $dados['sala_id'],
                    (int) $dados['docente_id'],
                    $data,
                    $horaInicio,
                    $horaFim
                );

                if ($motivo !== null) {
                    $puladas[] = date('d/m/Y', strtotime($data)) . ' (' . $motivo . ')';
                    continue;
                }

                $aulaId = $this->inserirNoQuadro($aprendizagemId, $dados, $data, $horaInicio, $horaFim);
                $this->vincularDocente($aulaId, (int) $dados['docente_id']);
                $geradas++;
            }

            if ($geradas === 0) {
                $this->conn->rollBack();

                return [
                    'sucesso' => false,
                    'mensagem' => 'Nenhuma aula foi gerada. Verifique conflitos: ' . implode(', ', array_slice($puladas, 0, 5)),
                ];
            }

            $this->conn->commit();

            $mensagem = $geradas . ' aula(s) de Aceleração geradas no quadro horario.';

            if (! empty($puladas)) {
                $mensagem .= ' Datas nao geradas: ' . implode(', ', array_slice($puladas, 0, 5));

                if (count($puladas) > 5) {
                    $mensagem .= ' e mais ' . (count($puladas) - 5) . '.';
                }
            }

            return ['sucesso' => true, 'mensagem' => $mensagem];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return ['sucesso' => false, 'mensagem' => 'Nao foi possivel gerar Aceleração: ' . $e->getMessage()];
        }
    }

    public function excluir(int $id): bool
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("DELETE FROM quadro_horario WHERE aprendizagem_quadro_id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->conn->prepare("DELETE FROM aprendizagem_quadros WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    private function inserirProgramacao(array $dados): int
    {
        $sql = "
            INSERT INTO aprendizagem_quadros (
                curso_oferta_id,
                unidade_curricular_id,
                sala_id,
                docente_id,
                data_inicio,
                data_fim,
                status,
                observacoes
            ) VALUES (
                :curso_oferta_id,
                :unidade_curricular_id,
                :sala_id,
                :docente_id,
                :data_inicio,
                :data_fim,
                'Ativo',
                :observacoes
            )
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':curso_oferta_id' => $dados['curso_oferta_id'],
            ':unidade_curricular_id' => $dados['unidade_curricular_id'],
            ':sala_id' => $dados['sala_id'],
            ':docente_id' => $dados['docente_id'],
            ':data_inicio' => $dados['data_inicio'],
            ':data_fim' => $dados['data_fim'],
            ':observacoes' => $dados['observacoes'],
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function inserirNoQuadro(int $aprendizagemId, array $dados, string $data, string $horaInicio, string $horaFim): int
    {
        $observacoes = 'Aceleração';

        if ($dados['observacoes'] !== '') {
            $observacoes .= ' - ' . $dados['observacoes'];
        }

        $sql = "
            INSERT INTO quadro_horario (
                aprendizagem_quadro_id,
                curso_oferta_id,
                unidade_curricular_id,
                sala_id,
                data_aula,
                hora_inicio,
                hora_fim,
                divisao_por_hora,
                dupla_docencia,
                visita_tecnica,
                ead_assincrona,
                status,
                observacoes
            ) VALUES (
                :aprendizagem_quadro_id,
                :curso_oferta_id,
                :unidade_curricular_id,
                :sala_id,
                :data_aula,
                :hora_inicio,
                :hora_fim,
                0,
                0,
                0,
                0,
                'Ativa',
                :observacoes
            )
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':aprendizagem_quadro_id' => $aprendizagemId,
            ':curso_oferta_id' => $dados['curso_oferta_id'],
            ':unidade_curricular_id' => $dados['unidade_curricular_id'],
            ':sala_id' => $dados['sala_id'],
            ':data_aula' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
            ':observacoes' => $observacoes,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function vincularDocente(int $aulaId, int $docenteId): void
    {
        $sql = "
            INSERT INTO quadro_horario_docentes (quadro_horario_id, docente_id)
            VALUES (:quadro_horario_id, :docente_id)
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':quadro_horario_id' => $aulaId,
            ':docente_id' => $docenteId,
        ]);
    }

    private function motivoBloqueio(int $turmaId, int $salaId, int $docenteId, string $data, string $horaInicio, string $horaFim): ?string
    {
        if ($this->dataBloqueada($data, $turmaId, $horaInicio, $horaFim)) {
            return 'calendario bloqueado';
        }

        if ($this->turmaOcupada($turmaId, $data, $horaInicio, $horaFim)) {
            return 'turma ja possui aula';
        }

        if ($this->salaOcupada($salaId, $data, $horaInicio, $horaFim)) {
            return 'sala ocupada';
        }

        if ($this->salaReservada($salaId, $data, $horaInicio, $horaFim)) {
            return 'sala reservada/manutenção';
        }

        if ($this->docenteOcupado($docenteId, $data, $horaInicio, $horaFim)) {
            return 'docente ocupado';
        }

        if ($this->docenteEmEducacaoCorporativa($docenteId, $data)) {
            return 'docente em curso';
        }

        return null;
    }

    private function dataBloqueada(string $data, int $turmaId, string $horaInicio, string $horaFim): bool
    {
        $turma = $this->buscarTurma($turmaId);

        $stmt = $this->conn->prepare("
            SELECT tipo, hora_inicio, hora_fim
            FROM calendario_bloqueios
            WHERE status = 'Ativo'
              AND data <= :data
              AND COALESCE(data_fim, data) >= :data
        ");
        $stmt->execute([':data' => $data]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $bloqueio) {
            if (! $this->bloqueioConflitaHorario($bloqueio, $horaInicio, $horaFim)) {
                continue;
            }

            if (($bloqueio['tipo'] ?? '') !== 'Parada Pedagogica') {
                return true;
            }

            if ((int) ($turma['participa_parada_pedagogica'] ?? 1) === 1) {
                return true;
            }
        }

        return false;
    }

    private function bloqueioConflitaHorario(array $bloqueio, string $horaInicio, string $horaFim): bool
    {
        $bloqueioInicio = (string) ($bloqueio['hora_inicio'] ?? '');
        $bloqueioFim = (string) ($bloqueio['hora_fim'] ?? '');

        if ($bloqueioInicio === '' || $bloqueioFim === '') {
            return true;
        }

        return $bloqueioInicio < $horaFim && $bloqueioFim > $horaInicio;
    }

    private function turmaOcupada(int $turmaId, string $data, string $horaInicio, string $horaFim): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM quadro_horario
            WHERE curso_oferta_id = :turma_id
              AND data_aula = :data
              AND status = 'Ativa'
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ");
        $stmt->execute([
            ':turma_id' => $turmaId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function salaOcupada(int $salaId, string $data, string $horaInicio, string $horaFim): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM quadro_horario
            WHERE sala_id = :sala_id
              AND data_aula = :data
              AND status = 'Ativa'
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ");
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteOcupado(int $docenteId, string $data, string $horaInicio, string $horaFim): bool
    {
        $stmt = $this->conn->prepare("
            SELECT qh.id
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            WHERE qhd.docente_id = :docente_id
              AND qh.data_aula = :data
              AND qh.status = 'Ativa'
              AND qh.hora_inicio < :hora_fim
              AND qh.hora_fim > :hora_inicio
            LIMIT 1
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function salaReservada(int $salaId, string $data, string $horaInicio, string $horaFim): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM sala_reservas
            WHERE sala_id = :sala_id
              AND status = 'Ativo'
              AND data_inicio <= :data
              AND data_fim >= :data
              AND hora_inicio < :hora_fim
              AND hora_fim > :hora_inicio
            LIMIT 1
        ");
        $stmt->execute([
            ':sala_id' => $salaId,
            ':data' => $data,
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteEmEducacaoCorporativa(int $docenteId, string $data): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM educacao_corporativa_docentes
            WHERE docente_id = :docente_id
              AND data = :data
              AND status = 'Ativo'
            LIMIT 1
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':data' => $data,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteVinculadoUc(int $docenteId, int $ucId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT docente_id
            FROM docente_unidades_curriculares
            WHERE docente_id = :docente_id
              AND unidade_curricular_id = :uc_id
            LIMIT 1
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':uc_id' => $ucId,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteAreaAprendizagem(int $docenteId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM docentes
            WHERE id = :docente_id
              AND status = 'Ativo'
              AND area_atuacao = 'Aprendizagem'
            LIMIT 1
        ");
        $stmt->execute([':docente_id' => $docenteId]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function buscarTurma(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT
                id,
                curso_modelo_id,
                hora_inicio,
                hora_fim,
                participa_parada_pedagogica,
                aula_segunda,
                aula_terca,
                aula_quarta,
                aula_quinta,
                aula_sexta,
                aula_sabado
            FROM cursos_ofertas
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $turma = $stmt->fetch(PDO::FETCH_ASSOC);

        return $turma ?: null;
    }

    private function buscarUc(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT id, curso_modelo_id
            FROM unidades_curriculares
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $uc = $stmt->fetch(PDO::FETCH_ASSOC);

        return $uc ?: null;
    }

    private function datasUteisPorPeriodo(string $dataInicio, string $dataFim): array
    {
        $inicio = strtotime($dataInicio);
        $fim = strtotime($dataFim);

        if ($inicio === false || $fim === false || $fim < $inicio) {
            return [];
        }

        $datas = [];
        $dataAtual = date('Y-m-d', $inicio);

        while (strtotime($dataAtual) <= $fim) {
            if ((int) date('N', strtotime($dataAtual)) <= 5) {
                $datas[] = $dataAtual;
            }

            $dataAtual = date('Y-m-d', strtotime($dataAtual . ' +1 day'));
        }

        return $datas;
    }
}
