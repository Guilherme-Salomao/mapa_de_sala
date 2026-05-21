<?php
    $mensagem = $_GET['msg'] ?? ($mensagem ?? '');
    $tipo = $_GET['tipo'] ?? ($tipo ?? '');
    $etapa = $_GET['etapa'] ?? ($etapa ?? 'email');
    $emailRecuperacao = $emailRecuperacao ?? '';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Esqueci minha senha - Sistema de Controle de Salas</title>

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
            <i class="bi bi-key"></i>
          </div>
          <h4 class="mb-1">Esqueci minha senha</h4>
          <div class="small text-muted">
            <?php echo $etapa === 'redefinir' ? 'Crie uma nova senha para sua conta' : 'Informe o e-mail cadastrado'; ?>
          </div>
        </div>

        <?php require_once __DIR__ . '/../components/alert.php'; ?>

        <?php if ($etapa === 'redefinir'): ?>
        <form action="/mapa_de_sala/public/?page=esqueci_senha&action=redefinir" method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" class="form-control" value="<?php echo htmlspecialchars($emailRecuperacao); ?>" disabled>
          </div>

          <div class="mb-3">
            <label for="senha" class="form-label">Nova senha</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-lock-fill"></i>
              </span>
              <input type="password" class="form-control" id="senha" name="senha" minlength="4" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="confirmar_senha" class="form-label">Confirmar nova senha</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-shield-lock-fill"></i>
              </span>
              <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" minlength="4" required>
            </div>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn app-btn-primary">
              <i class="bi bi-check-circle"></i> Alterar senha
            </button>
          </div>
        </form>
        <?php else: ?>
        <form action="/mapa_de_sala/public/?page=esqueci_senha&action=solicitar" method="POST" novalidate>
          <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-envelope-fill"></i>
              </span>
              <input type="email" class="form-control" id="email" name="email" placeholder="seuemail@exemplo.com" required>
            </div>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn app-btn-primary">
              <i class="bi bi-search"></i> Continuar
            </button>
          </div>
        </form>
        <?php endif; ?>

        <div class="text-center">
          <a href="/mapa_de_sala/public/" class="small fw-semibold text-decoration-none">
            <i class="bi bi-arrow-left"></i> Voltar ao login
          </a>
        </div>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
