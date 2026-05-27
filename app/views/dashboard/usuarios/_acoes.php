<div class="app-actions">
  <a href="/mapa_de_sala/public/?page=usuarios&action=editar&id=<?php echo $usuarioId; ?>"
    class="btn btn-sm btn-outline-primary app-action-btn">
    <i class="bi bi-pencil"></i> Editar
  </a>

  <form method="POST" action="/mapa_de_sala/public/?page=usuarios&action=excluir"
    onsubmit="return confirm('Deseja realmente excluir este usuário');">
    <input type="hidden" name="id" value="<?php echo $usuarioId; ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn">
      <i class="bi bi-trash"></i> Excluir
    </button>
  </form>
</div>
