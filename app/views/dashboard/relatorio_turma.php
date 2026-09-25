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

    $tituloPagina = 'Relatório da Turma';
    $subtituloPagina = 'Acompanhamento de carga horária por unidade curricular';
    $botaoTopoTexto = 'Nova Turma';
    $botaoTopoLink = './?page=turmas&action=cadastrar';
    $botaoTopoClasse = 'app-btn-primary';
    $botaoTopoIcone = 'bi-plus-circle';

    $formatarMinutosTurma = static function (int $minutos): string {
        $sinal = $minutos < 0 ? '-' : '';
        $minutos = abs($minutos);
        $horas = intdiv($minutos, 60);
        $restante = $minutos % 60;

        return $sinal . $horas . 'h' . ($restante > 0 ? ' e ' . $restante . 'min' : '');
    };

    $totalCargaMinutos = 0;
    $totalLancadasMinutos = 0;
    $totalDadasMinutos = 0;
    $cargaConclusaoMinutos = 0;
    $lancadasConclusaoMinutos = 0;
    $ucConclusaoPendente = false;
    $uc12Pendente = false;

    foreach ($linhas as $linhaResumo) {
        $cargaLinhaMinutos = (int) round(((float) ($linhaResumo['carga_horaria'] ?? 0)) * 60);
        $lancadasLinhaMinutos = (int) ($linhaResumo['minutos_lancados'] ?? 0);
        $totalCargaMinutos += $cargaLinhaMinutos;
        $totalLancadasMinutos += $lancadasLinhaMinutos;

        if ((int) ($linhaResumo['conta_conclusao'] ?? 1) === 1) {
            $cargaConclusaoMinutos += $cargaLinhaMinutos;
            $lancadasConclusaoMinutos += $lancadasLinhaMinutos;

            if ($lancadasLinhaMinutos < $cargaLinhaMinutos) {
                $ucConclusaoPendente = true;
            }
        } elseif ($lancadasLinhaMinutos < $cargaLinhaMinutos) {
            $uc12Pendente = true;
        }
    }

    $turmaConcluida = $cargaConclusaoMinutos > 0
        && $lancadasConclusaoMinutos >= $cargaConclusaoMinutos
        && ! $ucConclusaoPendente;
    $corDataFinal = $turmaConcluida ? '#198754' : '#f97316';
    $totalDadasMinutos = $totalLancadasMinutos;

    $formatarDataTurma = static function (?string $data): string {
        return ! empty($data) ? date('d/m/Y', strtotime($data)) : '-';
    };

    $nomesMesesRelatorioTurma = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];
    $mesRelatorioTurma = (int) date('n');
    $anoRelatorioTurma = (string) date('Y');
    $formatarDocentesTurma = static function (array $docentes): string {
        if (empty($docentes)) {
            return '<span class="text-muted">-</span>';
        }

        $itens = [];

        foreach ($docentes as $docente) {
            $nome = htmlspecialchars((string) ($docente['docente_nome'] ?? $docente['nome'] ?? ''));
            $totalAulas = (int) ($docente['total_aulas'] ?? 0);
            $sufixo = $totalAulas > 0 ? ' <span class="text-muted">(' . $totalAulas . ' aula' . ($totalAulas === 1 ? '' : 's') . ')</span>' : '';
            $principal = (int) ($docente['principal'] ?? 0) === 1
                ? ' <span class="badge bg-success-subtle text-success border border-success-subtle">Principal</span>'
                : '';
            $itens[] = '<div class="docente-relatorio-item">' . $nome . $sufixo . $principal . '</div>';
        }

        return implode('', $itens);
    };
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title>Relatório da Turma - SIGHA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />

  <style>
  .relatorio-turma-table th {
    background: #0d6efd;
    color: #fff;
    font-size: 0.94rem;
    line-height: 1.15;
    white-space: nowrap;
  }

  .relatorio-turma-table td {
    font-size: 1rem;
    vertical-align: middle;
  }

  .relatorio-turma-table .col-uc { width: 35%; }
  .relatorio-turma-table .col-docente { width: 20%; }
  .relatorio-turma-table .col-carga { width: 8.5%; }
  .relatorio-turma-table .col-a-lancar { width: 7.5%; }
  .relatorio-turma-table .col-lancadas { width: 9.5%; }
  .relatorio-turma-table .col-dadas { width: 8.5%; }
  .relatorio-turma-table .col-data { width: 5.5%; }

  .relatorio-turma-table .col-numero {
    text-align: center;
    white-space: nowrap;
  }

  .docente-relatorio-item {
    font-size: 0.92rem;
    line-height: 1.25;
    margin-bottom: 0.25rem;
  }

  .docente-relatorio-item:last-child {
    margin-bottom: 0;
  }


  .relatorio-print-header,
  .relatorio-print-footer {
    display: none;
  }

  @media print {
    @page {
      size: A4 landscape;
      margin: 8mm;
    }

    * {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    body {
      background: #fff !important;
    }

    header,
    footer,
    .app-sidebar,
    .relatorio-no-print,
    .page-header,
    .app-content > .app-card:not(.relatorio-print-area) {
      display: none !important;
    }

    .container-fluid,
    .row,
    .app-content {
      display: block !important;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      max-width: none !important;
    }

    .relatorio-print-area {
      border: 0 !important;
      box-shadow: none !important;
      padding: 0 !important;
    }

    .relatorio-print-header {
      align-items: center;
      background: #0d6efd !important;
      border-radius: 6px;
      color: #fff !important;
      display: flex !important;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 8px;
      padding: 8px 10px;
      text-align: left;
    }

    .relatorio-print-header .relatorio-print-oferta,
    .relatorio-print-header .relatorio-print-turma {
      color: #fff !important;
      font-size: 11px;
      line-height: 1.2;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .relatorio-print-header .relatorio-print-oferta {
      flex: 1 1 auto;
    }

    .relatorio-print-header .relatorio-print-turma {
      flex: 0 0 auto;
      font-size: 12px;
      font-weight: 700;
      text-align: left;
    }

    .relatorio-print-header .relatorio-print-oferta {
      text-align: right;
    }

    .relatorio-turma-table thead th {
      background: #0d6efd !important;
      color: #fff !important;
      font-size: 8.5px !important;
      line-height: 1.1;
      padding-left: 4px !important;
      padding-right: 4px !important;
      white-space: nowrap;
    }

    .relatorio-turma-table tbody tr:nth-child(even) td {
      background: #f4f9ff !important;
    }

    .relatorio-turma-table tbody tr.fw-bold td {
      background: #fff !important;
    }

    .table-responsive {
      overflow: visible !important;
    }

    .relatorio-print-footer {
      align-items: center;
      background: #fff !important;
      color: #31556a !important;
      display: flex !important;
      font-size: 6.5px;
      gap: 8px;
      justify-content: space-between;
      line-height: 1;
      margin-top: 1.2mm;
      min-height: 4mm;
      padding: 0.6mm 0 0;
    }

    .relatorio-print-footer div:first-child {
      flex: 1 1 auto;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .relatorio-print-footer div:last-child {
      flex: 0 0 auto;
      white-space: nowrap;
    }

    .relatorio-print-footer strong {
      color: #004a8d !important;
    }

    .relatorio-turma-table {
      font-size: 10px;
      table-layout: fixed;
      width: 100%;
    }

    .relatorio-turma-table th,
    .relatorio-turma-table td {
      font-size: 10px;
      padding: 6px 7px;
    }

    .relatorio-turma-table th:nth-child(1),
    .relatorio-turma-table td:nth-child(1) {
      width: 36%;
    }

    .relatorio-turma-table th:nth-child(2),
    .relatorio-turma-table td:nth-child(2) {
      width: 21%;
    }

    .docente-relatorio-item {
      font-size: 9px;
    }
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

  .horas-dadas-acima {
    color: #dc3545 !important;
    font-weight: 700;
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
          <div class="relatorio-no-print">
            <?php require_once __DIR__ . '/../components/page_header.php'; ?>
          </div>

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
                      $cargaResumoMinutos = (int) round(((float) ($resumo['carga_horaria_total'] ?? 0)) * 60);
                      $lancadasResumoMinutos = (int) ($resumo['minutos_lancados'] ?? 0);
                      $faltantesResumoMinutos = max($cargaResumoMinutos - $lancadasResumoMinutos, 0);
                      $ucsPendentesResumo = (int) ($resumo['ucs_pendentes_conclusao'] ?? 0);
                      $concluidaResumo = $cargaResumoMinutos > 0
                          && $lancadasResumoMinutos >= $cargaResumoMinutos
                          && $ucsPendentesResumo === 0;
                      $cargaUc12Resumo = (int) ($resumo['uc12_carga_minutos'] ?? 0);
                      $lancadasUc12Resumo = (int) ($resumo['uc12_minutos_lancados'] ?? 0);
                      $uc12PendenteResumo = $cargaUc12Resumo > 0 && $lancadasUc12Resumo < $cargaUc12Resumo;
                      $dataFimResumo = $concluidaResumo ? ($resumo['ultima_aula'] ?? null) : null;
                  ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($resumo['nome'] ?? ''); ?></strong><br>
                      <span class="small text-muted"><?php echo htmlspecialchars($resumo['codigo_oferta'] ?? ''); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($resumo['curso_nome'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($resumo['area_nome'] ?? '-'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($formatarMinutosTurma($cargaResumoMinutos)); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($formatarMinutosTurma($lancadasResumoMinutos)); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($formatarMinutosTurma($faltantesResumoMinutos)); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($formatarDataTurma($resumo['data_inicio'] ?? null)); ?></td>
                    <td class="text-center fw-bold <?php echo $concluidaResumo ? 'text-success' : 'text-warning'; ?>">
                      <?php echo htmlspecialchars($formatarDataTurma($dataFimResumo)); ?>
                    </td>
                    <td class="text-center">
                      <span class="badge <?php echo $concluidaResumo && ! $uc12PendenteResumo ? 'badge-concluida' : 'badge-andamento'; ?>">
                        <?php echo $concluidaResumo ? 'Carga atingida' : 'Em andamento'; ?>
                      </span>
                      <?php if ($concluidaResumo && $uc12PendenteResumo): ?>
                      <div class="small fw-bold text-warning mt-1">Falta UC12</div>
                      <?php endif; ?>
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
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
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
              <button type="button" class="btn btn-sm app-btn-primary flex-shrink-0 relatorio-no-print" id="btnImprimirRelatorioTurma">
                <i class="bi bi-printer"></i> Imprimir
              </button>
            </div>
          </div>

          <div class="app-card p-3 relatorio-print-area" id="relatorioTurmaImpressao">
            <div class="relatorio-print-header">
              <div class="relatorio-print-turma">
                Relatório da Turma: <?php echo htmlspecialchars($turmaSelecionada['nome'] ?? ''); ?>
              </div>
              <div class="relatorio-print-oferta">
                Oferta <?php echo htmlspecialchars($turmaSelecionada['codigo_oferta'] ?? ''); ?>
                <?php if (! empty($turmaSelecionada['curso_nome'])): ?>
                · <?php echo htmlspecialchars($turmaSelecionada['curso_nome']); ?>
                <?php endif; ?>
                · Inicial: <?php echo ! empty($datasTurma['data_inicial']) ? htmlspecialchars(date('d/m/Y', strtotime($datasTurma['data_inicial'])))  : '-'; ?>
                · Final: <?php echo $turmaConcluida && ! empty($datasTurma['data_final']) ? htmlspecialchars(date('d/m/Y', strtotime($datasTurma['data_final'])))  : '-'; ?>
              </div>
            </div>
            <?php if ($turmaConcluida && $uc12Pendente): ?>
            <div class="alert alert-warning py-2 mb-3 text-center fw-semibold">
              Carga principal atingida. Falta concluir a UC12.
            </div>
            <?php endif; ?>
            <div class="table-responsive">
              <table class="table table-bordered relatorio-turma-table mb-0">
                <thead>
                  <tr>
                    <th class="col-uc">Unidade Curricular</th>
                    <th class="col-docente">Docente</th>
                    <th class="text-center col-carga">Carga Horária</th>
                    <th class="text-center col-a-lancar">A Lançar</th>
                    <th class="text-center col-lancadas">Horas Lançadas</th>
                    <th class="text-center col-dadas">Horas Dadas</th>
                    <th class="text-center col-data">Data Inicial</th>
                    <th class="text-center col-data">Data Final</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($linhas)): ?>
                  <?php foreach ($linhas as $linha): ?>
                  <?php
                      $cargaHorariaMinutos = (int) round(((float) ($linha['carga_horaria'] ?? 0)) * 60);
                      $horasLancadasMinutos = (int) ($linha['minutos_lancados'] ?? 0);
                      $horasDadasMinutos = $horasLancadasMinutos;
                      $aLancarMinutos = $cargaHorariaMinutos - $horasLancadasMinutos;
                      $classeHoras = $horasLancadasMinutos === $cargaHorariaMinutos
                           ? 'horas-ok'
                          : ($horasLancadasMinutos > $cargaHorariaMinutos ? 'horas-acima' : 'horas-pendente');
                  ?>
                  <tr>
                    <td class="col-uc"><?php echo htmlspecialchars(($linha['codigo'] ?? '') . '-' . ($linha['nome'] ?? '')); ?></td>
                    <td class="col-docente"><?php echo $formatarDocentesTurma($linha['docentes'] ?? []); ?></td>
                    <td class="col-numero col-carga"><?php echo htmlspecialchars($formatarMinutosTurma($cargaHorariaMinutos)); ?></td>
                    <td class="col-numero col-a-lancar"><?php echo htmlspecialchars($formatarMinutosTurma($aLancarMinutos)); ?></td>
                    <td class="col-numero col-lancadas <?php echo $classeHoras; ?>"><?php echo htmlspecialchars($formatarMinutosTurma($horasLancadasMinutos)); ?></td>
                    <td class="col-numero col-dadas <?php echo $horasDadasMinutos > $cargaHorariaMinutos ? 'horas-dadas-acima' : ''; ?>">
                      <?php echo htmlspecialchars($formatarMinutosTurma($horasDadasMinutos)); ?>
                    </td>
                    <td class="col-numero col-data">
                      <?php echo ! empty($linha['data_inicial']) ? htmlspecialchars(date('d/m/y', strtotime($linha['data_inicial'])))  : '-'; ?>
                    </td>
                    <td class="col-numero col-data">
                      <?php echo ! empty($linha['data_final']) ? htmlspecialchars(date('d/m/y', strtotime($linha['data_final'])))  : '-'; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <tr class="fw-bold">
                    <td class="col-uc">Total</td>
                    <td class="col-docente"></td>
                    <td class="col-numero col-carga"><?php echo htmlspecialchars($formatarMinutosTurma($totalCargaMinutos)); ?></td>
                    <td class="col-numero col-a-lancar"><?php echo htmlspecialchars($formatarMinutosTurma($totalCargaMinutos - $totalLancadasMinutos)); ?></td>
                    <td class="col-numero col-lancadas"><?php echo htmlspecialchars($formatarMinutosTurma($totalLancadasMinutos)); ?></td>
                    <td class="col-numero col-dadas"><?php echo htmlspecialchars($formatarMinutosTurma($totalDadasMinutos)); ?></td>
                    <td colspan="2"></td>
                  </tr>
                  <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                      Nenhuma unidade curricular encontrada para esta turma.
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <div class="relatorio-print-footer">
              <div><strong>Usuário:</strong> <span id="relatorioTurmaUsuarioImpressao"></span></div>
              <div><strong>Impresso em:</strong> <span id="relatorioTurmaHorarioImpressao"></span></div>
            </div>
          </div>
          <?php else: ?>
          <div class="app-card p-4 text-center text-muted">
            Selecione uma turma para visualizar o relatório.
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
  if (pageTitle) pageTitle.textContent = "Relatório da Turma";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "./?page=logout";
    }
  });

  const btnImprimirRelatorioTurma = document.getElementById("btnImprimirRelatorioTurma");
  const relatorioTurmaUsuarioImpressao = document.getElementById("relatorioTurmaUsuarioImpressao");
  const relatorioTurmaHorarioImpressao = document.getElementById("relatorioTurmaHorarioImpressao");
  const usuarioRelatorioTurma = <?php echo json_encode($usuarioLogado, JSON_UNESCAPED_UNICODE); ?>;

  function atualizarRodapeRelatorioTurma() {
    const agora = new Date();
    const horario = agora.toLocaleDateString("pt-BR") + ", " + agora.toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit"
    });

    if (relatorioTurmaUsuarioImpressao) relatorioTurmaUsuarioImpressao.textContent = usuarioRelatorioTurma;
    if (relatorioTurmaHorarioImpressao) relatorioTurmaHorarioImpressao.textContent = horario;
  }

  window.addEventListener("beforeprint", atualizarRodapeRelatorioTurma);

  if (btnImprimirRelatorioTurma) {
    btnImprimirRelatorioTurma.addEventListener("click", function() {
      atualizarRodapeRelatorioTurma();
      const tituloOriginal = document.title;
      const turmaRelatorio = <?php echo json_encode($turmaSelecionada['nome'] ?? 'Turma', JSON_UNESCAPED_UNICODE); ?>;
      const mesRelatorio = <?php echo json_encode($nomesMesesRelatorioTurma[$mesRelatorioTurma] ?? date('m'), JSON_UNESCAPED_UNICODE); ?>;
      const anoRelatorio = <?php echo json_encode($anoRelatorioTurma, JSON_UNESCAPED_UNICODE); ?>;
      const tituloImpressao = `Relatório da Turma - ${turmaRelatorio} - ${mesRelatorio} - ${anoRelatorio}`
        .replace(/[\\/:*?"<>|]+/g, "-")
        .replace(/\s+/g, " ")
        .trim();

      const restaurarTitulo = function() {
        document.title = tituloOriginal;
        window.removeEventListener("afterprint", restaurarTitulo);
      };

      document.title = tituloImpressao;
      window.addEventListener("afterprint", restaurarTitulo);
      window.print();
    });
  }
  </script>
</body>

</html>






