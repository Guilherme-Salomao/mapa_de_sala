<?php

require_once __DIR__ . '/../core/Database.php';

class RelatorioGestor
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listarResumoMensal(int $mes, int $ano, array $escopo = ['tipo' => 'todos', 'ids' => []]): array
    {
        $docentes = $this->listarDocentes($escopo);
        $linhas = [];

        foreach ($docentes as $docente) {
            $docenteId = (int) $docente['id'];
            $escala = $this->listarEscala($docenteId);
            $horasEscala = $this->calcularHorasEscalaMes($escala, $mes, $ano);
            $horasCurso = $this->calcularHorasCursoMes($docenteId, $escala, $mes, $ano);
            $horasAula = $this->calcularHorasAulaMes($docenteId, $mes, $ano);
            $horasPlanejamento = max($horasEscala - $horasCurso - $horasAula, 0);
            $total = $horasAula + $horasCurso + $horasPlanejamento;

            $linhas[] = [
                'docente_id' => $docenteId,
                'docente_nome' => $docente['nome'] ?? '',
                'docente_email' => $docente['email'] ?? '',
                'area_atuacao' => $docente['area_atuacao'] ?? '',
                'horas_semanais' => (int) ($docente['horas_semanais'] ?? 0),
                'horas_aula' => $horasAula,
                'horas_curso' => $horasCurso,
                'horas_planejamento' => $horasPlanejamento,
                'total_horas' => $total,
                'percentual_aula' => $total > 0 ? round(($horasAula / $total) * 100, 1) : 0,
                'percentual_curso' => $total > 0 ? round(($horasCurso / $total) * 100, 1) : 0,
                'percentual_planejamento' => $total > 0 ? round(($horasPlanejamento / $total) * 100, 1) : 0,
            ];
        }

        return $linhas;
    }

    private function listarDocentes(array $escopo): array
    {
        $sql = "
            SELECT d.id, d.area_atuacao, d.horas_semanais, u.nome, u.email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN areas a ON a.nome = d.area_atuacao
            WHERE d.status = 'Ativo'
        ";

        $params = [];
        $this->aplicarEscopo($sql, $params, $escopo);

        $sql .= " ORDER BY u.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listarEscala(int $docenteId): array
    {
        $sql = "
            SELECT dia_semana, periodo, horas
            FROM docente_escala
            WHERE docente_id = :docente_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function calcularHorasAulaMes(int $docenteId, int $mes, int $ano): float
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $sql = "
            SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(qh.hora_fim, qh.hora_inicio)) / 3600), 0) AS horas
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            WHERE qhd.docente_id = :docente_id
              AND qh.status = 'Ativa'
              AND qh.data_aula BETWEEN :inicio AND :fim
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return round((float) ($resultado['horas'] ?? 0), 2);
    }

    private function calcularHorasCursoMes(int $docenteId, array $escala, int $mes, int $ano): float
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

        $sql = "
            SELECT data
            FROM educacao_corporativa_docentes
            WHERE docente_id = :docente_id
              AND status = 'Ativo'
              AND data BETWEEN :inicio AND :fim
            GROUP BY data
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        $horas = 0.0;

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $curso) {
            $horas += $this->horasEscalaData($escala, (string) $curso['data']);
        }

        return round($horas, 2);
    }

    private function calcularHorasEscalaMes(array $escala, int $mes, int $ano): float
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $diasNoMes = (int) date('t', strtotime($inicio));
        $horas = 0.0;

        for ($dia = 1; $dia <= $diasNoMes; $dia++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $horas += $this->horasEscalaData($escala, $data);
        }

        return round($horas, 2);
    }

    private function horasEscalaData(array $escala, string $data): float
    {
        $diaSemana = $this->diaSemanaPorData($data);
        $horas = 0.0;

        foreach ($escala as $item) {
            if ($this->normalizarDiaSemana((string) ($item['dia_semana'] ?? '')) === $diaSemana) {
                $horas += (float) ($item['horas'] ?? 0);
            }
        }

        return $horas;
    }

    private function diaSemanaPorData(string $data): string
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

    private function normalizarDiaSemana(string $dia): string
    {
        $dia = $this->normalizarTexto($dia);

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

    private function normalizarTexto(string $texto): string
    {
        $texto = strtolower($texto);
        $texto = str_replace(
            ['á', 'à', 'ã', 'â', 'ä', 'é', 'ê', 'í', 'ó', 'õ', 'ô', 'ú', 'ç', 'á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'õ', 'ô', 'ú', 'ç'],
            ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
            $texto
        );

        return $texto;
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
            $placeholder = ':area_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql .= " AND a.id IN (" . implode(',', $placeholders) . ")";
    }
}
