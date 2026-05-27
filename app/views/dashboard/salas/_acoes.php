<?php
    $salaId = (int) ($salaId ?? 0);
?>

<div class="app-actions">

  <a href="/mapa_de_sala/public/?page=salas&action=editar&id=<?php echo $salaId; ?>"
    class="btn btn-sm btn-outline-primary app-action-btn" title="Editar sala">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <form method="POST" action="/mapa_de_sala/public/?page=salas&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir esta sala');">
    <input type="hidden" name="id" value="<?php echo $salaId; ?>">

    <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn" title="Excluir sala">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>

</div>
