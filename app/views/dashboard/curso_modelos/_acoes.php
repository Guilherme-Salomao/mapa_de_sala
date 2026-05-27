<?php
    $cursoId = (int) ($cursoId ?? 0);
    $nivelUsuario = $_SESSION['usuario']['nivel_acesso'] ?? '';
?>

<div class="app-actions">
  <a href="/mapa_de_sala/public/?page=cursos&action=editar&id=<?php echo $cursoId; ?>"
    class="btn btn-sm btn-outline-primary app-action-btn" title="Editar curso">
    <i class="bi bi-pencil"></i>
    Editar
  </a>

  <?php if ($nivelUsuario !== 'Professor'): ?>
  <form method="POST" action="/mapa_de_sala/public/?page=cursos&action=excluir" class="d-inline"
    onsubmit="return confirm('Deseja realmente excluir este curso');">
    <input type="hidden" name="id" value="<?php echo $cursoId; ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn" title="Excluir curso">
      <i class="bi bi-trash"></i>
      Excluir
    </button>
  </form>
  <?php endif; ?>
</div>
