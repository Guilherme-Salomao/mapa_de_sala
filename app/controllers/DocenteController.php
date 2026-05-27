<?php

require_once __DIR__ . '/../models/Docente.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../core/AccessControl.php';

class DocenteController
{
    private Docente $docenteModel;
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->docenteModel = new Docente();
        $this->usuarioModel = new Usuario();
    }

    public function index(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();

        if ($access->nivel() === 'Professor') {
            $docenteId = $access->docenteId();

            if ($docenteId === null) {
                $this->redirecionar('/mapa_de_sala/public/?page=home&tipo=erro&msg=' . urlencode('Seu usuario ainda nao esta vinculado a um cadastro docente.'));
            }

            $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $docenteId);
        }

        $busca  = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');

        $escopoDocentes = $access->escopoAreaAtuacao();
        $docentes      = $this->docenteModel->listar($busca, $status, $escopoDocentes);
        $totalDocentes = $this->docenteModel->contar($busca, $status, $escopoDocentes);

        require_once __DIR__ . '/../views/dashboard/docentes.php';
    }

    public function cadastrar(): void
    {
        $this->exigirLogin();
        $this->bloquearProfessor();

        $usuariosDisponiveis = $this->docenteModel->listarUsuariosDisponiveis();
        $areas = $this->docenteModel->listarAreas((new AccessControl())->escopoAreaAtuacao());
        $cursoModelos = $this->docenteModel->listarCursoModelosComUc();
        $unidadesCurriculares = $this->docenteModel->listarUnidadesCurriculares();

        require_once __DIR__ . '/../views/dashboard/cadastrar_docente.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();
        $this->bloquearProfessor();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Método inválido.'));
        }

        $dados = $this->obterDadosPost();
        $queryBase = $this->montarQueryCadastro($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatórios.'));
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
        $access = new AccessControl();
        $cadastroProprioDocente = $access->nivel() === 'Professor';

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente inválido.'));
        }

        if ($cadastroProprioDocente && $access->docenteId() !== $id) {
            $this->redirecionar('/mapa_de_sala/public/?page=home&tipo=erro&msg=' . urlencode('Voce so pode acessar o seu proprio cadastro docente.'));
        }

        $escopoDocentes = $cadastroProprioDocente ? ['tipo' => 'todos', 'ids' => []] : $access->escopoAreaAtuacao();
        $docenteForm = $this->docenteModel->buscarPorId($id, $escopoDocentes);

        if (! $docenteForm) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente não encontrado.'));
        }

        $usuariosDisponiveis = $this->docenteModel->listarUsuariosDisponiveis((int) $docenteForm['usuario_id']);
        $areas = $this->docenteModel->listarAreas($access->escopoAreaAtuacao());
        $cursoModelos = $this->docenteModel->listarCursoModelosComUc();
        $unidadesCurriculares = $this->docenteModel->listarUnidadesCurriculares();
        $somenteVinculosUc = false;

        require_once __DIR__ . '/../views/dashboard/editar_docente.php';
    }

    public function atualizar(): void
    {
        $this->exigirLogin();
        $access = new AccessControl();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Método inválido.'));
        }

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);
        $escopoAtualizacao = $access->nivel() === 'Professor' ? ['tipo' => 'todos', 'ids' => []] : $access->escopoAreaAtuacao();
        $docenteAtual = $dados['id'] > 0 ? $this->docenteModel->buscarPorId($dados['id'], $escopoAtualizacao) : null;

        if ($access->nivel() === 'Professor') {
            if (! $docenteAtual || $access->docenteId() !== $dados['id']) {
                $this->redirecionar('/mapa_de_sala/public/?page=home&tipo=erro&msg=' . urlencode('Voce so pode alterar o seu proprio cadastro docente.'));
            }

            $dados['usuario_id'] = (int) $docenteAtual['usuario_id'];
            $dados['area_atuacao'] = (string) $docenteAtual['area_atuacao'];
            $dados['status'] = (string) $docenteAtual['status'];

            $erroPerfil = $this->validarPerfilProprio($dados);

            if ($erroPerfil !== null) {
                $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode($erroPerfil));
            }
        }

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Dados inválidos para atualização.'));
        }

        if (! $docenteAtual) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente não encontrado.'));
        }

        if (! $this->docenteModel->usuarioExiste($dados['usuario_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Usuário informado não foi encontrado.'));
        }

        if ($this->docenteModel->usuarioJaVinculado($dados['usuario_id'], $dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Este usuário já está vinculado a outro docente.'));
        }

        if ($this->docenteModel->atualizar($dados)) {
            if ($access->nivel() === 'Professor') {
                $_SESSION['usuario']['nome'] = $dados['usuario_nome'];
                $_SESSION['usuario']['email'] = $dados['usuario_email'];
                $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=sucesso&msg=' . urlencode('Cadastro atualizado com sucesso.'));
            }

            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=sucesso&msg=' . urlencode('Docente atualizado com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=docentes&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Não foi possível atualizar o docente.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();
        $this->bloquearProfessor();

        $id = (int) ($_POST['id'] ?? 0);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente inválido.'));
        }

        if (! $this->docenteModel->buscarPorId($id, $escopo)) {
            $this->redirecionar('/mapa_de_sala/public/?page=docentes&tipo=erro&msg=' . urlencode('Docente nao encontrado na sua area de atuacao.'));
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

    private function bloquearProfessor(): void
    {
        if ((new AccessControl())->nivel() === 'Professor') {
            $this->redirecionar('/mapa_de_sala/public/?page=home&tipo=erro&msg=' . urlencode('Voce nao tem permissao para esta acao.'));
        }
    }

    private function obterDadosPost(): array
    {
        $escala = $this->obterEscalaPost();

        return [
            'usuario_id'      => (int) ($_POST['usuario_id'] ?? 0),
            'horas_semanais' => $this->totalHorasEscala($escala),
            'area_atuacao'   => trim($_POST['area_atuacao'] ?? ''),
            'status'         => trim($_POST['status'] ?? 'Ativo'),
            'observacoes'    => trim($_POST['observacoes'] ?? ''),
            'escala'         => $escala,
            'unidades_curriculares' => $this->obterUcsPost(),
            'usuario_nome' => trim($_POST['usuario_nome'] ?? ''),
            'usuario_email' => trim($_POST['usuario_email'] ?? ''),
            'senha' => trim($_POST['senha'] ?? ''),
            'confirmar_senha' => trim($_POST['confirmar_senha'] ?? ''),
            'senha_hash' => null,
        ];
    }

    private function validarPerfilProprio(array &$dados): ?string
    {
        if ($dados['usuario_nome'] === '' || $dados['usuario_email'] === '') {
            return 'Preencha nome e e-mail.';
        }

        if (! filter_var($dados['usuario_email'], FILTER_VALIDATE_EMAIL)) {
            return 'Informe um e-mail valido.';
        }

        if ($this->usuarioModel->emailExiste($dados['usuario_email'], (int) $dados['usuario_id'])) {
            return 'Ja existe outro usuario com este e-mail.';
        }

        if ($dados['senha'] !== '' || $dados['confirmar_senha'] !== '') {
            if ($dados['senha'] !== $dados['confirmar_senha']) {
                return 'As senhas nao conferem.';
            }

            if (strlen($dados['senha']) < 4) {
                return 'A nova senha deve ter no minimo 4 caracteres.';
            }

            $dados['senha_hash'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }

        return null;
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
