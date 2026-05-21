<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $indicadores = $indicadores ?? [];
    $aulasPorTurno = $aulasPorTurno ?? ['Manha' => [], 'Tarde' => [], 'Noite' => []];
    $dataHoje = $dataHoje ?? date('Y-m-d');

    $dataHojeFormatada = date('d/m/Y', strtotime($dataHoje));
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Sistema de Controle de Salas</title>

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
              <div class="small text-muted">Visao geral do quadro horario em <?php echo htmlspecialchars($dataHojeFormatada); ?></div>
            </div>

          </div>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="/mapa_de_sala/public/" class="row g-2 align-items-end">
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
                <a href="/mapa_de_sala/public/?page=home" class="btn btn-outline-secondary">
                  <i class="bi bi-calendar-event"></i> Hoje
                </a>
              </div>
            </form>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
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

            <div class="col-12 col-md-6 col-xl-3">
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

            <div class="col-12 col-md-6 col-xl-3">
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

            <div class="col-12 col-md-6 col-xl-3">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Salas em manutencao</div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($indicadores['salas_manutencao'] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--manut">
                    <i class="bi bi-tools"></i>
                  </div>
                </div>
                <div class="small text-muted mt-2">Indisponiveis no cadastro</div>
              </div>
            </div>
          </div>

          <div class="app-card p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <div>
                <div class="fw-bold">Mapa de sala</div>
              </div>
            </div>

            <div class="row g-3">
              <?php foreach ($aulasPorTurno as $turno => $aulasTurno): ?>
              <?php
                  $iconeTurno = [
                      'Manha' => 'bi-sunrise',
                      'Tarde' => 'bi-sun',
                      'Noite' => 'bi-moon-stars',
                  ][$turno] ?? 'bi-calendar';
              ?>
              <div class="col-12 col-xl-4">
                <div class="border rounded p-3 h-100">
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2 fw-bold">
                      <i class="bi <?php echo htmlspecialchars($iconeTurno); ?>"></i>
                      <?php echo htmlspecialchars($turno); ?>
                    </div>
                    <span class="badge text-bg-primary fs-6 px-3 py-2">
                      <?php echo count($aulasTurno); ?> turma(s)
                    </span>
                  </div>

                  <?php if (! empty($aulasTurno)): ?>
                  <div class="d-grid gap-2">
                    <?php foreach ($aulasTurno as $aula): ?>
                    <div class="border rounded p-2">
                      <div class="fw-semibold"><?php echo htmlspecialchars($aula['sala_nome'] ?? ''); ?></div>
                      <div><?php echo htmlspecialchars($aula['turma_nome'] ?? ''); ?></div>
                      <div class="small text-muted"><?php echo htmlspecialchars($aula['docentes'] ?? ''); ?></div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php else: ?>
                  <div class="text-center text-muted py-4">
                    Nenhuma aula neste turno.
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
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
  if (pageTitle) pageTitle.textContent = "Dashboard";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "/mapa_de_sala/public/?page=logout";
    }
  });
  </script>
</body>

</html>
