<?php
    $cursoId = (int) ($cursoId ?? 0);
    $cursoModeloId = (int) ($curso['curso_modelo_id'] ?? 0);
    $ucsTurma = $ucsPorCursoModelo[$cursoModeloId] ?? [];
?>

<div class="app-actions">
  <a href="./?page=turmas&action=editar&id=<?php echo $cursoId; ?>"
    class="btn btn-sm btn-outline-primary app-action-btn" title="Editar turma">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <button type="button" class="btn btn-sm btn-outline-success app-action-btn" data-bs-toggle="modal"
    data-bs-target="#gerarQuadro_<?php echo $cursoId; ?>" title="Gerar quadro horário">
    <i class="bi bi-calendar-plus"></i>
    Gerar
  </button>

  <form method="POST" action="./?page=turmas&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir esta turma');">
    <input type="hidden" name="id" value="<?php echo $cursoId; ?>">

    <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn" title="Excluir turma">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
</div>

<div class="modal fade" id="gerarQuadro_<?php echo $cursoId; ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="./?page=turmas&action=gerar_quadro">
        <div class="modal-header">
          <h5 class="modal-title">Gerar quadro horário</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo $cursoId; ?>">

          <div class="mb-3 text-start">
            <label class="form-label" for="modo_geracao_<?php echo $cursoId; ?>">Tipo de geração</label>
            <select class="form-select js-modo-geracao" id="modo_geracao_<?php echo $cursoId; ?>" name="modo_geracao"
              data-modal-id="<?php echo $cursoId; ?>">
              <option value="completo">Gerar quadro completo</option>
              <option value="uc_dia">Gerar uma UC em dias da semana</option>
            </select>
          </div>

          <div class="mb-3 text-start">
            <label class="form-label" for="data_inicio_<?php echo $cursoId; ?>">Data inicial</label>
            <input type="date" class="form-control" id="data_inicio_<?php echo $cursoId; ?>" name="data_inicio" required>
          </div>

          <div class="js-gerar-uc-campos d-none" data-modal-id="<?php echo $cursoId; ?>">
            <div class="mb-3 text-start">
              <label class="form-label" for="unidade_curricular_id_<?php echo $cursoId; ?>">Unidade Curricular</label>
              <select class="form-select js-gerar-uc-input" id="unidade_curricular_id_<?php echo $cursoId; ?>"
                name="unidade_curricular_id" disabled>
                <option value="">Selecione...</option>
                <?php foreach ($ucsTurma as $ucTurma): ?>
                <option value="<?php echo (int) ($ucTurma['id'] ?? 0); ?>">
                  <?php echo htmlspecialchars(($ucTurma['codigo'] ?? '') . ' - ' . ($ucTurma['nome'] ?? '') . ' (' . (int) ($ucTurma['carga_horaria'] ?? 0) . 'h)'); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3 text-start">
              <span class="form-label d-block">Dias da semana</span>
              <div class="d-flex flex-wrap gap-2">
                <label class="form-check app-inline-check mb-0">
                  <input class="form-check-input js-gerar-uc-input js-gerar-dia-semana" type="checkbox"
                    name="dias_semana[]" value="1" disabled>
                  <span class="form-check-label">Segunda</span>
                </label>
                <label class="form-check app-inline-check mb-0">
                  <input class="form-check-input js-gerar-uc-input js-gerar-dia-semana" type="checkbox"
                    name="dias_semana[]" value="2" disabled>
                  <span class="form-check-label">Terça</span>
                </label>
                <label class="form-check app-inline-check mb-0">
                  <input class="form-check-input js-gerar-uc-input js-gerar-dia-semana" type="checkbox"
                    name="dias_semana[]" value="3" disabled>
                  <span class="form-check-label">Quarta</span>
                </label>
                <label class="form-check app-inline-check mb-0">
                  <input class="form-check-input js-gerar-uc-input js-gerar-dia-semana" type="checkbox"
                    name="dias_semana[]" value="4" disabled>
                  <span class="form-check-label">Quinta</span>
                </label>
                <label class="form-check app-inline-check mb-0">
                  <input class="form-check-input js-gerar-uc-input js-gerar-dia-semana" type="checkbox"
                    name="dias_semana[]" value="5" disabled>
                  <span class="form-check-label">Sexta</span>
                </label>
                <label class="form-check app-inline-check mb-0">
                  <input class="form-check-input js-gerar-uc-input js-gerar-dia-semana" type="checkbox"
                    name="dias_semana[]" value="6" disabled>
                  <span class="form-check-label">Sábado</span>
                </label>
              </div>
            </div>
          </div>

          <div class="mb-3 text-start">
            <label class="form-label" for="sala_id_<?php echo $cursoId; ?>">Sala preferencial</label>
            <select class="form-select" id="sala_id_<?php echo $cursoId; ?>" name="sala_id">
              <option value="">Sem sala preferencial</option>
              <?php foreach (($salas ?? []) as $sala): ?>
              <option value="<?php echo (int) $sala['id']; ?>">
                <?php echo htmlspecialchars(($sala['nome'] ?? '') . ' - ' . ($sala['tipo'] ?? '')); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="alert alert-warning mb-0 text-start">
            A geração respeita aulas já lançadas, calendário, reserva de salas e dias de aula da turma.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn app-btn-primary">
            <i class="bi bi-calendar-plus"></i> Gerar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
