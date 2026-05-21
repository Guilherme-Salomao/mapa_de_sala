<?php

require_once __DIR__ . '/../core/Database.php';

class RelatorioDocente
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listarDocentes(): array
    {
        $sql = "
            SELECT d.id, u.nome, u.email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.status = 'Ativo'
            ORDER BY u.nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarDocente(int $id): ?array
    {
        $sql = "
            SELECT d.id, u.nome, u.email
            FROM docentes d
            INNER JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.id = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $docente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $docente ?: null;
    }

    public function listarEscala(int $docenteId): array
    {
        $sql = "
            SELECT dia_semana, periodo, horas
            FROM docente_escala
            WHERE docente_id = :docente_id
            ORDER BY FIELD(dia_semana, 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'),
                     FIELD(periodo, 'Manhã', 'Tarde', 'Noite')
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAulasMensais(int $docenteId, int $mes, int $ano): array
    {
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $fim = date('Y-m-t', strtotime($inicio));

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
                s.nome AS sala_nome
            FROM quadro_horario qh
            INNER JOIN quadro_horario_docentes qhd ON qhd.quadro_horario_id = qh.id
            INNER JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
            INNER JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
            INNER JOIN salas s ON s.id = qh.sala_id
            WHERE qhd.docente_id = :docente_id
              AND qh.status = 'Ativa'
              AND qh.data_aula BETWEEN :inicio AND :fim
            ORDER BY qh.data_aula ASC, qh.hora_inicio ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':docente_id' => $docenteId,
            ':inicio' => $inicio,
            ':fim' => $fim,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
