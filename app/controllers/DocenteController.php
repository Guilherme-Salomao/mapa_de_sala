<?php

require_once __DIR__ . '/../models/Docente.php';

class DocenteController
{
    private Docente $docenteModel;

    public function __construct()
    {
        $this->docenteModel = new Docente();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $busca  = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');

        $docentes      = $this->docenteModel->listar($busca, $status);
        $totalDocentes = $this->docenteModel->contar($busca, $status);

        require_once __DIR__ . '/../views/dashboard/docentes.php';
    }

    public function cadastrar(): void
    {
        $this->exigirLogin();

        $usuariosDisponiveis = $this->docenteModel->listarUsuariosDisponiveis();
        $unidadesCurriculares = $this->docenteModel->listarUnidadesCurriculares();

        require_once __DIR__ . '/../views/dashboard/cadastrar_docente.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Método inválido.'));
        }

        $dados = $this->obterDadosPost();
        $queryBase = $this->montarQueryCadastro($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatórios.'));
        }

        if ($this->totalHorasEscala($dados['escala']) > $dados['horas_semanais']) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('A escala não pode ultrapassar as horas semanais do docente.'));
        }

        if (! $this->docenteModel->usuarioExiste($dados['usuario_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Usuário informado não foi encontrado.'));
        }

        if ($this->docenteModel->usuarioJaVinculado($dados['usuario_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Este usuário já está vinculado a um docente.'));
        }

        if ($this->docenteModel->salvar($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=sucesso&msg=' . urlencode('Docente cadastrado com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Não foi possível cadastrar o docente.'));
    }

    public function editar(): void
    {
        $this->exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente inválido.'));
        }

        $docenteForm = $this->docenteModel->buscarPorId($id);

        if (! $docenteForm) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente não encontrado.'));
        }

        $usuariosDisponiveis = $this->docenteModel->listarUsuariosDisponiveis((int) $docenteForm['usuario_id']);
        $unidadesCurriculares = $this->docenteModel->listarUnidadesCurriculares();

        require_once __DIR__ . '/../views/dashboard/editar_docente.php';
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Método inválido.'));
        }

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Dados inválidos para atualização.'));
        }

        if ($this->totalHorasEscala($dados['escala']) > $dados['horas_semanais']) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('A escala não pode ultrapassar as horas semanais do docente.'));
        }

        if (! $this->docenteModel->buscarPorId($dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente não encontrado.'));
        }

        if (! $this->docenteModel->usuarioExiste($dados['usuario_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Usuário informado não foi encontrado.'));
        }

        if ($this->docenteModel->usuarioJaVinculado($dados['usuario_id'], $dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Este usuário já está vinculado a outro docente.'));
        }

        if ($this->docenteModel->atualizar($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=sucesso&msg=' . urlencode('Docente atualizado com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Não foi possível atualizar o docente.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente inválido.'));
        }

        if ($this->docenteModel->excluir($id)) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=sucesso&msg=' . urlencode('Docente excluído com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Não foi possível excluir o docente.'));
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            $this->redirecionar('/mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
        }
    }

    private function obterDadosPost(): array
    {
        return [
            'usuario_id'      => (int) ($_POST['usuario_id'] ?? 0),
            'horas_semanais' => (int) ($_POST['horas_semanais'] ?? 0),
            'area_atuacao'   => trim($_POST['area_atuacao'] ?? ''),
            'status'         => trim($_POST['status'] ?? 'Ativo'),
            'observacoes'    => trim($_POST['observacoes'] ?? ''),
            'escala'         => $this->obterEscalaPost(),
            'unidades_curriculares' => $this->obterUcsPost(),
        ];
    }

    private function validarDados(array $dados): bool
    {
        return $dados['usuario_id'] > 0
            && $dados['horas_semanais'] > 0
            && $dados['horas_semanais'] <= 60
            && $dados['area_atuacao'] !== ''
            && in_array($dados['status'], ['Ativo', 'Inativo'], true);
    }

    private function obterEscalaPost(): array
    {
        $diasValidos = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $periodosValidos = ['Manhã', 'Tarde', 'Noite'];
        $escalaPost = $_POST['escala'] ?? [];
        $escala = [];

        if (! is_array($escalaPost)) {
            return $escala;
        }

        foreach ($escalaPost as $diaSemana => $periodos) {
            if (! in_array($diaSemana, $diasValidos, true) || ! is_array($periodos)) {
                continue;
            }

            foreach ($periodos as $periodo => $dadosPeriodo) {
                if (! in_array($periodo, $periodosValidos, true) || ! is_array($dadosPeriodo)) {
                    continue;
                }

                $ativo = isset($dadosPeriodo['ativo']);
                $horas = (int) ($dadosPeriodo['horas'] ?? 0);

                if ($ativo && $horas > 0) {
                    $escala[] = [
                        'dia_semana' => $diaSemana,
                        'periodo'    => $periodo,
                        'horas'      => $horas,
                    ];
                }
            }
        }

        return $escala;
    }

    private function obterUcsPost(): array
    {
        $ucs = $_POST['unidades_curriculares'] ?? [];

        if (! is_array($ucs)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ucs))));
    }

    private function totalHorasEscala(array $escala): int
    {
        return (int) array_sum(array_column($escala, 'horas'));
    }

    private function montarQueryCadastro(array $dados): string
    {
        return http_build_query([
            'page'            => 'docentes',
            'action'          => 'cadastrar',
            'usuario_id'      => $dados['usuario_id'] > 0 ? $dados['usuario_id'] : '',
            'horas_semanais' => $dados['horas_semanais'] > 0 ? $dados['horas_semanais'] : '',
            'area_atuacao'   => $dados['area_atuacao'],
            'status'         => $dados['status'],
            'observacoes'    => $dados['observacoes'],
        ]);
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
