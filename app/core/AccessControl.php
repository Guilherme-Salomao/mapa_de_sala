<?php

require_once __DIR__ . '/Database.php';

class AccessControl
{
    private PDO $conn;
    private array $usuario;

    public function __construct(?array $usuario = null)
    {
        $database = new Database();
        $this->conn = $database->connect();
        $this->usuario = $usuario ?? ($_SESSION['usuario'] ?? []);
    }

    public function nivel(): string
    {
        return (string) ($this->usuario['nivel_acesso'] ?? '');
    }

    public function usuarioId(): int
    {
        return (int) ($this->usuario['id'] ?? 0);
    }

    public function isAdmin(): bool
    {
        return $this->nivel() === 'Admin';
    }

    public function usaFiltroArea(): bool
    {
        return in_array($this->nivel(), ['Gestor', 'Apoio'], true);
    }

    public function usaFiltroUcDocente(): bool
    {
        return $this->nivel() === 'Professor';
    }

    public function areasUsuario(): array
    {
        $sql = "
            SELECT area_id
            FROM usuario_areas
            WHERE usuario_id = :usuario_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $this->usuarioId()]);

        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'area_id'));
    }

    public function docenteId(): ?int
    {
        $sql = "
            SELECT id
            FROM docentes
            WHERE usuario_id = :usuario_id
              AND status = 'Ativo'
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':usuario_id' => $this->usuarioId()]);
        $docente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $docente ? (int) $docente['id'] : null;
    }

    public function ucsDocente(): array
    {
        $docenteId = $this->docenteId();

        if ($docenteId === null) {
            return [];
        }

        $sql = "
            SELECT unidade_curricular_id
            FROM docente_unidades_curriculares
            WHERE docente_id = :docente_id
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);

        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'unidade_curricular_id'));
    }

    public function escopo(): array
    {
        if ($this->isAdmin()) {
            return ['tipo' => 'todos', 'ids' => []];
        }

        if ($this->usaFiltroArea()) {
            return ['tipo' => 'areas', 'ids' => $this->areasUsuario()];
        }

        if ($this->usaFiltroUcDocente()) {
            return ['tipo' => 'ucs', 'ids' => $this->ucsDocente()];
        }

        return ['tipo' => 'todos', 'ids' => []];
    }
}
