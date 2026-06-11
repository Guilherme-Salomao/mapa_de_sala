<?php
    $ucId = (int) ($ucId ?? 0);
    $queryFiltrosRetorno = http_build_query($filtrosRetorno ?? []);
?>

<div class="app-actions">
  <a href="./?page=ucs&action=editar&id=<?php echo $ucId; ?>&<?php echo htmlspecialchars($queryFiltrosRetorno); ?>"
    class="btn btn-sm btn-outline-primary app-action-btn" title="Editar UC">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <form method="POST" action="./?page=ucs&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir esta UC');">
    <input type="hidden" name="id" value="<?php echo $ucId; ?>">
    <input type="hidden" name="retorno_busca" value="<?php echo htmlspecialchars($filtrosRetorno['retorno_busca'] ?? ''); ?>">
    <input type="hidden" name="retorno_status" value="<?php echo htmlspecialchars($filtrosRetorno['retorno_status'] ?? 'todos'); ?>">
    <input type="hidden" name="retorno_curso_modelo_id" value="<?php echo (int) ($filtrosRetorno['retorno_curso_modelo_id'] ?? 0); ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn" title="Excluir UC">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
</div>
