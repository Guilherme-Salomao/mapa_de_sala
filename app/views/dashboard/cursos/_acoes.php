<?php
    $cursoId = (int) ($cursoId ?? 0);
?>

<div class="d-flex justify-content-end gap-2">
  <a href="/mapa_de_sala/public/?page=turmas&action=editar&id=<?php echo $cursoId; ?>"
    class="btn btn-sm btn-outline-primary" title="Editar turma">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
    data-bs-target="#gerarQuadro_<?php echo $cursoId; ?>" title="Gerar quadro horario">
    <i class="bi bi-calendar-plus"></i>
    Gerar
  </button>

  <form method="POST" action="/mapa_de_sala/public/?page=turmas&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir esta turma?');">
    <input type="hidden" name="id" value="<?php echo $cursoId; ?>">

    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir turma">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
</div>

<div class="modal fade" id="gerarQuadro_<?php echo $cursoId; ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="/mapa_de_sala/public/?page=turmas&action=gerar_quadro">
        <div class="modal-header">
          <h5 class="modal-title">Gerar quadro horário</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo $cursoId; ?>">

          <div class="mb-3 text-start">
            <label class="form-label" for="data_inicio_<?php echo $cursoId; ?>">Início da turma</label>
            <input type="date" class="form-control" id="data_inicio_<?php echo $cursoId; ?>" name="data_inicio" required>
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
            A geração substitui o quadro atual desta turma. Aulas de outras turmas não serão alteradas.
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
