<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../core/Email.php';
require_once __DIR__ . '/../core/AuditLog.php';

class LoginController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function autenticar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionarComMensagem('Método de requisição inválido.', 'erro');
        }

        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');

        if (empty($email) || empty($senha)) {
            $this->redirecionarComMensagem('Preencha e-mail e senha.', 'erro');
        }

        $usuario = $this->usuarioModel->buscarPorEmail($email);

        if (! $usuario) {
            $this->redirecionarComMensagem('Usuário não encontrado.', 'erro');
        }

        if ($usuario['status'] !== 'Ativo') {
            $this->redirecionarComMensagem('Usuário inativo. Procure o administrador.', 'erro');
        }

        if (! password_verify($senha, $usuario['senha'])) {
            $this->redirecionarComMensagem('Senha inválida.', 'erro');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id'           => $usuario['id'],
            'nome'         => $usuario['nome'],
            'email'        => $usuario['email'],
            'nivel_acesso' => $usuario['nivel_acesso'],
            'status'       => $usuario['status'],
        ];

        AuditLog::registrarRequisicao('login', 'entrar');

        $this->usuarioModel->atualizarUltimoLogin((int) $usuario['id']);

        header('Location: ./?page=home');
        exit;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();

        header('Location: ./?tipo=sucesso&msg=' . urlencode('Logout realizado com sucesso.'));
        exit;
    }

    public function cadastro(): void
    {
        $mensagem = $_GET['msg'] ?? '';
        $tipo = $_GET['tipo'] ?? '';

        require_once __DIR__ . '/../views/auth/cadastro.php';
    }

    public function cadastrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./?page=cadastro&tipo=erro&msg=' . urlencode('Método inválido.'));
            exit;
        }

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nivelAcesso = trim($_POST['nivel_acesso'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $confirmarSenha = trim($_POST['confirmar_senha'] ?? '');

        $queryBase = http_build_query([
            'page' => 'cadastro',
            'nome' => $nome,
            'email' => $email,
            'nivel_acesso' => $nivelAcesso,
        ]);

        if ($nome === '' || $email === '' || $nivelAcesso === '' || $senha === '' || $confirmarSenha === '') {
            header('Location: ./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos.'));
            exit;
        }

        if (! in_array($nivelAcesso, ['Gestor', 'Professor', 'Apoio'], true)) {
            header('Location: ./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Selecione um nível de acesso válido.'));
            exit;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Informe um e-mail válido.'));
            exit;
        }

        if (strlen($senha) < 4) {
            header('Location: ./?' . $queryBase . '&tipo=erro&msg=' . urlencode('A senha deve ter no mínimo 4 caracteres.'));
            exit;
        }

        if ($senha !== $confirmarSenha) {
            header('Location: ./?' . $queryBase . '&tipo=erro&msg=' . urlencode('As senhas não conferem.'));
            exit;
        }

        if ($this->usuarioModel->emailExiste($email)) {
            header('Location: ./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Já existe uma conta com este e-mail.'));
            exit;
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        if ($this->usuarioModel->cadastrar($nome, $email, $senhaHash, $nivelAcesso, 'Inativo')) {
            Email::enviarNovoCadastro([
                'nome' => $nome,
                'email' => $email,
                'nivel_acesso' => $nivelAcesso,
            ]);

            header('Location: ./?tipo=sucesso&msg=' . urlencode('Cadastro realizado com sucesso. Aguarde o administrador validar seus dados e liberar o acesso ao sistema.'));
            exit;
        }

        header('Location: ./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Não foi possível criar a conta.'));
        exit;
    }

    public function esqueciSenha(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $mensagem = $_GET['msg'] ?? '';
        $tipo = $_GET['tipo'] ?? '';
        $etapa = $_GET['etapa'] ?? 'email';
        $emailRecuperacao = $_SESSION['reset_senha_email'] ?? '';

        if ($etapa === 'redefinir' && empty($_SESSION['reset_senha_usuario_id'])) {
            header('Location: ./?page=esqueci_senha&tipo=erro&msg=' . urlencode('Informe o e-mail antes de alterar a senha.'));
            exit;
        }

        require_once __DIR__ . '/../views/auth/esqueci_senha.php';
    }

    public function solicitarRedefinicao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $email = trim($_POST['email'] ?? '');

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ./?page=esqueci_senha&tipo=erro&msg=' . urlencode('Informe um e-mail válido.'));
            exit;
        }

        $usuario = $this->usuarioModel->buscarPorEmail($email);

        if (! $usuario) {
            header('Location: ./?page=esqueci_senha&tipo=erro&msg=' . urlencode('E-mail não encontrado no sistema.'));
            exit;
        }

        $_SESSION['reset_senha_usuario_id'] = (int) $usuario['id'];
        $_SESSION['reset_senha_email'] = $usuario['email'];

        header('Location: ./?page=esqueci_senha&etapa=redefinir&tipo=sucesso&msg=' . urlencode('E-mail localizado. Informe a nova senha.'));
        exit;
    }

    public function redefinirSenha(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuarioId = (int) ($_SESSION['reset_senha_usuario_id'] ?? 0);
        $senha = trim($_POST['senha'] ?? '');
        $confirmarSenha = trim($_POST['confirmar_senha'] ?? '');

        if ($usuarioId <= 0) {
            header('Location: ./?page=esqueci_senha&tipo=erro&msg=' . urlencode('Informe o e-mail antes de alterar a senha.'));
            exit;
        }

        if (strlen($senha) < 4) {
            header('Location: ./?page=esqueci_senha&etapa=redefinir&tipo=erro&msg=' . urlencode('A senha deve ter no mínimo 4 caracteres.'));
            exit;
        }

        if ($senha !== $confirmarSenha) {
            header('Location: ./?page=esqueci_senha&etapa=redefinir&tipo=erro&msg=' . urlencode('As senhas não conferem.'));
            exit;
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $usuario = $this->usuarioModel->buscarPorId($usuarioId);

        if ($this->usuarioModel->atualizarSenhaEStatus($usuarioId, $senhaHash, 'Inativo')) {
            if ($usuario) {
                Email::enviarRedefinicaoSenha([
                    'nome' => $usuario['nome'] ?? '',
                    'email' => $usuario['email'] ?? '',
                    'nivel_acesso' => $usuario['nivel_acesso'] ?? '',
                ]);
            }

            unset($_SESSION['reset_senha_usuario_id'], $_SESSION['reset_senha_email']);

            header('Location: ./?tipo=sucesso&msg=' . urlencode('Senha alterada com sucesso. Seu cadastro ficou inativo e aguardará validação do administrador para liberação do acesso.'));
            exit;
        }

        header('Location: ./?page=esqueci_senha&etapa=redefinir&tipo=erro&msg=' . urlencode('Não foi possível alterar a senha.'));
        exit;
    }

    private function redirecionarComMensagem(string $mensagem, string $tipo = 'erro'): void
    {
        header('Location: ./?tipo=' . urlencode($tipo) . '&msg=' . urlencode($mensagem));
        exit;
    }
}
