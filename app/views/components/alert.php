<?php if (! empty($mensagem)): ?>
<?php
    $mensagemAlerta = (string) $mensagem;
    $classeAlerta = 'alert-danger';
    $iconeAlerta  = 'bi-exclamation-triangle';

    if (($tipo ?? '') === 'sucesso') {
        $classeAlerta = 'alert-success';
        $iconeAlerta  = 'bi-check-circle';
    } elseif (($tipo ?? '') === 'aviso') {
        $classeAlerta = 'alert-warning';
        $iconeAlerta  = 'bi-exclamation-circle';
    } elseif (($tipo ?? '') === 'info') {
        $classeAlerta = 'alert-info';
        $iconeAlerta  = 'bi-info-circle';
    }
?>
<div class="alert <?php echo $classeAlerta; ?>" role="alert">
  <i class="bi <?php echo $iconeAlerta; ?>"></i>
  <?php echo htmlspecialchars($mensagemAlerta, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>
