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
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        $cursos      = $this->cursoModel->listar($busca, $status, $escopo);
        $totalCursos = $this->cursoModel->contar($busca, $status, $escopo);
        $salas       = $this->cursoModel->listarSalasAtivas();

        require_once __DIR__ . '/../views/dashboard/cursos.php';
    }

    public function cadastrar(): void
    {
        $this->exigirLogin();

        $cursoModelos = $this->cursoModel->listarCursoModelos((new AccessControl())->escopoAreaAtuacao());

        require_once __DIR__ . '/../views/dashboard/cadastrar_curso.php';
    }

    public function editar(): void
    {
        $this->exigirLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Turma invalida.'));
        }

        $cursoForm = $this->cursoModel->buscarPorId($id);

        if (! $cursoForm || ! $this->cursoModel->turmaPertenceEscopo($id, $escopo)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Turma nao encontrada.'));
        }

        $cursoModelos = $this->cursoModel->listarCursoModelos((new AccessControl())->escopoAreaAtuacao());

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
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if (! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatorios.'));
        }

        if (! $this->cursoModel->cursoModeloExiste($dados['curso_modelo_id'], $escopo)) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Curso selecionado nao foi encontrado.'));
        }

        if ($this->cursoModel->codigoOfertaExiste($dados['codigo_oferta'])) {
            $this->redirecionar('/mapa_de_sala/public/?' . $queryBase . '&tipo=erro&msg=' . urlencode('Ja existe um curso com este codigo de oferta.'));
        }

        $turmaId = $this->cursoModel->salvar($dados);

        if ($turmaId) {
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
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        if (! $this->cursoModel->cursoModeloExiste($dados['curso_modelo_id'], $escopo)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Curso selecionado nao foi encontrado.'));
        }

        if (! $this->cursoModel->buscarPorId($dados['id']) || ! $this->cursoModel->turmaPertenceEscopo($dados['id'], $escopo)) {
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
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($id <= 0) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Turma invalida.'));
        }

        if (! $this->cursoModel->turmaPertenceEscopo($id, $escopo)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Turma nao encontrada.'));
        }

        if ($this->cursoModel->excluir($id)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=sucesso&msg=' . urlencode('Turma excluida com sucesso.'));
        }

        $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Nao foi possivel excluir a turma. Verifique se existe algum vinculo.'));
    }

    public function gerarQuadro(): void
    {
        $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Metodo invalido.'));
        }

        $id = (int) ($_POST['id'] ?? 0);
        $dataInicio = trim($_POST['data_inicio'] ?? '');
        $salaId = (int) ($_POST['sala_id'] ?? 0);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($id <= 0 || $dataInicio === '' || ! $this->cursoModel->turmaPertenceEscopo($id, $escopo)) {
            $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=erro&msg=' . urlencode('Dados invalidos para gerar o quadro horario.'));
        }

        $resultado = $this->cursoModel->gerarQuadroCompleto($id, $dataInicio, $salaId > 0 ? $salaId : null);
        $tipo = ! empty($resultado['sucesso']) ? 'sucesso' : 'erro';

        $this->redirecionar('/mapa_de_sala/public/?page=turmas&tipo=' . $tipo . '&msg=' . urlencode($resultado['mensagem'] ?? 'Processo concluido.'));
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
            'hora_inicio'         => trim($_POST['hora_inicio'] ?? ''),
            'hora_fim'            => trim($_POST['hora_fim'] ?? ''),
            'aula_segunda'        => isset($_POST['aula_segunda']) ? 1 : 0,
            'aula_terca'          => isset($_POST['aula_terca']) ? 1 : 0,
            'aula_quarta'         => isset($_POST['aula_quarta']) ? 1 : 0,
            'aula_quinta'         => isset($_POST['aula_quinta']) ? 1 : 0,
            'aula_sexta'          => isset($_POST['aula_sexta']) ? 1 : 0,
            'aula_sabado'         => isset($_POST['aula_sabado']) ? 1 : 0,
            'status'              => trim($_POST['status'] ?? 'Em andamento'),
            'descricao'           => trim($_POST['descricao'] ?? ''),
        ];
    }

    private function validarDados(array $dados): bool
    {
        return $dados['nome'] !== ''
            && $dados['curso_modelo_id'] > 0
            && $dados['codigo_oferta'] !== ''
            && $this->validarHorario($dados)
            && $this->temDiaAula($dados)
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
            'hora_inicio'         => $dados['hora_inicio'],
            'hora_fim'            => $dados['hora_fim'],
            'aula_segunda'        => $dados['aula_segunda'],
            'aula_terca'          => $dados['aula_terca'],
            'aula_quarta'         => $dados['aula_quarta'],
            'aula_quinta'         => $dados['aula_quinta'],
            'aula_sexta'          => $dados['aula_sexta'],
            'aula_sabado'         => $dados['aula_sabado'],
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

    private function temDiaAula(array $dados): bool
    {
        foreach (['aula_segunda', 'aula_terca', 'aula_quarta', 'aula_quinta', 'aula_sexta', 'aula_sabado'] as $campo) {
            if ((int) ($dados[$campo] ?? 0) === 1) {
                return true;
            }
        }

        return false;
    }

}

