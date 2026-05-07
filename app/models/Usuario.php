<?php

require_once __DIR__ . '/../core/Database.php';

class Usuario
{
    private PDO $conn;
    private string $table = 'usuarios';

    public function __construct()
    {
        $database   = new Database();
        $this->conn = $database->connect();
    }

    public function buscarPorId(int $id): array | false
    {
        $sql = "SELECT id, nome, email, nivel_acesso, status, ultimo_login, criado_em, atualizado_em
                FROM {$this->table}
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function buscarPorEmail(string $email): array | false
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE email = :email
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function listarTodos(): array
    {
        $sql = "SELECT id, nome, email, nivel_acesso, status, ultimo_login, criado_em, atualizado_em
                FROM {$this->table}
                ORDER BY nome ASC";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll();
    }

    public function cadastrar(
        string $nome,
        string $email,
        string $senha,
        string $nivelAcesso = 'Apoio',
        string $status = 'Ativo'
    ): bool {
        $sql = "INSERT INTO {$this->table}
                (nome, email, senha, nivel_acesso, status)
                VALUES (:nome, :email, :senha, :nivel_acesso, :status)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':nome'         => $nome,
            ':email'        => $email,
            ':senha'        => $senha,
            ':nivel_acesso' => $nivelAcesso,
            ':status'       => $status,
        ]);
    }

    public function atualizar(
        int $id,
        string $nome,
        string $email,
        string $nivelAcesso,
        string $status
    ): bool {
        $sql = "UPDATE {$this->table}
                SET nome = :nome,
                    email = :email,
                    nivel_acesso = :nivel_acesso,
                    status = :status
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id'           => $id,
            ':nome'         => $nome,
            ':email'        => $email,
            ':nivel_acesso' => $nivelAcesso,
            ':status'       => $status,
        ]);
    }

    public function atualizarSenha(int $id, string $novaSenha): bool
    {
        $sql = "UPDATE {$this->table}
                SET senha = :senha
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':id'    => $id,
            ':senha' => $novaSenha,
        ]);
    }

    public function excluir(int $id): bool
    {
        $sql = "DELETE FROM {$this->table}
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function atualizarUltimoLogin(int $id): bool
    {
        $sql = "UPDATE {$this->table}
                SET ultimo_login = NOW()
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function emailExiste(string $email, ?int $ignorarId = null): bool
    {
        $sql = "SELECT id
                FROM {$this->table}
                WHERE email = :email";

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
        }

        $sql .= " LIMIT 1";

        $stmt  = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);

        if ($ignorarId !== null) {
            $stmt->bindValue(':ignorar_id', $ignorarId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return (bool) $stmt->fetch();
    }

    public function listarComFiltros(?string $nome = null, ?string $nivelAcesso = null): array
    {
        $sql = "SELECT id, nome, email, nivel_acesso, status, ultimo_login, criado_em, atualizado_em
                FROM {$this->table}
                WHERE 1=1";

        $params = [];

        if (! empty($nome)) {
            $sql             .= " AND (nome LIKE :nome OR email LIKE :nome)";
            $params[':nome']  = '%' . $nome . '%';
        }

        if (! empty($nivelAcesso) && $nivelAcesso !== 'todos') {
            $sql                     .= " AND nivel_acesso = :nivel_acesso";
            $params[':nivel_acesso']  = $nivelAcesso;
        }

        $sql .= " ORDER BY nome ASC";

        $stmt  = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contarComFiltros(?string $nome = null, ?string $nivelAcesso = null): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM {$this->table}
                WHERE 1=1";

        $params = [];

        if (! empty($nome)) {
            $sql             .= " AND (nome LIKE :nome OR email LIKE :nome)";
            $params[':nome']  = '%' . $nome . '%';
        }

        if (! empty($nivelAcesso) && $nivelAcesso !== 'todos') {
            $sql                     .= " AND nivel_acesso = :nivel_acesso";
            $params[':nivel_acesso']  = $nivelAcesso;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch();

        return (int) ($resultado['total'] ?? 0);
    }
}