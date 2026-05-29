<?php

require_once __DIR__ . '/../core/Database.php';

class Home
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function indicadores(string $data): array
    {
        return [
            'total_salas' => $this->contar("SELECT COUNT(*) AS total FROM salas"),
            'salas_ocupadas' => $this->contarSalasOcupadas($data),
            'salas_livres' => $this->contarSalasLivres($data),
            'salas_manutencao' => $this->contarSalasManutencao($data),
            'salas_reservadas' => $this->contarSalasReservadas($data, 'Reservada'),
        ];
    }

    public function aulasDoDia(string $data, array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $sql = "
            SELECT
                qh.id,
                qh.data_aula,
                qh.hora_inicio,
                qh.hora_fim,
                qh.curso_oferta_id,
                qh.sala_id,
                qh.visita_tecnica,
                qh.ead_assincrona,
                qh.aprendizagem_quadro_id,
                co.nome AS turma_nome,
                co.codigo_oferta,
                CASE
                    WHEN qh.hora_inicio < '12:00:00' AND qh.hora_fim > '18:00:00' THEN 'Integral'
                    WHEN qh.hora_inicio < '12:00:00' AND qh.hora_fim > '12:00:00' THEN 'Integral'
                    WHEN qh.hora_inicio < '12:00:00' THEN 'Manha'
                    WHEN qh.hora_inicio < '18:00:00' THEN 'Tarde'
                    ELSE 'Noite'
                END AS periodo,
                uc.codigo AS uc_codigo,
                uc.nome AS uc_nome,
                s.nome AS sala_nome,
                GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS docentes
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            LEFT JOIN salas s ON s.id = qh.sala_id
            LEFT JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            LEFT JOIN docentes d ON d.id = qhd.docente_id
            LEFT JOIN usuarios u ON u.id = d.usuario_id
            WHERE qh.data_aula = :data
              AND qh.status = 'Ativa'
        ";

        $params = [':data' => $data];
        $this->aplicarEscopoAulas($sql, $params, $escopo);

        $sql .= "
            GROUP BY
                qh.id,
                qh.data_aula,
                qh.hora_inicio,
                qh.hora_fim,
                qh.curso_oferta_id,
                qh.sala_id,
                qh.visita_tecnica,
                qh.ead_assincrona,
                qh.aprendizagem_quadro_id,
                co.nome,
                co.codigo_oferta,
                periodo,
                uc.codigo,
                uc.nome,
                s.nome
            ORDER BY qh.hora_inicio ASC, s.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function proximasAulas(string $data, int $limite = 6): array
    {
        $sql = "
            SELECT
                qh.id,
                qh.data_aula,
                qh.hora_inicio,
                qh.hora_fim,
                co.nome AS turma_nome,
                uc.codigo AS uc_codigo,
                uc.nome AS uc_nome,
                s.nome AS sala_nome
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            LEFT JOIN salas s ON s.id = qh.sala_id
            WHERE qh.data_aula > :data
              AND qh.status = 'Ativa'
            ORDER BY qh.data_aula ASC, qh.hora_inicio ASC
            LIMIT :limite
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':data', $data);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function aulasPorTurno(string $data, array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $aulas = $this->aulasDoDia($data, $escopo);
        $reservas = $this->reservasDoDia($data);
        $turnos = [
            'Manha' => [],
            'Tarde' => [],
            'Noite' => [],
        ];

        foreach ($aulas as $aula) {
            $periodo = $this->normalizarPeriodo((string) ($aula['periodo'] ?? ''));
            $aula['ucs_mapa'] = $this->textoUcMapa($aula);

            if ($periodo === '' || ! isset($turnos[$periodo])) {
                continue;
            }

            $chave = $periodo . '|' . (int) ($aula['sala_id'] ?? 0) . '|' . (int) ($aula['curso_oferta_id'] ?? 0);

            if (! isset($turnos[$periodo][$chave])) {
                $turnos[$periodo][$chave] = $aula;
                continue;
            }

            $turnos[$periodo][$chave] = $this->mesclarAulaMapa($turnos[$periodo][$chave], $aula);
        }

        foreach ($reservas as $reserva) {
            foreach ($this->periodosPorHorario((string) $reserva['hora_inicio'], (string) $reserva['hora_fim']) as $periodo) {
                $chave = $periodo . '|reserva|' . (int) $reserva['id'];
                $turnos[$periodo][$chave] = [
                    'id' => 'reserva-' . (int) $reserva['id'],
                    'data_aula' => $data,
                    'hora_inicio' => $reserva['hora_inicio'],
                    'hora_fim' => $reserva['hora_fim'],
                    'curso_oferta_id' => 0,
                    'sala_id' => $reserva['sala_id'],
                    'sala_nome' => $reserva['sala_nome'],
                    'turma_nome' => $reserva['motivo'] ?: 'Sala reservada',
                    'ucs_mapa' => '',
                    'docentes' => '',
                    'solicitante_nome' => $reserva['solicitante_nome'] ?? '',
                    'tipo_reserva' => $reserva['tipo'] ?? 'Reservada',
                    'visita_tecnica' => 0,
                    'ead_assincrona' => 0,
                    'aprendizagem_quadro_id' => null,
                ];
            }
        }

        foreach ($turnos as $periodo => $aulasTurno) {
            $turnos[$periodo] = array_values($aulasTurno);
        }

        return $turnos;
    }

    public function ocupacaoPorPeriodo(string $data): array
    {
        $sql = "
            SELECT
                CASE
                    WHEN qh.hora_inicio < '12:00:00' AND qh.hora_fim > '18:00:00' THEN 'Integral'
                    WHEN qh.hora_inicio < '12:00:00' AND qh.hora_fim > '12:00:00' THEN 'Integral'
                    WHEN qh.hora_inicio < '12:00:00' THEN 'Manha'
                    WHEN qh.hora_inicio < '18:00:00' THEN 'Tarde'
                    ELSE 'Noite'
                END AS periodo,
                COUNT(*) AS total
            FROM quadro_horario qh
            WHERE qh.data_aula = :data
              AND qh.status = 'Ativa'
            GROUP BY periodo
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':data' => $data]);

        $periodos = [
            'Manha' => 0,
            'Tarde' => 0,
            'Noite' => 0,
        ];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $periodo = $this->normalizarPeriodo((string) ($linha['periodo'] ?? ''));

            if ($periodo !== '') {
                $periodos[$periodo] = (int) $linha['total'];
            }
        }

        return $periodos;
    }

    public function semanaDocente(int $docenteId, string $dataReferencia): array
    {
        $timestamp = strtotime($dataReferencia);

        if ($timestamp === false) {
            return [];
        }

        $inicioSemana = date('Y-m-d', strtotime('monday this week', $timestamp));
        $fimSemana = date('Y-m-d', strtotime($inicioSemana . ' +5 days'));
        $escala = $this->escalaDocente($docenteId);
        $aulas = $this->aulasDocentePeriodo($docenteId, $inicioSemana, $fimSemana);
        $cursos = $this->cursosCorporativosDocentePeriodo($docenteId, $inicioSemana, $fimSemana);
        $aulasPorData = [];
        $cursosPorData = [];
        $semana = [];

        foreach ($aulas as $aula) {
            $aulasPorData[(string) $aula['data_aula']][] = $aula;
        }

        foreach ($cursos as $curso) {
            $cursosPorData[(string) $curso['data']][] = $curso;
        }

        for ($i = 0; $i < 6; $i++) {
            $data = date('Y-m-d', strtotime($inicioSemana . ' +' . $i . ' days'));
            $diaKey = $this->diaSemanaKey($data);
            $eventos = [];
            $periodosComAula = [];

            foreach (($aulasPorData[$data] ?? []) as $aula) {
                $periodo = $this->normalizarPeriodoPorHorario((string) $aula['hora_inicio']);
                $periodosComAula[$periodo] = true;
                $eventos[] = [
                    'tipo' => 'aula',
                    'periodo' => $periodo,
                    'hora' => substr((string) $aula['hora_inicio'], 0, 5) . ' - ' . substr((string) $aula['hora_fim'], 0, 5),
                    'titulo' => $aula['turma_nome'] ?? '',
                    'uc' => trim(($aula['uc_codigo'] ?? '') . ' - ' . ($aula['uc_nome'] ?? '')),
                    'sala' => $aula['sala_nome'] ?? '',
                    'visita_tecnica' => (int) ($aula['visita_tecnica'] ?? 0),
                    'ead_assincrona' => (int) ($aula['ead_assincrona'] ?? 0),
                    'aprendizagem_quadro_id' => $aula['aprendizagem_quadro_id'] ?? null,
                ];
            }

            if (! empty($cursosPorData[$data]) && ! empty($escala[$diaKey])) {
                $periodosCurso = array_map(fn($item) => $item['periodo'], $escala[$diaKey]);
                $eventos[] = [
                    'tipo' => 'curso',
                    'periodo' => implode(' / ', array_unique($periodosCurso)),
                    'hora' => '',
                    'titulo' => 'Educação Corporativa',
                    'uc' => $cursosPorData[$data][0]['titulo'] ?? '',
                    'sala' => '',
                    'visita_tecnica' => 0,
                    'ead_assincrona' => 0,
                    'aprendizagem_quadro_id' => null,
                ];
            } else {
                foreach (($escala[$diaKey] ?? []) as $itemEscala) {
                    $periodo = (string) ($itemEscala['periodo'] ?? '');

                    if ($periodo === '' || isset($periodosComAula[$periodo])) {
                        continue;
                    }

                    $eventos[] = [
                        'tipo' => 'planejamento',
                        'periodo' => $periodo,
                        'hora' => $this->formatarHoras((float) ($itemEscala['horas'] ?? 0)),
                        'titulo' => 'Planejamento',
                        'uc' => '',
                        'sala' => '',
                        'visita_tecnica' => 0,
                        'ead_assincrona' => 0,
                        'aprendizagem_quadro_id' => null,
                    ];
                }
            }

            $semana[] = [
                'data' => $data,
                'dia_nome' => $this->diaSemanaLabel($data),
                'dia_mes' => date('d/m', strtotime($data)),
                'hoje' => $data === date('Y-m-d'),
                'eventos' => $eventos,
            ];
        }

        return $semana;
    }

    public function indicadoresDocente(int $docenteId, string $dataReferencia): array
    {
        $timestamp = strtotime($dataReferencia);

        if ($timestamp === false) {
            return [
                'horas_semana' => 0,
                'horas_mes' => 0,
                'percentual_aula' => 0,
                'percentual_planejamento' => 0,
                'percentual_curso' => 0,
            ];
        }

        $inicioSemana = date('Y-m-d', strtotime('monday this week', $timestamp));
        $fimSemana = date('Y-m-d', strtotime($inicioSemana . ' +5 days'));
        $inicioMes = date('Y-m-01', $timestamp);
        $fimMes = date('Y-m-t', $timestamp);
        $resumoSemana = $this->resumoDocentePeriodo($docenteId, $inicioSemana, $fimSemana);
        $resumoMes = $this->resumoDocentePeriodo($docenteId, $inicioMes, $fimMes);
        $totalMes = $resumoMes['aula'] + $resumoMes['planejamento'] + $resumoMes['curso'];

        return [
            'horas_semana' => $resumoSemana['aula'] + $resumoSemana['planejamento'] + $resumoSemana['curso'],
            'horas_mes' => $totalMes,
            'percentual_aula' => $totalMes > 0 ? round(($resumoMes['aula'] / $totalMes) * 100, 1) : 0,
            'percentual_planejamento' => $totalMes > 0 ? round(($resumoMes['planejamento'] / $totalMes) * 100, 1) : 0,
            'percentual_curso' => $totalMes > 0 ? round(($resumoMes['curso'] / $totalMes) * 100, 1) : 0,
        ];
    }

    public function indicadoresGestor(string $data, array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        return [
            'turmas_em_andamento' => $this->contarTurmasGestor($escopo),
            'docentes_ativos' => $this->contarDocentesGestor($escopo),
            'docentes_em_aula' => $this->contarDocentesEmAulaGestor($data, $escopo),
            'aulas_sem_docente' => $this->contarAulasSemDocenteGestor($data, $escopo),
            'docentes_planejamento' => $this->contarDocentesPlanejamentoGestor($data, $escopo),
        ];
    }

    public function resumoDocentesGestor(int $mes, int $ano, array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $docentes = $this->listarDocentesGestor($escopo);
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));
        $resumos = [];

        foreach ($docentes as $docente) {
            $resumo = $this->resumoDocentePeriodo((int) $docente['id'], $inicio, $fim);
            $total = $resumo['aula'] + $resumo['planejamento'] + $resumo['curso'];

            $resumos[] = [
                'docente_nome' => $docente['nome'] ?? '',
                'area_atuacao' => $docente['area_atuacao'] ?? '',
                'horas_semanais' => (float) ($docente['horas_semanais'] ?? 0),
                'percentual_aula' => $total > 0 ? round(($resumo['aula'] / $total) * 100, 1) : 0,
                'percentual_planejamento' => $total > 0 ? round(($resumo['planejamento'] / $total) * 100, 1) : 0,
                'percentual_curso' => $total > 0 ? round(($resumo['curso'] / $total) * 100, 1) : 0,
            ];
        }

        return array_slice($resumos, 0, 8);
    }

    private function contar(string $sql): int
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    private function contarTurmasGestor(array $escopo): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM cursos_ofertas co
            LEFT JOIN curso_modelos cm ON cm.id = co.curso_modelo_id
            WHERE co.status = 'Em andamento'
        ";
        $params = [];
        $this->aplicarEscopoCursos($sql, $params, $escopo);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    private function contarDocentesGestor(array $escopo): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM docentes d
            LEFT JOIN areas a ON a.nome = d.area_atuacao
            WHERE d.status = 'Ativo'
        ";
        $params = [];
        $this->aplicarEscopoDocentes($sql, $params, $escopo);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    private function contarDocentesEmAulaGestor(string $data, array $escopo): int
    {
        $sql = "
            SELECT COUNT(DISTINCT qhd.docente_id) AS total
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            WHERE qh.data_aula = :data
              AND qh.status = 'Ativa'
        ";
        $params = [':data' => $data];
        $this->aplicarEscopoAulas($sql, $params, $escopo);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    private function contarDocentesPlanejamentoGestor(string $data, array $escopo): int
    {
        $docentes = $this->listarDocentesGestor($escopo);
        $total = 0;

        foreach ($docentes as $docente) {
            $docenteId = (int) ($docente['id'] ?? 0);

            if ($docenteId <= 0) {
                continue;
            }

            if (! $this->docenteTemEscalaNaData($docenteId, $data)) {
                continue;
            }

            if ($this->docenteTemAulaNaData($docenteId, $data)) {
                continue;
            }

            if ($this->docenteTemCursoNaData($docenteId, $data)) {
                continue;
            }

            $total++;
        }

        return $total;
    }

    private function contarAulasSemDocenteGestor(string $data, array $escopo): int
    {
        $sql = "
            SELECT COUNT(DISTINCT qh.id) AS total
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            LEFT JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            WHERE qh.data_aula = :data
              AND qh.status = 'Ativa'
              AND qhd.id IS NULL
        ";
        $params = [':data' => $data];
        $this->aplicarEscopoAulas($sql, $params, $escopo);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    private function listarDocentesGestor(array $escopo): array
    {
        $sql = "
            SELECT d.id, d.area_atuacao, d.horas_semanais, u.nome
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN areas a ON a.nome = d.area_atuacao
            WHERE d.status = 'Ativo'
        ";
        $params = [];
        $this->aplicarEscopoDocentes($sql, $params, $escopo);
        $sql .= " ORDER BY u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function docenteTemEscalaNaData(int $docenteId, string $data): bool
    {
        $dia = $this->diaSemanaLabel($data);

        if ($dia === '') {
            return false;
        }

        $stmt = $this->conn->prepare("
            SELECT id
            FROM docente_escala
            WHERE docente_id = :docente_id
              AND dia_semana = :dia_semana
            LIMIT 1
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':dia_semana' => $dia,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteTemAulaNaData(int $docenteId, string $data): bool
    {
        $stmt = $this->conn->prepare("
            SELECT qh.id
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            WHERE qhd.docente_id = :docente_id
              AND qh.data_aula = :data
              AND qh.status = 'Ativa'
            LIMIT 1
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':data' => $data,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function docenteTemCursoNaData(int $docenteId, string $data): bool
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

    private function escalaDocente(int $docenteId): array
    {
        $stmt = $this->conn->prepare("
            SELECT dia_semana, periodo, horas
            FROM docente_escala
            WHERE docente_id = :docente_id
        ");
        $stmt->execute([':docente_id' => $docenteId]);
        $escala = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $dia = $this->normalizarDiaSemana((string) ($item['dia_semana'] ?? ''));
            $periodo = $this->normalizarPeriodo((string) ($item['periodo'] ?? ''));

            if ($dia === '' || $periodo === '') {
                continue;
            }

            $escala[$dia][] = [
                'periodo' => $periodo,
                'horas' => (float) ($item['horas'] ?? 0),
            ];
        }

        return $escala;
    }

    private function resumoDocentePeriodo(int $docenteId, string $inicio, string $fim): array
    {
        $escala = $this->escalaDocente($docenteId);
        $aulas = $this->aulasDocentePeriodo($docenteId, $inicio, $fim);
        $cursos = $this->cursosCorporativosDocentePeriodo($docenteId, $inicio, $fim);
        $aulasPorData = [];
        $cursosPorData = [];
        $resumo = [
            'aula' => 0.0,
            'planejamento' => 0.0,
            'curso' => 0.0,
        ];

        foreach ($aulas as $aula) {
            $aulasPorData[(string) $aula['data_aula']][] = $aula;
        }

        foreach ($cursos as $curso) {
            $cursosPorData[(string) $curso['data']][] = $curso;
        }

        $data = $inicio;

        while (strtotime($data) !== false && strtotime($data) <= strtotime($fim)) {
            $diaKey = $this->diaSemanaKey($data);
            $escalaData = $escala[$diaKey] ?? [];
            $periodosComAula = [];

            foreach (($aulasPorData[$data] ?? []) as $aula) {
                $periodo = $this->normalizarPeriodoPorHorario((string) $aula['hora_inicio']);
                $periodosComAula[$periodo] = true;
                $resumo['aula'] += $this->horasEntre((string) $aula['hora_inicio'], (string) $aula['hora_fim']);
            }

            if (! empty($cursosPorData[$data]) && ! empty($escalaData)) {
                foreach ($escalaData as $itemEscala) {
                    $resumo['curso'] += (float) ($itemEscala['horas'] ?? 0);
                }

                $data = date('Y-m-d', strtotime($data . ' +1 day'));
                continue;
            }

            foreach ($escalaData as $itemEscala) {
                $periodo = (string) ($itemEscala['periodo'] ?? '');

                if ($periodo === '' || isset($periodosComAula[$periodo])) {
                    continue;
                }

                $resumo['planejamento'] += (float) ($itemEscala['horas'] ?? 0);
            }

            $data = date('Y-m-d', strtotime($data . ' +1 day'));
        }

        return $resumo;
    }

    private function horasEntre(string $horaInicio, string $horaFim): float
    {
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return 0.0;
        }

        return ($fim - $inicio) / 3600;
    }

    private function aulasDocentePeriodo(int $docenteId, string $inicio, string $fim): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                qh.data_aula,
                qh.hora_inicio,
                qh.hora_fim,
                qh.visita_tecnica,
                qh.ead_assincrona,
                qh.aprendizagem_quadro_id,
                co.nome AS turma_nome,
                uc.codigo AS uc_codigo,
                uc.nome AS uc_nome,
                s.nome AS sala_nome
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            LEFT JOIN salas s ON s.id = qh.sala_id
            WHERE qhd.docente_id = :docente_id
              AND qh.status = 'Ativa'
              AND qh.data_aula BETWEEN :inicio AND :fim
            ORDER BY qh.data_aula ASC, qh.hora_inicio ASC
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function cursosCorporativosDocentePeriodo(int $docenteId, string $inicio, string $fim): array
    {
        $stmt = $this->conn->prepare("
            SELECT data, titulo
            FROM educacao_corporativa_docentes
            WHERE docente_id = :docente_id
              AND status = 'Ativo'
              AND data BETWEEN :inicio AND :fim
            ORDER BY data ASC
        ");
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function contarSalasOcupadas(string $data): int
    {
        $sql = "
            SELECT COUNT(DISTINCT qh.sala_id) AS total
            FROM quadro_horario qh
            INNER JOIN salas s ON s.id = qh.sala_id
            WHERE qh.data_aula = :data
              AND qh.status = 'Ativa'
              AND s.status IN ('ativa', 'livre', 'uso')
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':data' => $data]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    private function contarSalasManutencao(string $data): int
    {
        $sql = "
            SELECT COUNT(DISTINCT sala_id) AS total
            FROM (
                SELECT id AS sala_id
                FROM salas
                WHERE status = 'manutencao'
                UNION
                SELECT sala_id
                FROM sala_reservas
                WHERE status = 'Ativo'
                  AND tipo = 'Manutencao'
                  AND data_inicio <= :data
                  AND data_fim >= :data
            ) manutencoes
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':data' => $data]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    private function contarSalasLivres(string $data): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM salas
            WHERE status IN ('ativa', 'livre', 'uso')
        ";

        $salasDisponiveis = $this->contar($sql);
        $salasOcupadas = $this->contarSalasOcupadas($data);
        $salasBloqueadas = $this->contarSalasReservadas($data, 'Reservada');

        return max(0, $salasDisponiveis - $salasOcupadas - $salasBloqueadas);
    }

    private function contarSalasReservadas(string $data, ?string $tipo = null): int
    {
        $sql = "
            SELECT COUNT(DISTINCT sala_id) AS total
            FROM sala_reservas
            WHERE status = 'Ativo'
              AND data_inicio <= :data
              AND data_fim >= :data
        ";

        $params = [':data' => $data];

        if ($tipo !== null) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $tipo;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
    }

    private function reservasDoDia(string $data): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                sr.id,
                sr.sala_id,
                sr.tipo,
                sr.hora_inicio,
                sr.hora_fim,
                sr.motivo,
                s.nome AS sala_nome,
                u.nome AS solicitante_nome
            FROM sala_reservas sr
            INNER JOIN salas s ON s.id = sr.sala_id
            LEFT JOIN usuarios u ON u.id = sr.solicitante_usuario_id
            WHERE sr.status = 'Ativo'
              AND sr.data_inicio <= :data
              AND sr.data_fim >= :data
              AND sr.tipo = 'Reservada'
            ORDER BY sr.hora_inicio ASC, s.nome ASC
        ");
        $stmt->execute([':data' => $data]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function mesclarAulaMapa(array $base, array $aula): array
    {
        $base['hora_inicio'] = min((string) ($base['hora_inicio'] ?? ''), (string) ($aula['hora_inicio'] ?? ''));
        $base['hora_fim'] = max((string) ($base['hora_fim'] ?? ''), (string) ($aula['hora_fim'] ?? ''));
        $base['docentes'] = $this->mesclarNomes(
            (string) ($base['docentes'] ?? ''),
            (string) ($aula['docentes'] ?? '')
        );
        $base['ucs_mapa'] = $this->mesclarNomes(
            (string) ($base['ucs_mapa'] ?? $this->textoUcMapa($base)),
            (string) ($aula['ucs_mapa'] ?? $this->textoUcMapa($aula))
        );
        $base['visita_tecnica'] = ((int) ($base['visita_tecnica'] ?? 0) === 1 || (int) ($aula['visita_tecnica'] ?? 0) === 1) ? 1 : 0;
        $base['ead_assincrona'] = ((int) ($base['ead_assincrona'] ?? 0) === 1 || (int) ($aula['ead_assincrona'] ?? 0) === 1) ? 1 : 0;
        $base['aprendizagem_quadro_id'] = ! empty($base['aprendizagem_quadro_id'])
            ? $base['aprendizagem_quadro_id']
            : ($aula['aprendizagem_quadro_id'] ?? null);

        return $base;
    }

    private function textoUcMapa(array $aula): string
    {
        $codigo = trim((string) ($aula['uc_codigo'] ?? ''));
        $nome = trim((string) ($aula['uc_nome'] ?? ''));

        if ($codigo === '' && $nome === '') {
            return '';
        }

        return trim($codigo . ($nome !== '' ? ' - ' . $nome : ''));
    }

    private function mesclarNomes(string ...$listas): string
    {
        $nomes = [];

        foreach ($listas as $lista) {
            foreach (array_map('trim', explode(',', $lista)) as $nome) {
                if ($nome !== '') {
                    $nomes[$nome] = true;
                }
            }
        }

        return implode(', ', array_keys($nomes));
    }

    private function periodosPorHorario(string $horaInicio, string $horaFim): array
    {
        $periodos = [];
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return $periodos;
        }

        $faixas = [
            'Manha' => ['00:00:00', '12:00:00'],
            'Tarde' => ['12:00:00', '18:00:00'],
            'Noite' => ['18:00:00', '23:59:59'],
        ];

        foreach ($faixas as $periodo => [$faixaInicio, $faixaFim]) {
            if ($inicio < strtotime($faixaFim) && $fim > strtotime($faixaInicio)) {
                $periodos[] = $periodo;
            }
        }

        return $periodos;
    }

    private function normalizarPeriodo(string $periodo): string
    {
        $periodo = strtolower(trim($periodo));

        if (str_contains($periodo, 'manh')) {
            return 'Manha';
        }

        if (str_contains($periodo, 'tarde')) {
            return 'Tarde';
        }

        if (str_contains($periodo, 'noite')) {
            return 'Noite';
        }

        return '';
    }

    private function normalizarPeriodoPorHorario(string $horaInicio): string
    {
        $hora = substr($horaInicio, 0, 5);

        if ($hora < '12:00') {
            return 'Manha';
        }

        if ($hora < '18:00') {
            return 'Tarde';
        }

        return 'Noite';
    }

    private function diaSemanaKey(string $data): string
    {
        return [
            1 => 'segunda',
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado',
            7 => 'domingo',
        ][(int) date('N', strtotime($data))] ?? '';
    }

    private function diaSemanaLabel(string $data): string
    {
        return [
            1 => 'Segunda',
            2 => 'Terça',
            3 => 'Quarta',
            4 => 'Quinta',
            5 => 'Sexta',
            6 => 'Sábado',
            7 => 'Domingo',
        ][(int) date('N', strtotime($data))] ?? '';
    }

    private function normalizarDiaSemana(string $dia): string
    {
        $dia = strtolower($dia);
        $dia = str_replace(
            ['á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'õ', 'ô', 'ú', 'ç'],
            ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
            $dia
        );

        if (str_contains($dia, 'segunda')) {
            return 'segunda';
        }

        if (str_contains($dia, 'ter')) {
            return 'terca';
        }

        if (str_contains($dia, 'quarta')) {
            return 'quarta';
        }

        if (str_contains($dia, 'quinta')) {
            return 'quinta';
        }

        if (str_contains($dia, 'sexta')) {
            return 'sexta';
        }

        if (str_contains($dia, 'sab')) {
            return 'sabado';
        }

        return '';
    }

    private function aplicarEscopoAulas(string &$sql, array &$params, array $escopo): void
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
            $placeholder = ':escopo_aula_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        if ($tipo === 'areas') {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM curso_modelos cm_escopo
                WHERE cm_escopo.id = co.curso_modelo_id
                  AND cm_escopo.area_id IN (" . implode(',', $placeholders) . ")
            )";
            return;
        }

        if ($tipo === 'ucs') {
            $sql .= " AND qh.unidade_curricular_id IN (" . implode(',', $placeholders) . ")";
        }
    }

    private function aplicarEscopoCursos(string &$sql, array &$params, array $escopo): void
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
            $placeholder = ':escopo_curso_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND cm.area_id IN (" . implode(',', $placeholders) . ")";
    }

    private function aplicarEscopoDocentes(string &$sql, array &$params, array $escopo): void
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
            $placeholder = ':escopo_docente_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND a.id IN (" . implode(',', $placeholders) . ")";
    }

    private function formatarHoras(float $horas): string
    {
        if (fmod($horas, 1.0) === 0.0) {
            return (int) $horas . 'h';
        }

        $horasInteiras = (int) floor($horas);
        $minutos = (int) round(($horas - $horasInteiras) * 60);

        return $horasInteiras . 'h' . str_pad((string) $minutos, 2, '0', STR_PAD_LEFT);
    }
}
