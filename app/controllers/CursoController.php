<?php

require_once __DIR__ . '/../models/Curso.php';

class CursoController
{
    private Curso $cursoModel;

    public function __construct()
    {
        $this->cursoModel = new Curso();
    }

    public function index(): void
    {
        $this->exigirLogin();

        $busca  = trim($_GET['busca'] ?? '');
        $status = trim($_GET['status'] ?? 'todos');

        $cursos      = $this->cursoModel->listar($busca, $status);
        $totalCursos = $this->cursoModel->contar($busca, $status);

        require_once __DIR__ . '/../views/dashboard/cursos.php';
    }

    public function cadastrar(): void
    {
        $this->exigirLogin();

        $cursoModelos = $this->cursoModel->listarCursoModelos();

        require_once __DIR__ . '/../views/dashboard/cadastrar_curso.php';
    }

    public function editar(): void
    {
        $this->exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Turma invalida.'));
        }

        $cursoForm = $this->cursoModel->buscarPorId($id);

        if (! $cursoForm) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Turma nao encontrada.'));
        }

        $cursoModelos = $this->cursoModel->listarCursoModelos();

        require_once __DIR__ . '/../views/dashboard/editar_curso.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Metodo invalido.'));
        }

        $dados = $this->obterDadosPost();
        $queryBase = $this->montarQueryCadastro($dados);

        if (! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatorios.'));
        }

        if (! $this->cursoModel->cursoModeloExiste($dados['curso_modelo_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Curso selecionado nao foi encontrado.'));
        }

        if ($this->cursoModel->codigoOfertaExiste($dados['codigo_oferta'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Ja existe um curso com este codigo de oferta.'));
        }

        if ($this->cursoModel->salvar($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=sucesso&msg=' . urlencode('Turma cadastrada com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar a turma.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Metodo invalido.'));
        }

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        if (! $this->cursoModel->cursoModeloExiste($dados['curso_modelo_id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Curso selecionado nao foi encontrado.'));
        }

        if (! $this->cursoModel->buscarPorId($dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Turma nao encontrada.'));
        }

        if ($this->cursoModel->codigoOfertaExiste($dados['codigo_oferta'], $dados['id'])) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Ja existe outro curso com este codigo de oferta.'));
        }

        if ($this->cursoModel->atualizar($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=sucesso&msg=' . urlencode('Turma atualizada com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=turmas&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar a turma.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Turma invalida.'));
        }

        if ($this->cursoModel->excluir($id)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=sucesso&msg=' . urlencode('Turma excluida com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Nao foi possivel excluir a turma. Verifique se existe algum vinculo.'));
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
            'curso_modelo_id'     => (int) ($_POST['curso_modelo_id'] ?? 0),
            'nome'                => trim($_POST['nome'] ?? ''),
            'codigo_oferta'       => trim($_POST['codigo_oferta'] ?? ''),
            'periodo'             => trim($_POST['periodo'] ?? ''),
            'carga_horaria_total' => (int) ($_POST['carga_horaria_total'] ?? 0),
            'hora_aula'           => (int) ($_POST['hora_aula'] ?? 0),
            'status'              => trim($_POST['status'] ?? 'Em andamento'),
            'descricao'           => trim($_POST['descricao'] ?? ''),
        ];
    }

    private function validarDados(array $dados): bool
    {
        return $dados['nome'] !== ''
            && $dados['curso_modelo_id'] > 0
            && $dados['codigo_oferta'] !== ''
            && $dados['periodo'] !== ''
            && $dados['carga_horaria_total'] > 0
            && $dados['hora_aula'] > 0
            && in_array($dados['status'], ['Em andamento', 'Finalizada'], true);
    }

    private function montarQueryCadastro(array $dados): string
    {
        return http_build_query([
            'page'                => 'turmas',
            'action'              => 'cadastrar',
            'curso_modelo_id'     => $dados['curso_modelo_id'] > 0 ? $dados['curso_modelo_id'] : '',
            'nome'                => $dados['nome'],
            'codigo_oferta'       => $dados['codigo_oferta'],
            'periodo'             => $dados['periodo'],
            'carga_horaria_total' => $dados['carga_horaria_total'] > 0 ? $dados['carga_horaria_total'] : '',
            'hora_aula'           => $dados['hora_aula'] > 0 ? $dados['hora_aula'] : '',
            'status'              => $dados['status'],
            'descricao'           => $dados['descricao'],
        ]);
    }

    private function redirecionar(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

