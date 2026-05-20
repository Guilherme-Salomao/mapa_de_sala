<form id="formDocente" method="POST" action="<?php echo $formAction; ?>" novalidate>
  <?php if ($modoEdicao): ?>
  <input type="hidden" name="id" value="<?php echo (int) ($docenteForm['id'] ?? 0); ?>">
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-6">
      <label for="usuario_id" class="form-label">Usuário vinculado</label>
      <select class="form-select" id="usuario_id" name="usuario_id" required>
        <option value="" <?php echo empty($docenteForm['usuario_id']) ? 'selected' : ''; ?> disabled>
          Selecione...
        </option>

        <?php foreach (($usuariosDisponiveis ?? []) as $usuario): ?>
        <?php
            $usuarioId = (int) ($usuario['id'] ?? 0);
            $selected  = ((int) ($docenteForm['usuario_id'] ?? 0) === $usuarioId) ? 'selected' : '';
        ?>
        <option value="<?php echo $usuarioId; ?>" <?php echo $selected; ?>>
          <?php echo htmlspecialchars(($usuario['nome'] ?? '') . ' - ' . ($usuario['email'] ?? '')); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="invalid-feedback">
        Selecione o usuário vinculado ao docente.
      </div>
      <?php if (empty($usuariosDisponiveis)): ?>
      <div class="form-text text-danger">Cadastre um usuário ativo com nível Professor antes de criar o docente.</div>
      <?php endif; ?>
    </div>

    <div class="col-12 col-md-6">
      <label for="area_atuacao" class="form-label">Área de atuação</label>
      <select class="form-select" id="area_atuacao" name="area_atuacao" required>
        <option value="" <?php echo empty($docenteForm['area_atuacao']) ? 'selected' : ''; ?> disabled>
          Selecione...
        </option>
        <?php
            $areasAtuacao = [
                'Tecnologia',
                'Saúde',
                'Aprendizagem',
                'Gestão e Negócios',
                'Beleza e Estética',
                'Gastronomia',
                'Idiomas',
                'Comunicação',
                'Design',
                'Educação',
            ];
        ?>
        <?php foreach ($areasAtuacao as $area): ?>
        <option value="<?php echo htmlspecialchars($area); ?>"
          <?php echo(($docenteForm['area_atuacao'] ?? '') === $area) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($area); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="invalid-feedback">
        Selecione a área de atuação.
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="horas_semanais" class="form-label">Horas semanais</label>
      <div class="input-group">
        <span class="input-group-text app-input-icon">
          <i class="bi bi-clock"></i>
        </span>
        <input type="number" class="form-control" id="horas_semanais" name="horas_semanais" min="1" max="60"
          placeholder="Ex.: 40" value="<?php echo htmlspecialchars($docenteForm['horas_semanais'] ?? ''); ?>" required>
        <span class="input-group-text">h</span>
        <div class="invalid-feedback">
          Informe uma carga horária entre 1 e 60 horas.
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="status" class="form-label">Status</label>
      <select class="form-select" id="status" name="status" required>
        <option value="Ativo" <?php echo(($docenteForm['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : ''; ?>>Ativo
        </option>
        <option value="Inativo" <?php echo(($docenteForm['status'] ?? '') === 'Inativo') ? 'selected' : ''; ?>>Inativo
        </option>
      </select>
      <div class="invalid-feedback">
        Selecione o status.
      </div>
    </div>

    <div class="col-12">
      <label for="observacoes" class="form-label">Observações</label>
      <textarea class="form-control" id="observacoes" name="observacoes" rows="4"
        placeholder="Informações adicionais sobre o docente..."><?php echo htmlspecialchars($docenteForm['observacoes'] ?? ''); ?></textarea>
    </div>

    <div class="col-12">
      <hr class="my-2" />

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div>
          <div class="fw-semibold">Escala de trabalho</div>
          <div class="small text-muted">Marque os períodos disponíveis e informe a quantidade de horas.</div>
        </div>
        <div class="small text-muted">
          Total selecionado: <span id="totalHorasEscala">0</span>h
        </div>
      </div>

      <?php
          $diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
          $periodos = ['Manhã', 'Tarde', 'Noite'];
          $escalaSelecionada = [];

          foreach (($docenteForm['escala'] ?? []) as $itemEscala) {
              $diaEscala = $itemEscala['dia_semana'] ?? '';
              $periodoEscala = $itemEscala['periodo'] ?? '';

              if ($diaEscala !== '' && $periodoEscala !== '') {
                  $escalaSelecionada[$diaEscala][$periodoEscala] = (int) ($itemEscala['horas'] ?? 0);
              }
          }
      ?>

      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="small text-muted">
            <tr>
              <th>Dia</th>
              <?php foreach ($periodos as $periodo): ?>
              <th><?php echo htmlspecialchars($periodo); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($diasSemana as $dia): ?>
            <tr>
              <td class="fw-semibold"><?php echo htmlspecialchars($dia); ?></td>
              <?php foreach ($periodos as $periodo): ?>
              <?php
                  $horasEscala = (int) ($escalaSelecionada[$dia][$periodo] ?? 0);
                  $campoId = 'escala_' . md5($dia . '_' . $periodo);
              ?>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="form-check mb-0">
                    <input class="form-check-input escala-check" type="checkbox"
                      name="escala[<?php echo htmlspecialchars($dia); ?>][<?php echo htmlspecialchars($periodo); ?>][ativo]"
                      id="<?php echo $campoId; ?>" <?php echo $horasEscala > 0 ? 'checked' : ''; ?>>
                  </div>
                  <input type="number" class="form-control form-control-sm escala-horas"
                    name="escala[<?php echo htmlspecialchars($dia); ?>][<?php echo htmlspecialchars($periodo); ?>][horas]"
                    min="1" max="12" value="<?php echo $horasEscala > 0 ? $horasEscala : ''; ?>"
                    placeholder="h" <?php echo $horasEscala > 0 ? '' : 'disabled'; ?>>
                </div>
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <hr class="my-4" />

  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <a href="/mapa_de_sala/public/?page=docentes" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>

    <button type="submit" class="btn app-btn-primary">
      <i class="bi bi-save"></i> <?php echo $botaoTexto; ?>
    </button>
  </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const totalHorasEscala = document.getElementById("totalHorasEscala");

  function atualizarTotalEscala() {
    let total = 0;

    document.querySelectorAll(".escala-horas").forEach(function(input) {
      if (!input.disabled) {
        total += Number(input.value || 0);
      }
    });

    if (totalHorasEscala) {
      totalHorasEscala.textContent = total;
    }
  }

  document.querySelectorAll(".escala-check").forEach(function(check) {
    const horasInput = check.closest("td").querySelector(".escala-horas");

    function alternarCampoHoras() {
      horasInput.disabled = !check.checked;
      horasInput.required = check.checked;

      if (!check.checked) {
        horasInput.value = "";
      } else if (!horasInput.value) {
        horasInput.value = 4;
      }

      atualizarTotalEscala();
    }

    check.addEventListener("change", alternarCampoHoras);
  });

  document.querySelectorAll(".escala-horas").forEach(function(input) {
    input.addEventListener("input", atualizarTotalEscala);
  });

  atualizarTotalEscala();
});
</script>
