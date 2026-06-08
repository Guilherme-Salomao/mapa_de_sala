<?php $temAulasSemDocente = ! empty($aulasSemDocenteGestor); ?>

<div class="app-card p-3 <?php echo $temAulasSemDocente ? 'border border-danger bg-danger-subtle' : ''; ?>">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <div class="fw-bold <?php echo $temAulasSemDocente ? 'text-danger' : ''; ?>">Aulas sem Docente</div>
      <div class="small text-muted"><?php echo htmlspecialchars($dataHojeFormatada); ?></div>
    </div>
    <span class="badge <?php echo $temAulasSemDocente ? 'text-bg-danger' : 'text-bg-success'; ?> fs-6 px-3 py-2">
      <?php echo count($aulasSemDocenteGestor ?? []); ?>
    </span>
  </div>

  <?php if ($temAulasSemDocente): ?>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="small text-muted">
        <tr>
          <th>Horário</th>
          <th>Turma</th>
          <th>UC</th>
          <th>Sala</th>
          <th class="text-end">Ação</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($aulasSemDocenteGestor as $aulaSemDocente): ?>
        <tr>
          <td class="fw-semibold">
            <?php echo htmlspecialchars(substr((string) ($aulaSemDocente['hora_inicio'] ?? ''), 0, 5)); ?>
            -
            <?php echo htmlspecialchars(substr((string) ($aulaSemDocente['hora_fim'] ?? ''), 0, 5)); ?>
          </td>
          <td>
            <div class="fw-semibold"><?php echo htmlspecialchars($aulaSemDocente['turma_nome'] ?? ''); ?></div>
            <div class="small text-muted">Oferta <?php echo htmlspecialchars($aulaSemDocente['codigo_oferta'] ?? ''); ?></div>
          </td>
          <td>
            <?php if (! empty($aulaSemDocente['uc_codigo']) && ($aulaSemDocente['uc_codigo'] ?? '') !== 'TURMA'): ?>
            <div class="small text-muted"><?php echo htmlspecialchars($aulaSemDocente['uc_codigo']); ?></div>
            <?php endif; ?>
            <?php echo htmlspecialchars($aulaSemDocente['uc_nome'] ?? ''); ?>
          </td>
          <td><?php echo htmlspecialchars($aulaSemDocente['sala_nome'] ?? 'Sala em aberto'); ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary app-action-btn"
              href="./?page=quadro_horario&curso_oferta_id=<?php echo (int) ($aulaSemDocente['curso_oferta_id'] ?? 0); ?>&mes=<?php echo (int) date('n', strtotime($dataHoje)); ?>&ano=<?php echo (int) date('Y', strtotime($dataHoje)); ?>"
              title="Abrir quadro horário">
              <i class="bi bi-calendar-week"></i>
              Abrir quadro
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="text-center text-muted py-4">
    <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
    Todas as aulas desta data possuem docente.
  </div>
  <?php endif; ?>
</div>
