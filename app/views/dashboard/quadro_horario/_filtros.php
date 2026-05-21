<form method="GET" action="/mapa_de_sala/public/" class="row g-2 align-items-center">
  <input type="hidden" name="page" value="quadro_horario">

  <div class="col-6 col-md-2">
    <label class="form-label small text-muted mb-1" for="mes">Mês</label>
    <select class="form-select" id="mes" name="mes">
      <?php
          $meses = [
              1 => 'Janeiro',
              2 => 'Fevereiro',
              3 => 'Março',
              4 => 'Abril',
              5 => 'Maio',
              6 => 'Junho',
              7 => 'Julho',
              8 => 'Agosto',
              9 => 'Setembro',
              10 => 'Outubro',
              11 => 'Novembro',
              12 => 'Dezembro',
          ];
      ?>
      <?php foreach ($meses as $numeroMes => $nomeMes): ?>
      <option value="<?php echo $numeroMes; ?>" <?php echo((int) ($mes ?? date('n')) === $numeroMes) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($nomeMes); ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-6 col-md-2">
    <label class="form-label small text-muted mb-1" for="ano">Ano</label>
    <input type="number" class="form-control" id="ano" name="ano" min="2000" max="2100"
      value="<?php echo (int) ($ano ?? date('Y')); ?>">
  </div>

  <div class="col-12 col-md-6">
    <label class="form-label small text-muted mb-1" for="curso_oferta_id">Turma/oferta</label>
    <select class="form-select" id="curso_oferta_id" name="curso_oferta_id" required>
      <option value="0" <?php echo empty($cursoOfertaId) ? 'selected' : ''; ?>>Selecione...</option>
      <?php foreach (($ofertas ?? []) as $oferta): ?>
      <option value="<?php echo (int) $oferta['id']; ?>"
        <?php echo((int) ($cursoOfertaId ?? 0) === (int) $oferta['id']) ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars(($oferta['nome'] ?? '') . ' - ' . ($oferta['codigo_oferta'] ?? '')); ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12 col-md-2 d-flex gap-2 align-self-end">
    <button type="submit" class="btn app-btn-primary w-100">
      <i class="bi bi-calendar-check"></i> Ver
    </button>
  </div>
</form>
