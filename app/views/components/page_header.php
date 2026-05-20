<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><?php echo htmlspecialchars($tituloPagina ?? ''); ?></h4>
    <div class="small text-muted">
      <?php echo htmlspecialchars($subtituloPagina ?? ''); ?>
    </div>
  </div>

  <?php if (! empty($botaoTopoTexto) && ! empty($botaoTopoLink)): ?>
  <div class="d-flex gap-2">
    <a href="<?php echo htmlspecialchars($botaoTopoLink); ?>"
      class="btn btn-sm <?php echo htmlspecialchars($botaoTopoClasse ?? 'btn-outline-secondary'); ?>">
      <?php if (! empty($botaoTopoIcone)): ?>
      <i class="bi <?php echo htmlspecialchars($botaoTopoIcone); ?>"></i>
      <?php endif; ?>
      <?php echo htmlspecialchars($botaoTopoTexto); ?>
    </a>
  </div>
  <?php endif; ?>
</div>