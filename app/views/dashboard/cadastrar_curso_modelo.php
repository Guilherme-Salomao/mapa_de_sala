<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $mensagem = $_GET['msg'] ?? '';
    $tipo = $_GET['tipo'] ?? '';

    $formAction = '/mapa_de_sala/public/?page=cursos&action=salvar';
    $botaoTexto = 'Salvar Curso';
    $modoEdicao = false;

    $cursoForm = [
        'area_id'             => $_GET['area_id'] ?? '',
        'nome'                => $_GET['nome'] ?? '',
        'carga_horaria_total' => $_GET['carga_horaria_total'] ?? '',
        'status'              => $_GET['status'] ?? 'Ativo',
    ];

    $tituloPagina = 'Cadastrar Curso';
    $subtituloPagina = 'Preencha os dados do modelo de curso';
    $botaoTopoTexto = 'Voltar';
    $botaoTopoLink = '/mapa_de_sala/public/?page=cursos';
    $botaoTopoClasse = 'btn-outline-secondary';
    $botaoTopoIcone = 'bi-arrow-left';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastrar Curso - SIGHA</title>
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
            $paginaAtiva = 'cursos';
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
            <?php require_once __DIR__ . '/curso_modelos/_form.php'; ?>
          </div>
        </section>
      </div>
    </div>
  </main>
  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const form = document.getElementById("formCursoModelo");
  if (form) {
    form.addEventListener("submit", function(e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.classList.add("was-validated");
      }
    });
  }
  </script>
</body>

</html>

