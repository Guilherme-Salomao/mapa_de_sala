<?php

require_once __DIR__ . '/../models/UnidadeCurricular.php';

class UnidadeCurricularController
{
    private UnidadeCurricular $ucModel;

    public function __construct()
    {
        $this->ucModel = new UnidadeCurricular();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $busca = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');
        $cursoModeloId = (int) ($_GET['curso_modelo_id'] ?? 0);

        $ucs = $this->ucModel->listar($busca, $status, $cursoModeloId);
        $totalUcs = $this->ucModel->contar($busca, $status, $cursoModeloId);
        $cursoModelos = $this->ucModel->listarCursoModelos();

        require_once __DIR__ . '/../views/dashboard/ucs.php';
    }

    public function cadastrar(): void
    {
        $this->exigirLogin();

        $cursoModelos = $this->ucModel->listarCursoModelos();

        require_once __DIR__ . '/../views/dashboard/cadastrar_uc.php';
    }

    public function editar(): void
    {
        $this->exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=erro&msg=' . urlencode('UC invalida.'));
        }

        $ucForm = $this->ucModel->buscarPorId($id);

        if (! $ucForm) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=erro&msg=' . urlencode('UC nao encontrada.'));
        }

        $cursoModelos = $this->ucModel->listarCursoModelos();

        require_once __DIR__ . '/../views/dashboard/editar_uc.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $queryBase = $this->montarQueryCadastro($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatorios.'));
        }

        if (! $this->ucModel->cursoModeloExiste($dados['curso_modelo_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Modelo de curso nao encontrado.'));
        }

        if ($this->ucModel->codigoExiste($dados['curso_modelo_id'], $dados['codigo'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Ja existe uma UC com este codigo para o modelo selecionado.'));
        }

        if ($this->ucModel->salvar($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=sucesso&msg=' . urlencode('UC cadastrada com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar a UC.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        if (! $this->ucModel->buscarPorId($dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=erro&msg=' . urlencode('UC nao encontrada.'));
        }

        if ($this->ucModel->codigoExiste($dados['curso_modelo_id'], $dados['codigo'], $dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Ja existe outra UC com este codigo para o modelo selecionado.'));
        }

        if ($this->ucModel->atualizar($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=sucesso&msg=' . urlencode('UC atualizada com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=ucs&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar a UC.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=erro&msg=' . urlencode('UC invalida.'));
        }

        if ($this->ucModel->excluir($id)) {
            $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=sucesso&msg=' . urlencode('UC excluida com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=ucs&tipo=erro&msg=' . urlencode('Nao foi possivel excluir a UC.'));
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            $this->redirecionar('/mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        }
    }

    private function obterDadosPost(): array
    {
        return [
            'curso_modelo_id' => (int) ($_POST['curso_modelo_id'] ?? 0),
            'codigo'          => trim($_POST['codigo'] ?? ''),
            'nome'            => trim($_POST['nome'] ?? ''),
            'carga_horaria'   => (int) ($_POST['carga_horaria'] ?? 0),
            'ordem'           => (int) ($_POST['ordem'] ?? 0),
            'status'          => trim($_POST['status'] ?? 'Ativa'),
        ];
    }

    private function validarDados(array $dados): bool
    {
        return $dados['curso_modelo_id'] > 0
            && $dados['codigo'] !== ''
            && $dados['nome'] !== ''
            && $dados['carga_horaria'] > 0
            && $dados['ordem'] > 0
            && in_array($dados['status'], ['Ativa', 'Inativa'], true);
    }

    private function montarQueryCadastro(array $dados): string
    {
        return http_build_query([
            'page'            => 'ucs',
            'action'          => 'cadastrar',
            'curso_modelo_id' => $dados['curso_modelo_id'] > 0 ? $dados['curso_modelo_id'] : '',
            'codigo'          => $dados['codigo'],
            'nome'            => $dados['nome'],
            'carga_horaria'   => $dados['carga_horaria'] > 0 ? $dados['carga_horaria'] : '',
            'ordem'           => $dados['ordem'] > 0 ? $dados['ordem'] : '',
            'status'          => $dados['status'],
        ]);
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
