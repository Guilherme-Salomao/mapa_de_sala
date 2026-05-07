<aside class="col-12 col-md-3 col-lg-2 app-sidebar">
  <nav class="app-sidebar-nav">
    <a class="app-side-link <?= ($paginaAtiva ?? '') === 'home' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=home">
      <i class="bi bi-grid-3x3-gap"></i>
      <span>Dashboard</span>
    </a>

    <a class="app-side-link <?= ($paginaAtiva ?? '') === 'salas' ? 'active' : '' ?>" href="#">
      <i class="bi bi-door-closed"></i>
      <span>Salas</span>
    </a>

    <a class="app-side-link <?= ($paginaAtiva ?? '') === 'usuarios' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=usuarios">
      <i class="bi bi-people"></i>
      <span>Usuários</span>
    </a>

    <a class="app-side-link <?= ($paginaAtiva ?? '') === 'relatorios' ? 'active' : '' ?>" href="#">
      <i class="bi bi-clipboard-data"></i>
      <span>Relatórios</span>
    </a>

    <a class="app-side-link <?= ($paginaAtiva ?? '') === 'configuracoes' ? 'active' : '' ?>" href="#">
      <i class="bi bi-gear"></i>
      <span>Configurações</span>
    </a>
  </nav>
</aside>