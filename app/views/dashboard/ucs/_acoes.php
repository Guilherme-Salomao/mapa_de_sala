<?php
    $ucId = (int) ($ucId ?? 0);
?>

<div class="d-flex justify-content-end gap-2">
  <a href="/mapa_de_sala/public/?page=ucs&action=editar&id=<?php echo $ucId; ?>" class="btn btn-sm btn-outline-primary"
    title="Editar UC">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <form method="POST" action="/mapa_de_sala/public/?page=ucs&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir esta UC?');">
    <input type="hidden" name="id" value="<?php echo $ucId; ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir UC">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
</div>
