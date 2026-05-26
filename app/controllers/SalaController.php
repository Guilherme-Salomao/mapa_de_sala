<?php

require_once __DIR__ . '/../models/Sala.php';

class SalaController
{
    private Sala $salaModel;

    public function __construct()
    {
        $this->salaModel = new Sala();
    }

    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        $busca  = $_GET['busca'] ?? '';
        $tipo   = $_GET['tipo_sala'] ?? 'todos';
        $status = $_GET['status'] ?? 'todos';

        $salas      = $this->salaModel->listar($busca, $tipo, $status);
        $totalSalas = count($salas);

        require_once __DIR__ . '/../views/dashboard/salas.php';
    }

    public function salvar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /mapa_de_sala/public/?page=salas&tipo=erro&msg=' . urlencode('Método inválido.'));
            exit;
        }

        $nome       = trim($_POST['nome'] ?? '');
        $tipo       = trim($_POST['tipo_sala'] ?? '');
        $capacidade = (int) ($_POST['capacidade'] ?? 0);
        $status     = trim($_POST['status'] ?? '');
        $descricao  = trim($_POST['descricao'] ?? '');
        $recursosTratados = $this->obterRecursosPost();

        $queryBase = http_build_query([
            'page'       => 'salas',
            'action'     => 'cadastrar',
            'nome'       => $nome,
            'tipo_sala'  => $tipo,
            'capacidade' => $capacidade > 0 ? $capacidade : '',
            'status'     => $status,
            'descricao'  => $descricao,
            'recursos'   => $recursosTratados,
        ]);

        if (empty($nome) || empty($tipo) || $capacidade <= 0 || ! $this->statusValido($status)) {
            header('Location: /mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatórios.'));
            exit;
        }

        if ($this->salaModel->nomeExiste($nome)) {
            header('Location: /mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Já existe uma sala cadastrada com esse nome.'));
            exit;
        }

        $dados = [
            'nome'       => $nome,
            'tipo'       => $tipo,
            'capacidade' => $capacidade,
            'status'     => $status,
            'descricao'  => $descricao,
            'recursos'   => $recursosTratados,
        ];

        $salvou = $this->salaModel->salvar($dados);

        if ($salvou) {
            header('Location: /mapa_de_sala/public/?page=salas&tipo=sucesso&msg=' . urlencode('Sala cadastrada com sucesso.'));
            exit;
        }

        header('Location: /mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Não foi possível cadastrar a sala.'));
        exit;
    }

    public function editar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /mapa_de_sala/public/?page=salas&tipo=erro&msg=' . urlencode('Sala não encontrada.'));
            exit;
        }

        $salaForm = $this->salaModel->buscarPorId($id);

        if (! $salaForm) {
            header('Location: /mapa_de_sala/public/?page=salas&tipo=erro&msg=' . urlencode('Sala não encontrada.'));
            exit;
        }

        $recursosDisponiveis = $this->salaModel->listarRecursos();

        $formAction = '/mapa_de_sala/public/?page=salas&action=atualizar';
        $botaoTexto = 'Atualizar Sala';
        $modoEdicao = true;

        require_once __DIR__ . '/../views/dashboard/editar_sala.php';
    }

    public function atualizar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        $dados = [
            'id'                  => $id,
            'nome'                => trim($_POST['nome'] ?? ''),
            'tipo'                => trim($_POST['tipo_sala'] ?? ''),
            'capacidade'          => (int) ($_POST['capacidade'] ?? 0),
            'status'              => trim($_POST['status'] ?? 'ativa'),
            'descricao'           => trim($_POST['descricao'] ?? ''),
            'recursos'            => $this->obterRecursosPost(),
        ];

        if (
            $id <= 0 ||
            empty($dados['nome']) ||
            empty($dados['tipo']) ||
            $dados['capacidade'] <= 0 ||
            ! $this->statusValido($dados['status'])
        ) {
            header('Location: /mapa_de_sala/public/?page=salas&tipo=erro&msg=' . urlencode('Dados inválidos para atualização.'));
            exit;
        }

        $atualizado = $this->salaModel->atualizar($dados);

        if ($atualizado) {
            header('Location: /mapa_de_sala/public/?page=salas&tipo=sucesso&msg=' . urlencode('Sala atualizada com sucesso.'));
            exit;
        }

        header('Location: /mapa_de_sala/public/?page=salas&action=editar&id=' . $id . '&tipo=erro&msg=' . urlencode('Erro ao atualizar sala.'));
        exit;
    }

    public function excluir(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /mapa_de_sala/public/?page=salas&tipo=erro&msg=' . urlencode('Sala inválida.'));
            exit;
        }

        $excluido = $this->salaModel->excluir($id);

        if ($excluido) {
            header('Location: /mapa_de_sala/public/?page=salas&tipo=sucesso&msg=' . urlencode('Sala excluída com sucesso.'));
            exit;
        }

        header('Location: /mapa_de_sala/public/?page=salas&tipo=erro&msg=' . urlencode('Erro ao excluir sala.'));
        exit;
    }

    private function statusValido(string $status): bool
    {
        return in_array($status, ['ativa', 'inativa'], true);
    }

    private function obterRecursosPost(): array
    {
        $recursosTratados = [];

        if (! empty($_POST['recursos']) && is_array($_POST['recursos'])) {
            foreach ($_POST['recursos'] as $recursoId) {
                $recursoId = (int) $recursoId;
                $quantidade = (int) ($_POST['quantidade_recursos'][$recursoId] ?? 1);

                if ($recursoId > 0 && $quantidade > 0) {
                    $recursosTratados[$recursoId] = $quantidade;
                }
            }
        }

        return $recursosTratados;
    }
}
