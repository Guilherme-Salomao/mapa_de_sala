<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuário';
    $salas = $salas ?? [];
    $totais = $totais ?? [];
    $data = $data ?? date('Y-m-d');
    $situacao = $situacao ?? 'todas';
    $dataFormatada = date('d/m/Y', strtotime($data));

    $tituloPagina = 'Relatório de Salas';
    $subtituloPagina = 'Consulte salas livres, ocupadas e em manutenção';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';
    $botaoTopoClasse = 'app-btn-primary';
    $botaoTopoIcone = 'bi-door-open';

    function labelSituacaoSala(string $situacao): string
    {
        return [
            'livre' => 'Livre',
            'ocupada' => 'Ocupada',
            'reservada' => 'Reservada',
            'manutenção' => 'Manutenção',
            'inativa' => 'Inativa',
        ][$situacao] ?? 'Todas';
    }

    function classeSituacaoSala(string $situacao): string
    {
        return [
            'livre' => 'text-bg-success',
            'ocupada' => 'text-bg-primary',
            'reservada' => 'text-bg-info',
            'manutenção' => 'text-bg-warning',
            'inativa' => 'text-bg-secondary',
        ][$situacao] ?? 'text-bg-light';
    }

    function detalhesTurnoSala(array $turno): array
    {
        $detalhes = [];

        foreach ($turno['aulas'] ?? [] as $aula) {
            $detalhes[] = trim(substr((string) ($aula['hora_inicio'] ?? ''), 0, 5) . ' - ' . substr((string) ($aula['hora_fim'] ?? ''), 0, 5) . ' - ' . ($aula['turma_nome'] ?? ''));
        }

        foreach ($turno['reservas'] ?? [] as $reserva) {
            $titulo = ($reserva['tipo'] ?? '') === 'Manutenção'
                 ? 'Manutenção'
                : (($reserva['motivo'] ?? '') ?: 'Reserva');
            $solicitante = ! empty($reserva['solicitante']) ? ' - ' . $reserva['solicitante'] : '';
            $detalhes[] = trim(substr((string) ($reserva['hora_inicio'] ?? ''), 0, 5) . ' - ' . substr((string) ($reserva['hora_fim'] ?? ''), 0, 5) . ' - ' . $titulo . $solicitante);
        }

        return $detalhes;
    }
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Relatório de Salas - SIGHA</title>

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
            $paginaAtiva = 'relatorio_salas';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="/mapa_de_sala/public/" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="relatorio_salas">
              <div class="col-12 col-md-4 col-lg-3">
                <label for="data" class="form-label">Data</label>
                <input type="date" class="form-control" id="data" name="data"
                  value="<?php echo htmlspecialchars($data); ?>">
              </div>

              <div class="col-12 col-md-4 col-lg-3">
                <label for="situacao" class="form-label">Situação</label>
                <select class="form-select" id="situacao" name="situacao">
                  <?php foreach (['todas' => 'Todas', 'livre' => 'Livres', 'ocupada' => 'Ocupadas', 'reservada' => 'Reservadas', 'manutenção' => 'Em manutenção', 'inativa' => 'Inativas'] as $valor => $label): ?>
                  <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo $situacao === $valor ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
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
                <a href="/mapa_de_sala/public/?page=relatorio_salas" class="btn btn-outline-secondary">
                  <i class="bi bi-calendar-event"></i> Hoje
                </a>
              </div>
            </form>
          </div>

          <div class="row g-3 mb-3">
            <?php
                $cardsRelatorio = [
                    'todas' => ['label' => 'Salas', 'icone' => 'bi-door-open', 'classe' => 'kpi-icon--default'],
                    'livre' => ['label' => 'Livres', 'icone' => 'bi-check-circle', 'classe' => 'kpi-icon--livre'],
                    'ocupada' => ['label' => 'Ocupadas', 'icone' => 'bi-calendar-check', 'classe' => 'kpi-icon--uso'],
                    'reservada' => ['label' => 'Reservadas', 'icone' => 'bi-bookmark-check', 'classe' => 'kpi-icon--uso'],
                    'manutenção' => ['label' => 'Manutenção', 'icone' => 'bi-tools', 'classe' => 'kpi-icon--manut'],
                ];
            ?>
            <?php foreach ($cardsRelatorio as $chave => $card): ?>
            <div class="col-12 col-md-6 col-xl">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted"><?php echo htmlspecialchars($card['label']); ?></div>
                    <div class="fs-3 fw-bold"><?php echo (int) ($totais[$chave] ?? 0); ?></div>
                  </div>
                  <div class="app-icon-badge app-icon-badge--sm kpi-icon <?php echo htmlspecialchars($card['classe']); ?>">
                    <i class="bi <?php echo htmlspecialchars($card['icone']); ?>"></i>
                  </div>
                </div>
                <div class="small text-muted mt-2"><?php echo htmlspecialchars($dataFormatada); ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="app-card p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
              <div class="fw-bold">Resultado</div>
              <div class="small text-muted"><?php echo count($salas); ?> sala(s)</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Sala</th>
                    <th>Tipo</th>
                    <th>Capacidade</th>
                    <th>Manhã</th>
                    <th>Tarde</th>
                    <th>Noite</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($salas)): ?>
                  <?php foreach ($salas as $sala): ?>
                  <?php $situacaoSala = (string) ($sala['situacao_calculada'] ?? ''); ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($sala['nome'] ?? ''); ?></div>
                      <?php if (! empty($sala['descricao'])): ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($sala['descricao']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($sala['tipo'] ?? ''); ?></td>
                    <td><?php echo (int) ($sala['capacidade'] ?? 0); ?></td>
                    <?php foreach (['Manha', 'Tarde', 'Noite'] as $turnoChave): ?>
                    <?php
                        $turnoSala = $sala['turnos'][$turnoChave] ?? ['situacao' => 'inativa', 'aulas' => [], 'reservas' => []];
                        $detalhesTurno = detalhesTurnoSala($turnoSala);
                    ?>
                    <td class="small">
                      <span class="badge <?php echo htmlspecialchars(classeSituacaoSala((string) $turnoSala['situacao'])); ?>">
                        <?php echo htmlspecialchars(labelSituacaoSala((string) $turnoSala['situacao'])); ?>
                      </span>
                      <?php if (! empty($detalhesTurno)): ?>
                      <div class="d-grid gap-1 mt-2">
                        <?php foreach ($detalhesTurno as $detalhe): ?>
                        <div><?php echo htmlspecialchars($detalhe); ?></div>
                        <?php endforeach; ?>
                      </div>
                      <?php else: ?>
                      <div class="text-muted mt-2">Sem lançamento</div>
                      <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      Nenhuma sala encontrada para o filtro selecionado.
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
  if (pageTitle) pageTitle.textContent = "Relatório de Salas";

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

