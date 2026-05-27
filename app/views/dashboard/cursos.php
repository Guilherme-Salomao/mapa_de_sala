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
    $tipoMsg       = $_GET['tipo'] ?? '';
    $busca         = $busca ?? ($_GET['busca'] ?? '');
    $status        = $status ?? ($_GET['status'] ?? 'todos');
    $cursos        = $cursos ?? [];
    $totalCursos   = $totalCursos ?? count($cursos);

    $tituloPagina    = 'Manutenção de Turmas';
    $subtituloPagina = 'Gerencie turmas, ofertas e carga horária';
    $botaoTopoTexto  = 'Nova Turma';
    $botaoTopoLink   = '/mapa_de_sala/public/?page=turmas&action=cadastrar';
    $botaoTopoClasse = 'app-btn-primary';
    $botaoTopoIcone  = 'bi-plus-circle';

    function minutosTurma(array $curso): int
    {
        $total = minutosEntreHorarioTurma($curso['hora_inicio'] ?? null, $curso['hora_fim'] ?? null);

        if ((int) ($curso['integral'] ?? 0) === 1) {
            $total += minutosEntreHorarioTurma($curso['hora_inicio_tarde'] ?? null, $curso['hora_fim_tarde'] ?? null);
        }

        return $total;
    }

    function minutosEntreHorarioTurma(string $horaInicio, string $horaFim): int
    {
        if (empty($horaInicio) || empty($horaFim)) {
            return 0;
        }

        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return 0;
        }

        return (int) round(($fim - $inicio) / 60);
    }

    function formatarHoraAulaTurma(array $curso): string
    {
        $minutosTotais = minutosTurma($curso);

        if ($minutosTotais <= 0) {
            return 'Não informado';
        }

        $horas = intdiv($minutosTotais, 60);
        $minutos = $minutosTotais % 60;

        return $minutos > 0
             ? $horas . 'h' . str_pad((string) $minutos, 2, '0', STR_PAD_LEFT)
            : $horas . 'h';
    }

    function periodoTurmaPorHorario(array $curso): string
    {
        if ((int) ($curso['integral'] ?? 0) === 1) {
            return 'Integral';
        }

        $horaInicio = $curso['hora_inicio'] ?? null;
        $horaFim = $curso['hora_fim'] ?? null;

        if (empty($horaInicio) || empty($horaFim)) {
            return 'Não informado';
        }

        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);

        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return 'Não informado';
        }

        $periodos = [];
        $faixas = [
            'Manha' => ['00:00', '12:00'],
            'Tarde' => ['12:00', '18:00'],
            'Noite' => ['18:00', '23:59'],
        ];

        foreach ($faixas as $periodo => [$inicioFaixa, $fimFaixa]) {
            $base = date('Y-m-d ', $inicio);
            $faixaInicio = strtotime($base . $inicioFaixa);
            $faixaFim = strtotime($base . $fimFaixa);

            if ($faixaInicio !== false && $faixaFim !== false && $inicio < $faixaFim && $fim > $faixaInicio) {
                $periodos[] = $periodo;
            }
        }

        return count($periodos) > 1 ? 'Integral' : ($periodos[0] ?? 'Não informado');
    }

    function horarioTurma(array $curso): string
    {
        if (empty($curso['hora_inicio']) || empty($curso['hora_fim'])) {
            return '';
        }

        $horario = substr($curso['hora_inicio'], 0, 5) . ' - ' . substr($curso['hora_fim'], 0, 5);

        if ((int) ($curso['integral'] ?? 0) === 1 && ! empty($curso['hora_inicio_tarde']) && ! empty($curso['hora_fim_tarde'])) {
            $horario .= ' / ' . substr($curso['hora_inicio_tarde'], 0, 5) . ' - ' . substr($curso['hora_fim_tarde'], 0, 5);
        }

        return $horario;
    }
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Turmas - SIGHA</title>

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

          <?php if (! empty($mensagem)): ?>
          <div class="alert <?php echo $tipoMsg === 'sucesso' ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <i class="bi <?php echo $tipoMsg === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($mensagem); ?>
          </div>
          <?php endif; ?>

          <div class="app-card p-3 mb-3">
            <?php require_once __DIR__ . '/cursos/_filtros.php'; ?>
          </div>

          <div class="app-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Lista de Turmas</div>
              <div class="small text-muted"><?php echo (int) $totalCursos; ?> turma(s)</div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="small text-muted">
                  <tr>
                    <th>Turma</th>
                    <th>Codigo da oferta</th>
                    <th>Periodo</th>
                    <th>Horario</th>
                    <th>Hora aula</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>

                <tbody>
                  <?php if (! empty($cursos)): ?>
                  <?php foreach ($cursos as $curso): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($curso['nome'] ?? ''); ?></div>
                      <?php if (! empty($curso['descricao'])): ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($curso['descricao']); ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($curso['codigo_oferta'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(periodoTurmaPorHorario($curso)); ?></td>
                    <td>
                      <?php if (! empty($curso['hora_inicio']) && ! empty($curso['hora_fim'])): ?>
                      <?php echo htmlspecialchars(horarioTurma($curso)); ?>
                      <?php else: ?>
                      <span class="text-muted">Não informado</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(formatarHoraAulaTurma($curso)); ?></td>
                    <td>
                      <?php
                          $statusCurso = $curso['status'] ?? 'Em andamento';
                          require __DIR__ . '/cursos/status_badge.php';
                      ?>
                    </td>
                    <td class="text-end">
                      <?php
                          $cursoId = (int) ($curso['id'] ?? 0);
                          require __DIR__ . '/cursos/_acoes.php';
                      ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      Nenhuma turma encontrada.
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
  if (pageTitle) pageTitle.textContent = "Turmas";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "/mapa_de_sala/public/?page=logout";
    }
  });
  </script>
</body>

</html>



