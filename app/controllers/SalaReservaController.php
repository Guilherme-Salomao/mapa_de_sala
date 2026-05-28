<?php

require_once __DIR__ . '/../models/SalaReserva.php';

class SalaReservaController
{
    private SalaReserva $reservaModel;

    public function __construct()
    {
        $this->reservaModel = new SalaReserva();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $dataInicio = trim($_GET['data_inicio'] ?? date('Y-m-d'));
        $dataFim = trim($_GET['data_fim'] ?? $dataInicio);
        $salaId = (int) ($_GET['sala_id'] ?? 0);
        $tipo = trim($_GET['tipo_reserva'] ?? 'todos');
        $status = trim($_GET['status'] ?? 'Ativo');

        if (strtotime($dataInicio) === false) {
            $dataInicio = date('Y-m-d');
        }

        if (strtotime($dataFim) === false || strtotime($dataFim) < strtotime($dataInicio)) {
            $dataFim = $dataInicio;
        }

        $salas = $this->reservaModel->listarSalas();
        $usuarios = $this->reservaModel->listarUsuarios();
        $reservas = $this->reservaModel->listar($dataInicio, $dataFim, $salaId, $tipo, $status);
        $aulas = $this->reservaModel->listarAulas($dataInicio, $dataFim);

        require_once __DIR__ . '/../views/dashboard/sala_reservas.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();

        if (! $this->validarReserva($dados)) {
            $this->redirecionar('./?page=gestao_salas&tipo=erro&msg=' . urlencode('Preencha os dados da reserva corretamente.'));
        }

        $resultado = $this->reservaModel->salvar($dados);
        $tipo = ! empty($resultado['sucesso']) ? 'sucesso' : 'erro';

        $this->redirecionar('./?page=gestao_salas&tipo=' . $tipo . '&msg=' . urlencode($resultado['mensagem'] ?? 'Processo concluido.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('./?page=gestao_salas&tipo=erro&msg=' . urlencode('Registro invalido.'));
        }

        if ($this->reservaModel->excluir($id)) {
            $this->redirecionar('./?page=gestao_salas&tipo=sucesso&msg=' . urlencode('Registro inativado com sucesso.'));
        }

        $this->redirecionar('./?page=gestao_salas&tipo=erro&msg=' . urlencode('Nao foi possivel inativar o registro.'));
    }

    public function trocarSala(): void
    {
        $this->exigirLogin();

        $aulaId = (int) ($_POST['aula_id'] ?? 0);
        $salaDestinoId = (int) ($_POST['sala_destino_id'] ?? 0);
        $permitirPermuta = isset($_POST['permitir_permuta']);

        $resultado = $this->reservaModel->trocarSala($aulaId, $salaDestinoId, $permitirPermuta);
        $tipo = ! empty($resultado['sucesso']) ? 'sucesso' : 'erro';

        $this->redirecionar('./?page=gestao_salas&tipo=' . $tipo . '&msg=' . urlencode($resultado['mensagem'] ?? 'Processo concluido.'));
    }

    private function obterDadosPost(): array
    {
        return [
            'sala_id' => (int) ($_POST['sala_id'] ?? 0),
            'tipo' => trim($_POST['tipo_reserva'] ?? 'Reserva'),
            'data_inicio' => trim($_POST['data_inicio'] ?? ''),
            'data_fim' => trim($_POST['data_fim'] ?? ''),
            'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
            'hora_fim' => trim($_POST['hora_fim'] ?? ''),
            'solicitante_usuario_id' => (int) ($_POST['solicitante_usuario_id'] ?? 0),
            'motivo' => trim($_POST['motivo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
        ];
    }

    private function validarReserva(array $dados): bool
    {
        return $dados['sala_id'] > 0
            && in_array($dados['tipo'], ['Reservada', 'Manutencao'], true)
            && $dados['solicitante_usuario_id'] > 0
            && $dados['motivo'] !== ''
            && strtotime($dados['data_inicio']) !== false
            && strtotime($dados['data_fim']) !== false
            && strtotime($dados['data_fim']) >= strtotime($dados['data_inicio'])
            && strtotime($dados['hora_inicio']) !== false
            && strtotime($dados['hora_fim']) !== false
            && strtotime($dados['hora_fim']) > strtotime($dados['hora_inicio']);
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            $this->redirecionar('./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        }
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
