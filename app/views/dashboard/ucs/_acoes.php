<?php
    $ucId = (int) ($ucId ?? 0);
?>

<div class="app-actions">
  <a href="./?page=ucs&action=editar&id=<?php echo $ucId; ?>"
    class="btn btn-sm btn-outline-primary app-action-btn" title="Editar UC">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <form method="POST" action="./?page=ucs&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir esta UC');">
    <input type="hidden" name="id" value="<?php echo $ucId; ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn" title="Excluir UC">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
</div>
