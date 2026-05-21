<form method="GET" action="/mapa_de_sala/public/" class="row g-2 align-items-center">

  <input type="hidden" name="page" value="salas">

  <div class="col-12 col-md-5">
    <div class="input-group">
      <span class="input-group-text app-input-icon">
        <i class="bi bi-search"></i>
      </span>

      <input type="text" name="busca" class="form-control" placeholder="Buscar por nome ou descrição..."
        value="<?php echo htmlspecialchars($busca ?? ''); ?>">
    </div>
  </div>

  <div class="col-6 col-md-3">
    <select name="tipo" class="form-select">
      <option value="todos" <?php echo(($tipo ?? 'todos') === 'todos') ? 'selected' : ''; ?>>
        Todos os tipos
      </option>

      <option value="Sala Convencional" <?php echo(($tipo ?? '') === 'Sala Convencional') ? 'selected' : ''; ?>>
        Sala Convencional
      </option>

      <option value="Laboratório de Informática"
        <?php echo(($tipo ?? '') === 'Laboratório de Informática') ? 'selected' : ''; ?>>
        Laboratório de Informática
      </option>

      <option value="Auditório" <?php echo(($tipo ?? '') === 'Auditório') ? 'selected' : ''; ?>>
        Auditório
      </option>

      <option value="Sala Teatro" <?php echo(($tipo ?? '') === 'Sala Teatro') ? 'selected' : ''; ?>>
        Sala Teatro
      </option>

      <option value="Laboratório de Beleza" <?php echo(($tipo ?? '') === 'Laboratório de Beleza') ? 'selected' : ''; ?>>
        Laboratório de Beleza
      </option>
    </select>
  </div>

  <div class="col-6 col-md-2">
    <select name="status" class="form-select">
      <option value="todos" <?php echo(($status ?? 'todos') === 'todos') ? 'selected' : ''; ?>>
        Todos
      </option>

      <option value="ativa" <?php echo(($status ?? '') === 'ativa') ? 'selected' : ''; ?>>
        Ativa
      </option>

      <option value="manutencao" <?php echo(($status ?? '') === 'manutencao') ? 'selected' : ''; ?>>
        Manutenção
      </option>

      <option value="inativa" <?php echo(($status ?? '') === 'inativa') ? 'selected' : ''; ?>>
        Inativa
      </option>
    </select>
  </div>

  <div class="col-12 col-md-2 d-flex gap-2">
    <button type="submit" class="btn app-btn-primary w-100">
      <i class="bi bi-funnel"></i> Filtrar
    </button>

    <a href="/mapa_de_sala/public/?page=salas" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-counterclockwise"></i>
    </a>
  </div>

</form>
