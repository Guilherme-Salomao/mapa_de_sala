<form method="GET" action="./" class="row g-2 align-items-center">
  <input type="hidden" name="page" value="ucs">

  <div class="col-12 col-md-4">
    <div class="input-group">
      <span class="input-group-text app-input-icon">
        <i class="bi bi-search"></i>
      </span>
      <input type="text" name="busca" class="form-control" placeholder="Buscar por codigo, UC ou curso..."
        value="<?php echo htmlspecialchars($busca ?? ''); ?>">
    </div>
  </div>

  <div class="col-12 col-md-4">
    <select name="curso_modelo_id" class="form-select">
      <option value="0" <?php echo((int) ($cursoModeloId ?? 0) === 0) ? 'selected' : ''; ?>>Todos os modelos</option>
      <?php foreach (($cursoModelos ?? []) as $cursoModelo): ?>
      <option value="<?php echo (int) $cursoModelo['id']; ?>"
        <?php echo((int) ($cursoModeloId ?? 0) === (int) $cursoModelo['id']) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($cursoModelo['nome']); ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-6 col-md-2">
    <select name="status" class="form-select">
      <option value="todos" <?php echo(($status ?? 'todos') === 'todos') ? 'selected' : ''; ?>>Todos</option>
      <option value="Ativa" <?php echo(($status ?? '') === 'Ativa') ? 'selected' : ''; ?>>Ativa</option>
      <option value="Inativa" <?php echo(($status ?? '') === 'Inativa') ? 'selected' : ''; ?>>Inativa</option>
    </select>
  </div>

  <div class="col-6 col-md-2 d-flex gap-2">
    <button type="submit" class="btn app-btn-primary w-100">
      <i class="bi bi-funnel"></i> Filtrar
    </button>
    <a href="./?page=ucs" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-counterclockwise"></i>
    </a>
  </div>
</form>
