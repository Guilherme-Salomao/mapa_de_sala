<?php
    $docenteId = (int) ($docenteId ?? 0);
?>

<div class="app-actions">
  <a href="./?page=docentes&action=editar&id=<?php echo $docenteId; ?>"
    class="btn btn-sm btn-outline-primary app-action-btn" title="Editar docente">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <form method="POST" action="./?page=docentes&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir este docente');">
    <input type="hidden" name="id" value="<?php echo $docenteId; ?>">

    <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn" title="Excluir docente">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
</div>
