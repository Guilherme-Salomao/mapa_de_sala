<?php
    if (session_status() === PHP_SESSION_NONE) {
    session_start();
    }

    if (! isset($_SESSION['usuario'])) {
    header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
    exit;
    }

    if (! isset($usuario) || empty($usuario)) {
    header('Location: /mapa_de_sala/public/?page=usuarios&tipo=erro&msg=' . urlencode('Usuário não encontrado.'));
    exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuário';
    $mensagem      = $_GET['msg'] ?? '';
    $tipo          = $_GET['tipo'] ?? '';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Editar Usuário - Sistema de Controle de Salas</title>

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
              <h4 class="mb-0">Editar Usuário</h4>
              <div class="small text-muted">
                Atualize os dados do usuário selecionado
              </div>
            </div>

            <div class="d-flex gap-2">
              <a class="btn btn-outline-secondary btn-sm" href="/mapa_de_sala/public/?page=usuarios">
                <i class="bi bi-arrow-left"></i> Voltar
              </a>
            </div>
          </div>

          <div class="app-card p-4">
            <?php if (! empty($mensagem)): ?>
            <div class="alert <?php echo $tipo === 'sucesso' ? 'alert-success' : 'alert-danger' ?>" role="alert">
              <i class="bi <?php echo $tipo === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i>
              <?php echo htmlspecialchars($mensagem) ?>
            </div>
            <?php endif; ?>

            <form id="formUsuario" method="POST" action="/mapa_de_sala/public/?page=usuarios&action=atualizar"
              novalidate>
              <input type="hidden" name="id" value="<?php echo (int) $usuario['id'] ?>">

              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label for="nome" class="form-label">Nome</label>
                  <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome completo"
                    minlength="3" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required />
                  <div class="invalid-feedback">
                    Informe o nome com no mínimo 3 caracteres.
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <label for="email" class="form-label">E-mail</label>
                  <div class="input-group">
                    <span class="input-group-text app-input-icon">
                      <i class="bi bi-envelope-fill"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="email@exemplo.com"
                      value="<?php echo htmlspecialchars($usuario['email']); ?>" required />
                    <div class="invalid-feedback">
                      Informe um e-mail válido.
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <label for="nivel_acesso" class="form-label">Nível de acesso</label>
                  <select class="form-select" id="nivel_acesso" name="nivel_acesso" required>
                    <option value="Admin" <?php echo $usuario['nivel_acesso'] === 'Admin' ? 'selected' : ''; ?>>Admin
                    </option>
                    <option value="Gestor" <?php echo $usuario['nivel_acesso'] === 'Gestor' ? 'selected' : ''; ?>>Gestor
                    </option>
                    <option value="Professor" <?php echo $usuario['nivel_acesso'] === 'Professor' ? 'selected' : ''; ?>>
                      Professor</option>
                    <option value="Apoio" <?php echo $usuario['nivel_acesso'] === 'Apoio' ? 'selected' : ''; ?>>Apoio
                    </option>
                  </select>
                  <div class="invalid-feedback">
                    Selecione o nível de acesso.
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <label for="status" class="form-label">Status</label>
                  <select class="form-select" id="status" name="status" required>
                    <option value="Ativo" <?php echo $usuario['status'] === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="Inativo" <?php echo $usuario['status'] === 'Inativo' ? 'selected' : ''; ?>>Inativo
                    </option>
                  </select>
                  <div class="invalid-feedback">
                    Selecione o status.
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <label for="senha" class="form-label">Nova senha</label>
                  <div class="input-group">
                    <span class="input-group-text app-input-icon">
                      <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" class="form-control" id="senha" name="senha"
                      placeholder="Crie uma nova senha" minlength="4" />
                    <button class="btn btn-outline-secondary" type="button" id="btnToggleSenha"
                      aria-label="Mostrar/ocultar senha">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                  <div class="form-text">Preencha apenas se desejar alterar a senha.</div>
                </div>

                <div class="col-12 col-md-6">
                  <label for="confSenha" class="form-label">Confirmar nova senha</label>
                  <div class="input-group">
                    <span class="input-group-text app-input-icon">
                      <i class="bi bi-shield-lock-fill"></i>
                    </span>
                    <input type="password" class="form-control" id="confSenha" name="confSenha"
                      placeholder="Repita a nova senha" minlength="4" />
                  </div>
                  <div class="form-text">Repita a senha somente se desejar alterá-la.</div>
                </div>
              </div>

              <hr class="my-4" />

              <div class="d-flex flex-wrap gap-2 justify-content-end">
                <a href="/mapa_de_sala/public/?page=usuarios" class="btn btn-outline-secondary">
                  <i class="bi bi-x-circle"></i> Cancelar
                </a>

                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-save"></i> Atualizar Usuário
                </button>
              </div>
            </form>
          </div>
        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Editar Usuário";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado) ?>;

  const form = document.getElementById("formUsuario");
  if (form) {
    form.addEventListener("submit", function(e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.classList.add("was-validated");
      }
    });
  }

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "/mapa_de_sala/public/?page=logout";
    }
  });
  </script>
</body>

</html>