<form id="formUsuario" method="POST" action="<?php echo $formAction; ?>" novalidate>
  <?php $perfilProprio = (bool) ($perfilProprio ?? false); ?>
  <?php if ($modoEdicao): ?>
  <input type="hidden" name="id" value="<?php echo (int) ($usuarioForm['id'] ?? 0); ?>">
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-6">
      <label for="nome" class="form-label">Nome</label>
      <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome completo" minlength="3"
        value="<?php echo htmlspecialchars($usuarioForm['nome'] ?? ''); ?>" required />
      <div class="invalid-feedback">
        Informe o nome com no mínimo 3 caracteres.
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="email" class="form-label">E-mail</label>
      <div class="input-group">
        <span class="input-group-text app-input-icon">
          <i class="bi bi-envelope-fill"></i>
        </span>
        <input type="email" class="form-control" id="email" name="email" placeholder="email@exemplo.com"
          value="<?php echo htmlspecialchars($usuarioForm['email'] ?? ''); ?>" required />
        <div class="invalid-feedback">
          Informe um e-mail válido.
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="nivel_acesso" class="form-label">Nível de acesso</label>
      <?php if ($perfilProprio): ?>
      <input type="hidden" name="nivel_acesso" value="<?php echo htmlspecialchars($usuarioForm['nivel_acesso'] ?? ''); ?>">
      <?php endif; ?>
      <select class="form-select" id="nivel_acesso" name="nivel_acesso" required <?php echo $perfilProprio ? 'disabled' : ''; ?>>
        <option value="" <?php echo empty($usuarioForm['nivel_acesso']) ? 'selected' : ''; ?> disabled>Selecione...
        </option>
        <option value="Admin" <?php echo(($usuarioForm['nivel_acesso'] ?? '') === 'Admin') ? 'selected' : ''; ?>>Admin
        </option>
        <option value="Gestor" <?php echo(($usuarioForm['nivel_acesso'] ?? '') === 'Gestor') ? 'selected' : ''; ?>>
          Gestor(a)</option>
        <option value="Professor"
          <?php echo(($usuarioForm['nivel_acesso'] ?? '') === 'Professor') ? 'selected' : ''; ?>>Professor(a)</option>
        <option value="Apoio" <?php echo(($usuarioForm['nivel_acesso'] ?? '') === 'Apoio') ? 'selected' : ''; ?>>Apoio
        </option>
      </select>
      <div class="invalid-feedback">
        Selecione o nível de acesso.
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="status" class="form-label">Status</label>
      <?php if ($perfilProprio): ?>
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($usuarioForm['status'] ?? ''); ?>">
      <?php endif; ?>
      <select class="form-select" id="status" name="status" required <?php echo $perfilProprio ? 'disabled' : ''; ?>>
        <option value="Ativo" <?php echo(($usuarioForm['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : ''; ?>>Ativo
        </option>
        <option value="Inativo" <?php echo(($usuarioForm['status'] ?? '') === 'Inativo') ? 'selected' : ''; ?>>Inativo
        </option>
      </select>
      <div class="invalid-feedback">
        Selecione o status.
      </div>
    </div>

    <div class="col-12 <?php echo $perfilProprio ? 'd-none' : ''; ?>" id="areasUsuarioWrap">
      <label class="form-label">Areas de acesso</label>
      <div class="row g-2">
        <?php foreach (($areas ?? []) as $area): ?>
        <?php $areaId = (int) ($area['id'] ?? 0); ?>
        <div class="col-12 col-md-4 col-lg-3">
          <label class="form-check border rounded p-2 d-flex align-items-center gap-2">
            <input class="form-check-input m-0" type="checkbox" name="areas[]" value="<?php echo $areaId; ?>"
              <?php echo in_array($areaId, $areasUsuario ?? [], true) ? 'checked' : ''; ?>>
            <span><?php echo htmlspecialchars($area['nome'] ?? ''); ?></span>
          </label>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="form-text">Usado para limitar o acesso de Gestor(a) e Apoio por area.</div>
    </div>

    <div class="col-12 col-md-6">
      <label for="senha" class="form-label">
        <?php echo $modoEdicao ? 'Nova senha' : 'Senha'; ?>
      </label>
      <div class="input-group">
        <span class="input-group-text app-input-icon">
          <i class="bi bi-lock-fill"></i>
        </span>
        <input type="password" class="form-control" id="senha" name="senha"
          placeholder="<?php echo $modoEdicao ? 'Crie uma nova senha' : 'Crie uma senha'; ?>" minlength="4"
          <?php echo $modoEdicao ? '' : 'required'; ?> />
        <button class="btn btn-outline-secondary" type="button" id="btnToggleSenha" aria-label="Mostrar/ocultar senha">
          <i class="bi bi-eye"></i>
        </button>
        <div class="invalid-feedback">
          <?php echo $modoEdicao
              ? 'Informe a nova senha com no mínimo 4 caracteres, se desejar alterá-la.'
              : 'Informe uma senha com no mínimo 4 caracteres.'; ?>
        </div>
      </div>
      <?php if ($modoEdicao): ?>
      <div class="form-text">Preencha apenas se desejar alterar a senha.</div>
      <?php endif; ?>
    </div>

    <div class="col-12 col-md-6">
      <label for="confSenha" class="form-label">
        <?php echo $modoEdicao ? 'Confirmar nova senha' : 'Confirmar senha'; ?>
      </label>
      <div class="input-group">
        <span class="input-group-text app-input-icon">
          <i class="bi bi-shield-lock-fill"></i>
        </span>
        <input type="password" class="form-control" id="confSenha" name="confSenha"
          placeholder="<?php echo $modoEdicao ? 'Repita a nova senha' : 'Repita a senha'; ?>" minlength="4"
          <?php echo $modoEdicao ? '' : 'required'; ?> />
        <div class="invalid-feedback">
          <?php echo $modoEdicao ? 'Confirme a nova senha.' : 'Confirme a senha.'; ?>
        </div>
      </div>
      <?php if ($modoEdicao): ?>
      <div class="form-text">Repita a senha somente se desejar alterá-la.</div>
      <?php endif; ?>
    </div>
  </div>

  <hr class="my-4" />

  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <a href="<?php echo $perfilProprio ? '/mapa_de_sala/public/?page=home' : '/mapa_de_sala/public/?page=usuarios'; ?>" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>

    <button type="submit" class="btn app-btn-primary">
      <i class="bi bi-save"></i> <?php echo $botaoTexto; ?>
    </button>
  </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const perfilProprio = <?php echo $perfilProprio ? 'true' : 'false'; ?>;
  const nivel = document.getElementById("nivel_acesso");
  const areasWrap = document.getElementById("areasUsuarioWrap");

  function alternarAreas() {
    if (!nivel || !areasWrap) return;
    if (perfilProprio) return;

    const mostrar = nivel.value === "Gestor" || nivel.value === "Apoio";
    areasWrap.classList.toggle("d-none", !mostrar);
  }

  if (nivel) {
    nivel.addEventListener("change", alternarAreas);
  }

  alternarAreas();
});
</script>
