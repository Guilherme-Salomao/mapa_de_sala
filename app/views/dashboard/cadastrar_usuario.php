<?php
    if (session_status() === PHP_SESSION_NONE) {
    session_start();
    }

    if (! isset($_SESSION['usuario'])) {
    header('Location: ./?tipo=erro&msg=' . urlencode('FaÃ§a login para acessar o sistema.'));
    exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'UsuÃ¡rio';
    $mensagem      = $_GET['msg'] ?? '';
    $tipo          = $_GET['tipo'] ?? '';

    $formAction = './?page=usuarios&action=salvar';
    $botaoTexto = 'Salvar UsuÃ¡rio';
    $modoEdicao = false;

    $usuarioForm = [
    'id'           => '',
    'nome'         => '',
    'email'        => '',
    'nivel_acesso' => '',
    'status'       => 'Ativo',
    ];
    $areas = $areas ?? [];
    $areasUsuario = [];

    $tituloPagina    = 'Cadastrar UsuÃ¡rio';
    $subtituloPagina = 'Preencha os dados para criar um novo usuÃ¡rio';
    $botaoTopoTexto  = 'Voltar';
    $botaoTopoLink   = './?page=usuarios';
    $botaoTopoClasse = 'btn-outline-secondary';
    $botaoTopoIcone  = 'bi-arrow-left';
    $botaoTexto      = 'Salvar UsuÃ¡rio';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/svg+xml" href="assets/img/sigha-favicon.svg" />
  <title>Cadastrar UsuÃ¡rio - SIGHA</title>

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
            $paginaAtiva = 'usuarios';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>
        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>
          <div class="app-card p-4">
            <?php if (! empty($mensagem)): ?>
            <div class="alert <?php echo $tipo === 'sucesso' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
              <i class="bi <?php echo $tipo === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
              <?php echo htmlspecialchars($mensagem) ?>
            </div>
            <?php endif; ?>
            <?php require_once __DIR__ . '/usuarios/_form.php'; ?>
          </div>
        </section>
      </div>
    </div>
  </main>
  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Cadastrar UsuÃ¡rio";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado) ?>;

  const btnToggleSenha = document.getElementById("btnToggleSenha");
  const senha = document.getElementById("senha");

  if (btnToggleSenha && senha) {
    btnToggleSenha.addEventListener("click", function() {
      const isPassword = senha.type === "password";
      senha.type = isPassword ? "text" : "password";
      btnToggleSenha.innerHTML = isPassword 
        '<i class="bi bi-eye-slash"></i>' :
        '<i class="bi bi-eye"></i>';
    });
  }

  const form = document.getElementById("formUsuario");
  if (form) {
    form.addEventListener("submit", function(e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.classList.add("was-validated");
        return;
      }

      const senhaValor = document.getElementById("senha").value;
      const confSenhaValor = document.getElementById("confSenha").value;

      if (senhaValor !== confSenhaValor) {
        e.preventDefault();
        form.classList.add("was-validated");
        alert("As senhas nÃ£o conferem.");
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

