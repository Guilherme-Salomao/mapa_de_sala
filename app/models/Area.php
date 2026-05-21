<?php

require_once __DIR__ . '/../core/Database.php';

class Area
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listarAtivas(): array
    {
        $sql = "
            SELECT id, nome, status
            FROM areas
            WHERE status = 'Ativa'
            ORDER BY nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodas(): array
    {
        $sql = "
            SELECT id, nome, status
            FROM areas
            ORDER BY nome ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
