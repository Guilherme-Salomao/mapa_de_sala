<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $mes = (int) ($mes ?? date('n'));
    $ano = (int) ($ano ?? date('Y'));
    $resumos = $resumos ?? [];
    $totais = $totais ?? [
        'docentes' => 0,
        'percentual_aula' => 0,
        'percentual_curso' => 0,
        'percentual_planejamento' => 0,
        'percentual_parada_pedagogica' => 0,
    ];

    $tituloPagina = 'Relatório Gestor';
    $subtituloPagina = 'Distribuição mensal da carga dos docentes';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';

    function formatarPercentualGestor(float $valor): string
    {
        return number_format($valor, 1, ',', '.') . '%';
    }

    function formatarHorasGestor(float $valor): string
    {
        return number_format($valor, 1, ',', '.') . 'h';
    }
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title>Relatorio Gestor - SIGHA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />

  <style>
  .gestor-resumo-card {
    border-left: 5px solid #0d6efd;
  }

  .gestor-resumo-card.curso {
    border-left-color: #198754;
  }

  .gestor-resumo-card.planejamento {
    border-left-color: #ffc107;
  }

  .gestor-resumo-card.parada {
    border-left-color: #0dcaf0;
  }

  .gestor-progress {
    height: .65rem;
  }
  </style>

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
            $paginaAtiva = 'relatorio_gestor';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="relatorio_gestor">

              <div class="col-6 col-lg-2">
                <label for="mes" class="form-label">Mês</label>
                <select class="form-select" id="mes" name="mes">
                  <?php for ($m = 1; $m <= 12; $m++): ?>
                  <option value="<?php echo $m; ?>" <?php echo $mes === $m ? 'selected' : ''; ?>>
                    <?php echo str_pad((string) $m, 2, '0', STR_PAD_LEFT); ?>
                  </option>
                  <?php endfor; ?>
                </select>
              </div>

              <div class="col-6 col-lg-2">
                <label for="ano" class="form-label">Ano</label>
                <input type="number" class="form-control" id="ano" name="ano" min="2000" max="2100"
                  value="<?php echo $ano; ?>">
              </div>

              <div class="col-12 col-lg-2">
                <button type="submit" class="btn app-btn-primary w-100">
                  <i class="bi bi-funnel"></i> Filtrar
                </button>
              </div>
            </form>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl">
              <div class="app-card gestor-resumo-card p-3 h-100">
                <div class="small text-muted">Docentes ativos</div>
                <div class="fs-3 fw-bold"><?php echo (int) ($totais['docentes'] ?? 0); ?></div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
              <div class="app-card gestor-resumo-card p-3 h-100">
                <div class="small text-muted">Em sala</div>
                <div class="fs-3 fw-bold"><?php echo formatarPercentualGestor((float) ($totais['percentual_aula'] ?? 0)); ?></div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
              <div class="app-card gestor-resumo-card curso p-3 h-100">
                <div class="small text-muted">Em curso</div>
                <div class="fs-3 fw-bold"><?php echo formatarPercentualGestor((float) ($totais['percentual_curso'] ?? 0)); ?></div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
              <div class="app-card gestor-resumo-card planejamento p-3 h-100">
                <div class="small text-muted">Planejamento</div>
                <div class="fs-3 fw-bold"><?php echo formatarPercentualGestor((float) ($totais['percentual_planejamento'] ?? 0)); ?></div>
              </div>
            </div>
            <div class="col-12 col-md-6 col-xl">
              <div class="app-card gestor-resumo-card parada p-3 h-100">
                <div class="small text-muted">Parada pedagógica</div>
                <div class="fs-3 fw-bold"><?php echo formatarPercentualGestor((float) ($totais['percentual_parada_pedagogica'] ?? 0)); ?></div>
              </div>
            </div>
          </div>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Resumo por Docente</div>
              <div class="small text-muted"><?php echo htmlspecialchars(sprintf('%02d/%04d', $mes, $ano)); ?></div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Docente</th>
                    <th>Área</th>
                    <th class="text-end">Sala</th>
                    <th class="text-end">Curso</th>
                    <th class="text-end">Planejamento</th>
                    <th class="text-end">Parada pedagógica</th>
                    <th class="text-end">Total</th>
                    <th style="min-width: 220px;">Distribuição</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($resumos)): ?>
                  <?php foreach ($resumos as $resumo): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold">
                        <?php echo htmlspecialchars($resumo['docente_nome'] ?? ''); ?> -
                        <?php echo formatarHorasGestor((float) ($resumo['horas_semanais'] ?? 0)); ?>
                      </div>
                      <div class="small text-muted"><?php echo htmlspecialchars($resumo['docente_email'] ?? ''); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($resumo['area_atuacao'] ?? ''); ?></td>
                    <td class="text-end">
                      <div class="fw-semibold"><?php echo formatarPercentualGestor((float) ($resumo['percentual_aula'] ?? 0)); ?></div>
                      <div class="small text-muted"><?php echo formatarHorasGestor((float) ($resumo['horas_aula'] ?? 0)); ?></div>
                    </td>
                    <td class="text-end">
                      <div class="fw-semibold"><?php echo formatarPercentualGestor((float) ($resumo['percentual_curso'] ?? 0)); ?></div>
                      <div class="small text-muted"><?php echo formatarHorasGestor((float) ($resumo['horas_curso'] ?? 0)); ?></div>
                    </td>
                    <td class="text-end">
                      <div class="fw-semibold"><?php echo formatarPercentualGestor((float) ($resumo['percentual_planejamento'] ?? 0)); ?></div>
                      <div class="small text-muted"><?php echo formatarHorasGestor((float) ($resumo['horas_planejamento'] ?? 0)); ?></div>
                    </td>
                    <td class="text-end">
                      <div class="fw-semibold"><?php echo formatarPercentualGestor((float) ($resumo['percentual_parada_pedagogica'] ?? 0)); ?></div>
                      <div class="small text-muted"><?php echo formatarHorasGestor((float) ($resumo['horas_parada_pedagogica'] ?? 0)); ?></div>
                    </td>
                    <td class="text-end"><?php echo formatarHorasGestor((float) ($resumo['total_horas'] ?? 0)); ?></td>
                    <td>
                      <div class="progress gestor-progress" role="progressbar">
                        <div class="progress-bar bg-primary"
                          style="width: <?php echo (float) ($resumo['percentual_aula'] ?? 0); ?>%"></div>
                        <div class="progress-bar bg-success"
                          style="width: <?php echo (float) ($resumo['percentual_curso'] ?? 0); ?>%"></div>
                        <div class="progress-bar bg-warning"
                          style="width: <?php echo (float) ($resumo['percentual_planejamento'] ?? 0); ?>%"></div>
                        <div class="progress-bar bg-info"
                          style="width: <?php echo (float) ($resumo['percentual_parada_pedagogica'] ?? 0); ?>%"></div>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                      Nenhum docente ativo encontrado para o período.
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
  if (pageTitle) pageTitle.textContent = "Relatório Gestor";

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

