<div class="app-card p-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <div class="fw-bold"><?php echo htmlspecialchars($tituloMapaGestor ?? 'Mapa de sala'); ?></div>
    </div>
  </div>

  <div class="row g-3">
    <?php foreach ($aulasPorTurno as $turno => $aulasTurno): ?>
    <?php
        $iconeTurno = [
            'Manhã' => 'bi-sunrise',
            'Tarde' => 'bi-sun',
            'Noite' => 'bi-moon-stars',
        ][$turno] ?? 'bi-calendar';
    ?>
    <div class="col-12 col-xl-4">
      <div class="border rounded p-3 h-100">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
          <div class="d-flex align-items-center gap-2 fw-bold">
            <i class="bi <?php echo htmlspecialchars($iconeTurno); ?>"></i>
            <?php echo htmlspecialchars($turno); ?>
          </div>
          <span class="badge text-bg-primary fs-6 px-3 py-2">
            <?php echo count($aulasTurno); ?> sala(s)
          </span>
        </div>

        <?php if (! empty($aulasTurno)): ?>
        <div class="d-grid gap-2">
          <?php foreach ($aulasTurno as $aula): ?>
          <div class="border rounded p-2">
            <div class="fw-semibold"><?php echo ! empty($aula['sala_nome']) ? htmlspecialchars($aula['sala_nome'])  : 'Sala em aberto'; ?></div>
            <div><?php echo htmlspecialchars($aula['turma_nome'] ?? ''); ?></div>
            <?php if (! empty($aula['ucs_mapa'])): ?>
            <div class="small fw-semibold">
              <?php echo htmlspecialchars((string) $aula['ucs_mapa']); ?>
            </div>
            <?php endif; ?>
            <?php if (! empty($aula['tipo_reserva'])): ?>
            <div class="my-1">
              <span class="badge text-bg-<?php echo ($aula['tipo_reserva'] ?? '') === 'Manutenção' ? 'danger' : 'primary'; ?>">
                <?php echo ($aula['tipo_reserva'] ?? '') === 'Manutenção' ? 'Manutenção' : 'Reservada'; ?>
              </span>
            </div>
            <?php endif; ?>
            <?php if ((int) ($aula['visita_tecnica'] ?? 0) === 1): ?>
            <div class="my-1">
              <span class="badge text-bg-info">Visita Técnica</span>
            </div>
            <?php endif; ?>
            <?php if ((int) ($aula['ead_assincrona'] ?? 0) === 1): ?>
            <div class="my-1">
              <span class="badge text-bg-secondary">EAD/Assíncrona</span>
            </div>
            <?php endif; ?>
            <?php if (! empty($aula['aprendizagem_quadro_id'])): ?>
            <div class="my-1">
              <span class="badge text-bg-warning">Aceleração</span>
            </div>
            <?php endif; ?>
            <?php if (! empty($aula['docentes'])): ?>
            <div class="small text-muted">Docente: <?php echo htmlspecialchars($aula['docentes']); ?></div>
            <?php endif; ?>
            <?php if (! empty($aula['solicitante_nome'])): ?>
            <div class="small text-muted fw-semibold">Solicitante: <?php echo htmlspecialchars($aula['solicitante_nome']); ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-4">
          Nenhuma aula neste turno.
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
