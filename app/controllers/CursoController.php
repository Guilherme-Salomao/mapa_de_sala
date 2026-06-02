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
        $docentesGeracao = $this->cursoModel->listarDocentesAtivos($escopo);
        $ucsPorCursoModelo = $this->cursoModel->listarUcsPorCursoModelos(array_column($cursos, 'curso_modelo_id'));

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
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Turma invalida.'));
        }

        $cursoForm = $this->cursoModel->buscarPorId($id);

        if (! $cursoForm || ! $this->cursoModel->turmaPertenceEscopo($id, $escopo)) {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Turma nao encontrada.'));
        }

        $cursoModelos = $this->cursoModel->listarCursoModelos((new AccessControl())->escopoAreaAtuacao());

        require_once __DIR__ . '/../views/dashboard/editar_curso.php';
    }

    public function salvar(): void
    {
        $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Metodo invalido.'));
        }

        $dados = $this->obterDadosPost();
        $queryBase = $this->montarQueryCadastro($dados);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if (! $this->validarDados($dados)) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Preencha todos os campos obrigatorios.'));
        }

        if (! $this->cursoModel->cursoModeloExiste($dados['curso_modelo_id'], $escopo)) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Curso selecionado nao foi encontrado.'));
        }

        if ($this->cursoModel->codigoOfertaExiste($dados['codigo_oferta'])) {
            $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Ja existe um curso com este codigo de oferta.'));
        }

        $turmaId = $this->cursoModel->salvar($dados);

        if ($turmaId) {
            $this->redirecionar('./?page=turmas&tipo=sucesso&msg=' . urlencode('Turma cadastrada com sucesso.'));
        }

        $this->redirecionar('./?' . $queryBase . '&tipo=erro&msg=' . urlencode('Nao foi possivel cadastrar a turma.'));
    }

    public function atualizar(): void
    {
        $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Metodo invalido.'));
        }

        $dados = $this->obterDadosPost();
        $dados['id'] = (int) ($_POST['id'] ?? 0);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($dados['id'] <= 0 || ! $this->validarDados($dados)) {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Dados invalidos para atualizacao.'));
        }

        if (! $this->cursoModel->cursoModeloExiste($dados['curso_modelo_id'], $escopo)) {
            $this->redirecionar('./?page=turmas&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Curso selecionado nao foi encontrado.'));
        }

        if (! $this->cursoModel->buscarPorId($dados['id']) || ! $this->cursoModel->turmaPertenceEscopo($dados['id'], $escopo)) {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Turma nao encontrada.'));
        }

        if ($this->cursoModel->codigoOfertaExiste($dados['codigo_oferta'], $dados['id'])) {
            $this->redirecionar('./?page=turmas&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Ja existe outro curso com este codigo de oferta.'));
        }

        if ($this->cursoModel->atualizar($dados)) {
            $this->redirecionar('./?page=turmas&tipo=sucesso&msg=' . urlencode('Turma atualizada com sucesso.'));
        }

        $this->redirecionar('./?page=turmas&action=editar&id=' . $dados['id'] . '&tipo=erro&msg=' . urlencode('Nao foi possivel atualizar a turma.'));
    }

    public function excluir(): void
    {
        $this->exigirLogin();

        $id = (int) ($_POST['id'] ?? 0);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($id <= 0) {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Turma invalida.'));
        }

        if (! $this->cursoModel->turmaPertenceEscopo($id, $escopo)) {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Turma nao encontrada.'));
        }

        if ($this->cursoModel->excluir($id)) {
            $this->redirecionar('./?page=turmas&tipo=sucesso&msg=' . urlencode('Turma excluida com sucesso.'));
        }

        $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Nao foi possivel excluir a turma. Verifique se existe algum vinculo.'));
    }

    public function gerarQuadro(): void
    {
        $this->exigirLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Metodo invalido.'));
        }

        $id = (int) ($_POST['id'] ?? 0);
        $modoGeracao = trim($_POST['modo_geracao'] ?? 'completo');
        $dataInicio = trim($_POST['data_inicio'] ?? '');
        $salaId = (int) ($_POST['sala_id'] ?? 0);
        $docenteId = (int) ($_POST['docente_id'] ?? 0);
        $escopo = (new AccessControl())->escopoAreaAtuacao();

        if ($id <= 0 || $dataInicio === '' || ! $this->cursoModel->turmaPertenceEscopo($id, $escopo)) {
            $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Dados invalidos para gerar o quadro horario.'));
        }

        if ($modoGeracao === 'uc_dia') {
            $unidadeCurricularId = (int) ($_POST['unidade_curricular_id'] ?? 0);
            $dataFim = trim($_POST['data_fim'] ?? '');
            $turnoGeracao = trim($_POST['turno_geracao'] ?? '');
            $diasSemanaPost = $_POST['dias_semana'] ?? [];
            $diasSemana = is_array($diasSemanaPost)
                ? array_map('intval', $diasSemanaPost)
                : [(int) $diasSemanaPost];

            if ($dataFim === '' || strtotime($dataFim) === false || strtotime($dataFim) < strtotime($dataInicio)) {
                $this->redirecionar('./?page=turmas&tipo=erro&msg=' . urlencode('Informe uma data final valida para gerar a UC.'));
            }

            $resultado = $this->cursoModel->gerarQuadroPorUcDia(
                $id,
                $unidadeCurricularId,
                $diasSemana,
                $dataInicio,
                $dataFim,
                $turnoGeracao !== '' ? $turnoGeracao : null,
                $salaId > 0 ? $salaId : null,
                $docenteId > 0 ? $docenteId : null
            );
        } else {
            $resultado = $this->cursoModel->gerarQuadroCompleto(
                $id,
                $dataInicio,
                $salaId > 0 ? $salaId : null,
                $docenteId > 0 ? $docenteId : null
            );
        }

        $tipo = ! empty($resultado['sucesso']) ? 'sucesso' : 'erro';

        $this->redirecionar('./?page=turmas&tipo=' . $tipo . '&msg=' . urlencode($resultado['mensagem'] ?? 'Processo concluido.'));
    }

    private function exigirLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['usuario'])) {
            $this->redirecionar('./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        }
    }

    private function obterDadosPost(): array
    {
        return [
            'curso_modelo_id'     => (int) ($_POST['curso_modelo_id'] ?? 0),
            'nome'                => trim($_POST['nome'] ?? ''),
            'codigo_oferta'       => trim($_POST['codigo_oferta'] ?? ''),
            'integral'            => isset($_POST['integral']) ? 1 : 0,
            'hora_inicio'         => trim($_POST['hora_inicio'] ?? ''),
            'hora_fim'            => trim($_POST['hora_fim'] ?? ''),
            'hora_inicio_tarde'   => trim($_POST['hora_inicio_tarde'] ?? ''),
            'hora_fim_tarde'      => trim($_POST['hora_fim_tarde'] ?? ''),
            'participa_parada_pedagogica' => isset($_POST['participa_parada_pedagogica']) ? 1 : 0,
            'participa_recesso_escolar' => isset($_POST['participa_recesso_escolar']) ? 1 : 0,
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
            'integral'            => $dados['integral'],
            'hora_inicio'         => $dados['hora_inicio'],
            'hora_fim'            => $dados['hora_fim'],
            'hora_inicio_tarde'   => $dados['hora_inicio_tarde'],
            'hora_fim_tarde'      => $dados['hora_fim_tarde'],
            'participa_parada_pedagogica' => $dados['participa_parada_pedagogica'],
            'participa_recesso_escolar' => $dados['participa_recesso_escolar'],
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
            return (int) ($dados['integral'] ?? 0) === 0;
        }

        if ($inicio === '' || $fim === '' || strtotime($fim) <= strtotime($inicio)) {
            return false;
        }

        if ((int) ($dados['integral'] ?? 0) === 0) {
            return true;
        }

        $inicioTarde = $dados['hora_inicio_tarde'] ?? '';
        $fimTarde = $dados['hora_fim_tarde'] ?? '';

        return $inicioTarde !== ''
            && $fimTarde !== ''
            && strtotime($fimTarde) > strtotime($inicioTarde)
            && strtotime($inicioTarde) >= strtotime($fim);
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

