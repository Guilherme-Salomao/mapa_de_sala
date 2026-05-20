<?php
    $cursoId = (int) ($cursoId ?? 0);
?>

<div class="d-flex justify-content-end gap-2">
  <a href="/mapa_de_sala/public/?page=turmas&action=editar&id=<?php echo $cursoId; ?>"
    class="btn btn-sm btn-outline-primary" title="Editar curso">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <form method="POST" action="/mapa_de_sala/public/?page=turmas&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir este curso?');">
    <input type="hidden" name="id" value="<?php echo $cursoId; ?>">

    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir curso">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
</div>

