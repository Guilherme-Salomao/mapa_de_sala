<form id="formUsuario" method="POST" action="<?php echo $formAction; ?>" novalidate>
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
      <select class="form-select" id="nivel_acesso" name="nivel_acesso" required>
        <option value="" <?php echo empty($usuarioForm['nivel_acesso']) ? 'selected' : ''; ?> disabled>Selecione...
        </option>
        <option value="Admin" <?php echo(($usuarioForm['nivel_acesso'] ?? '') === 'Admin') ? 'selected' : ''; ?>>Admin
        </option>
        <option value="Gestor" <?php echo(($usuarioForm['nivel_acesso'] ?? '') === 'Gestor') ? 'selected' : ''; ?>>
          Gestor</option>
        <option value="Professor"
          <?php echo(($usuarioForm['nivel_acesso'] ?? '') === 'Professor') ? 'selected' : ''; ?>>Professor</option>
        <option value="Apoio" <?php echo(($usuarioForm['nivel_acesso'] ?? '') === 'Apoio') ? 'selected' : ''; ?>>Apoio
        </option>
      </select>
      <div class="invalid-feedback">
        Selecione o nível de acesso.
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="status" class="form-label">Status</label>
      <select class="form-select" id="status" name="status" required>
        <option value="Ativo" <?php echo(($usuarioForm['status'] ?? 'Ativo') === 'Ativo') ? 'selected' : ''; ?>>Ativo
        </option>
        <option value="Inativo" <?php echo(($usuarioForm['status'] ?? '') === 'Inativo') ? 'selected' : ''; ?>>Inativo
        </option>
      </select>
      <div class="invalid-feedback">
        Selecione o status.
      </div>
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
    <a href="/mapa_de_sala/public/?page=usuarios" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>

    <button type="submit" class="btn app-btn-primary">
      <i class="bi bi-save"></i> <?php echo $botaoTexto; ?>
    </button>
  </div>
</form>