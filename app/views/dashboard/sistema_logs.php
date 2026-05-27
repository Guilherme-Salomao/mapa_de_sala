<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $logs = $logs ?? [];
    $paginas = $paginas ?? [];
    $busca = $busca ?? ($_GET['busca'] ?? '');
    $pagina = $pagina ?? ($_GET['pagina_log'] ?? 'todos');
    $dataInicio = $dataInicio ?? ($_GET['data_inicio'] ?? '');
    $dataFim = $dataFim ?? ($_GET['data_fim'] ?? '');

    $tituloPagina = 'Logs do Sistema';
    $subtituloPagina = 'Acompanhe as acoes realizadas pelos usuarios';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Logs do Sistema - SIGHA</title>

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
            $paginaAtiva = 'logs';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="/mapa_de_sala/public/" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="logs">

              <div class="col-12 col-lg-4">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                  <span class="input-group-text app-input-icon">
                    <i class="bi bi-search"></i>
                  </span>
                  <input type="text" name="busca" class="form-control" placeholder="Usuario, e-mail ou acao..."
                    value="<?php echo htmlspecialchars($busca); ?>">
                </div>
              </div>

              <div class="col-12 col-md-2">
                <label class="form-label">Tela</label>
                <select name="pagina_log" class="form-select">
                  <option value="todos" <?php echo $pagina === 'todos' ? 'selected' : ''; ?>>Todas</option>
                  <?php foreach ($paginas as $paginaOpcao): ?>
                  <option value="<?php echo htmlspecialchars($paginaOpcao); ?>"
                    <?php echo $pagina === $paginaOpcao ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(str_replace('_', ' ', $paginaOpcao)); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label">Data inicial</label>
                <input type="date" name="data_inicio" class="form-control"
                  value="<?php echo htmlspecialchars($dataInicio); ?>">
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label">Data final</label>
                <input type="date" name="data_fim" class="form-control"
                  value="<?php echo htmlspecialchars($dataFim); ?>">
              </div>

              <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn app-btn-primary w-100">
                  <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="/mapa_de_sala/public/?page=logs" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </div>
            </form>
          </div>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Ultimas acoes</div>
              <div class="small text-muted"><?php echo (int) ($totalLogs ?? count($logs)); ?> registro(s)</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Data/Hora</th>
                    <th>Usuario</th>
                    <th>Tela</th>
                    <th>Acao</th>
                    <th>IP</th>
                    <th>Dados</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($logs)): ?>
                  <?php foreach ($logs as $log): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['criado_em']))); ?></td>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sem usuario'); ?></div>
                      <div class="small text-muted"><?php echo htmlspecialchars($log['usuario_email'] ?? ''); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars(str_replace('_', ' ', $log['pagina'] ?? '')); ?></td>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($log['descricao'] ?? ''); ?></div>
                      <div class="small text-muted"><?php echo htmlspecialchars($log['acao'] ?? ''); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($log['ip'] ?? ''); ?></td>
                    <td style="min-width: 260px;">
                      <details>
                        <summary class="small">Ver detalhes</summary>
                        <pre class="small bg-body-tertiary border rounded p-2 mt-2 mb-0 text-wrap"><?php echo htmlspecialchars($log['dados'] ?? ''); ?></pre>
                      </details>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      Nenhum log encontrado.
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
  if (pageTitle) pageTitle.textContent = "Logs do Sistema";

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

