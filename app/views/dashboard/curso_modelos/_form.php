<form id="formCursoModelo" method="POST" action="<?php echo $formAction; ?>" novalidate>
  <?php if ($modoEdicao): ?>
  <input type="hidden" name="id" value="<?php echo (int) ($cursoForm['id'] ?? 0); ?>">
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-5">
      <label for="nome" class="form-label">Nome do curso</label>
      <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex.: Tecnico em Informatica"
        value="<?php echo htmlspecialchars($cursoForm['nome'] ?? ''); ?>" required>
      <div class="invalid-feedback">Informe o nome do curso.</div>
    </div>

    <div class="col-12 col-md-4">
      <label for="area_id" class="form-label">Area</label>
      <select class="form-select" id="area_id" name="area_id" required>
        <option value="" <?php echo empty($cursoForm['area_id']) ? 'selected' : ''; ?> disabled>Selecione...</option>
        <?php foreach (($areas ?? []) as $area): ?>
        <option value="<?php echo (int) $area['id']; ?>"
          <?php echo((int) ($cursoForm['area_id'] ?? ($_GET['area_id'] ?? 0)) === (int) $area['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($area['nome'] ?? ''); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="invalid-feedback">Selecione a area do curso.</div>
    </div>

    <div class="col-12 col-md-3">
      <label for="carga_horaria_total" class="form-label">Carga horária total</label>
      <div class="input-group">
        <span class="input-group-text app-input-icon">
          <i class="bi bi-hourglass-split"></i>
        </span>
        <input type="number" class="form-control" id="carga_horaria_total" name="carga_horaria_total" min="1"
          step="1" placeholder="Ex.: 1200"
          value="<?php echo htmlspecialchars($cursoForm['carga_horaria_total'] ?? ''); ?>" required>
        <span class="input-group-text">h</span>
        <div class="invalid-feedback">Informe a carga horária total.</div>
      </div>
    </div>

    <div class="col-12 col-md-3">
      <label for="status" class="form-label">Status</label>
      <select class="form-select" id="status" name="status" required>
        <option value="Ativo" <?php echo(($cursoForm['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : ''; ?>>Ativo
        </option>
        <option value="Inativo" <?php echo(($cursoForm['status'] ?? '') === 'Inativo') ? 'selected' : ''; ?>>Inativo
        </option>
      </select>
      <div class="invalid-feedback">Selecione o status.</div>
    </div>
  </div>

  <hr class="my-4" />

  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <a href="/mapa_de_sala/public/?page=cursos" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>
    <button type="submit" class="btn app-btn-primary">
      <i class="bi bi-save"></i> <?php echo $botaoTexto; ?>
    </button>
  </div>
</form>
