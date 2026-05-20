<?php
    $statusUc = $statusUc ?? 'Ativa';
    $classeBadge = $statusUc === 'Ativa' ? 'text-bg-success' : 'text-bg-secondary';
?>

<span class="badge <?php echo $classeBadge; ?>">
  <?php echo htmlspecialchars($statusUc); ?>
</span>
