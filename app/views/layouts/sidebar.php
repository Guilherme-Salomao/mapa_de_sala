<aside class="col-12 col-md-3 col-lg-2 app-sidebar">
  <?php
      $nivelMenu       = $_SESSION['usuario']['nivel_acesso'] ?? '';
      $isAdminMenu     = $nivelMenu === 'Admin';
      $isGestorMenu    = $nivelMenu === 'Gestor';
      $isApoioMenu     = $nivelMenu === 'Apoio';
      $isProfessorMenu = $nivelMenu === 'Professor';
  ?>
  <nav class="app-sidebar-nav">
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'home' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=home">
      <i class="bi bi-grid-3x3-gap"></i>
      <span>Dashboard</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'perfil' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=perfil">
      <i class="bi bi-person-circle"></i>
      <span>Meus Dados</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'quadro_horario' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=quadro_horario">
      <i class="bi bi-calendar-week"></i>
      <span>Quadro Horario</span>
    </a>

    <?php if (! $isProfessorMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'calendario' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=calendario">
      <i class="bi bi-calendar-x"></i>
      <span>Calendario</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'educacao_corporativa' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=educacao_corporativa">
      <i class="bi bi-mortarboard"></i>
      <span>Educacao Corporativa</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_turma' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=relatorio_turma">
      <i class="bi bi-table"></i>
      <span>Relatorio Turma</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_salas' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=relatorio_salas">
      <i class="bi bi-door-open"></i>
      <span>Relatorio Salas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_gestor' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=relatorio_gestor">
      <i class="bi bi-bar-chart-line"></i>
      <span>Relatorio Gestor</span>
    </a>
    <?php endif; ?>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_docente' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=relatorio_docente">
      <i class="bi bi-clipboard-data"></i>
      <span>Relatorio Docente</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'cursos' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=cursos">
      <i class="bi bi-journal-bookmark"></i>
      <span>Cursos</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'ucs' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=ucs">
      <i class="bi bi-list-check"></i>
      <span>UCs</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'turmas' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=turmas">
      <i class="bi bi-calendar3"></i>
      <span>Turmas</span>
    </a>

    <a class="app-side-link <?php echo in_array(($paginaAtiva ?? ''), ['aprendizagem', 'aceleracao'], true) ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=aceleracao">
      <i class="bi bi-lightning-charge"></i>
      <span>Aceleração</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'docentes' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=docentes">
      <i class="bi bi-person-badge"></i>
      <span><?php echo $isProfessorMenu ? 'Meu Cadastro' : 'Docentes'; ?></span>
    </a>

    <?php if (! $isProfessorMenu): ?>
    <?php if ($isAdminMenu || $isApoioMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'salas' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=salas">
      <i class="bi bi-door-closed"></i>
      <span>Salas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'gestao_salas' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=gestao_salas">
      <i class="bi bi-arrow-left-right"></i>
      <span>Gestão de Salas</span>
    </a>
    <?php endif; ?>

    <?php if ($isAdminMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'usuarios' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=usuarios">
      <i class="bi bi-people"></i>
      <span>Usuarios</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'logs' ? 'active' : '' ?>"
      href="/mapa_de_sala/public/?page=logs">
      <i class="bi bi-list-columns-reverse"></i>
      <span>Logs</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>
  </nav>
</aside>
