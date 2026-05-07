<?php

require_once __DIR__ . '/../models/Usuario.php';

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

        $this->usuarioModel->atualizarUltimoLogin((int) $usuario['id']);

        header('Location: /mapa_de_sala/public/?page=home');
        exit;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();

        header('Location: /mapa_de_sala/public/?tipo=sucesso&msg=' . urlencode('Logout realizado com sucesso.'));
        exit;
    }

    private function redirecionarComMensagem(string $mensagem, string $tipo = 'erro'): void
    {
        header('Location: /mapa_de_sala/public/?tipo=' . urlencode($tipo) . '&msg=' . urlencode($mensagem));
        exit;
    }
}