<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $mensagem = $_GET['msg'] ?? '';
    $tipoMsg = $_GET['tipo'] ?? '';
    $busca = $busca ?? ($_GET['busca'] ?? '');
    $status = $status ?? ($_GET['status'] ?? 'todos');
    $bloqueios = $bloqueios ?? [];
    $bloqueioForm = $bloqueioForm ?? null;

    $formEdicao = ! empty($bloqueioForm);
    $formAction = $formEdicao ? './?page=calendario&action=atualizar'
        : './?page=calendario&action=salvar';

    $formData = $bloqueioForm['data'] ?? ($_GET['data'] ?? '');
    $formDataFim = $bloqueioForm['data_fim'] ?? ($_GET['data_fim'] ?? '');
    $formTitulo = $bloqueioForm['titulo'] ?? ($_GET['titulo'] ?? '');
    $formTipo = $bloqueioForm['tipo'] ?? ($_GET['tipo_bloqueio'] ?? 'Feriado');
    $formDescricao = $bloqueioForm['descricao'] ?? ($_GET['descricao'] ?? '');
    $formStatus = $bloqueioForm['status'] ?? ($_GET['status_bloqueio'] ?? 'Ativo');

    $tituloPagina = 'Calendario';
    $subtituloPagina = 'Cadastre feriados, recessos e paradas pedagogicas';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';

    function labelTipoBloqueio(string $tipo): string
    {
        return $tipo === 'Parada Pedagogica' ? 'Parada Pedagógica' : $tipo;
    }
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Calendario - SIGHA</title>

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
            $paginaAtiva = 'calendario';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <?php if (! empty($mensagem)): ?>
          <div class="alert <?php echo $tipoMsg === 'sucesso' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <i class="bi <?php echo $tipoMsg === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
          <?php endif; ?>

          <div class="app-card p-3 mb-3">
            <form method="POST" action="<?php echo htmlspecialchars($formAction); ?>" class="row g-2 align-items-end">
              <?php if ($formEdicao): ?>
              <input type="hidden" name="id" value="<?php echo (int) $bloqueioForm['id']; ?>">
              <?php endif; ?>

              <div class="col-12 col-md-2">
                <label class="form-label">Data inicial</label>
                <input type="date" name="data" class="form-control"
                  value="<?php echo htmlspecialchars($formData); ?>" required>
              </div>

              <div class="col-12 col-md-2 calendario-data-fim">
                <label class="form-label">Data final</label>
                <input type="date" name="data_fim" class="form-control"
                  value="<?php echo htmlspecialchars($formDataFim); ?>">
              </div>

              <div class="col-12 col-md-3">
                <label class="form-label">Titulo</label>
                <input type="text" name="titulo" class="form-control" maxlength="150"
                  value="<?php echo htmlspecialchars($formTitulo); ?>" required>
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" required>
                  <?php foreach (['Feriado', 'Recesso', 'Parada Pedagogica'] as $tipoOpcao): ?>
                  <option value="<?php echo $tipoOpcao; ?>" <?php echo $formTipo === $tipoOpcao ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(labelTipoBloqueio($tipoOpcao)); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                  <option value="Ativo" <?php echo $formStatus === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                  <option value="Inativo" <?php echo $formStatus === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Descricao</label>
                <textarea name="descricao" class="form-control" rows="2"><?php echo htmlspecialchars($formDescricao); ?></textarea>
              </div>

              <div class="col-12 d-flex gap-2 justify-content-end">
                <?php if ($formEdicao): ?>
                <a href="./?page=calendario" class="btn btn-outline-secondary">
                  <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <?php endif; ?>

                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-save"></i>
                  <?php echo $formEdicao ? 'Salvar Alteracao' : 'Salvar Data'; ?>
                </button>
              </div>
            </form>
          </div>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-center">
              <input type="hidden" name="page" value="calendario">

              <div class="col-12 col-md-7">
                <div class="input-group">
                  <span class="input-group-text app-input-icon">
                    <i class="bi bi-search"></i>
                  </span>
                  <input type="text" name="busca" class="form-control" placeholder="Buscar por titulo ou descricao..."
                    value="<?php echo htmlspecialchars($busca); ?>">
                </div>
              </div>

              <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                  <option value="todos" <?php echo $status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                  <option value="Ativo" <?php echo $status === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                  <option value="Inativo" <?php echo $status === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
              </div>

              <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn app-btn-primary w-100">
                  <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="./?page=calendario" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </div>
            </form>
          </div>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Datas cadastradas</div>
              <div class="small text-muted"><?php echo (int) ($totalBloqueios ?? count($bloqueios)); ?> registro(s)</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Titulo</th>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($bloqueios)): ?>
                  <?php foreach ($bloqueios as $bloqueio): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($bloqueio['titulo'] ?? ''); ?></div>
                      <?php if (! empty($bloqueio['descricao'])): ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($bloqueio['descricao']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php echo htmlspecialchars(date('d/m/Y', strtotime($bloqueio['data']))); ?>
                      <?php if (! empty($bloqueio['data_fim'])): ?>
                      <div class="small text-muted">ate <?php echo htmlspecialchars(date('d/m/Y', strtotime($bloqueio['data_fim']))); ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(labelTipoBloqueio($bloqueio['tipo'] ?? '')); ?></td>
                    <td>
                      <span class="badge <?php echo ($bloqueio['status'] ?? '') === 'Ativo' ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                        <?php echo htmlspecialchars($bloqueio['status'] ?? ''); ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="app-actions">
                        <a href="./?page=calendario&action=editar&id=<?php echo (int) $bloqueio['id']; ?>"
                          class="btn btn-sm btn-outline-primary app-action-btn">
                          <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form method="POST" action="./?page=calendario&action=excluir">
                          <input type="hidden" name="id" value="<?php echo (int) $bloqueio['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger app-action-btn">
                            <i class="bi bi-trash"></i> Excluir
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                      Nenhuma data encontrada.
                    </td>
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
  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Calendario";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "./?page=logout";
    }
  });

  const tipoCalendario = document.querySelector('select[name="tipo"]');
  const campoDataFim = document.querySelector(".calendario-data-fim");
  const inputDataFim = document.querySelector('input[name="data_fim"]');

  function atualizarDataFimCalendario() {
    const mostrar = tipoCalendario && tipoCalendario.value === "Recesso";
    if (campoDataFim) campoDataFim.classList.toggle("d-none", !mostrar);
    if (inputDataFim) inputDataFim.required = !!mostrar;
  }

  if (tipoCalendario) {
    tipoCalendario.addEventListener("change", atualizarDataFimCalendario);
    atualizarDataFimCalendario();
  }
  </script>
</body>

</html>


