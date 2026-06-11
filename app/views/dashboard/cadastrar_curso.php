<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['usuario'])) {
        header('Location: ./?tipo=erro&msg=' . urlencode('Faca login para acessar o sistema.'));
        exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuario';
    $mensagem      = $_GET['msg'] ?? '';
    $tipo          = $_GET['tipo'] ?? '';

    $formAction = './?page=turmas&action=salvar';
    $botaoTexto = 'Salvar Turma';
    $modoEdicao = false;

    $cursoForm = [
        'curso_modelo_id'     => $_GET['curso_modelo_id'] ?? '',
        'nome'                => $_GET['nome'] ?? '',
        'codigo_oferta'       => $_GET['codigo_oferta'] ?? '',
        'cidade_nome'         => $_GET['cidade'] ?? '',
        'integral'            => $_GET['integral'] ?? '0',
        'hora_inicio'         => $_GET['hora_inicio'] ?? '',
        'hora_fim'            => $_GET['hora_fim'] ?? '',
        'hora_inicio_tarde'   => $_GET['hora_inicio_tarde'] ?? '',
        'hora_fim_tarde'      => $_GET['hora_fim_tarde'] ?? '',
        'participa_parada_pedagogica' => $_GET['participa_parada_pedagogica'] ?? '1',
        'participa_recesso_escolar' => $_GET['participa_recesso_escolar'] ?? '1',
        'aula_segunda'        => $_GET['aula_segunda'] ?? '1',
        'aula_terca'          => $_GET['aula_terca'] ?? '1',
        'aula_quarta'         => $_GET['aula_quarta'] ?? '1',
        'aula_quinta'         => $_GET['aula_quinta'] ?? '1',
        'aula_sexta'          => $_GET['aula_sexta'] ?? '1',
        'aula_sabado'         => $_GET['aula_sabado'] ?? '1',
        'status'              => $_GET['status'] ?? 'Em andamento',
        'descricao'           => $_GET['descricao'] ?? '',
    ];

    $tituloPagina    = 'Cadastrar Turma';
    $subtituloPagina = 'Preencha os dados para criar um novo curso';
    $botaoTopoTexto  = 'Voltar';
    $botaoTopoLink   = './?page=turmas';
    $botaoTopoClasse = 'btn-outline-secondary';
    $botaoTopoIcone  = 'bi-arrow-left';
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title>Cadastrar Turma - SIGHA</title>

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
      window.location.href = "./?page=logout";
    }
  });
  </script>
</body>

</html>


