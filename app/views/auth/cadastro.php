<?php
    $mensagem = $_GET['msg'] ?? ($mensagem ?? '');
    $tipo = $_GET['tipo'] ?? ($tipo ?? '');
    $nome = $_GET['nome'] ?? '';
    $email = $_GET['email'] ?? '';
    $nivelAcesso = $_GET['nivel_acesso'] ?? '';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastro - SIGHA</title>

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
            <i class="bi bi-person-plus"></i>
          </div>
          <h4 class="mb-1">Criar conta</h4>
          <div class="small text-muted">
            Informe seus dados para acessar o sistema
          </div>
        </div>

        <?php require_once __DIR__ . '/../components/alert.php'; ?>

        <form action="/mapa_de_sala/public/?page=cadastro" method="POST" novalidate>
          <div class="mb-3">
            <label for="nome" class="form-label">Nome</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-person-fill"></i>
              </span>
              <input type="text" class="form-control" id="nome" name="nome" placeholder="Seu nome"
                value="<?php echo htmlspecialchars($nome); ?>" required />
            </div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-envelope-fill"></i>
              </span>
              <input type="email" class="form-control" id="email" name="email" placeholder="seuemail@exemplo.com"
                value="<?php echo htmlspecialchars($email); ?>" required />
            </div>
          </div>

          <div class="mb-3">
            <label for="nivel_acesso" class="form-label">Nivel de acesso</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-person-gear"></i>
              </span>
              <select class="form-select" id="nivel_acesso" name="nivel_acesso" required>
                <option value="" <?php echo $nivelAcesso === '' ? 'selected' : ''; ?> disabled>Selecione...</option>
                <option value="Gestor" <?php echo $nivelAcesso === 'Gestor' ? 'selected' : ''; ?>>Gestor(a)</option>
                <option value="Professor" <?php echo $nivelAcesso === 'Professor' ? 'selected' : ''; ?>>Professor(a)</option>
                <option value="Apoio" <?php echo $nivelAcesso === 'Apoio' ? 'selected' : ''; ?>>Apoio</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-lock-fill"></i>
              </span>
              <input type="password" class="form-control" id="senha" name="senha" placeholder="Minimo 4 caracteres"
                minlength="4" required />
            </div>
          </div>

          <div class="mb-3">
            <label for="confirmar_senha" class="form-label">Confirmar senha</label>
            <div class="input-group">
              <span class="input-group-text app-input-icon">
                <i class="bi bi-lock-fill"></i>
              </span>
              <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha"
                placeholder="Digite novamente" minlength="4" required />
            </div>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn app-btn-primary">
              <i class="bi bi-check-circle"></i> Criar conta
            </button>
          </div>

          <div class="text-center">
            <span class="small text-muted">Ja possui conta</span>
            <a href="/mapa_de_sala/public/" class="small fw-semibold text-decoration-none">
              <i class="bi bi-box-arrow-in-right"></i> Entrar
            </a>
          </div>
        </form>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

