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
    $ofertas = $ofertas ?? [];
    $aulas = $aulas ?? [];
    $mes = (int) ($mes ?? date('n'));
    $ano = (int) ($ano ?? date('Y'));
    $cursoOfertaId = (int) ($cursoOfertaId ?? 0);

    $tituloPagina = 'Quadro Horario';
    $subtituloPagina = 'Monte e acompanhe o quadro mensal da turma';
    $botaoTopoTexto = '';
    $botaoTopoLink = '';
    $botaoTopoClasse = 'app-btn-primary';
    $botaoTopoIcone = 'bi-plus-circle';

    $aulasPorData = [];

    foreach ($aulas as $aula) {
        $aulasPorData[$aula['data_aula']][] = $aula;
    }

    $primeiroDia = sprintf('%04d-%02d-01', $ano, $mes);
    $diasNoMes = (int) date('t', strtotime($primeiroDia));
    $inicioSemana = (int) date('w', strtotime($primeiroDia));
    $nomesSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
?>

<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Quadro Horario - Sistema de Controle de Salas</title>

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
            $paginaAtiva = 'quadro_horario';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <?php require_once __DIR__ . '/../components/page_header.php'; ?>

          <?php if (($tipo ?? '') !== 'sucesso'): ?>
          <?php require_once __DIR__ . '/../components/alert.php'; ?>
          <?php endif; ?>

          <div class="app-card p-3 mb-3">
            <?php require_once __DIR__ . '/quadro_horario/_filtros.php'; ?>
          </div>

          <?php if (! empty($ofertaSelecionada)): ?>
          <div class="app-card p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2">
              <div>
                <div class="fw-bold"><?php echo htmlspecialchars($ofertaSelecionada['nome']); ?></div>
                <div class="small text-muted">
                  Oferta <?php echo htmlspecialchars($ofertaSelecionada['codigo_oferta']); ?>
                  · Período <?php echo htmlspecialchars($ofertaSelecionada['periodo']); ?>
                  <?php if (! empty($ofertaSelecionada['hora_inicio']) && ! empty($ofertaSelecionada['hora_fim'])): ?>
                  · <?php echo htmlspecialchars(substr($ofertaSelecionada['hora_inicio'], 0, 5) . ' - ' . substr($ofertaSelecionada['hora_fim'], 0, 5)); ?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="small text-muted">
                <?php echo count($aulas); ?> aula(s) no mês
              </div>
            </div>
          </div>

          <div class="app-card p-3">
            <div class="table-responsive">
              <table class="table table-bordered align-top mb-0">
                <thead class="small text-muted">
                  <tr>
                    <?php foreach ($nomesSemana as $nomeSemana): ?>
                    <th class="text-center" style="width: 14.285%;"><?php echo $nomeSemana; ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                      $diaAtual = 1;
                      $celulas = 0;

                      while ($diaAtual <= $diasNoMes):
                  ?>
                  <tr>
                    <?php for ($coluna = 0; $coluna < 7; $coluna++): ?>
                    <?php
                        $celulas++;
                        $mostrarDia = ! ($celulas <= $inicioSemana || $diaAtual > $diasNoMes);
                        $dataIso = $mostrarDia ? sprintf('%04d-%02d-%02d', $ano, $mes, $diaAtual) : '';
                        $periodoOferta = strtolower((string) ($ofertaSelecionada['periodo'] ?? ''));
                        $bloquearSabado = in_array($periodoOferta, ['tarde', 'noite'], true);
                        $diaComLancamento = ! empty($aulasPorData[$dataIso] ?? []);
                        $permiteLancamento = $mostrarDia && ! $diaComLancamento && $coluna !== 0 && ! ($coluna === 6 && $bloquearSabado);
                        $salasDisponiveisDia = $salasDisponiveisPorData[$dataIso] ?? [];
                        $docentesDisponiveisDia = $docentesDisponiveisPorData[$dataIso] ?? [];
                        $docentesBlocosDia = $docentesDisponiveisPorBloco[$dataIso] ?? [];
                        $cadastroDisponivel = ! empty($salasDisponiveisDia);
                        $dataId = str_replace('-', '_', $dataIso);
                    ?>
                    <td style="min-width: 160px; height: 150px;">
                      <?php if ($mostrarDia): ?>
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold"><?php echo $diaAtual; ?></span>
                        <?php if ($permiteLancamento): ?>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                          data-bs-target="#quickAdd_<?php echo $dataId; ?>" aria-expanded="false"
                          title="Adicionar aula">
                          <i class="bi bi-plus"></i>
                        </button>
                        <?php endif; ?>
                      </div>

                      <?php if ($permiteLancamento): ?>
                      <div class="collapse mb-2" id="quickAdd_<?php echo $dataId; ?>">
                        <form method="POST" action="/mapa_de_sala/public/?page=quadro_horario&action=salvar"
                          class="border rounded p-2 bg-body-tertiary">
                          <input type="hidden" name="curso_oferta_id" value="<?php echo $cursoOfertaId; ?>">
                          <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                          <input type="hidden" name="ano" value="<?php echo $ano; ?>">
                          <input type="hidden" name="data_aula" value="<?php echo $dataIso; ?>">
                          <input type="hidden" name="hora_inicio"
                            value="<?php echo htmlspecialchars(substr($ofertaSelecionada['hora_inicio'] ?? '', 0, 5)); ?>">
                          <input type="hidden" name="hora_fim"
                            value="<?php echo htmlspecialchars(substr($ofertaSelecionada['hora_fim'] ?? '', 0, 5)); ?>">
                          <input type="hidden" name="status" value="Ativa">

                          <div class="mb-2">
                            <select class="form-select form-select-sm" name="sala_id" required>
                              <option value="">
                                <?php echo empty($salasDisponiveisDia) ? 'Nenhuma sala disponivel' : 'Sala...'; ?>
                              </option>
                              <?php foreach ($salasDisponiveisDia as $sala): ?>
                              <option value="<?php echo (int) $sala['id']; ?>">
                                <?php echo htmlspecialchars($sala['nome'] ?? ''); ?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="mb-2" id="ucPrincipal_<?php echo $dataId; ?>">
                            <select class="form-select form-select-sm" name="unidade_curricular_id" required>
                              <option value="">UC...</option>
                              <?php foreach (($unidadesCurriculares ?? []) as $uc): ?>
                              <option value="<?php echo (int) $uc['id']; ?>">
                                <?php echo htmlspecialchars(($uc['codigo'] ?? '') . ' - ' . ($uc['nome'] ?? '')); ?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="mb-2" id="professorPrincipal_<?php echo $dataId; ?>">
                            <select class="form-select form-select-sm" name="docente_principal_id" required>
                              <option value="">
                                <?php echo empty($docentesDisponiveisDia) ? 'Nenhum professor disponivel' : 'Professor...'; ?>
                              </option>
                              <?php foreach ($docentesDisponiveisDia as $docente): ?>
                              <option value="<?php echo (int) $docente['id']; ?>"
                                data-uc-ids="<?php echo htmlspecialchars((string) ($docente['uc_ids'] ?? '')); ?>">
                                <?php echo htmlspecialchars($docente['nome'] ?? ''); ?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="d-flex flex-column gap-1 mb-2">
                            <label class="form-check small mb-0">
                              <input class="form-check-input quick-divisao" type="checkbox" name="divisao_por_hora"
                                data-target="blocosHora_<?php echo $dataId; ?>"
                                data-uc-target="ucPrincipal_<?php echo $dataId; ?>"
                                data-professor-target="professorPrincipal_<?php echo $dataId; ?>"
                                data-dupla-target="duplaWrap_<?php echo $dataId; ?>">
                              Divisão por hora
                            </label>
                            <label class="form-check small mb-0" id="duplaWrap_<?php echo $dataId; ?>">
                              <input class="form-check-input quick-dupla" type="checkbox" name="dupla_docencia"
                                data-target="docente2_<?php echo $dataId; ?>">
                              Dupla docência
                            </label>
                          </div>

                          <div class="mb-2 d-none" id="docente2_<?php echo $dataId; ?>">
                            <select class="form-select form-select-sm quick-docente2" name="docente_2_id">
                              <option value="">
                                <?php echo count($docentesDisponiveisDia) < 2 ? 'Sem professor 2 disponivel' : 'Professor 2...'; ?>
                              </option>
                              <?php foreach ($docentesDisponiveisDia as $docente): ?>
                              <option value="<?php echo (int) $docente['id']; ?>"
                                data-uc-ids="<?php echo htmlspecialchars((string) ($docente['uc_ids'] ?? '')); ?>">
                                <?php echo htmlspecialchars($docente['nome'] ?? ''); ?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="d-none mb-2" id="blocosHora_<?php echo $dataId; ?>">
                            <?php foreach (($blocosOferta ?? []) as $bloco): ?>
                            <?php
                                $chaveBloco = substr((string) $bloco['inicio'], 0, 5) . '|' . substr((string) $bloco['fim'], 0, 5);
                                $docentesBloco = $docentesBlocosDia[$chaveBloco] ?? [];
                            ?>
                            <div class="mb-2">
                              <label class="form-label small mb-1">
                                <?php echo htmlspecialchars(str_replace('|', ' - ', $chaveBloco)); ?>
                              </label>
                              <select class="form-select form-select-sm mb-1 quick-bloco-uc"
                                name="ucs_por_bloco[<?php echo htmlspecialchars($chaveBloco); ?>]">
                                <option value="">UC...</option>
                                <?php foreach (($unidadesCurriculares ?? []) as $uc): ?>
                                <option value="<?php echo (int) $uc['id']; ?>">
                                  <?php echo htmlspecialchars(($uc['codigo'] ?? '') . ' - ' . ($uc['nome'] ?? '')); ?>
                                </option>
                                <?php endforeach; ?>
                              </select>
                              <select class="form-select form-select-sm quick-bloco-docente"
                                name="docentes_por_bloco[<?php echo htmlspecialchars($chaveBloco); ?>]">
                                <option value="">
                                  <?php echo empty($docentesBloco) ? 'Nenhum professor disponivel' : 'Professor...'; ?>
                                </option>
                                <?php foreach ($docentesBloco as $docente): ?>
                                <option value="<?php echo (int) $docente['id']; ?>"
                                  data-uc-ids="<?php echo htmlspecialchars((string) ($docente['uc_ids'] ?? '')); ?>">
                                  <?php echo htmlspecialchars($docente['nome'] ?? ''); ?>
                                </option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <?php endforeach; ?>
                          </div>

                          <button type="submit" class="btn btn-sm app-btn-primary w-100" <?php echo $cadastroDisponivel ? '' : 'disabled'; ?>>
                            <i class="bi bi-save"></i> Salvar
                          </button>
                        </form>
                      </div>
                      <?php endif; ?>

                      <?php foreach (($aulasPorData[$dataIso] ?? []) as $aula): ?>
                      <?php
                          $docentesAula = $aula['docentes'] ?? [];
                          $docentePrincipalId = (int) ($docentesAula[0]['id'] ?? 0);
                          $docente2Id = (int) ($docentesAula[1]['id'] ?? 0);
                          $temDupla = $docente2Id > 0;
                          $editId = 'quickEdit_' . (int) $aula['id'];
                          $editDocente2Id = 'editDocente2_' . (int) $aula['id'];
                          $salasEdicao = $salasDisponiveisPorAula[(int) $aula['id']] ?? [];
                          $docentesEdicao = $docentesDisponiveisPorAula[(int) $aula['id']] ?? [];
                      ?>
                      <div class="border rounded p-2 mb-2 small">
                        <div class="fw-semibold">
                          <?php echo htmlspecialchars(substr($aula['hora_inicio'], 0, 5) . ' - ' . substr($aula['hora_fim'], 0, 5)); ?>
                        </div>
                        <div><?php echo htmlspecialchars(($aula['uc_codigo'] ?? '') . ' - ' . ($aula['uc_nome'] ?? '')); ?></div>
                        <div class="text-muted">Sala <?php echo htmlspecialchars($aula['sala_nome'] ?? ''); ?></div>
                        <?php if (! empty($aula['docentes'])): ?>
                        <div class="text-muted">
                          <?php echo htmlspecialchars(implode(', ', array_map(fn($docente) => $docente['nome'], $aula['docentes']))); ?>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex gap-1 mt-2">
                          <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#<?php echo $editId; ?>" aria-expanded="false" title="Editar aula">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <form method="POST" action="/mapa_de_sala/public/?page=quadro_horario&action=excluir">
                            <input type="hidden" name="id" value="<?php echo (int) $aula['id']; ?>">
                            <input type="hidden" name="curso_oferta_id" value="<?php echo $cursoOfertaId; ?>">
                            <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                            <input type="hidden" name="ano" value="<?php echo $ano; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                              <i class="bi bi-trash"></i>
                            </button>
                          </form>
                        </div>

                        <div class="collapse mt-2" id="<?php echo $editId; ?>">
                          <form method="POST" action="/mapa_de_sala/public/?page=quadro_horario&action=atualizar"
                            class="border rounded p-2 bg-body-tertiary">
                            <input type="hidden" name="id" value="<?php echo (int) $aula['id']; ?>">
                            <input type="hidden" name="curso_oferta_id" value="<?php echo $cursoOfertaId; ?>">
                            <input type="hidden" name="mes" value="<?php echo $mes; ?>">
                            <input type="hidden" name="ano" value="<?php echo $ano; ?>">
                            <input type="hidden" name="data_aula" value="<?php echo htmlspecialchars($aula['data_aula']); ?>">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($aula['status'] ?? 'Ativa'); ?>">
                            <input type="hidden" name="observacoes" value="<?php echo htmlspecialchars($aula['observacoes'] ?? ''); ?>">

                            <div class="mb-2">
                              <select class="form-select form-select-sm" name="sala_id" required>
                                <option value="">Sala...</option>
                                <?php foreach ($salasEdicao as $sala): ?>
                                <option value="<?php echo (int) $sala['id']; ?>"
                                  <?php echo (int) $aula['sala_id'] === (int) $sala['id'] ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($sala['nome'] ?? ''); ?>
                                </option>
                                <?php endforeach; ?>
                              </select>
                            </div>

                            <div class="mb-2">
                              <select class="form-select form-select-sm" name="unidade_curricular_id" required>
                                <option value="">UC...</option>
                                <?php foreach (($unidadesCurriculares ?? []) as $uc): ?>
                                <option value="<?php echo (int) $uc['id']; ?>"
                                  <?php echo (int) $aula['unidade_curricular_id'] === (int) $uc['id'] ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars(($uc['codigo'] ?? '') . ' - ' . ($uc['nome'] ?? '')); ?>
                                </option>
                                <?php endforeach; ?>
                              </select>
                            </div>

                            <div class="mb-2">
                              <select class="form-select form-select-sm" name="docente_principal_id" required>
                                <option value="">Professor...</option>
                                <?php foreach ($docentesEdicao as $docente): ?>
                                <option value="<?php echo (int) $docente['id']; ?>"
                                  data-uc-ids="<?php echo htmlspecialchars((string) ($docente['uc_ids'] ?? '')); ?>"
                                  <?php echo $docentePrincipalId === (int) $docente['id'] ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($docente['nome'] ?? ''); ?>
                                </option>
                                <?php endforeach; ?>
                              </select>
                            </div>

                            <div class="row g-2 mb-2">
                              <div class="col-6">
                                <input type="time" class="form-control form-control-sm" name="hora_inicio"
                                  value="<?php echo htmlspecialchars(substr($aula['hora_inicio'], 0, 5)); ?>" required>
                              </div>
                              <div class="col-6">
                                <input type="time" class="form-control form-control-sm" name="hora_fim"
                                  value="<?php echo htmlspecialchars(substr($aula['hora_fim'], 0, 5)); ?>" required>
                              </div>
                            </div>

                            <label class="form-check small mb-2">
                              <input class="form-check-input quick-dupla" type="checkbox" name="dupla_docencia"
                                data-target="<?php echo $editDocente2Id; ?>" <?php echo $temDupla ? 'checked' : ''; ?>>
                              Dupla docÃªncia
                            </label>

                            <div class="mb-2 <?php echo $temDupla ? '' : 'd-none'; ?>" id="<?php echo $editDocente2Id; ?>">
                              <select class="form-select form-select-sm quick-docente2" name="docente_2_id"
                                <?php echo $temDupla ? 'required' : ''; ?>>
                                <option value="">Professor 2...</option>
                                <?php foreach ($docentesEdicao as $docente): ?>
                                <option value="<?php echo (int) $docente['id']; ?>"
                                  data-uc-ids="<?php echo htmlspecialchars((string) ($docente['uc_ids'] ?? '')); ?>"
                                  <?php echo $docente2Id === (int) $docente['id'] ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($docente['nome'] ?? ''); ?>
                                </option>
                                <?php endforeach; ?>
                              </select>
                            </div>

                            <button type="submit" class="btn btn-sm app-btn-primary w-100">
                              <i class="bi bi-save"></i> Salvar
                            </button>
                          </form>
                        </div>
                      </div>
                      <?php endforeach; ?>
                      <?php $diaAtual++; ?>
                      <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php else: ?>
          <div class="app-card p-4 text-center text-muted">
            Selecione mês, ano e turma/oferta para visualizar o quadro horário.
          </div>
          <?php endif; ?>
        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Quadro Horario";

  const userName = document.getElementById("userName");
  if (userName) userName.textContent = <?php echo json_encode($usuarioLogado); ?>;

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "/mapa_de_sala/public/?page=logout";
    }
  });

  document.addEventListener("change", function(e) {
    if (e.target.matches('select[name="unidade_curricular_id"], .quick-bloco-uc')) {
      const form = e.target.closest("form");
      const ucId = e.target.value;

      if (form) {
        const selects = e.target.classList.contains("quick-bloco-uc")
          ? [e.target.parentElement.querySelector(".quick-bloco-docente")]
          : [
              form.querySelector('select[name="docente_principal_id"]'),
              form.querySelector('select[name="docente_2_id"]')
            ];

        selects.forEach(function(select) {
          if (!select) return;

          let selecionadoValido = true;

          select.querySelectorAll("option").forEach(function(option) {
            if (!option.value) return;

            const ucs = (option.dataset.ucIds || "").split(",").filter(Boolean);
            const mostrar = ucId && ucs.includes(ucId);
            option.hidden = !mostrar;

            if (option.selected && !mostrar) {
              selecionadoValido = false;
            }
          });

          if (!selecionadoValido) {
            select.value = "";
          }
        });
      }
    }

    if (e.target.classList.contains("quick-divisao")) {
      const blocos = document.getElementById(e.target.dataset.target);
      const uc = document.getElementById(e.target.dataset.ucTarget);
      const professor = document.getElementById(e.target.dataset.professorTarget);
      const dupla = document.getElementById(e.target.dataset.duplaTarget);

      if (blocos) {
        blocos.classList.toggle("d-none", !e.target.checked);
        blocos.querySelectorAll(".quick-bloco-uc, .quick-bloco-docente").forEach(function(select) {
          select.required = e.target.checked;
          if (!e.target.checked) {
            select.value = "";
          }
        });
      }

      if (uc) {
        uc.classList.toggle("d-none", e.target.checked);
        uc.querySelectorAll("select").forEach(function(select) {
          select.required = !e.target.checked;
          if (e.target.checked) {
            select.value = "";
          }
        });
      }

      if (professor) {
        professor.classList.toggle("d-none", e.target.checked);
        professor.querySelectorAll("select").forEach(function(select) {
          select.required = !e.target.checked;
          if (e.target.checked) {
            select.value = "";
          }
        });
      }

      if (dupla) {
        dupla.classList.toggle("d-none", e.target.checked);
        const checkbox = dupla.querySelector(".quick-dupla");
        if (checkbox && e.target.checked) {
          checkbox.checked = false;
          checkbox.dispatchEvent(new Event("change", { bubbles: true }));
        }
      }

      return;
    }

    if (!e.target.classList.contains("quick-dupla")) {
      return;
    }

    const target = document.getElementById(e.target.dataset.target);
    if (!target) {
      return;
    }

    const select = target.querySelector(".quick-docente2");
    target.classList.toggle("d-none", !e.target.checked);

    if (select) {
      select.required = e.target.checked;
      if (!e.target.checked) {
        select.value = "";
      }
    }
  });

  document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('select[name="unidade_curricular_id"], .quick-bloco-uc').forEach(function(select) {
      select.dispatchEvent(new Event("change", { bubbles: true }));
    });
  });
  </script>
</body>

</html>
