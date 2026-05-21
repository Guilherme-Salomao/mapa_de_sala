<?php

require_once __DIR__ . '/../models/Curso.php';
require_once __DIR__ . '/../core/AccessControl.php';

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
        $escopo = (new AccessControl())->escopo();

        $cursos      = $this->cursoModel->listar($busca, $status, $escopo);
        $totalCursos = $this->cursoModel->contar($busca, $status, $escopo);

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
            'hora_inicio'         => trim($_POST['hora_inicio'] ?? ''),
            'hora_fim'            => trim($_POST['hora_fim'] ?? ''),
            'carga_horaria_total' => (int) ($_POST['carga_horaria_total'] ?? 0),
            'hora_aula'           => $this->obterHoraAulaPost(),
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
            && $this->validarHorario($dados)
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
            'hora_inicio'         => $dados['hora_inicio'],
            'hora_fim'            => $dados['hora_fim'],
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

    private function validarHorario(array $dados): bool
    {
        $inicio = $dados['hora_inicio'];
        $fim = $dados['hora_fim'];

        if ($inicio === '' && $fim === '') {
            return true;
        }

        return $inicio !== '' && $fim !== '';
    }

    private function obterHoraAulaPost(): float
    {
        $horas = (int) ($_POST['hora_aula_horas'] ?? 0);
        $minutos = (int) ($_POST['hora_aula_minutos'] ?? 0);

        if ($horas < 0) {
            $horas = 0;
        }

        if ($minutos < 0) {
            $minutos = 0;
        }

        if ($minutos > 59) {
            $minutos = 59;
        }

        return round($horas + ($minutos / 60), 2);
    }
}

