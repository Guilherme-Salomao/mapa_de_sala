<form id="formSala" method="POST" action="<?php echo $formAction; ?>" novalidate>
  <?php if ($modoEdicao): ?>
  <input type="hidden" name="id" value="<?php echo (int) ($salaForm['id'] ?? 0); ?>">
  <?php endif; ?>

  <div class="row g-3">

    <div class="col-12 col-md-6">
      <label for="nome" class="form-label">Nome da sala</label>
      <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex.: Laboratório 01" minlength="3"
        value="<?php echo htmlspecialchars($salaForm['nome'] ?? ''); ?>" required />
      <div class="invalid-feedback">
        Informe o nome da sala com no mínimo 3 caracteres.
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="tipo" class="form-label">Tipo da sala</label>
      <select class="form-select" id="tipo" name="tipo_sala" required>
        <option value="" <?php echo empty($salaForm['tipo']) ? 'selected' : ''; ?> disabled>
          Selecione...
        </option>

        <option value="Sala Convencional"
          <?php echo(($salaForm['tipo'] ?? '') === 'Sala Convencional') ? 'selected' : ''; ?>>
          Sala Convencional
        </option>

        <option value="Laboratório de Informática"
          <?php echo(($salaForm['tipo'] ?? '') === 'Laboratório de Informática') ? 'selected' : ''; ?>>
          Laboratório de Informática
        </option>

        <option value="Auditório" <?php echo(($salaForm['tipo'] ?? '') === 'Auditório') ? 'selected' : ''; ?>>
          Auditório
        </option>

        <option value="Sala Teatro" <?php echo(($salaForm['tipo'] ?? '') === 'Sala Teatro') ? 'selected' : ''; ?>>
          Sala Teatro
        </option>

        <option value="Sala Experimental"
          <?php echo(($salaForm['tipo'] ?? '') === 'Sala Experimental') ? 'selected' : ''; ?>>
          Sala Experimental
        </option>

        <option value="Laboratório de Enfermagem"
          <?php echo(($salaForm['tipo'] ?? '') === 'Laboratório de Enfermagem') ? 'selected' : ''; ?>>
          Laboratório de Enfermagem
        </option>

        <option value="Laboratório de Beleza"
          <?php echo(($salaForm['tipo'] ?? '') === 'Laboratório de Beleza') ? 'selected' : ''; ?>>
          Laboratório de Beleza
        </option>
      </select>
      <div class="invalid-feedback">
        Selecione o tipo da sala.
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="capacidade" class="form-label">Capacidade</label>
      <div class="input-group">
        <span class="input-group-text app-input-icon">
          <i class="bi bi-people-fill"></i>
        </span>
        <input type="number" class="form-control" id="capacidade" name="capacidade" placeholder="Quantidade de pessoas"
          min="1" value="<?php echo htmlspecialchars($salaForm['capacidade'] ?? ''); ?>" required />
        <div class="invalid-feedback">
          Informe a capacidade da sala.
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <label for="status" class="form-label">Status</label>
      <select class="form-select" id="status" name="status" required>
        <option value="ativa" <?php echo(in_array(($salaForm['status'] ?? 'ativa'), ['ativa', 'livre', 'uso', 'manutencao'], true)) ? 'selected' : ''; ?>>
          Ativa
        </option>

        <option value="inativa" <?php echo(($salaForm['status'] ?? '') === 'inativa') ? 'selected' : ''; ?>>
          Inativa
        </option>
      </select>
      <div class="invalid-feedback">
        Selecione o status da sala.
      </div>
    </div>

    <div class="col-12">
      <label class="form-label">Recursos da sala</label>

      <div class="row g-2">
        <?php
            $recursosSelecionados = $salaForm['recursos'] ?? [];

            if (is_array($recursosSelecionados)) {
                $recursosNormalizados = [];
                $listaDeIds = array_keys($recursosSelecionados) === range(0, count($recursosSelecionados) - 1);

                foreach ($recursosSelecionados as $chaveRecurso => $valorRecurso) {
                    if ($listaDeIds) {
                        $recursoIdNormalizado = (int) $valorRecurso;
                        $quantidadeNormalizada = 1;
                    } else {
                        $recursoIdNormalizado = (int) $chaveRecurso;
                        $quantidadeNormalizada = (int) $valorRecurso;
                    }

                    if ($recursoIdNormalizado > 0) {
                        $recursosNormalizados[$recursoIdNormalizado] = max(1, $quantidadeNormalizada);
                    }
                }

                $recursosSelecionados = $recursosNormalizados;
            }

            if (! isset($recursosDisponiveis)) {
                $recursosDisponiveis = [];
            }
        ?>

        <?php if (! empty($recursosDisponiveis)): ?>
        <?php foreach ($recursosDisponiveis as $recurso): ?>
        <?php
            $recursoId   = (int) ($recurso['id'] ?? 0);
            $recursoNome = $recurso['nome'] ?? '';

            $recursoMarcado = isset($recursosSelecionados[$recursoId]);
            $checked        = $recursoMarcado ? 'checked' : '';
            $quantidade     = $recursoMarcado ? (int) $recursosSelecionados[$recursoId] : 1;
        ?>

        <div class="col-12 col-md-6">
          <div class="app-check-card">
            <div class="row g-2 align-items-center">
              <div class="col-8">
                <div class="form-check">
                  <input class="form-check-input recurso-check" type="checkbox" name="recursos[]"
                    value="<?php echo $recursoId; ?>" id="recurso_<?php echo $recursoId; ?>"
                    data-target="quantidade_<?php echo $recursoId; ?>" <?php echo $checked; ?>>

                  <label class="form-check-label" for="recurso_<?php echo $recursoId; ?>">
                    <?php echo htmlspecialchars($recursoNome); ?>
                  </label>
                </div>
              </div>

              <div class="col-4">
                <input type="number" class="form-control form-control-sm recurso-quantidade"
                  name="quantidade_recursos[<?php echo $recursoId; ?>]" id="quantidade_<?php echo $recursoId; ?>"
                  min="1" value="<?php echo $quantidade; ?>" <?php echo $recursoMarcado ? '' : 'disabled'; ?>>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="col-12">
          <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle"></i>
            Nenhum recurso cadastrado no banco de dados.
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-12">
      <label for="descricao" class="form-label">Descrição</label>
      <textarea class="form-control" id="descricao" name="descricao" rows="4"
        placeholder="Descreva informações adicionais da sala..."><?php echo htmlspecialchars($salaForm['descricao'] ?? ''); ?></textarea>
    </div>

  </div>

  <hr class="my-4" />

  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <a href="/mapa_de_sala/public/?page=salas" class="btn btn-outline-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>

    <button type="submit" class="btn app-btn-primary">
      <i class="bi bi-save"></i> <?php echo $botaoTexto; ?>
    </button>
  </div>
</form>
