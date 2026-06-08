<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuário';
    $turmas = $turmas ?? [];
    $aulasSemDocente = $aulasSemDocente ?? [];
    $dataInicio = $dataInicio ?? date('Y-m-d');
    $dataFim = $dataFim ?? date('Y-m-d', strtotime('+30 days'));
    $turmaId = (int) ($turmaId ?? 0);
    $temPendencias = ! empty($aulasSemDocente);

    $tituloPagina = 'Aulas sem Docente';
    $subtituloPagina = 'Antecipe a distribuição de docentes no quadro horário';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';
    $botaoTopoClasse = 'app-btn-primary';
    $botaoTopoIcone = 'bi-person-exclamation';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title>Aulas sem Docente - SIGHA</title>
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
            $paginaAtiva = 'relatorio_turmas_sem_docente';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="relatorio_turmas_sem_docente">

              <div class="col-12 col-md-3">
                <label for="data_inicio" class="form-label">Data inicial</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio"
                  value="<?php echo htmlspecialchars($dataInicio); ?>" required>
              </div>

              <div class="col-12 col-md-3">
                <label for="data_fim" class="form-label">Data final</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim"
                  value="<?php echo htmlspecialchars($dataFim); ?>" required>
              </div>

              <div class="col-12 col-md-4">
                <label for="turma_id" class="form-label">Turma</label>
                <select class="form-select" id="turma_id" name="turma_id">
                  <option value="0">Todas as turmas</option>
                  <?php foreach ($turmas as $turma): ?>
                  <option value="<?php echo (int) ($turma['id'] ?? 0); ?>"
                    <?php echo $turmaId === (int) ($turma['id'] ?? 0) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($turma['nome'] ?? '') . ' - ' . ($turma['codigo_oferta'] ?? '')); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-auto">
                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-search"></i> Filtrar
                </button>
              </div>

              <div class="col-12 col-md-auto">
                <a href="./?page=relatorio_turmas_sem_docente" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-clockwise"></i> Limpar
                </a>
              </div>
            </form>
          </div>

          <div class="app-card p-3 <?php echo $temPendencias ? 'border border-danger bg-danger-subtle' : ''; ?>">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <div>
                <div class="fw-bold <?php echo $temPendencias ? 'text-danger' : ''; ?>">Aulas aguardando docente</div>
                <div class="small text-muted">
                  <?php echo htmlspecialchars(date('d/m/Y', strtotime($dataInicio))); ?>
                  a
                  <?php echo htmlspecialchars(date('d/m/Y', strtotime($dataFim))); ?>
                </div>
              </div>
              <span class="badge <?php echo $temPendencias ? 'text-bg-danger' : 'text-bg-success'; ?> fs-6 px-3 py-2">
                <?php echo count($aulasSemDocente); ?>
              </span>
            </div>

            <?php if ($temPendencias): ?>
            <div class="d-flex flex-wrap gap-3 align-items-center mb-3 small">
              <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-success">Disponível com escala</span>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-warning">Disponível com troca de escala</span>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Turma</th>
                    <th>UC</th>
                    <th>Sala</th>
                    <th>Área</th>
                    <th>Possíveis docentes</th>
                    <th class="text-end">Ação</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($aulasSemDocente as $aula): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars(date('d/m/Y', strtotime($aula['data_aula']))); ?></td>
                    <td>
                      <?php echo htmlspecialchars(substr((string) $aula['hora_inicio'], 0, 5)); ?>
                      -
                      <?php echo htmlspecialchars(substr((string) $aula['hora_fim'], 0, 5)); ?>
                    </td>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($aula['turma_nome'] ?? ''); ?></div>
                      <div class="small text-muted">Oferta <?php echo htmlspecialchars($aula['codigo_oferta'] ?? ''); ?></div>
                    </td>
                    <td>
                      <?php if (($aula['uc_codigo'] ?? '') !== 'TURMA'): ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($aula['uc_codigo'] ?? ''); ?></div>
                      <?php endif; ?>
                      <?php echo htmlspecialchars($aula['uc_nome'] ?? ''); ?>
                    </td>
                    <td><?php echo htmlspecialchars($aula['sala_nome'] ?? 'Sala em aberto'); ?></td>
                    <td><?php echo htmlspecialchars($aula['area_nome'] ?? 'Não informada'); ?></td>
                    <td>
                      <?php if (! empty($aula['possiveis_docentes'])): ?>
                      <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($aula['possiveis_docentes'] as $docentePossivel): ?>
                        <?php $trocaEscala = ($docentePossivel['disponibilidade'] ?? '') === 'troca_escala'; ?>
                        <span class="badge <?php echo $trocaEscala ? 'text-bg-warning' : 'text-bg-success'; ?>">
                          <?php echo htmlspecialchars($docentePossivel['nome'] ?? ''); ?>
                        </span>
                        <?php endforeach; ?>
                      </div>
                      <?php else: ?>
                      <span class="text-danger fw-semibold">Nenhum disponível</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-primary app-action-btn"
                        href="./?page=quadro_horario&curso_oferta_id=<?php echo (int) $aula['curso_oferta_id']; ?>&mes=<?php echo (int) date('n', strtotime($aula['data_aula'])); ?>&ano=<?php echo (int) date('Y', strtotime($aula['data_aula'])); ?>">
                        <i class="bi bi-calendar-week"></i> Abrir quadro
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php else: ?>
            <div class="text-center text-muted py-5">
              <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
              Todas as aulas do período possuem docente.
            </div>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
