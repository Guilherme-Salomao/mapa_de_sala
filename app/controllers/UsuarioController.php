<?php

require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        $busca = trim($_GET['busca'] ?? '');
        $nivel = trim($_GET['nivel'] ?? 'todos');

        $usuarios      = $this->usuarioModel->listarComFiltros($busca, $nivel);
        $totalUsuarios = $this->usuarioModel->contarComFiltros($busca, $nivel);

        require __DIR__ . '/../views/dashboard/usuarios.php';
    }

    public function cadastrar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        $areas = $this->usuarioModel->listarAreas();

        require __DIR__ . '/../views/dashboard/cadastrar_usuario.php';
    }

    public function salvar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Método inválido.'));
            exit;
        }

        $nome        = trim($_POST['nome'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $nivelAcesso = trim($_POST['nivel_acesso'] ?? '');
        $status      = trim($_POST['status'] ?? 'Ativo');
        $senha       = trim($_POST['senha'] ?? '');
        $confSenha   = trim($_POST['confSenha'] ?? '');
        $areasUsuario = $this->obterAreasPost();

        if (
            empty($nome) ||
            empty($email) ||
            empty($nivelAcesso) ||
            empty($status) ||
            empty($senha) ||
            empty($confSenha)
        ) {
            header('Location: ./?page=usuarios&action=cadastrar&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatórios.'));
            exit;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ./?page=usuarios&action=cadastrar&tipo=erro&msg=' . urlencode('Informe um e-mail válido.'));
            exit;
        }

        if ($senha !== $confSenha) {
            header('Location: ./?page=usuarios&action=cadastrar&tipo=erro&msg=' . urlencode('As senhas não conferem.'));
            exit;
        }

        if ($this->usuarioModel->emailExiste($email)) {
            header('Location: ./?page=usuarios&action=cadastrar&tipo=erro&msg=' . urlencode('Já existe um usuário com este e-mail.'));
            exit;
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $salvou = $this->usuarioModel->cadastrar(
            $nome,
            $email,
            $senhaHash,
            $nivelAcesso,
            $status
        );

        if ($salvou) {
            $usuarioCriado = $this->usuarioModel->buscarPorEmail($email);

            if ($usuarioCriado && in_array($nivelAcesso, ['Gestor', 'Apoio'], true)) {
                $this->usuarioModel->salvarAreasUsuario((int) $usuarioCriado['id'], $areasUsuario);
            }

            header('Location: ./?page=usuarios&tipo=sucesso&msg=' . urlencode('Usuário cadastrado com sucesso.'));
            exit;
        }

        header('Location: ./?page=usuarios&action=cadastrar&tipo=erro&msg=' . urlencode('Não foi possível cadastrar o usuário.'));
        exit;
    }

    public function editar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Usuário inválido.'));
            exit;
        }

        $usuario = $this->usuarioModel->buscarPorId($id);

        if (! $usuario) {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Usuário não encontrado.'));
            exit;
        }

        $areas = $this->usuarioModel->listarAreas();
        $areasUsuario = $this->usuarioModel->listarAreasUsuario($id);

        require __DIR__ . '/../views/dashboard/editar_usuario.php';
    }

    public function perfil(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        $id = (int) ($_SESSION['usuario']['id'] ?? 0);
        $usuario = $this->usuarioModel->buscarPorId($id);

        if (! $usuario) {
            header('Location: ./?page=home&tipo=erro&msg=' . urlencode('Seu usuário não foi encontrado.'));
            exit;
        }

        $areas = [];
        $areasUsuario = [];

        require __DIR__ . '/../views/dashboard/perfil_usuario.php';
    }

    public function atualizar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Método inválido.'));
            exit;
        }

        $id          = (int) ($_POST['id'] ?? 0);
        $nome        = trim($_POST['nome'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $nivelAcesso = trim($_POST['nivel_acesso'] ?? '');
        $status      = trim($_POST['status'] ?? '');
        $senha       = trim($_POST['senha'] ?? '');
        $confSenha   = trim($_POST['confSenha'] ?? '');
        $areasUsuario = $this->obterAreasPost();

        if (
            $id <= 0 ||
            empty($nome) ||
            empty($email) ||
            empty($nivelAcesso) ||
            empty($status)
        ) {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatórios.'));
            exit;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ./?page=usuarios&action=editar&id=' . $id . '&tipo=erro&msg=' . urlencode('Informe um e-mail válido.'));
            exit;
        }

        if ($this->usuarioModel->emailExiste($email, $id)) {
            header('Location: ./?page=usuarios&action=editar&id=' . $id . '&tipo=erro&msg=' . urlencode('Já existe outro usuário com este e-mail.'));
            exit;
        }

        $usuario = $this->usuarioModel->buscarPorId($id);

        if (! $usuario) {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Usuário não encontrado.'));
            exit;
        }

        $atualizou = $this->usuarioModel->atualizar(
            $id,
            $nome,
            $email,
            $nivelAcesso,
            $status
        );

        if (! $atualizou) {
            header('Location: ./?page=usuarios&action=editar&id=' . $id . '&tipo=erro&msg=' . urlencode('Não foi possível atualizar o usuário.'));
            exit;
        }

        if (in_array($nivelAcesso, ['Gestor', 'Apoio'], true)) {
            $this->usuarioModel->salvarAreasUsuario($id, $areasUsuario);
        } else {
            $this->usuarioModel->salvarAreasUsuario($id, []);
        }

        if (! empty($senha) || ! empty($confSenha)) {
            if ($senha !== $confSenha) {
                header('Location: ./?page=usuarios&action=editar&id=' . $id . '&tipo=erro&msg=' . urlencode('As senhas não conferem.'));
                exit;
            }

            if (strlen($senha) < 4) {
                header('Location: ./?page=usuarios&action=editar&id=' . $id . '&tipo=erro&msg=' . urlencode('A nova senha deve ter no mínimo 4 caracteres.'));
                exit;
            }

            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $this->usuarioModel->atualizarSenha($id, $senhaHash);
        }

        header('Location: ./?page=usuarios&tipo=sucesso&msg=' . urlencode('Usuário atualizado com sucesso.'));
        exit;
    }

    public function atualizarPerfil(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./?page=perfil&tipo=erro&msg=' . urlencode('Método inválido.'));
            exit;
        }

        $id = (int) ($_SESSION['usuario']['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $confSenha = trim($_POST['confSenha'] ?? '');

        if ($id <= 0 || $nome === '' || $email === '') {
            header('Location: ./?page=perfil&tipo=erro&msg=' . urlencode('Preencha nome e e-mail.'));
            exit;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ./?page=perfil&tipo=erro&msg=' . urlencode('Informe um e-mail válido.'));
            exit;
        }

        if ($this->usuarioModel->emailExiste($email, $id)) {
            header('Location: ./?page=perfil&tipo=erro&msg=' . urlencode('Já existe outro usuário com este e-mail.'));
            exit;
        }

        $usuario = $this->usuarioModel->buscarPorId($id);

        if (! $usuario) {
            header('Location: ./?page=home&tipo=erro&msg=' . urlencode('Seu usuário não foi encontrado.'));
            exit;
        }

        $atualizou = $this->usuarioModel->atualizar(
            $id,
            $nome,
            $email,
            (string) $usuario['nivel_acesso'],
            (string) $usuario['status']
        );

        if (! $atualizou) {
            header('Location: ./?page=perfil&tipo=erro&msg=' . urlencode('Não foi possível atualizar seus dados.'));
            exit;
        }

        if ($senha !== '' || $confSenha !== '') {
            if ($senha !== $confSenha) {
                header('Location: ./?page=perfil&tipo=erro&msg=' . urlencode('As senhas não conferem.'));
                exit;
            }

            if (strlen($senha) < 4) {
                header('Location: ./?page=perfil&tipo=erro&msg=' . urlencode('A nova senha deve ter no mínimo 4 caracteres.'));
                exit;
            }

            $this->usuarioModel->atualizarSenha($id, password_hash($senha, PASSWORD_DEFAULT));
        }

        $_SESSION['usuario']['nome'] = $nome;
        $_SESSION['usuario']['email'] = $email;

        header('Location: ./?page=perfil&tipo=sucesso&msg=' . urlencode('Dados atualizados com sucesso.'));
        exit;
    }

    public function excluir(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Método inválido para exclusão.'));
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Usuário inválido.'));
            exit;
        }

        if ((int) $_SESSION['usuario']['id'] === $id) {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Não é permitido excluir o próprio usuário logado.'));
            exit;
        }

        $usuario = $this->usuarioModel->buscarPorId($id);

        if (! $usuario) {
            header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Usuário não encontrado.'));
            exit;
        }

        $sucesso = $this->usuarioModel->excluir($id);

        if ($sucesso) {
            header('Location: ./?page=usuarios&tipo=sucesso&msg=' . urlencode('Usuário excluído com sucesso.'));
            exit;
        }

        header('Location: ./?page=usuarios&tipo=erro&msg=' . urlencode('Não foi possível excluir o usuário.'));
        exit;
    }

    private function obterAreasPost(): array
    {
        $areas = $_POST['areas'] ?? [];

        if (! is_array($areas)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $areas))));
    }
}
