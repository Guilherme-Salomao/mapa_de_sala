<form id="formUc" method="POST" action="<?php echo $formAction; ?>" novalidate>
  <?php if ($modoEdicao): ?>
  <input type="hidden" name="id" value="<?php echo (int) ($ucForm['id'] ?? 0); ?>">
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-6">
      <label for="curso_modelo_id" class="form-label">Modelo do curso</label>
      <select class="form-select" id="curso_modelo_id" name="curso_modelo_id" required>
        <option value="" <?php echo empty($ucForm['curso_modelo_id']) ? 'selected' : ''; ?> disabled>Selecione...
        </option>
        <?php foreach (($cursoModelos ?? []) as $cursoModelo): ?>
        <option value="<?php echo (int) $cursoModelo['id']; ?>"
          <?php echo((int) ($ucForm['curso_modelo_id'] ?? 0) === (int) $cursoModelo['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($cursoModelo['nome']); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="invalid-feedback">Selecione o modelo do curso.</div>
    </div>

    <div class="col-12 col-md-6">
      <label for="codigo" class="form-label">Codigo da UC</label>
      <input type="text" class="form-control" id="codigo" name="codigo" maxlength="20" placeholder="Ex.: UC1"
        value="<?php echo htmlspecialchars($ucForm['codigo'] ?? ''); ?>" required>
      <div class="invalid-feedback">Informe o codigo da UC.</div>
    </div>

    <div class="col-12 col-md-6">
      <label for="nome" class="form-label">Nome da UC</label>
      <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex.: Logica de Programacao"
        value="<?php echo htmlspecialchars($ucForm['nome'] ?? ''); ?>" required>
      <div class="invalid-feedback">Informe o nome da UC.</div>
    </div>

    <div class="col-12 col-md-3">
      <label for="carga_horaria" class="form-label">Carga horaria</label>
      <div class="input-group">
        <span class="input-group-text app-input-icon">
          <i class="bi bi-hourglass-split"></i>
        </span>
        <input type="number" class="form-control" id="carga_horaria" name="carga_horaria" min="1" step="1"
          placeholder="Ex.: 80" value="<?php echo htmlspecialchars($ucForm['carga_horaria'] ?? ''); ?>" required>
        <span class="input-group-text">h</span>
        <div class="invalid-feedback">Informe a carga horaria.</div>
      </div>
    </div>

    <div class="col-12 col-md-3">
      <label for="status" class="form-label">Status</label>
      <select class="form-select" id="status" name="status" required>
        <option value="Ativa" <?php echo(($ucForm['status'] ?? 'Ativa') === 'Ativa') ? 'selected' : ''; ?>>Ativa
        </option>
        <option value="Inativa" <?php echo(($ucForm['status'] ?? '') === 'Inativa') ? 'selected' : ''; ?>>Inativa
        </option>
      </select>
      <div class="invalid-feedback">Selecione o status.</div>
    </div>
  </div>

  <hr class="my-4" />

  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <a href="/mapa_de_sala/public/?page=ucs" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>
    <button type="submit" class="btn app-btn-primary">
      <i class="bi bi-save"></i> <?php echo $botaoTexto; ?>
    </button>
  </div>
</form>
