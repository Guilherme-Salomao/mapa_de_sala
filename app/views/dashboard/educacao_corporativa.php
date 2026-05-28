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
    $docentes = $docentes ?? [];
    $registros = $registros ?? [];
    $registroForm = $registroForm ?? null;

    $formEdicao = ! empty($registroForm);
    $formAction = $formEdicao ? './?page=educacao_corporativa&action=atualizar'
        : './?page=educacao_corporativa&action=salvar';

    $formDocenteId = (int) ($registroForm['docente_id'] ?? ($_GET['docente_id'] ?? 0));
    $formData = $registroForm['data'] ?? ($_GET['data'] ?? '');
    $formTitulo = $registroForm['titulo'] ?? ($_GET['titulo'] ?? '');
    $formDescricao = $registroForm['descricao'] ?? ($_GET['descricao'] ?? '');
    $formStatus = $registroForm['status'] ?? ($_GET['status_registro'] ?? 'Ativo');

    $tituloPagina = 'Educação Corporativa';
    $subtituloPagina = 'Registre os dias em que o docente está em curso';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title>Educação Corporativa - SIGHA</title>

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
            $paginaAtiva = 'educacao_corporativa';
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
              <input type="hidden" name="id" value="<?php echo (int) $registroForm['id']; ?>">
              <?php endif; ?>

              <div class="col-12 col-lg-4">
                <label class="form-label">Docente</label>
                <select name="docente_id" class="form-select" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($docentes as $docente): ?>
                  <option value="<?php echo (int) $docente['id']; ?>"
                    <?php echo $formDocenteId === (int) $docente['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($docente['nome'] ?? ''); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-2">
                <label class="form-label">Data</label>
                <input type="date" name="data" class="form-control"
                  value="<?php echo htmlspecialchars($formData); ?>" required>
              </div>

              <div class="col-12 col-lg-4">
                <label class="form-label">Curso</label>
                <input type="text" name="titulo" class="form-control" maxlength="150"
                  value="<?php echo htmlspecialchars($formTitulo); ?>" required>
              </div>

              <div class="col-12 col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                  <option value="Ativo" <?php echo $formStatus === 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                  <option value="Inativo" <?php echo $formStatus === 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Observação</label>
                <textarea name="descricao" class="form-control" rows="2"><?php echo htmlspecialchars($formDescricao); ?></textarea>
              </div>

              <div class="col-12 d-flex gap-2 justify-content-end">
                <?php if ($formEdicao): ?>
                <a href="./?page=educacao_corporativa" class="btn btn-outline-secondary">
                  <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <?php endif; ?>

                <button type="submit" class="btn app-btn-primary">
                  <i class="bi bi-save"></i>
                  <?php echo $formEdicao ? 'Salvar Alteração' : 'Salvar Curso'; ?>
                </button>
              </div>
            </form>
          </div>

          <div class="app-card p-3 mb-3">
            <form method="GET" action="./" class="row g-2 align-items-center">
              <input type="hidden" name="page" value="educacao_corporativa">

              <div class="col-12 col-md-7">
                <div class="input-group">
                  <span class="input-group-text app-input-icon">
                    <i class="bi bi-search"></i>
                  </span>
                  <input type="text" name="busca" class="form-control" placeholder="Buscar por docente ou curso..."
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
                <a href="./?page=educacao_corporativa" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </div>
            </form>
          </div>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Cursos cadastrados</div>
              <div class="small text-muted"><?php echo (int) ($totalRegistros ?? count($registros)); ?> registro(s)</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Docente</th>
                    <th>Data</th>
                    <th>Curso</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (! empty($registros)): ?>
                  <?php foreach ($registros as $registro): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($registro['docente_nome'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($registro['data']))); ?></td>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($registro['titulo'] ?? ''); ?></div>
                      <?php if (! empty($registro['descricao'])): ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($registro['descricao']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge <?php echo ($registro['status'] ?? '') === 'Ativo' ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                        <?php echo htmlspecialchars($registro['status'] ?? ''); ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="app-actions">
                        <a href="./?page=educacao_corporativa&action=editar&id=<?php echo (int) $registro['id']; ?>"
                          class="btn btn-sm btn-outline-primary app-action-btn">
                          <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form method="POST" action="./?page=educacao_corporativa&action=excluir">
                          <input type="hidden" name="id" value="<?php echo (int) $registro['id']; ?>">
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
                      Nenhum curso encontrado.
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
  if (pageTitle) pageTitle.textContent = "Educação Corporativa";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "./?page=logout";
    }
  });
  </script>
</body>

</html>


