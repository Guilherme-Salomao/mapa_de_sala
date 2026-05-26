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

    private function contar(string $sql): int
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
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
        $base['visita_tecnica'] = ((int) ($base['visita_tecnica'] ?? 0) === 1 || (int) ($aula['visita_tecnica'] ?? 0) === 1) ? 1 : 0;
        $base['ead_assincrona'] = ((int) ($base['ead_assincrona'] ?? 0) === 1 || (int) ($aula['ead_assincrona'] ?? 0) === 1) ? 1 : 0;
        $base['aprendizagem_quadro_id'] = ! empty($base['aprendizagem_quadro_id'])
            ? $base['aprendizagem_quadro_id']
            : ($aula['aprendizagem_quadro_id'] ?? null);

        return $base;
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
}
