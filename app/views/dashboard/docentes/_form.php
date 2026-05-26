<?php
    $somenteVinculosUc = $somenteVinculosUc ?? false;
    $cadastroProprioDocente = $cadastroProprioDocente ?? false;
?>

<form id="formDocente" method="POST" action="<?php echo $formAction; ?>" novalidate>
  <?php if ($modoEdicao): ?>
  <input type="hidden" name="id" value="<?php echo (int) ($docenteForm['id'] ?? 0); ?>">
  <?php endif; ?>

  <div class="row g-3">
    <?php if (! $somenteVinculosUc): ?>
    <div class="col-12 col-lg-5">
      <label for="usuario_id" class="form-label">Usuario vinculado</label>
      <?php if ($cadastroProprioDocente): ?>
      <input type="hidden" name="usuario_id" value="<?php echo (int) ($docenteForm['usuario_id'] ?? 0); ?>">
      <input type="text" class="form-control" id="usuario_id"
        value="<?php echo htmlspecialchars(($docenteForm['usuario_nome'] ?? '') . ' - ' . ($docenteForm['usuario_email'] ?? '')); ?>"
        disabled>
      <?php else: ?>
      <select class="form-select" id="usuario_id" name="usuario_id" required>
        <option value="" <?php echo empty($docenteForm['usuario_id']) ? 'selected' : ''; ?> disabled>
          Selecione...
        </option>

        <?php foreach (($usuariosDisponiveis ?? []) as $usuario): ?>
        <?php
            $usuarioId = (int) ($usuario['id'] ?? 0);
            $selected = ((int) ($docenteForm['usuario_id'] ?? 0) === $usuarioId) ? 'selected' : '';
        ?>
        <option value="<?php echo $usuarioId; ?>" <?php echo $selected; ?>>
          <?php echo htmlspecialchars(($usuario['nome'] ?? '') . ' - ' . ($usuario['email'] ?? '')); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="invalid-feedback">Selecione o usuario vinculado ao docente.</div>
      <?php if (empty($usuariosDisponiveis)): ?>
      <div class="form-text text-danger">Cadastre um usuario ativo com nivel Professor antes de criar o docente.</div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
      <label for="area_atuacao" class="form-label">Area de atuacao</label>
      <?php if ($cadastroProprioDocente): ?>
      <input type="hidden" name="area_atuacao" value="<?php echo htmlspecialchars($docenteForm['area_atuacao'] ?? ''); ?>">
      <input type="text" class="form-control" id="area_atuacao"
        value="<?php echo htmlspecialchars($docenteForm['area_atuacao'] ?? ''); ?>" disabled>
      <?php else: ?>
      <select class="form-select" id="area_atuacao" name="area_atuacao" required>
        <option value="" <?php echo empty($docenteForm['area_atuacao']) ? 'selected' : ''; ?> disabled>
          Selecione...
        </option>
        <?php foreach (($areas ?? []) as $area): ?>
        <option value="<?php echo htmlspecialchars($area['nome'] ?? ''); ?>"
          <?php echo (($docenteForm['area_atuacao'] ?? '') === ($area['nome'] ?? '')) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($area['nome'] ?? ''); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="invalid-feedback">Selecione a area de atuacao.</div>
      <?php endif; ?>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
      <label for="status" class="form-label">Status</label>
      <?php if ($cadastroProprioDocente): ?>
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($docenteForm['status'] ?? 'Ativo'); ?>">
      <input type="text" class="form-control" id="status"
        value="<?php echo htmlspecialchars($docenteForm['status'] ?? 'Ativo'); ?>" disabled>
      <?php else: ?>
      <select class="form-select" id="status" name="status" required>
        <option value="Ativo" <?php echo (($docenteForm['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : ''; ?>>
          Ativo
        </option>
        <option value="Inativo" <?php echo (($docenteForm['status'] ?? '') === 'Inativo') ? 'selected' : ''; ?>>
          Inativo
        </option>
      </select>
      <div class="invalid-feedback">Selecione o status.</div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (! $somenteVinculosUc): ?>
    <div class="col-12">
      <label for="observacoes" class="form-label">Observacoes</label>
      <textarea class="form-control" id="observacoes" name="observacoes" rows="3"
        placeholder="Informacoes adicionais sobre o docente..."><?php echo htmlspecialchars($docenteForm['observacoes'] ?? ''); ?></textarea>
    </div>

    <div class="col-12">
      <hr class="my-2" />

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div>
          <div class="fw-semibold">Escala de trabalho</div>
          <div class="small text-muted">Marque os periodos disponiveis e informe a quantidade de horas.</div>
        </div>
        <div class="small text-muted">
          Horas semanais: <span id="totalHorasEscala">0</span>h
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
    <?php endif; ?>

    <div class="col-12">
      <hr class="my-2" />

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div class="fw-semibold">UCs que o docente pode atuar</div>
        <div class="small text-muted">
          Selecionadas: <span id="totalUcsSelecionadas">0</span>
        </div>
      </div>

      <div class="row g-2 align-items-end mb-3">
        <div class="col-12 col-md-5">
          <label for="filtroCursoUc" class="form-label">Curso</label>
          <select class="form-select" id="filtroCursoUc">
            <option value="">Selecione o curso...</option>
            <?php foreach (($cursoModelos ?? []) as $cursoModelo): ?>
            <option value="<?php echo (int) $cursoModelo['id']; ?>">
              <?php echo htmlspecialchars(($cursoModelo['nome'] ?? '') . (! empty($cursoModelo['area_nome']) ? ' - ' . $cursoModelo['area_nome'] : '')); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-5">
          <label for="filtroUc" class="form-label">UC</label>
          <select class="form-select" id="filtroUc" disabled>
            <option value="">Selecione a UC...</option>
            <?php foreach (($unidadesCurriculares ?? []) as $uc): ?>
            <option value="<?php echo (int) $uc['id']; ?>"
              data-curso-id="<?php echo (int) ($uc['curso_modelo_id'] ?? 0); ?>"
              data-label="<?php echo htmlspecialchars(($uc['codigo'] ?? '') . ' - ' . ($uc['nome'] ?? '') . ' - ' . ($uc['curso_nome'] ?? '')); ?>">
              <?php echo htmlspecialchars(($uc['codigo'] ?? '') . ' - ' . ($uc['nome'] ?? '')); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-2">
          <button type="button" class="btn app-btn-primary w-100" id="btnAdicionarUc">
            <i class="bi bi-plus-circle"></i> UC
          </button>
        </div>

        <div class="col-12">
          <button type="button" class="btn btn-outline-primary" id="btnAdicionarCursoUc" disabled>
            <i class="bi bi-check2-square"></i> Adicionar todas as UCs do curso
          </button>
        </div>
      </div>

      <div class="border rounded p-3">
        <div id="ucsSelecionadasLista" class="d-flex flex-column gap-2"></div>
        <div id="ucsSelecionadasVazio" class="text-muted small">Nenhuma UC vinculada.</div>
      </div>
    </div>
  </div>

  <hr class="my-4" />

  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <a href="<?php echo ($somenteVinculosUc || $cadastroProprioDocente) ? '/mapa_de_sala/public/?page=home' : '/mapa_de_sala/public/?page=docentes'; ?>"
      class="btn btn-outline-secondary">
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
  const cursoSelect = document.getElementById("filtroCursoUc");
  const ucSelect = document.getElementById("filtroUc");
  const btnAdicionarUc = document.getElementById("btnAdicionarUc");
  const btnAdicionarCursoUc = document.getElementById("btnAdicionarCursoUc");
  const listaUcs = document.getElementById("ucsSelecionadasLista");
  const vazioUcs = document.getElementById("ucsSelecionadasVazio");
  const totalUcsSelecionadas = document.getElementById("totalUcsSelecionadas");
  const ucsSelecionadas = new Map();
  const ucsIniciais = <?php echo json_encode(array_map('intval', $docenteForm['unidades_curriculares'] ?? [])); ?>;

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

  function atualizarFiltroUc() {
    if (!cursoSelect || !ucSelect) return;

    const cursoId = cursoSelect.value;
    ucSelect.disabled = !cursoId;
    if (btnAdicionarCursoUc) {
      btnAdicionarCursoUc.disabled = !cursoId;
    }
    ucSelect.value = "";

    ucSelect.querySelectorAll("option").forEach(function(option) {
      if (!option.value) return;
      option.hidden = option.dataset.cursoId !== cursoId;
    });
  }

  function renderizarUcs() {
    if (!listaUcs || !vazioUcs) return;

    listaUcs.innerHTML = "";
    vazioUcs.classList.toggle("d-none", ucsSelecionadas.size > 0);

    ucsSelecionadas.forEach(function(label, id) {
      const item = document.createElement("div");
      item.className = "d-flex align-items-center justify-content-between gap-2 border rounded px-2 py-2";
      item.innerHTML = `
        <div class="small">${label}</div>
        <div class="d-flex align-items-center gap-2">
          <input type="hidden" name="unidades_curriculares[]" value="${id}">
          <button type="button" class="btn btn-sm btn-outline-danger" data-remover-uc="${id}" title="Remover UC">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      `;
      listaUcs.appendChild(item);
    });

    if (totalUcsSelecionadas) {
      totalUcsSelecionadas.textContent = ucsSelecionadas.size;
    }
  }

  function adicionarUc(id, label) {
    if (!id || !label || ucsSelecionadas.has(String(id))) return;
    ucsSelecionadas.set(String(id), label);
    renderizarUcs();
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

  if (cursoSelect) {
    cursoSelect.addEventListener("change", atualizarFiltroUc);
  }

  if (btnAdicionarUc && ucSelect) {
    btnAdicionarUc.addEventListener("click", function() {
      const option = ucSelect.selectedOptions[0];
      if (!option || !option.value) return;
      adicionarUc(option.value, option.dataset.label || option.textContent.trim());
    });
  }

  if (btnAdicionarCursoUc && cursoSelect && ucSelect) {
    btnAdicionarCursoUc.addEventListener("click", function() {
      const cursoId = cursoSelect.value;
      if (!cursoId) return;

      ucSelect.querySelectorAll("option").forEach(function(option) {
        if (!option.value || option.dataset.cursoId !== cursoId) return;
        adicionarUc(option.value, option.dataset.label || option.textContent.trim());
      });
    });
  }

  if (listaUcs) {
    listaUcs.addEventListener("click", function(e) {
      const botao = e.target.closest("[data-remover-uc]");
      if (!botao) return;
      ucsSelecionadas.delete(String(botao.dataset.removerUc));
      renderizarUcs();
    });
  }

  if (ucSelect) {
    ucsIniciais.forEach(function(id) {
      const option = ucSelect.querySelector(`option[value="${id}"]`);
      if (option) {
        adicionarUc(id, option.dataset.label || option.textContent.trim());
      }
    });
  }

  atualizarFiltroUc();
  atualizarTotalEscala();
  renderizarUcs();
});
</script>
