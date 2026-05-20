<?php
    $status = $status ?? '';

    $classeStatus = 'text-bg-secondary';

    if ($status === 'Ativo') {
    $classeStatus = 'text-bg-success';
    } elseif ($status === 'Inativo') {
    $classeStatus = 'text-bg-secondary';
    }
?>

<span class="badge <?php echo $classeStatus; ?>">
  <?php echo htmlspecialchars($status); ?>
</span>