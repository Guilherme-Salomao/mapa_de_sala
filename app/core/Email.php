<?php

class Email
{
    public static function enviarNovoCadastro(array $dados): bool
    {
        self::carregarConfig();

        $destinatario = defined('MAIL_NOVO_CADASTRO_PARA') ? trim((string) MAIL_NOVO_CADASTRO_PARA) : '';

        if ($destinatario === '') {
            return false;
        }

        $assunto = 'Novo cadastro aguardando liberacao';
        $mensagem = self::montarMensagemNovoCadastro($dados);
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . self::fromHeader(),
            'Reply-To: ' . ($dados['email'] ?? self::fromEmail()),
        ];

        return mail($destinatario, $assunto, $mensagem, implode("\r\n", $headers));
    }

    public static function enviarRedefinicaoSenha(array $dados): bool
    {
        self::carregarConfig();

        $destinatario = defined('MAIL_NOVO_CADASTRO_PARA') ? trim((string) MAIL_NOVO_CADASTRO_PARA) : '';

        if ($destinatario === '') {
            return false;
        }

        $assunto = 'Usuario redefiniu senha e aguarda liberacao';
        $mensagem = self::montarMensagemRedefinicaoSenha($dados);
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . self::fromHeader(),
            'Reply-To: ' . ($dados['email'] ?? self::fromEmail()),
        ];

        return mail($destinatario, $assunto, $mensagem, implode("\r\n", $headers));
    }

    private static function montarMensagemNovoCadastro(array $dados): string
    {
        return implode(PHP_EOL, [
            'Um novo cadastro foi realizado e aguarda validacao.',
            '',
            'Nome: ' . ($dados['nome'] ?? ''),
            'E-mail: ' . ($dados['email'] ?? ''),
            'Nivel solicitado: ' . ($dados['nivel_acesso'] ?? ''),
            'Status: Inativo',
            '',
            'Acesse o sistema para validar os dados e liberar o acesso.',
        ]);
    }

    private static function montarMensagemRedefinicaoSenha(array $dados): string
    {
        return implode(PHP_EOL, [
            'Um usuario redefiniu a senha e agora aguarda nova liberacao.',
            '',
            'Nome: ' . ($dados['nome'] ?? ''),
            'E-mail: ' . ($dados['email'] ?? ''),
            'Nivel de acesso: ' . ($dados['nivel_acesso'] ?? ''),
            'Status: Inativo',
            '',
            'Acesse o sistema para validar os dados e liberar novamente o acesso.',
        ]);
    }

    private static function carregarConfig(): void
    {
        if (! defined('MAIL_FROM')) {
            require_once __DIR__ . '/../config/config.php';
        }
    }

    private static function fromHeader(): string
    {
        $nome = defined('MAIL_FROM_NAME') ? (string) MAIL_FROM_NAME : 'Sistema';
        $email = self::fromEmail();

        return $nome . ' <' . $email . '>';
    }

    private static function fromEmail(): string
    {
        return defined('MAIL_FROM') ? (string) MAIL_FROM : 'nao-responder@localhost';
    }
}
