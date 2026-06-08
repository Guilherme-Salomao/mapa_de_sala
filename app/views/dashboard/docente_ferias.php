<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuário';
    $mensagem = $_GET['msg'] ?? '';
    $tipoMsg = $_GET['tipo'] ?? '';
    $docentes = $docentes ?? [];
    $registros = $registros ?? [];
    $registroForm = $registroForm ?? null;
    $paginaPeriodo = $paginaPeriodo ?? 'ferias';
    $nomePeriodo = $nomePeriodo ?? 'Férias';
    $nomePeriodoPlural = $nomePeriodoPlural ?? 'Férias';
    $subtituloPeriodo = $subtituloPeriodo ?? 'Cadastre e consulte os períodos de férias dos docentes';
    $nomePeriodoMinusculo = $paginaPeriodo === 'compensacao' ? 'compensação' : 'férias';
    $nomePeriodoPluralMinusculo = $paginaPeriodo === 'compensacao' ? 'compensações' : 'férias';
    $dataInicio = $dataInicio ?? date('Y-m-01');
    $dataFim = $dataFim ?? date('Y-m-t');
    $relatorioProprioDocente = $relatorioProprioDocente ?? false;
    $formEdicao = ! empty($registroForm);
    $formAction = $formEdicao
        ? './?page=' . $paginaPeriodo . '&action=atualizar'
        : './?page=' . $paginaPeriodo . '&action=salvar';
    $formDocenteId = (int) ($registroForm['docente_id'] ?? ($docentes[0]['id'] ?? 0));
    $formDataInicio = $registroForm['data_inicio'] ?? '';
    $formDataFim = $registroForm['data_fim'] ?? '';
    $formObservacoes = $registroForm['observacoes'] ?? '';
    $formStatus = $registroForm['status'] ?? 'Ativo';
    $tituloPagina = $nomePeriodo;
    $subtituloPagina = $subtituloPeriodo;
    $botaoTopoTexto = '';
    $botaoTopoLink = '';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title><?php echo htmlspecialchars($nomePeriodo); ?> - SIGHA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
</head>

<body>
  <?php require_once __DIR__ . '/../layouts/header.php'; ?>

  <main class="flex-grow-1">
    <div class="container-fluid">
      <div class="row g-0">
        <?php
            $paginaAtiva = $paginaPeriodo;
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <?php if (! empty($mensagem)): ?>
          <div class="alert <?php echo $tipoMsg === 'sucesso' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
          <?php endif; ?>

          <div class="app-card p-3 mb-3">
            <form method="POST" action="<?php echo htmlspecialchars($formAction); ?>" class="row g-2 align-items-end">
              <?php if ($formEdicao): ?>
              <input type="hidden" name="id" value="<?php echo (int) $registroForm['id']; ?>">
              <?php endif; ?>

              <div class="col-12 col-lg-4">
                <label class="form-label">Docente</label>
                <?php if ($relatorioProprioDocente): ?>
                <input type="hidden" name="docente_id" value="<?php echo $formDocenteId; ?>">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($docentes[0]['nome'] ?? ''); ?>" disabled>
                <?php else: ?>
                <select name="docente_id" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($docentes as $docente): ?>
                  <option value="<?php echo (int) $docente['id']; ?>"
                    <?php echo $formDocenteId === (int) $docente['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($docente['nome'] ?? '') . ' - ' . ($docente['area_atuacao'] ?? '')); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <?php endif; ?>
              </div>

              <div class="col-12 col-md-3 col-lg-2">
                <label class="form-label">Data inicial</label>
                <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($formDataInicio); ?>" required>
              </div>

              <div class="col-12 col-md-3 col-lg-2">
                <label class="form-label">
                  Data final<?php echo $paginaPeriodo === 'compensacao' ? ' (opcional)' : ''; ?>
                </label>
                <input type="date" name="data_fim" class="form-control"
                  value="<?php echo htmlspecialchars($formDataFim); ?>"
                  <?php echo $paginaPeriodo === 'compensacao' ? '' : 'required'; ?>>
              </div>

              <div class="col-12 col-md-3 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                  <option value="Ativo" <?php echo $formStatus === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                  <option value="Inativo" <?php echo $formStatus === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Observação</label>
                <textarea name="observacoes" class="form-control" rows="2"><?php echo htmlspecialchars($formObservacoes); ?></textarea>
              </div>

              <div class="col-12 d-flex gap-2 justify-content-end">
                <?php if ($formEdicao): ?>
                <a href="./?page=<?php echo urlencode($paginaPeriodo); ?>" class="btn btn-outline-secondary">
                  <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <?php endif; ?>
                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-save"></i>
                  <?php echo $formEdicao ? 'Salvar Alteração' : 'Salvar ' . htmlspecialchars($nomePeriodo); ?>
                </button>
              </div>
            </form>
          </div>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="<?php echo htmlspecialchars($paginaPeriodo); ?>">
              <div class="col-12 col-md-4 col-lg-3">
                <label for="ano" class="form-label">Ano</label>
                <input type="number" id="ano" name="ano" class="form-control" min="1900" max="2200"
                  value="<?php echo (int) ($ano ?? date('Y')); ?>" required>
              </div>
              <div class="col-12 col-md-auto">
                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-funnel"></i> Filtrar
                </button>
              </div>
              <div class="col-12 col-md-auto">
                <a href="./?page=<?php echo urlencode($paginaPeriodo); ?>" class="btn btn-outline-secondary" title="Limpar filtro">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </div>
            </form>
          </div>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">
                Relatório de <?php echo htmlspecialchars($nomePeriodoPluralMinusculo); ?>
                de <?php echo (int) ($ano ?? date('Y')); ?>
              </div>
              <div class="small text-muted"><?php echo (int) ($totalRegistros ?? count($registros)); ?> registro(s)</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Docente</th>
                    <th>Área</th>
                    <th>Data inicial</th>
                    <th>Data final</th>
                    <th>Dias</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($registros)): ?>
                  <?php foreach ($registros as $registro): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($registro['docente_nome'] ?? ''); ?></div>
                      <?php if (! empty($registro['observacoes'])): ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($registro['observacoes']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($registro['area_atuacao'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($registro['data_inicio']))); ?></td>
                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($registro['data_fim']))); ?></td>
                    <td class="fw-semibold"><?php echo (int) ($registro['quantidade_dias'] ?? 0); ?></td>
                    <td>
                      <span class="badge <?php echo ($registro['status'] ?? '') === 'Ativo' ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                        <?php echo htmlspecialchars($registro['status'] ?? ''); ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="app-actions">
                        <a href="./?page=<?php echo urlencode($paginaPeriodo); ?>&action=editar&id=<?php echo (int) $registro['id']; ?>"
                          class="btn btn-sm btn-outline-primary app-action-btn">
                          <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form method="POST" action="./?page=<?php echo urlencode($paginaPeriodo); ?>&action=excluir">
                          <input type="hidden" name="id" value="<?php echo (int) $registro['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn">
                            <i class="bi bi-trash"></i> Excluir
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      Nenhum período de <?php echo htmlspecialchars($nomePeriodoMinusculo); ?> encontrado.
                    </td>
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

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = <?php echo json_encode($nomePeriodo, JSON_UNESCAPED_UNICODE); ?>;
  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;
  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) window.location.href = "./?page=logout";
  });
  </script>
</body>

</html>
