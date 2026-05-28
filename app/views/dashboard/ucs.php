<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $mensagem = $_GET['msg'] ?? '';
    $tipoMsg = $_GET['tipo'] ?? '';
    $busca = $busca ?? ($_GET['busca'] ?? '');
    $status = $status ?? ($_GET['status'] ?? 'todos');
    $cursoModeloId = $cursoModeloId ?? (int) ($_GET['curso_modelo_id'] ?? 0);
    $ucs = $ucs ?? [];
    $totalUcs = $totalUcs ?? count($ucs);

    $tituloPagina = 'Manutenção de UCs';
    $subtituloPagina = 'Gerencie unidades curriculares por modelo de curso';
    $botaoTopoTexto = 'Nova UC';
    $botaoTopoLink = './?page=ucs&action=cadastrar';
    $botaoTopoClasse = 'app-btn-primary';
    $botaoTopoIcone = 'bi-plus-circle';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>UCs - SIGHA</title>

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
            $paginaAtiva = 'ucs';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <?php if (! empty($mensagem)): ?>
          <div class="alert <?php echo $tipoMsg === 'sucesso' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <i class="bi <?php echo $tipoMsg === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
          <?php endif; ?>

          <div class="app-card p-3 mb-3">
            <?php require_once __DIR__ . '/ucs/_filtros.php'; ?>
          </div>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Lista de UCs</div>
              <div class="small text-muted"><?php echo (int) $totalUcs; ?> UC(s)</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Codigo</th>
                    <th>UC</th>
                    <th>Modelo do curso</th>
                    <th>Carga horária</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>

                <tbody>
                  <?php if (! empty($ucs)): ?>
                  <?php foreach ($ucs as $uc): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($uc['codigo'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($uc['nome'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($uc['curso_modelo_nome'] ?? ''); ?></td>
                    <td><?php echo (int) ($uc['carga_horaria'] ?? 0); ?>h</td>
                    <td>
                      <?php
                          $statusUc = $uc['status'] ?? 'Ativa';
                          require __DIR__ . '/ucs/status_badge.php';
                      ?>
                    </td>
                    <td class="text-end">
                      <?php
                          $ucId = (int) ($uc['id'] ?? 0);
                          require __DIR__ . '/ucs/_acoes.php';
                      ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      Nenhuma UC encontrada.
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
  if (pageTitle) pageTitle.textContent = "UCs";

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


