<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuário';
    $mensagem      = $_GET['msg'] ?? '';
    $tipo          = $_GET['tipo'] ?? '';

    $formAction = '/mapa_de_sala/public/?page=docentes&action=salvar';
    $botaoTexto = 'Salvar Docente';
    $modoEdicao = false;

    $docenteForm = [
        'usuario_id'      => $_GET['usuario_id'] ?? '',
        'area_atuacao'   => $_GET['area_atuacao'] ?? '',
        'status'         => $_GET['status'] ?? 'Ativo',
        'observacoes'    => $_GET['observacoes'] ?? '',
        'unidades_curriculares' => [],
    ];
    $areas = $areas ?? [];
    $cursoModelos = $cursoModelos ?? [];
    $unidadesCurriculares = $unidadesCurriculares ?? [];

    $tituloPagina    = 'Cadastrar Docente';
    $subtituloPagina = 'Vincule um usuário professor ao cadastro docente';
    $botaoTopoTexto  = 'Voltar';
    $botaoTopoLink   = '/mapa_de_sala/public/?page=docentes';
    $botaoTopoClasse = 'btn-outline-secondary';
    $botaoTopoIcone  = 'bi-arrow-left';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastrar Docente - SIGHA</title>

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
            $paginaAtiva = 'docentes';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <div class="app-card p-4">
            <?php if (! empty($mensagem)): ?>
            <div class="alert <?php echo $tipo === 'sucesso' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
              <i class="bi <?php echo $tipo === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
              <?php echo htmlspecialchars($mensagem); ?>
            </div>
            <?php endif; ?>

            <?php require_once __DIR__ . '/docentes/_form.php'; ?>
          </div>
        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Cadastrar Docente";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  const form = document.getElementById("formDocente");
  if (form) {
    form.addEventListener("submit", function(e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.classList.add("was-validated");
      }
    });
  }

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "/mapa_de_sala/public/?page=logout";
    }
  });
  </script>
</body>

</html>

