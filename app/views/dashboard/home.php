<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $nivelHome = $_SESSION['usuario']['nivel_acesso'] ?? '';
    $isApoioHome = $nivelHome === 'Apoio';
    $dashboardGestor = $dashboardGestor ?? false;
    $indicadores = $indicadores ?? [];
    $aulasPorTurno = $aulasPorTurno ?? ['Manha' => [], 'Tarde' => [], 'Noite' => []];
    $indicadoresGestor = $indicadoresGestor ?? [];
    $resumoDocentesGestor = $resumoDocentesGestor ?? [];
    $dashboardDocente = $dashboardDocente ?? false;
    $minhaSemana = $minhaSemana ?? [];
    $indicadoresDocente = $indicadoresDocente ?? [
        'horas_semana' => 0,
        'horas_mes' => 0,
        'percentual_aula' => 0,
        'percentual_planejamento' => 0,
        'percentual_curso' => 0,
    ];
    $dataHoje = $dataHoje ?? date('Y-m-d');

    $dataHojeFormatada = date('d/m/Y', strtotime($dataHoje));
    $mesAnoReferencia = date('m/Y', strtotime($dataHoje));
    $areaGestorLabel = 'área';
    $dataAtualAtalho = date('Y-m-d');
    $dataAmanhaAtalho = date('Y-m-d', strtotime($dataAtualAtalho . ' +1 day'));
    $dataDepoisAmanhaAtalho = date('Y-m-d', strtotime($dataAtualAtalho . ' +2 days'));
    $atalhosSemanaGestor = [];

    for ($offset = -3; $offset <= 3; $offset++) {
        $dataAtalho = date('Y-m-d', strtotime($dataHoje . ' ' . ($offset >= 0 ? '+' : '') . $offset . ' days'));
        $atalhosSemanaGestor[] = [
            'data' => $dataAtalho,
            'label' => $dataAtalho === $dataAtualAtalho ? 'Hoje' : date('d/m/Y', strtotime($dataAtalho)),
            'icone' => $offset < 0 ? 'bi-calendar-minus' : ($offset > 0 ? 'bi-calendar-plus' : 'bi-calendar-event'),
        ];
    }

    function formatarHorasHome(float $horas): string
    {
        if (fmod($horas, 1.0) === 0.0) {
            return (int) $horas . 'h';
        }

        $horasInteiras = (int) floor($horas);
        $minutos = (int) round(($horas - $horasInteiras) * 60);

        return $horasInteiras . 'h' . str_pad((string) $minutos, 2, '0', STR_PAD_LEFT);
    }
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title>Dashboard - SIGHA</title>

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
            $paginaAtiva = 'home';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
              <h4 class="mb-0">Dashboard</h4>
              <div class="small text-muted">Visão geral do quadro horário em <?php echo htmlspecialchars($dataHojeFormatada); ?></div>
            </div>

          </div>

          <?php if ($dashboardGestor): ?>
          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="home">
              <div class="col-12 col-md-4 col-lg-3">
                <label for="data_gestor" class="form-label">Data</label>
                <input type="date" class="form-control" id="data_gestor" name="data"
                  value="<?php echo htmlspecialchars($dataHoje); ?>">
              </div>
              <div class="col-12 col-md-auto">
                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-search"></i> Filtrar
                </button>
              </div>
              <?php foreach ($atalhosSemanaGestor as $atalhoGestor): ?>
              <div class="col-12 col-md-auto">
                <a href="./?page=home&data=<?php echo htmlspecialchars($atalhoGestor['data']); ?>"
                  class="btn <?php echo $dataHoje === $atalhoGestor['data'] ? 'app-btn-primary' : 'btn-outline-secondary'; ?>">
                  <i class="bi <?php echo htmlspecialchars($atalhoGestor['icone']); ?>"></i>
                  <?php echo htmlspecialchars($atalhoGestor['label']); ?>
                </a>
              </div>
              <?php endforeach; ?>
            </form>
          </div>

          <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-3">
            <div class="col">
              <div class="app-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Turmas em andamento</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadoresGestor['turmas_em_andamento'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--default">
                    <i class="bi bi-calendar3"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col">
              <div class="app-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Total de docentes na <?php echo htmlspecialchars($areaGestorLabel); ?></div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadoresGestor['docentes_ativos'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--livre">
                    <i class="bi bi-person-badge"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col">
              <div class="app-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Docentes em aula (<?php echo htmlspecialchars($dataHojeFormatada); ?>)</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadoresGestor['docentes_em_aula'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--uso">
                    <i class="bi bi-easel"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col">
              <div class="app-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Aulas sem docentes (<?php echo htmlspecialchars($dataHojeFormatada); ?>)</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadoresGestor['aulas_sem_docente'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--manut">
                    <i class="bi bi-exclamation-triangle"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col">
              <div class="app-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Docentes em planejamento (<?php echo htmlspecialchars($dataHojeFormatada); ?>)</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadoresGestor['docentes_planejamento'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--default">
                    <i class="bi bi-journal-text"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12">
              <div class="app-card p-3 h-100">
                <div class="fw-bold mb-3">Docentes no mês <?php echo htmlspecialchars($mesAnoReferencia); ?></div>
                <?php if (! empty($resumoDocentesGestor)): ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead class="small text-muted">
                      <tr>
                        <th>Docente</th>
                        <th class="text-end">Sala</th>
                        <th class="text-end">Plan.</th>
                        <th class="text-end">Curso</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($resumoDocentesGestor as $docenteResumo): ?>
                      <tr>
                        <td>
                          <div class="fw-semibold"><?php echo htmlspecialchars($docenteResumo['docente_nome'] ?? ''); ?></div>
                          <div class="small text-muted"><?php echo (int) ($docenteResumo['horas_semanais'] ?? 0); ?>h semanais</div>
                        </td>
                        <td class="text-end"><?php echo number_format((float) ($docenteResumo['percentual_aula'] ?? 0), 1, ',', '.'); ?>%</td>
                        <td class="text-end"><?php echo number_format((float) ($docenteResumo['percentual_planejamento'] ?? 0), 1, ',', '.'); ?>%</td>
                        <td class="text-end"><?php echo number_format((float) ($docenteResumo['percentual_curso'] ?? 0), 1, ',', '.'); ?>%</td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4">Nenhum docente ativo na área.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php elseif (! $dashboardDocente): ?>
          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="home">
              <div class="col-12 col-md-4 col-lg-3">
                <label for="data" class="form-label">Data</label>
                <input type="date" class="form-control" id="data" name="data"
                  value="<?php echo htmlspecialchars($dataHoje); ?>">
              </div>
              <div class="col-12 col-md-auto">
                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-search"></i> Filtrar
                </button>
              </div>
              <div class="col-12 col-md-auto">
                <a href="./?page=home&data=<?php echo htmlspecialchars($dataAtualAtalho); ?>"
                  class="btn <?php echo $dataHoje === $dataAtualAtalho ? 'app-btn-primary' : 'btn-outline-secondary'; ?>">
                  <i class="bi bi-calendar-event"></i> Hoje
                </a>
              </div>
              <?php if ($isApoioHome): ?>
              <div class="col-12 col-md-auto">
                <a href="./?page=home&data=<?php echo htmlspecialchars($dataAmanhaAtalho); ?>"
                  class="btn <?php echo $dataHoje === $dataAmanhaAtalho ? 'app-btn-primary' : 'btn-outline-secondary'; ?>">
                  <i class="bi bi-calendar-plus"></i>
                  <?php echo htmlspecialchars(date('d/m/Y', strtotime($dataAmanhaAtalho))); ?>
                </a>
              </div>
              <div class="col-12 col-md-auto">
                <a href="./?page=home&data=<?php echo htmlspecialchars($dataDepoisAmanhaAtalho); ?>"
                  class="btn <?php echo $dataHoje === $dataDepoisAmanhaAtalho ? 'app-btn-primary' : 'btn-outline-secondary'; ?>">
                  <i class="bi bi-calendar-plus"></i>
                  <?php echo htmlspecialchars(date('d/m/Y', strtotime($dataDepoisAmanhaAtalho))); ?>
                </a>
              </div>
              <?php endif; ?>
            </form>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Salas cadastradas</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadores['total_salas'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--default">
                    <i class="bi bi-door-open"></i>
                  </div>
                </div>
                <div class="small text-muted mt-2">Total geral do cadastro</div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Salas ocupadas</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadores['salas_ocupadas'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--uso">
                    <i class="bi bi-calendar-check"></i>
                  </div>
                </div>
                <div class="small text-muted mt-2"><?php echo htmlspecialchars($dataHojeFormatada); ?></div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Salas livres</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadores['salas_livres'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--livre">
                    <i class="bi bi-check-circle"></i>
                  </div>
                </div>
                <div class="small text-muted mt-2"><?php echo htmlspecialchars($dataHojeFormatada); ?></div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Salas em manutenção</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadores['salas_manutencao'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--manut">
                    <i class="bi bi-tools"></i>
                  </div>
                </div>
                <div class="small text-muted mt-2"><?php echo htmlspecialchars($dataHojeFormatada); ?></div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Salas reservadas</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadores['salas_reservadas'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--uso">
                    <i class="bi bi-bookmark-check"></i>
                  </div>
                </div>
                <div class="small text-muted mt-2"><?php echo htmlspecialchars($dataHojeFormatada); ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($dashboardDocente): ?>
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Carga Horária</div>
                    <div class="fs-3 fw-bold"><?php echo htmlspecialchars(formatarHorasHome((float) ($indicadoresDocente['horas_semana'] ?? 0))); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--default">
                    <i class="bi bi-calendar-week"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Carga Horária Mensal (<?php echo htmlspecialchars($mesAnoReferencia); ?>)</div>
                    <div class="fs-3 fw-bold"><?php echo htmlspecialchars(formatarHorasHome((float) ($indicadoresDocente['horas_mes'] ?? 0))); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--uso">
                    <i class="bi bi-calendar3"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">% em sala (<?php echo htmlspecialchars($mesAnoReferencia); ?>)</div>
                    <div class="fs-3 fw-bold"><?php echo number_format((float) ($indicadoresDocente['percentual_aula'] ?? 0), 1, ',', '.'); ?>%</div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--livre">
                    <i class="bi bi-easel"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">% em planejamento (<?php echo htmlspecialchars($mesAnoReferencia); ?>)</div>
                    <div class="fs-3 fw-bold"><?php echo number_format((float) ($indicadoresDocente['percentual_planejamento'] ?? 0), 1, ',', '.'); ?>%</div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--manut">
                    <i class="bi bi-journal-text"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">% em curso (<?php echo htmlspecialchars($mesAnoReferencia); ?>)</div>
                    <div class="fs-3 fw-bold"><?php echo number_format((float) ($indicadoresDocente['percentual_curso'] ?? 0), 1, ',', '.'); ?>%</div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--uso">
                    <i class="bi bi-mortarboard"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="app-card p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <div>
                <div class="fw-bold">Minha semana</div>
                <div class="small text-muted">Aulas, planejamento e educação corporativa</div>
              </div>
            </div>

            <div class="row g-3">
              <?php foreach ($minhaSemana as $diaSemana): ?>
              <div class="col-12 col-md-6 col-xl-2">
                <div class="border rounded p-3 h-100 <?php echo ! empty($diaSemana['hoje']) ? 'border-primary bg-primary-subtle' : ''; ?>">
                  <div class="text-center mb-3">
                    <div class="fw-bold"><?php echo htmlspecialchars($diaSemana['dia_nome'] ?? ''); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($diaSemana['dia_mes'] ?? ''); ?></div>
                  </div>

                  <?php if (! empty($diaSemana['eventos'])): ?>
                  <div class="d-grid gap-2">
                    <?php foreach ($diaSemana['eventos'] as $evento): ?>
                    <?php
                        $tipoEvento = (string) ($evento['tipo'] ?? '');
                        $classeEvento = [
                            'aula' => 'border-primary',
                            'planejamento' => 'border-warning bg-warning-subtle',
                            'curso' => 'border-success bg-success-subtle',
                        ][$tipoEvento] ?? '';
                    ?>
                    <div class="border rounded p-2 small <?php echo htmlspecialchars($classeEvento); ?>">
                      <div class="fw-bold text-center"><?php echo htmlspecialchars($evento['periodo'] ?? ''); ?></div>
                      <?php if (! empty($evento['hora'])): ?>
                      <div class="fw-semibold text-center"><?php echo htmlspecialchars($evento['hora']); ?></div>
                      <?php endif; ?>
                      <div class="fw-semibold text-center"><?php echo htmlspecialchars($evento['titulo'] ?? ''); ?></div>
                      <?php if (! empty($evento['uc'])): ?>
                      <div class="text-center"><?php echo htmlspecialchars($evento['uc']); ?></div>
                      <?php endif; ?>
                      <?php if (! empty($evento['sala'])): ?>
                      <div class="text-muted text-center">Sala <?php echo htmlspecialchars($evento['sala']); ?></div>
                      <?php endif; ?>

                      <?php if ((int) ($evento['visita_tecnica'] ?? 0) === 1): ?>
                      <div class="text-center mt-1">
                        <span class="badge text-bg-info">Visita Técnica</span>
                      </div>
                      <?php endif; ?>
                      <?php if ((int) ($evento['ead_assincrona'] ?? 0) === 1): ?>
                      <div class="text-center mt-1">
                        <span class="badge text-bg-secondary">EAD/Assíncrona</span>
                      </div>
                      <?php endif; ?>
                      <?php if (! empty($evento['aprendizagem_quadro_id'])): ?>
                      <div class="text-center mt-1">
                        <span class="badge text-bg-warning">Aceleração</span>
                      </div>
                      <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php else: ?>
                  <div class="text-center text-muted small py-3">Sem escala</div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php else: ?>
          <?php require __DIR__ . '/home_mapa_sala.php'; ?>
          <?php endif; ?>

        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Dashboard";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "./?page=logout";
    }
  });
  </script>
</body>

</html>

