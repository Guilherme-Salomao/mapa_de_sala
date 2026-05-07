<header class="app-header">
  <div
    class="container-fluid d-flex align-items-center justify-content-between py-2"
  >
    <!-- Lado esquerdo -->
    <div class="d-flex align-items-center gap-3">
      <!-- Botão menu mobile (para dashboard com sidebar futura) -->
      <button
        class="btn btn-outline-light btn-sm d-md-none"
        type="button"
        id="btnToggleSidebar"
      >
        <i class="bi bi-list"></i>
      </button>
      <!-- Logo / Nome Sistema -->
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-building fs-4"></i>
        <div class="lh-sm">
          <div class="fw-bold">Sistema de Controle de Salas</div>
          <div class="small opacity-75" id="pageTitle">Painel do Sistema</div>
        </div>
      </div>
    </div>
    <!-- Lado direito -->
    <div class="d-flex align-items-center gap-3">
      <!-- Usuário logado -->
      <div class="d-flex align-items-center gap-2 small">
        <i class="bi bi-person-circle fs-5"></i>
        <span id="userName">Admin</span>
      </div>
      <!-- Botão sair -->
      <button class="btn btn-outline-light btn-sm" id="btnLogout">
        <i class="bi bi-box-arrow-right"></i> Sair
      </button>
    </div>
  </div>
</header>
