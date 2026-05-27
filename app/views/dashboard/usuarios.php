<?php
    if (session_status() === PHP_SESSION_NONE) {
    session_start();
    }

    if (! isset($_SESSION['usuario'])) {
    header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
    exit;
    }

    $usuarios      = $usuarios ?? [];
    $totalUsuarios = $totalUsuarios ?? 0;
    $busca         = $busca ?? '';
    $nivel         = $nivel ?? 'todos';
    $mensagem      = $_GET['msg'] ?? '';
    $tipo          = $_GET['tipo'] ?? '';

    $tituloPagina    = 'Manutenção de Usuários';
    $subtituloPagina = 'Gerencie cadastro e níveis de acesso';
    $botaoTopoTexto  = 'Novo Usuário';
    $botaoTopoLink   = '/mapa_de_sala/public/?page=usuarios&action=cadastrar';
    $botaoTopoClasse = 'app-btn-primary';
    $botaoTopoIcone  = 'bi-person-plus';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Usuários - SIGHA</title>

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
            $paginaAtiva = 'usuarios';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">

          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <div class="app-card p-3 mb-3">
            <?php require __DIR__ . '/usuarios/_filtros.php'; ?>
          </div>

          <?php require_once __DIR__ . '/../components/alert.php'; ?>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Lista de Usuários</div>
              <div class="small text-muted" id="totalUsuarios"><?php echo $totalUsuarios; ?> usuários</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Nível</th>
                    <th>Status</th>
                    <th>Último login</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>
                <tbody id="tbodyUsuarios">
                  <?php if (! empty($usuarios)): ?>
                  <?php foreach ($usuarios as $usuario): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($usuario['nome']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td>
                      <?php
                          $nivelAcesso = $usuario['nivel_acesso'];
                          require __DIR__ . '/usuarios/nivel_badge.php';
                      ?>
                    </td>
                    <td>
                      <?php
                          $status = $usuario['status'];
                          require __DIR__ . '/usuarios/status_badge.php';
                      ?>
                    </td>
                    <td>
                      <?php echo ! empty($usuario['ultimo_login']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca acessou'; ?>
                    </td>
                    <td class="text-end">
                      <?php
                          $usuarioId = $usuario['id'];
                          require __DIR__ . '/usuarios/_acoes.php';
                      ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      Nenhum usuário encontrado.
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
  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "/mapa_de_sala/public/?page=logout";
    }
  });
  </script>
</body>

</html>

