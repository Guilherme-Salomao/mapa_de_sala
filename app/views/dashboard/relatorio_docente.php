<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $docentes = $docentes ?? [];
    $docenteId = (int) ($docenteId ?? 0);
    $relatorioProprioDocente = $relatorioProprioDocente ?? false;
    $mes = (int) ($mes ?? date('n'));
    $ano = (int) ($ano ?? date('Y'));
    $eventosPorData = $eventosPorData ?? [];
    $periodosEscala = $periodosEscala ?? [];
    $resumoCarga = $resumoCarga ?? [
        'horas_aula' => 0,
        'horas_planejamento' => 0,
        'horas_curso' => 0,
        'total_horas' => 0,
        'percentual_aula' => 0,
        'percentual_planejamento' => 0,
        'percentual_curso' => 0,
    ];

    $tituloPagina = 'Relatorio Docente';
    $subtituloPagina = 'Calendario mensal com aulas e planejamento';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';

    $primeiroDia = sprintf('%04d-%02d-01', $ano, $mes);
    $diasNoMes = (int) date('t', strtotime($primeiroDia));
    $inicioSemana = (int) date('w', strtotime($primeiroDia));
    $nomesSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];

    function abreviarUcRelatorioDocente(string $texto, int $limite = 6): string
    {
        $texto = trim($texto);

        if ($texto === '') {
            return '';
        }

        $partes = explode(' - ', $texto, 2);
        $codigo = $partes[0] ?? '';
        $nome = $partes[1] ?? $texto;
        $palavrasIgnoradas = ['a', 'as', 'o', 'os', 'e', 'de', 'da', 'das', 'do', 'dos', 'em', 'no', 'na', 'nos', 'nas', 'para', 'por'];
        $palavras = preg_split('/\s+/', trim($nome)) ?: [];
        $abreviadas = [];

        foreach ($palavras as $palavra) {
            $limpa = trim($palavra, " \t\n\r\0\x0B.,;:()[]{}");

            if ($limpa === '') {
                continue;
            }

            if (in_array(strtolower($limpa), $palavrasIgnoradas, true)) {
                continue;
            }

            $abreviadas[] = mb_strlen($limpa, 'UTF-8') > $limite
                ? mb_substr($limpa, 0, $limite, 'UTF-8')
                : $limpa;
        }

        $nomeAbreviado = implode(' ', array_slice($abreviadas, 0, 5));

        return trim($codigo . ($nomeAbreviado !== '' ? ' - ' . $nomeAbreviado : ''));
    }
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Relatorio Docente - SIGHA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />

  <style>
  .periodo-badge {
    align-items: center;
    border-radius: 999px;
    display: inline-flex;
    font-size: .95rem;
    font-weight: 700;
    justify-content: center;
    min-width: 110px;
    padding: .45rem .8rem;
  }

  .periodo-manha {
    background: #fff3cd;
    border-color: #ffc107 !important;
    color: #7a5200;
  }

  .periodo-tarde {
    background: #ffe5d0;
    border-color: #fd7e14 !important;
    color: #8a3d00;
  }

  .periodo-noite {
    background: #dbeafe;
    border-color: #2563eb !important;
    color: #1e3a8a;
  }

  .periodo-curso {
    background: #dcfce7;
    border-color: #16a34a !important;
    color: #14532d;
   }

  .relatorio-calendario thead th {
    background: #0d6efd;
    color: #fff;
    font-weight: 700;
    padding-bottom: .75rem;
    padding-top: .75rem;
  }

  .relatorio-calendario thead th:first-child {
    background: #f97316;
  }

  .relatorio-uc {
    color: #0f172a;
    font-size: .82rem;
    font-weight: 700;
    line-height: 1.25;
    text-align: center;
  }

  .relatorio-turma {
    font-weight: 600;
    line-height: 1.25;
    text-align: center;
  }

  .relatorio-sala {
    text-align: center;
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
            $paginaAtiva = 'relatorio_docente';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="/mapa_de_sala/public/" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="relatorio_docente">

              <div class="col-12 col-lg-5">
                <label for="docente_id" class="form-label">Docente</label>
                <?php if ($relatorioProprioDocente): ?>
                <input type="hidden" name="docente_id" value="<?php echo $docenteId; ?>">
                <input type="text" class="form-control" id="docente_id"
                  value="<?php echo htmlspecialchars($docenteSelecionado['nome'] ?? ''); ?>" disabled>
                <?php else: ?>
                <select class="form-select" id="docente_id" name="docente_id" required>
                  <?php if (empty($docentes)): ?>
                  <option value="">Nenhum docente ativo</option>
                  <?php endif; ?>
                  <?php foreach ($docentes as $docente): ?>
                  <option value="<?php echo (int) $docente['id']; ?>"
                    <?php echo $docenteId === (int) $docente['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($docente['nome'] ?? ''); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <?php endif; ?>
              </div>

              <div class="col-6 col-lg-2">
                <label for="mes" class="form-label">Mes</label>
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

              <div class="col-12 col-lg-3">
                <button type="submit" class="btn app-btn-primary w-100">
                  <i class="bi bi-funnel"></i> Filtrar
                </button>
              </div>
            </form>
          </div>

          <?php if (! empty($docenteSelecionado)): ?>
          <div class="app-card p-3 mb-3">
            <div class="d-flex flex-nowrap align-items-center justify-content-between gap-3 overflow-auto">
              <div class="flex-shrink-0">
                <div class="fw-bold"><?php echo htmlspecialchars($docenteSelecionado['nome'] ?? ''); ?></div>
                <div class="small text-muted">
                  <?php echo htmlspecialchars(sprintf('%02d/%04d', $mes, $ano)); ?>
                </div>
              </div>

              <div class="d-flex flex-nowrap gap-2 flex-shrink-0">
                <?php foreach ($periodosEscala as $periodoKey => $periodoLabel): ?>
                <span class="periodo-badge periodo-<?php echo htmlspecialchars($periodoKey); ?> border">
                  <?php echo htmlspecialchars($periodoLabel); ?>
                </span>
                <?php endforeach; ?>
              </div>

              <div class="d-flex flex-nowrap gap-2 flex-shrink-0 ms-auto">
                <span class="badge text-bg-primary d-inline-flex align-items-center justify-content-center fs-6 py-2"
                  style="min-width: 190px;">
                  Aula: <?php echo number_format((float) $resumoCarga['percentual_aula'], 1, ',', '.'); ?>%
                </span>
                <span class="badge text-bg-warning d-inline-flex align-items-center justify-content-center fs-6 py-2"
                  style="min-width: 190px;">
                  Planejamento: <?php echo number_format((float) $resumoCarga['percentual_planejamento'], 1, ',', '.'); ?>%
                </span>
                <span class="badge text-bg-success d-inline-flex align-items-center justify-content-center fs-6 py-2"
                  style="min-width: 190px;">
                  Curso: <?php echo number_format((float) ($resumoCarga['percentual_curso'] ?? 0), 1, ',', '.'); ?>%
                </span>
              </div>
            </div>
          </div>

          <div class="app-card p-3">
            <div class="table-responsive">
              <table class="table table-bordered align-top mb-0 relatorio-calendario">
                <thead class="small text-muted">
                  <tr>
                    <?php foreach ($nomesSemana as $nomeSemana): ?>
                    <th class="text-center" style="width: 14.285%;"><?php echo $nomeSemana; ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                      $diaAtual = 1;
                      $celulas = 0;

                      while ($diaAtual <= $diasNoMes):
                  ?>
                  <tr>
                    <?php for ($coluna = 0; $coluna < 7; $coluna++): ?>
                    <?php
                        $celulas++;
                        $mostrarDia = ! ($celulas <= $inicioSemana || $diaAtual > $diasNoMes);
                        $dataIso = $mostrarDia ? sprintf('%04d-%02d-%02d', $ano, $mes, $diaAtual) : '';
                    ?>
                    <td style="min-width: 170px; height: 150px;">
                      <?php if ($mostrarDia): ?>
                      <div class="fw-semibold mb-2"><?php echo $diaAtual; ?></div>

                      <?php if (! empty($eventosPorData[$dataIso])): ?>
                      <?php foreach ($eventosPorData[$dataIso] as $evento): ?>
                      <?php $isAula = ($evento['tipo'] ?? '') === 'aula'; ?>
                      <?php $periodoClasse = 'periodo-' . strtolower((string) ($evento['periodo_key'] ?? '')); ?>
                      <div class="border rounded p-2 mb-2 small <?php echo htmlspecialchars($periodoClasse); ?>">
                        <div class="fw-bold text-center mb-1">
                          <?php echo htmlspecialchars($evento['periodo'] ?? ''); ?>
                        </div>
                        <?php if (! empty($evento['hora'])): ?>
                        <div class="fw-semibold text-center mb-1">
                          <?php echo htmlspecialchars($evento['hora']); ?>
                        </div>
                        <?php endif; ?>
                        <div class="relatorio-turma"><?php echo htmlspecialchars($evento['turma'] ?? ''); ?></div>
                        <?php if ($isAula && ! empty($evento['uc'])): ?>
                        <div class="relatorio-uc" title="<?php echo htmlspecialchars($evento['uc']); ?>">
                          <?php echo htmlspecialchars(abreviarUcRelatorioDocente((string) $evento['uc'])); ?>
                        </div>
                        <?php endif; ?>
                        <?php if (! empty($evento['sala'])): ?>
                        <div class="text-muted relatorio-sala">Sala <?php echo htmlspecialchars($evento['sala']); ?></div>
                        <?php endif; ?>
                      </div>
                      <?php endforeach; ?>
                      <?php else: ?>
                      <div class="small text-muted">Sem escala</div>
                      <?php endif; ?>

                      <?php $diaAtual++; ?>
                      <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php else: ?>
          <div class="app-card p-4 text-center text-muted">
            Selecione um docente para visualizar o relatorio.
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
  if (pageTitle) pageTitle.textContent = "Relatorio Docente";

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

