<?php

require_once __DIR__ . '/../core/Database.php';

class SistemaLog
{
    private PDO $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function listar(string $busca = '', string $pagina = 'todos', string $dataInicio = '', string $dataFim = ''): array
    {
        $sql = "
            SELECT *
            FROM sistema_logs
            WHERE 1 = 1
        ";

        $params = [];

        if ($busca !== '') {
            $sql .= " AND (
                usuario_nome LIKE :busca
                OR usuario_email LIKE :busca
                OR descricao LIKE :busca
                OR acao LIKE :busca
            )";
            $params[':busca'] = '%' . $busca . '%';
        }

        if ($pagina !== 'todos') {
            $sql .= " AND pagina = :pagina";
            $params[':pagina'] = $pagina;
        }

        if ($dataInicio !== '') {
            $sql .= " AND DATE(criado_em) >= :data_inicio";
            $params[':data_inicio'] = $dataInicio;
        }

        if ($dataFim !== '') {
            $sql .= " AND DATE(criado_em) <= :data_fim";
            $params[':data_fim'] = $dataFim;
        }

        $sql .= " ORDER BY criado_em DESC LIMIT 500";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPaginas(): array
    {
        $sql = "
            SELECT DISTINCT pagina
            FROM sistema_logs
            ORDER BY pagina ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'pagina');
    }

    public function salvar(array $dados): bool
    {
        try {
            $sql = "
                INSERT INTO sistema_logs (
                    usuario_id,
                    usuario_nome,
                    usuario_email,
                    nivel_acesso,
                    metodo,
                    pagina,
                    acao,
                    descricao,
                    dados,
                    ip,
                    navegador
                ) VALUES (
                    :usuario_id,
                    :usuario_nome,
                    :usuario_email,
                    :nivel_acesso,
                    :metodo,
                    :pagina,
                    :acao,
                    :descricao,
                    :dados,
                    :ip,
                    :navegador
                )
            ";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':usuario_id' => $dados['usuario_id'],
                ':usuario_nome' => $dados['usuario_nome'],
                ':usuario_email' => $dados['usuario_email'],
                ':nivel_acesso' => $dados['nivel_acesso'],
                ':metodo' => $dados['metodo'],
                ':pagina' => $dados['pagina'],
                ':acao' => $dados['acao'],
                ':descricao' => $dados['descricao'],
                ':dados' => $dados['dados'],
                ':ip' => $dados['ip'],
                ':navegador' => $dados['navegador'],
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
