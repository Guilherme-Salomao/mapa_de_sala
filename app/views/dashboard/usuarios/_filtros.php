<form method="GET" action="./" class="row g-2 align-items-center">
  <input type="hidden" name="page" value="usuarios">

  <div class="col-12 col-md-6">
    <div class="input-group">
      <span class="input-group-text app-input-icon">
        <i class="bi bi-search"></i>
      </span>
      <input id="buscaUsuario" name="busca" type="text" class="form-control" placeholder="Buscar por nome ou e-mail..."
        value="<?php echo htmlspecialchars($busca); ?>" />
    </div>
  </div>

  <div class="col-6 col-md-3">
    <select id="filtroNivel" name="nivel" class="form-select">
      <option value="todos" <?php echo $nivel === 'todos' ? 'selected' : ''; ?>>Todos os níveis</option>
      <option value="Admin" <?php echo $nivel === 'Admin' ? 'selected' : ''; ?>>Admin</option>
      <option value="Gestor" <?php echo $nivel === 'Gestor' ? 'selected' : ''; ?>>Gestor(a)</option>
      <option value="Professor" <?php echo $nivel === 'Professor' ? 'selected' : ''; ?>>Professor(a)</option>
      <option value="Apoio" <?php echo $nivel === 'Apoio' ? 'selected' : ''; ?>>Apoio</option>
    </select>
  </div>

  <div class="col-6 col-md-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary w-100">
      <i class="bi bi-funnel"></i> Filtrar
    </button>

    <a href="./?page=usuarios" id="btnLimpar" class="btn btn-outline-secondary w-100">
      <i class="bi bi-arrow-counterclockwise"></i> Limpar
    </a>
  </div>
</form>
