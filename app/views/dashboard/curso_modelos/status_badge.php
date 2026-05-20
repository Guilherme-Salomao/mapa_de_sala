<?php
    $statusCurso = $statusCurso ?? 'Ativo';
    $classeBadge = $statusCurso === 'Ativo' ? 'text-bg-success' : 'text-bg-secondary';
?>

<span class="badge <?php echo $classeBadge; ?>">
  <?php echo htmlspecialchars($statusCurso); ?>
</span>
