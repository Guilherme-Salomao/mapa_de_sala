  <?php
      if (session_status() === PHP_SESSION_NONE) {
          session_start();
      }

      if (! isset($_SESSION['usuario'])) {
          header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
          exit;
      }

      $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuário';
      $usuarios      = $usuarios ?? [];
      $totalUsuarios = $totalUsuarios ?? 0;
      $busca         = $busca ?? '';
      $nivel         = $nivel ?? 'todos';
      $mensagem      = $_GET['msg'] ?? '';
      $tipo          = $_GET['tipo'] ?? '';
  ?>
  <!doctype html>
  <html lang="pt-br">

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Usuários - Sistema de Controle de Salas</title>

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
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
              <div>
                <h4 class="mb-0">Usuários</h4>
                <div class="small text-muted">
                  Gerencie cadastro e níveis de acesso
                </div>
              </div>

              <div class="d-flex gap-2">
                <a href="/mapa_de_sala/public/?page=usuarios&action=cadastrar" class="btn btn-sm app-btn-primary">
                  <i class="bi bi-person-plus"></i> Novo Usuário
                </a>
              </div>
            </div>

            <div class="app-card p-3 mb-3">
              <form method="GET" action="/mapa_de_sala/public/" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="usuarios">

                <div class="col-12 col-md-6">
                  <div class="input-group">
                    <span class="input-group-text app-input-icon">
                      <i class="bi bi-search"></i>
                    </span>
                    <input id="buscaUsuario" name="busca" type="text" class="form-control"
                      placeholder="Buscar por nome ou e-mail..." value="<?php echo htmlspecialchars($busca) ?>" />
                  </div>
                </div>

                <div class="col-6 col-md-3">
                  <select id="filtroNivel" name="nivel" class="form-select">
                    <option value="todos" <?php echo $nivel === 'todos' ? 'selected' : '' ?>>Todos os níveis</option>
                    <option value="Admin" <?php echo $nivel === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Gestor" <?php echo $nivel === 'Gestor' ? 'selected' : '' ?>>Gestor</option>
                    <option value="Professor" <?php echo $nivel === 'Professor' ? 'selected' : '' ?>>Professor</option>
                    <option value="Apoio" <?php echo $nivel === 'Apoio' ? 'selected' : '' ?>>Apoio</option>
                  </select>
                </div>

                <div class="col-6 col-md-3 d-flex gap-2">
                  <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                  </button>

                  <a href="/mapa_de_sala/public/?page=usuarios" id="btnLimpar" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-counterclockwise"></i> Limpar
                  </a>
                </div>
              </form>
            </div>

            <?php if (! empty($mensagem)): ?>
            <div class="alert <?php echo $tipo === 'sucesso' ? 'alert-success' : 'alert-danger' ?>" role="alert">
              <?php echo htmlspecialchars($mensagem) ?>
            </div>
            <?php endif; ?>


            <div class="app-card p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-bold">Lista de Usuários</div>
                <div class="small text-muted" id="totalUsuarios"><?php echo $totalUsuarios ?> usuários</div>
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
                      <td class="fw-semibold"><?php echo htmlspecialchars($usuario['nome']) ?></td>
                      <td><?php echo htmlspecialchars($usuario['email']) ?></td>
                      <td>
                        <span class="badge text-bg-primary">
                          <?php echo htmlspecialchars($usuario['nivel_acesso']) ?>
                        </span>
                      </td>
                      <td>
                        <span
                          class="badge <?php echo $usuario['status'] === 'Ativo' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                          <?php echo htmlspecialchars($usuario['status']) ?>
                        </span>
                      </td>
                      <td>
                        <?php echo ! empty($usuario['ultimo_login']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca acessou' ?>
                      </td>
                      <td class="text-end">
                        <a href="/mapa_de_sala/public/?page=usuarios&action=editar&id=<?php echo $usuario['id'] ?>"
                          class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-pencil"></i> Editar
                        </a>


                        <form method="POST" action="/mapa_de_sala/public/?page=usuarios&action=excluir" class="d-inline"
                          onsubmit="return confirm('Deseja realmente excluir este usuário?');">
                          <input type="hidden" name="id" value="<?php echo $usuario['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
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
    const pageTitle = document.getElementById("pageTitle");
    if (pageTitle) pageTitle.textContent = "Usuários";

    const userName = document.getElementById("userName");
    if (userName) userName.textContent = <?php echo json_encode($usuarioLogado) ?>;

    document.addEventListener("click", function(e) {
      if (e.target.closest("#btnLogout")) {
        window.location.href = "/mapa_de_sala/public/?page=logout";
      }
    });
    </script>
  </body>

  </html>