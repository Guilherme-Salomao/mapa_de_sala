<?php
    if (session_status() === PHP_SESSION_NONE) {
    session_start();
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? null;
?>

<header class="app-header">
  <div class="container-fluid d-flex align-items-center justify-content-between py-2">
    <!-- Lado esquerdo -->
    <div class="d-flex align-items-center gap-3">
      <!-- Botão menu mobile (para dashboard com sidebar futura) -->
      <button class="btn btn-outline-light btn-sm d-md-none" type="button" id="btnToggleSidebar">
        <i class="bi bi-list"></i>
      </button>
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-calendar2-week fs-4"></i>
        <div class="lh-sm">
          <div class="fw-bold">SIGHA</div>
          <div class="small opacity-75" id="pageTitle">Sistema Integrado de Gestão de Horários e Ambientes</div>
        </div>
      </div>
    </div>
    <!-- Lado direito -->
    <?php if ($usuarioLogado): ?>
    <div class="d-flex align-items-center gap-3">
      <div class="d-flex align-items-center gap-2 small">
        <i class="bi bi-person-circle fs-5"></i>
        <span id="userName"><?php echo htmlspecialchars($usuarioLogado); ?></span>
      </div>

      <button class="btn btn-outline-light btn-sm" id="btnLogout">
        <i class="bi bi-box-arrow-right"></i> Sair
      </button>
    </div>
    <?php endif; ?>
  </div>
</header>
