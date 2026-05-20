<?php
    $statusDocente = $statusDocente ?? 'Ativo';
    $classeBadge   = $statusDocente === 'Ativo' ? 'text-bg-success' : 'text-bg-secondary';
?>

<span class="badge <?php echo $classeBadge; ?>">
  <?php echo htmlspecialchars($statusDocente); ?>
</span>
