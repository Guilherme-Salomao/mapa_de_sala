<aside class="col-12 col-md-3 col-lg-2 app-sidebar">
  <nav class="app-sidebar-nav">
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'home' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=home">
      <i class="bi bi-grid-3x3-gap"></i>
      <span>Dashboard</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'salas' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=salas">
      <i class="bi bi-door-closed"></i>
      <span>Salas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'usuarios' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=usuarios">
      <i class="bi bi-people"></i>
      <span>Usuários</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'docentes' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=docentes">
      <i class="bi bi-person-badge"></i>
      <span>Docentes</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'cursos' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=cursos">
      <i class="bi bi-journal-bookmark"></i>
      <span>Cursos</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'turmas' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=turmas">
      <i class="bi bi-calendar3"></i>
      <span>Turmas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'ucs' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=ucs">
      <i class="bi bi-list-check"></i>
      <span>UCs</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorios' ? 'active' : '' ?>" href="#">
      <i class="bi bi-clipboard-data"></i>
      <span>Relatórios</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'configuracoes' ? 'active' : '' ?>" href="#">
      <i class="bi bi-gear"></i>
      <span>Configurações</span>
    </a>
  </nav>
</aside>
