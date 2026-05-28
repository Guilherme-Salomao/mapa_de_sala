<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $mensagem = $_GET['msg'] ?? '';
    $tipoMsg = $_GET['tipo'] ?? '';
    $busca = $busca ?? ($_GET['busca'] ?? '');
    $status = $status ?? ($_GET['status'] ?? 'todos');
    $registros = $registros ?? [];
    $turmas = $turmas ?? [];
    $ucs = $ucs ?? [];
    $salas = $salas ?? [];
    $docentes = $docentes ?? [];

    $formTurmaId = (int) ($_GET['curso_oferta_id'] ?? 0);
    $formUcId = (int) ($_GET['unidade_curricular_id'] ?? 0);
    $formSalaId = (int) ($_GET['sala_id'] ?? 0);
    $formDocenteId = (int) ($_GET['docente_id'] ?? 0);
    $formDataInicio = $_GET['data_inicio'] ?? date('Y-m-d');
    $formDataFim = $_GET['data_fim'] ?? date('Y-m-d');
    $formObservacoes = $_GET['observacoes'] ?? '';

    $tituloPagina = 'Aceleração';
    $subtituloPagina = 'Geração automática da aceleração no quadro horário';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title>Aceleração - SIGHA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />

  <script>
  (function() {
    const tema = localStorage.getItem("tema") || "light";
    document.documentElement.setAttribute("data-bs-theme", tema);
  })();
  </script>
</head>

<body>
  <?php require_once __DIR__ . '/../layouts/header.php'; ?>

  <main class="flex-grow-1">
    <div class="container-fluid">
      <div class="row g-0">
        <?php
            $paginaAtiva = 'aceleracao';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <?php if (! empty($mensagem)): ?>
          <div class="alert <?php echo $tipoMsg === 'sucesso' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <i class="bi <?php echo $tipoMsg === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
          <?php endif; ?>

          <div class="app-card p-3 mb-3">
            <form method="POST" action="./?page=aceleracao&action=salvar" class="row g-2 align-items-end">
              <div class="col-12 col-lg-4">
                <label class="form-label">Turma</label>
                <select name="curso_oferta_id" id="aprendizagemTurma" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($turmas as $turma): ?>
                  <option value="<?php echo (int) $turma['id']; ?>"
                    data-curso-modelo="<?php echo (int) $turma['curso_modelo_id']; ?>"
                    <?php echo $formTurmaId === (int) $turma['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($turma['nome'] ?? '') . ' - Oferta ' . ($turma['codigo_oferta'] ?? '')); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-lg-4">
                <label class="form-label">Unidade Curricular</label>
                <select name="unidade_curricular_id" id="aprendizagemUc" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($ucs as $uc): ?>
                  <option value="<?php echo (int) $uc['id']; ?>"
                    data-curso-modelo="<?php echo (int) $uc['curso_modelo_id']; ?>"
                    <?php echo $formUcId === (int) $uc['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($uc['codigo'] ?? '') . ' - ' . ($uc['nome'] ?? '') . ' (' . ($uc['curso_nome'] ?? '') . ')'); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-lg-4">
                <label class="form-label">Sala</label>
                <select name="sala_id" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($salas as $sala): ?>
                  <option value="<?php echo (int) $sala['id']; ?>"
                    <?php echo $formSalaId === (int) $sala['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($sala['nome'] ?? '') . ' - ' . ($sala['tipo'] ?? '')); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-lg-4">
                <label class="form-label">Docente</label>
                <select name="docente_id" id="aprendizagemDocente" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($docentes as $docente): ?>
                  <option value="<?php echo (int) $docente['id']; ?>"
                    data-ucs="<?php echo htmlspecialchars(',' . ($docente['ucs_vinculadas'] ?? '') . ','); ?>"
                    <?php echo $formDocenteId === (int) $docente['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($docente['nome'] ?? '') . ' - ' . ($docente['area_atuacao'] ?? '')); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label">Data inicial</label>
                <input type="date" name="data_inicio" class="form-control"
                  value="<?php echo htmlspecialchars($formDataInicio); ?>" required>
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label">Data final</label>
                <input type="date" name="data_fim" class="form-control"
                  value="<?php echo htmlspecialchars($formDataFim); ?>" required>
              </div>

              <div class="col-12 col-lg-8">
                <label class="form-label">Observação</label>
                <input type="text" name="observacoes" class="form-control" maxlength="255"
                  value="<?php echo htmlspecialchars($formObservacoes); ?>">
              </div>

              <div class="col-12 col-lg-4 d-flex justify-content-end">
                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-calendar-plus"></i> Gerar no Quadro
                </button>
              </div>
            </form>
          </div>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-center">
              <input type="hidden" name="page" value="aceleracao">

              <div class="col-12 col-md-7">
                <div class="input-group">
                  <span class="input-group-text app-input-icon">
                    <i class="bi bi-search"></i>
                  </span>
                  <input type="text" name="busca" class="form-control" placeholder="Buscar por turma, sala, UC ou docente..."
                    value="<?php echo htmlspecialchars($busca); ?>">
                </div>
              </div>

              <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                  <option value="todos" <?php echo $status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                  <option value="Ativo" <?php echo $status === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                  <option value="Inativo" <?php echo $status === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
              </div>

              <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn app-btn-primary w-100">
                  <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="./?page=aceleracao" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </div>
            </form>
          </div>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Programações de Aceleração</div>
              <div class="small text-muted"><?php echo (int) ($totalRegistros ?? count($registros)); ?> registro(s)</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Turma</th>
                    <th>Unidade Curricular</th>
                    <th>Sala</th>
                    <th>Docente</th>
                    <th>Período</th>
                    <th>Aulas</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($registros)): ?>
                  <?php foreach ($registros as $registro): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($registro['turma_nome'] ?? ''); ?></div>
                      <div class="small text-muted"><?php echo htmlspecialchars($registro['codigo_oferta'] ?? ''); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars(($registro['uc_codigo'] ?? '') . ' - ' . ($registro['uc_nome'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($registro['sala_nome'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($registro['docente_nome'] ?? ''); ?></td>
                    <td>
                      <?php echo htmlspecialchars(date('d/m/Y', strtotime($registro['data_inicio']))); ?>
                      até
                      <?php echo htmlspecialchars(date('d/m/Y', strtotime($registro['data_fim']))); ?>
                    </td>
                    <td><?php echo (int) ($registro['aulas_geradas'] ?? 0); ?></td>
                    <td class="text-end">
                      <form method="POST" action="./?page=aceleracao&action=excluir" class="app-actions">
                        <input type="hidden" name="id" value="<?php echo (int) $registro['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn">
                          <i class="bi bi-trash"></i> Excluir
                        </button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">Nenhuma programação cadastrada.</td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  (function() {
    const turmaSelect = document.getElementById("aprendizagemTurma");
    const ucSelect = document.getElementById("aprendizagemUc");
    const docenteSelect = document.getElementById("aprendizagemDocente");

    function filtrarUcs() {
      const turmaOption = turmaSelect.options[turmaSelect.selectedIndex];
      const cursoModelo = turmaOption ? turmaOption.getAttribute("data-curso-modelo") : "";
      let primeiraUc = "";

      Array.from(ucSelect.options).forEach((option) => {
        if (!option.value) {
          option.hidden = false;
          return;
        }

        const visivel = !cursoModelo || option.getAttribute("data-curso-modelo") === cursoModelo;
        option.hidden = !visivel;

        if (visivel && !primeiraUc) {
          primeiraUc = option.value;
        }
      });

      const selecionada = ucSelect.options[ucSelect.selectedIndex];

      if (selecionada && selecionada.hidden) {
        ucSelect.value = primeiraUc;
      }

      filtrarDocentes();
    }

    function filtrarDocentes() {
      const ucId = ucSelect.value;
      let primeiroDocente = "";

      Array.from(docenteSelect.options).forEach((option) => {
        if (!option.value) {
          option.hidden = false;
          return;
        }

        const ucs = option.getAttribute("data-ucs") || "";
        const visivel = ucId !== "" && ucs.includes("," + ucId + ",");
        option.hidden = !visivel;

        if (visivel && !primeiroDocente) {
          primeiroDocente = option.value;
        }
      });

      const selecionado = docenteSelect.options[docenteSelect.selectedIndex];

      if (!selecionado || selecionado.hidden) {
        docenteSelect.value = primeiroDocente;
      }
    }

    turmaSelect.addEventListener("change", filtrarUcs);
    ucSelect.addEventListener("change", filtrarDocentes);
    filtrarUcs();
  })();
  </script>
</body>

</html>


