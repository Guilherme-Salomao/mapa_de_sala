<?php

    $recursosSala = $recursosSala ?? [];

?>

<?php if (! empty($recursosSala)): ?>
<div class="sala-recursos-list">
  <?php foreach ($recursosSala as $recurso): ?>
  <?php
          if (is_array($recurso)) {
              $nome       = $recurso['nome'] ?? '';
              $quantidade = $recurso['quantidade'] ?? null;
          } else {
              $nome       = $recurso;
              $quantidade = null;
          }
      ?>

  <span class="sala-recurso">
    <?php echo htmlspecialchars($nome); ?>

    <?php if ($quantidade !== null): ?>
    <strong>
      x<?php echo (int) $quantidade; ?>
    </strong>
    <?php endif; ?>
  </span>
  <?php endforeach; ?>
</div>
<?php else: ?>
<span class="text-muted small">
  Sem recursos
</span>
<?php endif; ?>