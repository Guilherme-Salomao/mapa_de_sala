<form method="GET" action="./" class="row g-2 align-items-center">
  <input type="hidden" name="page" value="turmas">

  <div class="col-12 col-md-5">
    <div class="input-group">
      <span class="input-group-text app-input-icon">
        <i class="bi bi-search"></i>
      </span>
      <input type="text" name="busca" class="form-control" placeholder="Buscar por nome, codigo ou descricao..."
        value="<?php echo htmlspecialchars($busca ?? ''); ?>">
    </div>
  </div>

  <div class="col-6 col-md-2">
    <select name="status" class="form-select">
      <option value="Em andamento" <?php echo(($status ?? 'Em andamento') === 'Em andamento') ? 'selected' : ''; ?>>
        Em andamento
      </option>
      <option value="Finalizada" <?php echo(($status ?? '') === 'Finalizada') ? 'selected' : ''; ?>>
        Finalizada
      </option>
      <option value="todos" <?php echo(($status ?? '') === 'todos') ? 'selected' : ''; ?>>
        Todas
      </option>
    </select>
  </div>

  <div class="col-6 col-md-3">
    <select name="cidade_id" class="form-select" aria-label="Filtrar por cidade">
      <option value="0">Todas as cidades</option>
      <?php foreach (($cidades ?? []) as $cidade): ?>
      <option value="<?php echo (int) $cidade['id']; ?>"
        <?php echo((int) ($cidadeId ?? 0) === (int) $cidade['id']) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($cidade['nome']); ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-6 col-md-2 d-flex gap-2">
    <button type="submit" class="btn app-btn-primary w-100">
      <i class="bi bi-funnel"></i> Filtrar
    </button>

    <a href="./?page=turmas" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-counterclockwise"></i>
    </a>
  </div>
</form>

