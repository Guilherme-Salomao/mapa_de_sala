<?php
    $statusCurso = $statusCurso ?? 'Em andamento';
    $classeBadge = $statusCurso === 'Em andamento' ? 'text-bg-success' : 'text-bg-secondary';
?>

<span class="badge <?php echo $classeBadge; ?>">
  <?php echo htmlspecialchars($statusCurso); ?>
</span>
