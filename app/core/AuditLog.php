<?php

require_once __DIR__ . '/../models/SistemaLog.php';

class AuditLog
{
    public static function registrarRequisicao(string $pagina, string $acao): void
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $usuario = $_SESSION['usuario'] ?? [];
            $dados = self::limparDados($_POST);

            $log = new SistemaLog();
            $log->salvar([
                'usuario_id' => isset($usuario['id']) ? (int) $usuario['id'] : null,
                'usuario_nome' => $usuario['nome'] ?? null,
                'usuario_email' => $usuario['email'] ?? null,
                'nivel_acesso' => $usuario['nivel_acesso'] ?? null,
                'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                'pagina' => $pagina,
                'acao' => $acao !== '' ? $acao : 'autenticar',
                'descricao' => self::descricao($pagina, $acao),
                'dados' => json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'navegador' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $e) {
            return;
        }
    }

    private static function limparDados(array $dados): array
    {
        $bloqueados = [
            'senha',
            'confirmar_senha',
            'password',
            'token',
            'csrf_token',
        ];

        foreach ($dados as $chave => $valor) {
            if (in_array(strtolower((string) $chave), $bloqueados, true)) {
                $dados[$chave] = '[protegido]';
                continue;
            }

            if (is_array($valor)) {
                $dados[$chave] = self::limparDados($valor);
            }
        }

        return $dados;
    }

    private static function descricao(string $pagina, string $acao): string
    {
        $nomes = [
            'salvar' => 'Cadastro',
            'atualizar' => 'Atualizacao',
            'excluir' => 'Exclusao',
            'solicitar' => 'Solicitacao',
            'redefinir' => 'Redefinicao',
            '' => 'Autenticacao',
        ];

        $acaoTexto = $nomes[$acao] ?? ucfirst($acao);
        $paginaTexto = str_replace('_', ' ', $pagina);

        return trim($acaoTexto . ' em ' . $paginaTexto);
    }
}
