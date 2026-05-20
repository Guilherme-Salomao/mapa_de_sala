<?php
    $docenteId = (int) ($docenteId ?? 0);
?>

<div class="d-flex justify-content-end gap-2">
  <a href="/mapa_de_sala/public/?page=docentes&action=editar&id=<?php echo $docenteId; ?>"
    class="btn btn-sm btn-outline-primary" title="Editar docente">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <form method="POST" action="/mapa_de_sala/public/?page=docentes&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir este docente?');">
    <input type="hidden" name="id" value="<?php echo $docenteId; ?>">

    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir docente">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
</div>
