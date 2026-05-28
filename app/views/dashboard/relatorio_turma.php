<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $turmas = $turmas ?? [];
    $resumoTurmas = $resumoTurmas ?? [];
    $turmaId = (int) ($turmaId ?? 0);
    $linhas = $linhas ?? [];
    $datasTurma = $datasTurma ?? ['data_inicial' => null, 'data_final' => null];

    $tituloPagina = 'Relatorio da Turma';
    $subtituloPagina = 'Acompanhamento de carga horária por unidade curricular';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';

    $totalCarga = 0;
    $totalLancadas = 0;
    $totalDadas = 0;

    foreach ($linhas as $linhaResumo) {
        $totalCarga += (float) ($linhaResumo['carga_horaria'] ?? 0);
        $totalLancadas += round((float) ($linhaResumo['horas_lancadas'] ?? 0), 2);
    }

    $turmaConcluida = $totalCarga > 0 && $totalLancadas >= $totalCarga;
    $corDataFinal = $turmaConcluida ? '#198754' : '#f97316';
    $totalDadas = $totalLancadas;

    $formatarDataTurma = static function (?string $data): string {
        return ! empty($data) ? date('d/m/Y', strtotime($data)) : '-';
    };
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Relatorio da Turma - SIGHA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />

  <style>
  .relatorio-turma-table th {
    background: #0d6efd;
    color: #fff;
    font-size: 1rem;
    white-space: nowrap;
  }

  .relatorio-turma-table td {
    font-size: 1rem;
    vertical-align: middle;
  }

  .relatorio-turma-table .col-numero {
    text-align: center;
    white-space: nowrap;
  }

  .horas-ok {
    background: #86ef8b !important;
  }

  .horas-acima {
    background: #35c43b !important;
  }

  .horas-pendente {
    background: #fff3cd !important;
  }

  .relatorio-turma-resumo th {
    background: #0d6efd;
    color: #fff;
    white-space: nowrap;
  }

  .relatorio-turma-resumo td {
    vertical-align: middle;
  }

  .badge-concluida {
    background: #198754;
  }

  .badge-andamento {
    background: #f97316;
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
            $paginaAtiva = 'relatorio_turma';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <div class="app-card p-3 mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
              <div>
                <h2 class="h5 mb-1">Início e fim das turmas</h2>
                <div class="text-muted small">
                  A data final só aparece quando a turma atingiu a carga horária total do curso.
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered relatorio-turma-resumo mb-0">
                <thead>
                  <tr>
                    <th>Turma</th>
                    <th>Curso</th>
                    <th>Área</th>
                    <th class="text-center">Carga Total</th>
                    <th class="text-center">Horas Lançadas</th>
                    <th class="text-center">A Lançar</th>
                    <th class="text-center">Data Inicial</th>
                    <th class="text-center">Data Final</th>
                    <th class="text-center">Situação</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($resumoTurmas)): ?>
                  <?php foreach ($resumoTurmas as $resumo): ?>
                  <?php
                      $cargaResumo = (float) ($resumo['carga_horaria_total'] ?? 0);
                      $lancadasResumo = round((float) ($resumo['horas_lancadas'] ?? 0), 2);
                      $faltantesResumo = max($cargaResumo - $lancadasResumo, 0);
                      $concluidaResumo = $cargaResumo > 0 && $lancadasResumo >= $cargaResumo;
                      $dataFimResumo = $concluidaResumo ? ($resumo['ultima_aula'] ?? null) : null;
                  ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($resumo['nome'] ?? ''); ?></strong><br>
                      <span class="small text-muted"><?php echo htmlspecialchars($resumo['codigo_oferta'] ?? ''); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($resumo['curso_nome'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($resumo['area_nome'] ?? '-'); ?></td>
                    <td class="text-center"><?php echo number_format($cargaResumo, 0, ',', '.'); ?>h</td>
                    <td class="text-center"><?php echo number_format($lancadasResumo, 1, ',', '.'); ?>h</td>
                    <td class="text-center"><?php echo number_format($faltantesResumo, 1, ',', '.'); ?>h</td>
                    <td class="text-center"><?php echo htmlspecialchars($formatarDataTurma($resumo['data_inicio'] ?? null)); ?></td>
                    <td class="text-center fw-bold <?php echo $concluidaResumo ? 'text-success' : 'text-warning'; ?>">
                      <?php echo htmlspecialchars($formatarDataTurma($dataFimResumo)); ?>
                    </td>
                    <td class="text-center">
                      <span class="badge <?php echo $concluidaResumo ? 'badge-concluida' : 'badge-andamento'; ?>">
                        <?php echo $concluidaResumo ? 'Carga atingida' : 'Em andamento'; ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                      Nenhuma turma encontrada para sua área.
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="relatorio_turma">

              <div class="col-12 col-lg-8">
                <label for="turma_id" class="form-label">Turma</label>
                <select class="form-select" id="turma_id" name="turma_id" required>
                  <option value="">Selecione a turma...</option>
                  <?php foreach ($turmas as $turma): ?>
                  <option value="<?php echo (int) $turma['id']; ?>"
                    <?php echo $turmaId === (int) $turma['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(($turma['nome'] ?? '') . ' - ' . ($turma['codigo_oferta'] ?? '')); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-lg-4">
                <button type="submit" class="btn app-btn-primary w-100">
                  <i class="bi bi-funnel"></i> Filtrar
                </button>
              </div>
            </form>
          </div>

          <?php if (! empty($turmaSelecionada)): ?>
          <div class="app-card p-3 mb-3">
            <div class="d-flex flex-nowrap align-items-center gap-3 overflow-auto">
              <div class="fw-bold flex-shrink-0"><?php echo htmlspecialchars($turmaSelecionada['nome'] ?? ''); ?></div>
              <div class="small text-muted flex-shrink-0">
                Oferta <?php echo htmlspecialchars($turmaSelecionada['codigo_oferta'] ?? ''); ?>
                <?php if (! empty($turmaSelecionada['curso_nome'])): ?>
                · <?php echo htmlspecialchars($turmaSelecionada['curso_nome']); ?>
                <?php endif; ?>
              </div>
              <div class="small flex-shrink-0">
                Data inicial:
                <strong>
                  <?php echo ! empty($datasTurma['data_inicial']) ? htmlspecialchars(date('d/m/Y', strtotime($datasTurma['data_inicial'])))  : '-'; ?>
                </strong>
              </div>
              <div class="small flex-shrink-0">
                Data final:
                <strong style="color: <?php echo $corDataFinal; ?>;">
                  <?php echo $turmaConcluida && ! empty($datasTurma['data_final']) ? htmlspecialchars(date('d/m/Y', strtotime($datasTurma['data_final'])))  : '-'; ?>
                </strong>
              </div>
            </div>
          </div>

          <div class="app-card p-3">
            <div class="table-responsive">
              <table class="table table-bordered relatorio-turma-table mb-0">
                <thead>
                  <tr>
                    <th>Unidade Curricular</th>
                    <th class="text-center">Carga Horária</th>
                    <th class="text-center">A Lancar</th>
                    <th class="text-center">Horas Lancadas</th>
                    <th class="text-center">Horas Dadas</th>
                    <th class="text-center">Data Inicial</th>
                    <th class="text-center">Data Final</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($linhas)): ?>
                  <?php foreach ($linhas as $linha): ?>
                  <?php
                      $cargaHoraria = (float) ($linha['carga_horaria'] ?? 0);
                      $horasLancadas = round((float) ($linha['horas_lancadas'] ?? 0), 2);
                      $horasDadas = $horasLancadas;
                      $aLancar = $cargaHoraria - $horasLancadas;
                      $classeHoras = abs($horasLancadas - $cargaHoraria) < 0.01
                           ? 'horas-ok'
                          : ($horasLancadas > $cargaHoraria ? 'horas-acima' : 'horas-pendente');
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars(($linha['codigo'] ?? '') . '-' . ($linha['nome'] ?? '')); ?></td>
                    <td class="col-numero"><?php echo number_format($cargaHoraria, 0, ',', '.'); ?></td>
                    <td class="col-numero"><?php echo number_format($aLancar, 0, ',', '.'); ?></td>
                    <td class="col-numero <?php echo $classeHoras; ?>"><?php echo number_format($horasLancadas, 0, ',', '.'); ?></td>
                    <td class="col-numero"><?php echo number_format($horasDadas, 0, ',', '.'); ?></td>
                    <td class="col-numero">
                      <?php echo ! empty($linha['data_inicial']) ? htmlspecialchars(date('d/m/y', strtotime($linha['data_inicial'])))  : '-'; ?>
                    </td>
                    <td class="col-numero">
                      <?php echo ! empty($linha['data_final']) ? htmlspecialchars(date('d/m/y', strtotime($linha['data_final'])))  : '-'; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <tr class="fw-bold">
                    <td>Total</td>
                    <td class="col-numero"><?php echo number_format($totalCarga, 0, ',', '.'); ?></td>
                    <td class="col-numero"><?php echo number_format($totalCarga - $totalLancadas, 0, ',', '.'); ?></td>
                    <td class="col-numero"><?php echo number_format($totalLancadas, 0, ',', '.'); ?></td>
                    <td class="col-numero"><?php echo number_format($totalDadas, 0, ',', '.'); ?></td>
                    <td colspan="2"></td>
                  </tr>
                  <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      Nenhuma unidade curricular encontrada para esta turma.
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php else: ?>
          <div class="app-card p-4 text-center text-muted">
            Selecione uma turma para visualizar o relatorio.
          </div>
          <?php endif; ?>
        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Relatorio da Turma";

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

