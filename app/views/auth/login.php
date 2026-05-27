<?php
    $mensagem = $_GET['msg'] ?? '';
    $tipo     = $_GET['tipo'] ?? '';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - SIGHA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
</head>

<body>

  <?php require_once __DIR__ . '/../layouts/header.php'; ?>

  <main class="flex-grow-1 d-flex align-items-center justify-content-center px-3 py-4">
    <div class="col-12 col-sm-10 col-md-7 col-lg-4">
      <div class="app-card p-4">

        <div class="text-center mb-4">
          <div class="app-icon-badge mx-auto mb-2">
            <i class="bi bi-calendar2-week"></i>
          </div>
          <h4 class="mb-1">SIGHA</h4>
          <div class="small text-muted">
            Sistema Integrado de Gestão de Horários e Ambientes
          </div>
        </div>

        <?php require_once __DIR__ . '/../components/alert.php'; ?>

        <form id="loginForm" action="" method="POST" novalidate>
          <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-envelope-fill"></i>
              </span>
              <input type="email" class="form-control" id="email" name="email" placeholder="seuemail@exemplo.com"
                required />
              <div class="invalid-feedback">Informe um e-mail válido.</div>
            </div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Senha</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-lock-fill"></i>
              </span>
              <input type="password" class="form-control" id="password" name="senha" placeholder="Digite sua senha"
                minlength="4" required />
              <button class="btn btn-outline-secondary" type="button" id="btnTogglePass"
                aria-label="Mostrar ou ocultar senha">
                <i class="bi bi-eye"></i>
              </button>
              <div class="invalid-feedback">
                Informe sua senha com no mínimo 4 caracteres.
              </div>
            </div>
          </div>

          <div class="text-end mb-3">
            <a href="/mapa_de_sala/public/?page=esqueci_senha" class="small text-decoration-none">Esqueci minha senha</a>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn app-btn-primary">
              <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
          </div>

          <div class="text-center">
            <span class="small text-muted">Não possui conta</span>
            <a href="/mapa_de_sala/public/?page=cadastro" class="small fw-semibold text-decoration-none">
              <i class="bi bi-person-plus"></i> Cadastre-se
            </a>
          </div>
        </form>

      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/mostrar_senha.js"></script>
</body>

</html>

