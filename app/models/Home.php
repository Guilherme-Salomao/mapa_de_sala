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
            'salas_manutencao' => $this->contar("SELECT COUNT(*) AS total FROM salas WHERE status = 'manutencao'"),
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
                co.nome AS turma_nome,
                co.codigo_oferta,
                co.periodo,
                uc.codigo AS uc_codigo,
                uc.nome AS uc_nome,
                s.nome AS sala_nome,
                GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS docentes
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            INNER JOIN salas s ON s.id = qh.sala_id
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
                co.nome,
                co.codigo_oferta,
                co.periodo,
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
            INNER JOIN salas s ON s.id = qh.sala_id
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

            $turnos[$periodo][] = $aula;
        }

        return $turnos;
    }

    public function ocupacaoPorPeriodo(string $data): array
    {
        $sql = "
            SELECT co.periodo, COUNT(*) AS total
            FROM quadro_horario qh
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            WHERE qh.data_aula = :data
              AND qh.status = 'Ativa'
            GROUP BY co.periodo
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
            SELECT COUNT(DISTINCT sala_id) AS total
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

    private function contarSalasLivres(string $data): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM salas
            WHERE status IN ('ativa', 'livre', 'uso')
        ";

        $salasDisponiveis = $this->contar($sql);
        $salasOcupadas = $this->contarSalasOcupadas($data);

        return max(0, $salasDisponiveis - $salasOcupadas);
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
