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
      href="./?page=home">
      <i class="bi bi-grid-3x3-gap"></i>
      <span>Dashboard</span>
    </a>

    <?php if (! $isProfessorMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'perfil' ? 'active' : '' ?>"
      href="./?page=perfil">
      <i class="bi bi-person-circle"></i>
      <span>Meu Cadastro</span>
    </a>
    <?php endif; ?>

    <?php if ($isApoioMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'quadro_horario' ? 'active' : '' ?>"
      href="./?page=quadro_horario">
      <i class="bi bi-calendar-week"></i>
      <span>Quadro Horário</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'gestao_salas' ? 'active' : '' ?>"
      href="./?page=gestao_salas">
      <i class="bi bi-arrow-left-right"></i>
      <span>Gestão de Sala</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'salas' ? 'active' : '' ?>"
      href="./?page=salas">
      <i class="bi bi-door-closed"></i>
      <span>Salas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_salas' ? 'active' : '' ?>"
      href="./?page=relatorio_salas">
      <i class="bi bi-door-open"></i>
      <span>Relatório de Salas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_turma' ? 'active' : '' ?>"
      href="./?page=relatorio_turma">
      <i class="bi bi-table"></i>
      <span>Relatório de Turmas</span>
    </a>

    <a class="app-side-link <?php echo in_array(($paginaAtiva ?? ''), ['aprendizagem', 'aceleracao'], true) ? 'active' : '' ?>"
      href="./?page=aceleracao">
      <i class="bi bi-lightning-charge"></i>
      <span>Aceleração</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'calendario' ? 'active' : '' ?>"
      href="./?page=calendario">
      <i class="bi bi-calendar-x"></i>
      <span>Calendário</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'educacao_corporativa' ? 'active' : '' ?>"
      href="./?page=educacao_corporativa">
      <i class="bi bi-mortarboard"></i>
      <span>Educação Corporativa</span>
    </a>

    <?php if ($isProfessorMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_turma' ? 'active' : '' ?>"
      href="./?page=relatorio_turma">
      <i class="bi bi-table"></i>
      <span>Relatório Turma</span>
    </a>
    <?php endif; ?>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'cursos' ? 'active' : '' ?>"
      href="./?page=cursos">
      <i class="bi bi-journal-bookmark"></i>
      <span>Cursos</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'ucs' ? 'active' : '' ?>"
      href="./?page=ucs">
      <i class="bi bi-list-check"></i>
      <span>UCs</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'turmas' ? 'active' : '' ?>"
      href="./?page=turmas">
      <i class="bi bi-calendar3"></i>
      <span>Turmas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'docentes' ? 'active' : '' ?>"
      href="./?page=docentes">
      <i class="bi bi-person-badge"></i>
      <span>Docentes</span>
    </a>
    <?php elseif ($isGestorMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_gestor' ? 'active' : '' ?>"
      href="./?page=relatorio_gestor">
      <i class="bi bi-bar-chart-line"></i>
      <span>Relatório Gestor</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'quadro_horario' ? 'active' : '' ?>"
      href="./?page=quadro_horario">
      <i class="bi bi-calendar-week"></i>
      <span>Quadro Horário</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_turma' ? 'active' : '' ?>"
      href="./?page=relatorio_turma">
      <i class="bi bi-table"></i>
      <span>Relatório Turma</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_docente' ? 'active' : '' ?>"
      href="./?page=relatorio_docente">
      <i class="bi bi-clipboard-data"></i>
      <span>Relatório Docente</span>
    </a>

    <?php if ($isProfessorMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_turma' ? 'active' : '' ?>"
      href="./?page=relatorio_turma">
      <i class="bi bi-table"></i>
      <span>Relatório Turma</span>
    </a>
    <?php endif; ?>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'cursos' ? 'active' : '' ?>"
      href="./?page=cursos">
      <i class="bi bi-journal-bookmark"></i>
      <span>Cursos</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'ucs' ? 'active' : '' ?>"
      href="./?page=ucs">
      <i class="bi bi-list-check"></i>
      <span>UCs</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'turmas' ? 'active' : '' ?>"
      href="./?page=turmas">
      <i class="bi bi-calendar3"></i>
      <span>Turmas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'docentes' ? 'active' : '' ?>"
      href="./?page=docentes">
      <i class="bi bi-person-badge"></i>
      <span>Docentes</span>
    </a>

    <a class="app-side-link <?php echo in_array(($paginaAtiva ?? ''), ['aprendizagem', 'aceleracao'], true) ? 'active' : '' ?>"
      href="./?page=aceleracao">
      <i class="bi bi-lightning-charge"></i>
      <span>Aceleração</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'calendario' ? 'active' : '' ?>"
      href="./?page=calendario">
      <i class="bi bi-calendar-x"></i>
      <span>Calendário</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'educacao_corporativa' ? 'active' : '' ?>"
      href="./?page=educacao_corporativa">
      <i class="bi bi-mortarboard"></i>
      <span>Educação Corporativa</span>
    </a>
    <?php else: ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'quadro_horario' ? 'active' : '' ?>"
      href="./?page=quadro_horario">
      <i class="bi bi-calendar-week"></i>
      <span>Quadro Horário</span>
    </a>

    <?php if (! $isProfessorMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'calendario' ? 'active' : '' ?>"
      href="./?page=calendario">
      <i class="bi bi-calendar-x"></i>
      <span>Calendário</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'educacao_corporativa' ? 'active' : '' ?>"
      href="./?page=educacao_corporativa">
      <i class="bi bi-mortarboard"></i>
      <span>Educação Corporativa</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_turma' ? 'active' : '' ?>"
      href="./?page=relatorio_turma">
      <i class="bi bi-table"></i>
      <span>Relatório Turma</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_salas' ? 'active' : '' ?>"
      href="./?page=relatorio_salas">
      <i class="bi bi-door-open"></i>
      <span>Relatório Salas</span>
    </a>

    <?php if ($isAdminMenu || $isGestorMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_gestor' ? 'active' : '' ?>"
      href="./?page=relatorio_gestor">
      <i class="bi bi-bar-chart-line"></i>
      <span>Relatório Gestor</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_docente' ? 'active' : '' ?>"
      href="./?page=relatorio_docente">
      <i class="bi bi-clipboard-data"></i>
      <span>Relatório Docente</span>
    </a>

    <?php if ($isProfessorMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'relatorio_turma' ? 'active' : '' ?>"
      href="./?page=relatorio_turma">
      <i class="bi bi-table"></i>
      <span>Relatório Turma</span>
    </a>
    <?php endif; ?>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'cursos' ? 'active' : '' ?>"
      href="./?page=cursos">
      <i class="bi bi-journal-bookmark"></i>
      <span>Cursos</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'ucs' ? 'active' : '' ?>"
      href="./?page=ucs">
      <i class="bi bi-list-check"></i>
      <span>UCs</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'turmas' ? 'active' : '' ?>"
      href="./?page=turmas">
      <i class="bi bi-calendar3"></i>
      <span>Turmas</span>
    </a>

    <a class="app-side-link <?php echo in_array(($paginaAtiva ?? ''), ['aprendizagem', 'aceleracao'], true) ? 'active' : '' ?>"
      href="./?page=aceleracao">
      <i class="bi bi-lightning-charge"></i>
      <span>Aceleração</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'docentes' ? 'active' : '' ?>"
      href="./?page=docentes">
      <i class="bi bi-person-badge"></i>
      <span><?php echo $isProfessorMenu ? 'Meu Cadastro' : 'Docentes'; ?></span>
    </a>

    <?php if ($isAdminMenu): ?>
    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'salas' ? 'active' : '' ?>"
      href="./?page=salas">
      <i class="bi bi-door-closed"></i>
      <span>Salas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'gestao_salas' ? 'active' : '' ?>"
      href="./?page=gestao_salas">
      <i class="bi bi-arrow-left-right"></i>
      <span>Gestão de Salas</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'usuarios' ? 'active' : '' ?>"
      href="./?page=usuarios">
      <i class="bi bi-people"></i>
      <span>Usuários</span>
    </a>

    <a class="app-side-link <?php echo($paginaAtiva ?? '') === 'logs' ? 'active' : '' ?>"
      href="./?page=logs">
      <i class="bi bi-list-columns-reverse"></i>
      <span>Logs</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>
  </nav>
</aside>
