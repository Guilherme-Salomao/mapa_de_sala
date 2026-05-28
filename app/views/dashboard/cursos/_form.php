<form id="formCurso" method="POST" action="<?php echo $formAction; ?>" novalidate>
  <?php if ($modoEdicao): ?>
  <input type="hidden" name="id" value="<?php echo (int) ($cursoForm['id'] ?? 0); ?>">
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-6">
      <label for="curso_modelo_id" class="form-label">Curso</label>
      <select class="form-select" id="curso_modelo_id" name="curso_modelo_id" required>
        <option value="" <?php echo empty($cursoForm['curso_modelo_id']) ? 'selected' : ''; ?> disabled>
          Selecione...
        </option>
        <?php foreach (($cursoModelos ?? []) as $cursoModelo): ?>
        <option value="<?php echo (int) $cursoModelo['id']; ?>"
          <?php echo((int) ($cursoForm['curso_modelo_id'] ?? 0) === (int) $cursoModelo['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($cursoModelo['nome']); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="invalid-feedback">Selecione o curso.</div>
    </div>

    <div class="col-12 col-md-6">
      <label for="nome" class="form-label">Nome da turma</label>
      <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex.: Tecnico em Informatica - Tarde"
        value="<?php echo htmlspecialchars($cursoForm['nome'] ?? ''); ?>" required>
      <div class="invalid-feedback">Informe o nome da turma.</div>
    </div>

    <div class="col-12 col-md-3">
      <label for="codigo_oferta" class="form-label">Codigo da oferta</label>
      <input type="text" class="form-control" id="codigo_oferta" name="codigo_oferta" placeholder="Ex.: TI-2026-01"
        value="<?php echo htmlspecialchars($cursoForm['codigo_oferta'] ?? ''); ?>" required>
      <div class="invalid-feedback">Informe o codigo da oferta.</div>
    </div>

    <div class="col-12 col-md-3">
      <label for="hora_inicio" class="form-label">Inicio manha</label>
      <input type="time" class="form-control" id="hora_inicio" name="hora_inicio"
        value="<?php echo htmlspecialchars(substr($cursoForm['hora_inicio'] ?? '', 0, 5)); ?>">
      <div class="form-text">Opcional.</div>
    </div>

    <div class="col-12 col-md-3">
      <label for="hora_fim" class="form-label">Fim manha</label>
      <input type="time" class="form-control" id="hora_fim" name="hora_fim"
        value="<?php echo htmlspecialchars(substr($cursoForm['hora_fim'] ?? '', 0, 5)); ?>">
      <div class="form-text">Opcional.</div>
    </div>

    <div class="col-12 col-md-3">
      <label for="status" class="form-label">Status</label>
      <select class="form-select" id="status" name="status" required>
        <option value="Em andamento"
          <?php echo(($cursoForm['status'] ?? 'Em andamento') === 'Em andamento') ? 'selected' : ''; ?>>
          Em andamento
        </option>
        <option value="Finalizada" <?php echo(($cursoForm['status'] ?? '') === 'Finalizada') ? 'selected' : ''; ?>>
          Finalizada
        </option>
      </select>
      <div class="invalid-feedback">Selecione o status.</div>
    </div>

    <div class="col-12">
      <label class="form-check border rounded p-2 d-flex align-items-center gap-2">
        <input class="form-check-input m-0" type="checkbox" id="integral" name="integral" value="1"
          <?php echo((int) ($cursoForm['integral'] ?? 0) === 1) ? 'checked' : ''; ?>>
        <span class="fw-semibold">Turma integral</span>
      </label>
    </div>

    <div class="col-12">
      <label class="form-check border rounded p-2 d-flex align-items-center gap-2">
        <input class="form-check-input m-0" type="checkbox" name="participa_parada_pedagogica" value="1"
          <?php echo((int) ($cursoForm['participa_parada_pedagogica'] ?? 1) === 1) ? 'checked' : ''; ?>>
        <span class="fw-semibold">Participa de parada pedagogica</span>
      </label>
    </div>

    <div class="col-12 col-md-3 turma-integral-campo">
      <label for="hora_inicio_tarde" class="form-label">Inicio tarde</label>
      <input type="time" class="form-control" id="hora_inicio_tarde" name="hora_inicio_tarde"
        value="<?php echo htmlspecialchars(substr($cursoForm['hora_inicio_tarde'] ?? '', 0, 5)); ?>">
    </div>

    <div class="col-12 col-md-3 turma-integral-campo">
      <label for="hora_fim_tarde" class="form-label">Fim tarde</label>
      <input type="time" class="form-control" id="hora_fim_tarde" name="hora_fim_tarde"
        value="<?php echo htmlspecialchars(substr($cursoForm['hora_fim_tarde'] ?? '', 0, 5)); ?>">
    </div>

    <div class="col-12">
      <label class="form-label">Dias de aula</label>
      <div class="row g-2">
        <?php
            $diasAulaTurma = [
                'aula_segunda' => 'Segunda',
                'aula_terca'   => 'Terca',
                'aula_quarta'  => 'Quarta',
                'aula_quinta'  => 'Quinta',
                'aula_sexta'   => 'Sexta',
                'aula_sabado'  => 'Sabado',
            ];
        ?>
        <?php foreach ($diasAulaTurma as $campoDia => $labelDia): ?>
        <div class="col-6 col-md-4 col-lg-2">
          <label class="form-check border rounded p-2 d-flex align-items-center gap-2 h-100">
            <input class="form-check-input m-0" type="checkbox" name="<?php echo $campoDia; ?>" value="1"
              <?php echo((int) ($cursoForm[$campoDia] ?? 0) === 1) ? 'checked' : ''; ?>>
            <span><?php echo htmlspecialchars($labelDia); ?></span>
          </label>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="form-text">Marque os dias em que esta turma tem aula.</div>
    </div>

    <div class="col-12">
      <label for="descricao" class="form-label">Descricao</label>
      <textarea class="form-control" id="descricao" name="descricao" rows="4"
        placeholder="Informações adicionais sobre o curso..."><?php echo htmlspecialchars($cursoForm['descricao'] ?? ''); ?></textarea>
    </div>

  </div>

  <hr class="my-4" />

  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <a href="./?page=turmas" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>

    <button type="submit" class="btn app-btn-primary">
      <i class="bi bi-save"></i> <?php echo $botaoTexto; ?>
    </button>
  </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const integral = document.getElementById("integral");
  const campos = document.querySelectorAll(".turma-integral-campo");

  function atualizarCamposIntegral() {
    campos.forEach(function(campo) {
      campo.classList.toggle("d-none", !integral.checked);
    });
  }

  if (integral) {
    integral.addEventListener("change", atualizarCamposIntegral);
    atualizarCamposIntegral();
  }
});
</script>