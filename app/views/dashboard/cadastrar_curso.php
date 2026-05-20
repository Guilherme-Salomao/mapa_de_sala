<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $mensagem      = $_GET['msg'] ?? '';
    $tipo          = $_GET['tipo'] ?? '';

    $formAction = '/mapa_de_sala/public/?page=turmas&action=salvar';
    $botaoTexto = 'Salvar Turma';
    $modoEdicao = false;

    $cursoForm = [
        'curso_modelo_id'     => $_GET['curso_modelo_id'] ?? '',
        'nome'                => $_GET['nome'] ?? '',
        'codigo_oferta'       => $_GET['codigo_oferta'] ?? '',
        'periodo'             => $_GET['periodo'] ?? '',
        'carga_horaria_total' => $_GET['carga_horaria_total'] ?? '',
        'hora_aula'           => $_GET['hora_aula'] ?? '',
        'status'              => $_GET['status'] ?? 'Em andamento',
        'descricao'           => $_GET['descricao'] ?? '',
    ];

    $tituloPagina    = 'Cadastrar Turma';
    $subtituloPagina = 'Preencha os dados para criar um novo curso';
    $botaoTopoTexto  = 'Voltar';
    $botaoTopoLink   = '/mapa_de_sala/public/?page=turmas';
    $botaoTopoClasse = 'btn-outline-secondary';
    $botaoTopoIcone  = 'bi-arrow-left';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastrar Turma - Sistema de Controle de Salas</title>

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
            $paginaAtiva = 'turmas';
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

            <?php require_once __DIR__ . '/cursos/_form.php'; ?>
          </div>
        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Cadastrar Turma";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  const form = document.getElementById("formCurso");
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

