<?php
    $nivelAcesso = $nivelAcesso ?? '';

    $classeNivel = 'text-bg-secondary';

    if ($nivelAcesso === 'Admin') {
    $classeNivel = 'text-bg-danger';
    } elseif ($nivelAcesso === 'Gestor') {
    $classeNivel = 'text-bg-primary';
    } elseif ($nivelAcesso === 'Professor') {
    $classeNivel = 'text-bg-success';
    } elseif ($nivelAcesso === 'Apoio') {
    $classeNivel = 'text-bg-dark';
    }

    $textoNivel = [
        'Gestor' => 'Gestor(a)',
        'Professor' => 'Professor(a)',
    ][$nivelAcesso] ?? $nivelAcesso;
?>

<span class="badge <?php echo $classeNivel; ?>">
  <?php echo htmlspecialchars($textoNivel); ?>
</span>
