<?php

require_once __DIR__ . '/../core/Database.php';

class Cidade
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listarAtivas(): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, nome
            FROM cidades
            WHERE status = 'Ativa'
            ORDER BY nome ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obterOuCriarPorNome(string $nome): ?int
    {
        $nome = trim(preg_replace('/\s+/', ' ', $nome) ?? '');

        $tamanho = function_exists('mb_strlen') ? mb_strlen($nome) : strlen($nome);

        if ($nome === '' || $tamanho > 100) {
            return null;
        }

        $stmt = $this->conn->prepare("
            SELECT id
            FROM cidades
            WHERE nome = :nome
            LIMIT 1
        ");
        $stmt->execute([':nome' => $nome]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int) $id;
        }

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO cidades (nome, status)
                VALUES (:nome, 'Ativa')
            ");
            $stmt->execute([':nome' => $nome]);

            return (int) $this->conn->lastInsertId();
        } catch (Throwable $e) {
            $stmt = $this->conn->prepare("
                SELECT id
                FROM cidades
                WHERE nome = :nome
                LIMIT 1
            ");
            $stmt->execute([':nome' => $nome]);
            $id = $stmt->fetchColumn();

            return $id ? (int) $id : null;
        }
    }
}
