<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $mensagem = $_GET['msg'] ?? '';
    $tipoMsg = $_GET['tipo'] ?? '';
    $salas = $salas ?? [];
    $reservas = $reservas ?? [];
    $aulas = $aulas ?? [];
    $usuarios = $usuarios ?? [];
    $dataInicio = $dataInicio ?? date('Y-m-d');
    $dataFim = $dataFim ?? $dataInicio;
    $salaId = (int) ($salaId ?? 0);
    $tipo = $tipo ?? 'todos';
    $status = $status ?? 'Ativo';

    $tituloPagina = 'Gestão de Salas';
    $subtituloPagina = 'Reserve, coloque em manutenção e reorganize salas';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestão de Salas - SIGHA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />

  <script>
  (function() {
    const tema = localStorage.getItem("tema") || "light";
    document.documentElement.setAttribute("data-bs-theme", tema);
  })();
  </script>
</head>

<body>
  <?php require_once __DIR__ . '/../layouts/header.php'; ?>

  <main class="flex-grow-1">
    <div class="container-fluid">
      <div class="row g-0">
        <?php
            $paginaAtiva = 'gestao_salas';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <?php if (! empty($mensagem)): ?>
          <div class="alert <?php echo $tipoMsg === 'sucesso' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
          <?php endif; ?>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="/mapa_de_sala/public/" class="row g-2 align-items-end">
              <input type="hidden" name="page" value="gestao_salas">
              <div class="col-6 col-lg-2">
                <label class="form-label">Data inicial</label>
                <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($dataInicio); ?>">
              </div>
              <div class="col-6 col-lg-2">
                <label class="form-label">Data final</label>
                <input type="date" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($dataFim); ?>">
              </div>
              <div class="col-12 col-lg-3">
                <label class="form-label">Sala</label>
                <select name="sala_id" class="form-select">
                  <option value="0">Todas</option>
                  <?php foreach ($salas as $sala): ?>
                  <option value="<?php echo (int) $sala['id']; ?>" <?php echo $salaId === (int) $sala['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($sala['nome'] ?? ''); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-lg-2">
                <label class="form-label">Tipo</label>
                <select name="tipo_reserva" class="form-select">
                  <?php foreach (['todos' => 'Todos', 'Reservada' => 'Reservada', 'Manutenção' => 'Manutenção'] as $valor => $label): ?>
                  <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo $tipo === $valor ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <option value="Ativo" <?php echo $status === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                  <option value="Inativo" <?php echo $status === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
              </div>
              <div class="col-12 col-lg-1 d-grid">
                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </form>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
              <div class="app-card p-3 h-100">
                <div class="fw-bold mb-3">Reservar ou Manutenção</div>
                <form method="POST" action="/mapa_de_sala/public/?page=gestao_salas&action=salvar" class="row g-2">
                  <div class="col-12 col-md-7">
                    <label class="form-label">Sala</label>
                    <select name="sala_id" class="form-select" required>
                      <option value="">Selecione...</option>
                      <?php foreach ($salas as $sala): ?>
                      <option value="<?php echo (int) $sala['id']; ?>"><?php echo htmlspecialchars($sala['nome'] ?? ''); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12 col-md-5">
                    <label class="form-label">Tipo</label>
                    <select name="tipo_reserva" class="form-select" required>
                      <?php foreach (['Reservada' => 'Reservada', 'Manutenção' => 'Manutenção'] as $valor => $label): ?>
                      <option value="<?php echo htmlspecialchars($valor); ?>"><?php echo htmlspecialchars($label); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Data inicial</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($dataInicio); ?>" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Data final</label>
                    <input type="date" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($dataFim); ?>" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Hora início</label>
                    <input type="time" name="hora_inicio" class="form-control" required>
                  </div>
                  <div class="col-6">
                    <label class="form-label">Hora fim</label>
                    <input type="time" name="hora_fim" class="form-control" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Solicitante</label>
                    <select name="solicitante_usuario_id" class="form-select" required>
                      <option value="">Selecione...</option>
                      <?php foreach ($usuarios as $usuario): ?>
                      <option value="<?php echo (int) $usuario['id']; ?>">
                        <?php echo htmlspecialchars(($usuario['nome'] ?? '') . ' - ' . ($usuario['nivel_acesso'] ?? '')); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Motivo</label>
                    <input type="text" name="motivo" class="form-control" maxlength="150" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Observação</label>
                    <textarea name="descricao" class="form-control" rows="2"></textarea>
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn app-btn-primary">
                      <i class="bi bi-save"></i> Salvar
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <div class="col-12 col-xl-6">
              <div class="app-card p-3 h-100">
                <div class="fw-bold mb-3">Trocar Sala</div>
                <form method="POST" action="/mapa_de_sala/public/?page=gestao_salas&action=trocar_sala" class="row g-2">
                  <div class="col-12">
                    <label class="form-label">Aula</label>
                    <select name="aula_id" class="form-select" required>
                      <option value="">Selecione...</option>
                      <?php foreach ($aulas as $aula): ?>
                      <option value="<?php echo (int) $aula['id']; ?>">
                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($aula['data_aula'])) . ' ' . substr($aula['hora_inicio'], 0, 5) . ' - ' . substr($aula['hora_fim'], 0, 5) . ' | ' . ($aula['sala_nome'] ?? 'Sala') . ' | ' . ($aula['turma_nome'] ?? '')); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Sala destino</label>
                    <select name="sala_destino_id" class="form-select" required>
                      <option value="">Selecione...</option>
                      <?php foreach ($salas as $sala): ?>
                      <option value="<?php echo (int) $sala['id']; ?>"><?php echo htmlspecialchars($sala['nome'] ?? ''); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-check">
                      <input class="form-check-input" type="checkbox" name="permitir_permuta" value="1">
                      <span class="form-check-label">Permitir permuta se a sala destino estiver ocupada no mesmo horário</span>
                    </label>
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn app-btn-primary">
                      <i class="bi bi-arrow-left-right"></i> Trocar
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="app-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-bold">Reservas e manuten??es</div>
              <div class="small text-muted"><?php echo count($reservas); ?> registro(s)</div>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Sala</th>
                    <th>Tipo</th>
                    <th>Período</th>
                    <th>Solicitante</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($reservas)): ?>
                  <?php foreach ($reservas as $reserva): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($reserva['sala_nome'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(($reserva['tipo'] ?? '') === 'Manutenção' ? 'Manutenção' : ($reserva['tipo'] ?? '')); ?></td>
                    <td>
                      <?php echo htmlspecialchars(date('d/m/Y', strtotime($reserva['data_inicio'])) . ' até ' . date('d/m/Y', strtotime($reserva['data_fim']))); ?>
                      <div class="small text-muted"><?php echo htmlspecialchars(substr($reserva['hora_inicio'], 0, 5) . ' - ' . substr($reserva['hora_fim'], 0, 5)); ?></div>
                    </td>
                    <td>
                      <?php echo htmlspecialchars($reserva['solicitante_nome'] ?? ($reserva['solicitante'] ?? '')); ?>
                      <?php if (! empty($reserva['motivo'])): ?>
                      <div class="small fw-semibold"><?php echo htmlspecialchars($reserva['motivo']); ?></div>
                      <?php endif; ?>
                      <?php if (! empty($reserva['descricao'])): ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($reserva['descricao']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($reserva['status'] ?? ''); ?></td>
                    <td class="text-end">
                      <?php if (($reserva['status'] ?? '') === 'Ativo'): ?>
                      <form method="POST" action="/mapa_de_sala/public/?page=gestao_salas&action=excluir" class="app-actions">
                        <input type="hidden" name="id" value="<?php echo (int) $reserva['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn">
                          <i class="bi bi-x-circle"></i> Inativar
                        </button>
                      </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">Nenhum registro encontrado.</td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>


