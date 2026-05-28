<form method="GET" action="./" class="row g-2 align-items-center">
  <input type="hidden" name="page" value="cursos">

  <div class="col-12 col-md-7">
    <div class="input-group">
      <span class="input-group-text app-input-icon">
        <i class="bi bi-search"></i>
      </span>
      <input type="text" name="busca" class="form-control" placeholder="Buscar por nome do curso..."
        value="<?php echo htmlspecialchars($busca ?? ''); ?>">
    </div>
  </div>

  <div class="col-6 col-md-3">
    <select name="status" class="form-select">
      <option value="todos" <?php echo(($status ?? 'todos') === 'todos') ? 'selected' : ''; ?>>Todos</option>
      <option value="Ativo" <?php echo(($status ?? '') === 'Ativo') ? 'selected' : ''; ?>>Ativo</option>
      <option value="Inativo" <?php echo(($status ?? '') === 'Inativo') ? 'selected' : ''; ?>>Inativo</option>
    </select>
  </div>

  <div class="col-6 col-md-2 d-flex gap-2">
    <button type="submit" class="btn app-btn-primary w-100">
      <i class="bi bi-funnel"></i> Filtrar
    </button>
    <a href="./?page=cursos" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-counterclockwise"></i>
    </a>
  </div>
</form>
