<?php
    $salaId = (int) ($salaId ?? 0);
?>

<div class="d-flex justify-content-end gap-2">

  <a href="/mapa_de_sala/public/?page=salas&action=editar&id=<?php echo $salaId; ?>"
    class="btn btn-sm btn-outline-primary" title="Editar sala">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <form method="POST" action="/mapa_de_sala/public/?page=salas&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir esta sala?');">
    <input type="hidden" name="id" value="<?php echo $salaId; ?>">

    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir sala">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>

</div>