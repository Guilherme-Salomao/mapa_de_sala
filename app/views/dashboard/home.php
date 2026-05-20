<?php
    if (session_status() === PHP_SESSION_NONE) {
    session_start();
    }

    if (! isset($_SESSION['usuario'])) {
    header('Location: /mapa_de_sala/public/?tipo=erro&msg=' . urlencode('Faça login para acessar o sistema.'));
    exit;
    }

    $usuarioLogado = $_SESSION['usuario']['nome'] ?? 'Usuário';
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Sistema de Controle de Salas</title>

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
            $paginaAtiva = 'home';
            require_once __DIR__ . '/../layouts/sidebar.php';
        ?>

        <section class="col-12 col-md-9 col-lg-10 p-3 p-md-4 app-content">
          <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
              <h4 class="mb-0">Dashboard</h4>
              <div class="small text-muted">Visão geral do uso das salas</div>
            </div>

            <div class="d-flex gap-2">
              <button class="btn btn-sm app-btn-primary" id="btnNovaSala">
                <i class="bi bi-plus-circle"></i> Nova Sala
              </button>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Total de salas</div>
                    <div class="fs-3 fw-bold" id="kpiTotal">0</div>
                  </div>

                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--default">
                    <i class="bi bi-door-open"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Livres</div>
                    <div class="fs-3 fw-bold" id="kpiLivres">0</div>
                  </div>

                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--livre">
                    <i class="bi bi-check-circle"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Em uso</div>
                    <div class="fs-3 fw-bold" id="kpiEmUso">0</div>
                  </div>

                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--uso">
                    <i class="bi bi-exclamation-circle"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
              <div class="app-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted">Manutenção</div>
                    <div class="fs-3 fw-bold" id="kpiUpdates">8</div>
                  </div>

                  <div class="app-icon-badge app-icon-badge--sm kpi-icon kpi-icon--manut">
                    <i class="bi bi-tools"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12">
              <div class="app-card p-3">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                  <div>
                    <div class="fw-bold">Ocupação por período</div>
                    <div class="small text-muted">
                      Selecione a data e veja as salas ocupadas por turno
                    </div>
                  </div>

                  <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select id="fDia" class="form-select form-select-sm app-date-select" aria-label="Dia"></select>
                    <select id="fMes" class="form-select form-select-sm app-date-select" aria-label="Mês"></select>
                    <select id="fAno" class="form-select form-select-sm app-date-select" aria-label="Ano"></select>

                    <button class="btn btn-sm btn-outline-secondary" id="btnHoje">
                      <i class="bi bi-calendar-event"></i> Hoje
                    </button>

                    <button class="btn btn-sm btn-outline-secondary" id="btnLimparAgenda">
                      <i class="bi bi-eraser"></i> Limpar
                    </button>
                  </div>
                </div>

                <hr class="my-3" />

                <div class="d-flex flex-wrap gap-2 mb-3">
                  <button class="btn btn-sm app-tab active" data-turno="manha" id="tabManha">
                    <i class="bi bi-sunrise"></i> Manhã
                  </button>
                  <button class="btn btn-sm app-tab" data-turno="tarde" id="tabTarde">
                    <i class="bi bi-sun"></i> Tarde
                  </button>
                  <button class="btn btn-sm app-tab" data-turno="noite" id="tabNoite">
                    <i class="bi bi-moon-stars"></i> Noite
                  </button>
                </div>

                <div id="agendaResumo" class="small text-muted mb-2"></div>

                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead class="small text-muted">
                      <tr>
                        <th>Sala</th>
                        <th>Turma alocada</th>
                        <th class="text-end">Ações</th>
                      </tr>
                    </thead>
                    <tbody id="tbodyAgenda"></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  const usuarioLogado = <?php echo json_encode($usuarioLogado) ?>;

  const pageTitle = document.getElementById("pageTitle");
  if (pageTitle) pageTitle.textContent = "Dashboard";

  const salasPadrao = [{
      id: 1,
      nome: "Sala 01",
      tipo: "Sala",
      capacidade: 30,
      status: "livre"
    },
    {
      id: 2,
      nome: "Sala 02",
      tipo: "Sala",
      capacidade: 30,
      status: "uso"
    },
    {
      id: 3,
      nome: "Laboratório 01",
      tipo: "Lab",
      capacidade: 25,
      status: "livre"
    },
    {
      id: 4,
      nome: "Laboratório 02",
      tipo: "Lab",
      capacidade: 25,
      status: "uso"
    },
    {
      id: 5,
      nome: "Sala Maker",
      tipo: "Especial",
      capacidade: 20,
      status: "livre"
    },
    {
      id: 6,
      nome: "Auditório",
      tipo: "Especial",
      capacidade: 80,
      status: "livre"
    }
  ];

  const KEY = "salas";

  function carregarSalas() {
    const raw = localStorage.getItem(KEY);
    if (!raw) {
      localStorage.setItem(KEY, JSON.stringify(salasPadrao));
      return [...salasPadrao];
    }
    return JSON.parse(raw);
  }

  let salas = carregarSalas();

  function atualizarKPIs(lista) {
    document.getElementById("kpiTotal").textContent = lista.length;
    document.getElementById("kpiLivres").textContent = lista.filter((s) => s.status === "livre").length;
    document.getElementById("kpiEmUso").textContent = lista.filter((s) => s.status === "uso").length;
  }

  function render() {
    atualizarKPIs(salas);
  }

  document.getElementById("btnNovaSala").addEventListener("click", () => {
    alert("Em breve: tela de cadastro de salas.");
  });

  document.addEventListener("click", function(e) {
    if (e.target.closest("#btnLogout")) {
      window.location.href = "/mapa_de_sala/public/?page=logout";
    }
  });

  render();

  const KEY_AGENDA = "agenda";

  function getISODate(y, m, d) {
    const mm = String(m).padStart(2, "0");
    const dd = String(d).padStart(2, "0");
    return `${y}-${mm}-${dd}`;
  }

  function loadAgenda() {
    const raw = localStorage.getItem(KEY_AGENDA);
    if (!raw) {
      const today = new Date();
      const y = today.getFullYear();
      const m = today.getMonth() + 1;
      const d = today.getDate();
      const iso = getISODate(y, m, d);

      const seed = {};
      seed[iso] = {
        manha: [{
            sala: "Laboratório 01",
            turma: "SEDUC 2025"
          },
          {
            sala: "Sala 02",
            turma: "Técnico em Informática"
          }
        ],
        tarde: [{
          sala: "Sala Maker",
          turma: "Design de Interiores"
        }],
        noite: [{
          sala: "Auditório",
          turma: "Palestra / Evento"
        }]
      };

      localStorage.setItem(KEY_AGENDA, JSON.stringify(seed));
      return seed;
    }
    return JSON.parse(raw);
  }

  function saveAgenda(agenda) {
    localStorage.setItem(KEY_AGENDA, JSON.stringify(agenda));
  }

  let agenda = loadAgenda();
  let turnoAtual = "manha";

  function fillDateSelectors() {
    const sDia = document.getElementById("fDia");
    const sMes = document.getElementById("fMes");
    const sAno = document.getElementById("fAno");

    sDia.innerHTML = "";
    for (let d = 1; d <= 31; d++) {
      const opt = document.createElement("option");
      opt.value = String(d);
      opt.textContent = String(d).padStart(2, "0");
      sDia.appendChild(opt);
    }

    const meses = [
      "01 - Jan", "02 - Fev", "03 - Mar", "04 - Abr",
      "05 - Mai", "06 - Jun", "07 - Jul", "08 - Ago",
      "09 - Set", "10 - Out", "11 - Nov", "12 - Dez"
    ];

    sMes.innerHTML = "";
    meses.forEach((txt, idx) => {
      const opt = document.createElement("option");
      opt.value = String(idx + 1);
      opt.textContent = txt;
      sMes.appendChild(opt);
    });

    const anoAtual = new Date().getFullYear();
    sAno.innerHTML = "";
    for (let y = anoAtual - 1; y <= anoAtual + 2; y++) {
      const opt = document.createElement("option");
      opt.value = String(y);
      opt.textContent = String(y);
      sAno.appendChild(opt);
    }
  }

  function setTodayInSelectors() {
    const now = new Date();
    document.getElementById("fDia").value = String(now.getDate());
    document.getElementById("fMes").value = String(now.getMonth() + 1);
    document.getElementById("fAno").value = String(now.getFullYear());
  }

  function getSelectedISO() {
    const d = Number(document.getElementById("fDia").value);
    const m = Number(document.getElementById("fMes").value);
    const y = Number(document.getElementById("fAno").value);
    return getISODate(y, m, d);
  }

  function ensureDay(iso) {
    if (!agenda[iso]) {
      agenda[iso] = {
        manha: [],
        tarde: [],
        noite: []
      };
      saveAgenda(agenda);
    }
  }

  function badgeOcupacao(qtd) {
    if (qtd > 0) {
      return `<span class="agenda-badge agenda-badge--ocupado"><i class="bi bi-x-circle"></i> ${qtd} ocupada(s)</span>`;
    }
    return `<span class="agenda-badge agenda-badge--livre"><i class="bi bi-check-circle"></i> Livre</span>`;
  }

  function renderAgenda() {
    const iso = getSelectedISO();
    ensureDay(iso);

    const lista = agenda[iso][turnoAtual] || [];
    const turnoLabel =
      turnoAtual === "manha" ? "Manhã" :
      turnoAtual === "tarde" ? "Tarde" : "Noite";

    document.getElementById("agendaResumo").innerHTML =
      `Data: <strong>${iso}</strong> • Período: <strong>${turnoLabel}</strong> • ${badgeOcupacao(lista.length)}`;

    const tbody = document.getElementById("tbodyAgenda");

    if (lista.length === 0) {
      tbody.innerHTML = `
          <tr>
            <td colspan="3" class="text-center text-muted py-4">
              Nenhuma sala ocupada neste período.
            </td>
          </tr>
        `;
      return;
    }

    tbody.innerHTML = lista.map((item, idx) => `
        <tr>
          <td class="fw-semibold">${item.sala}</td>
          <td>${item.turma}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" data-action="editarAgenda" data-idx="${idx}">
              <i class="bi bi-pencil"></i> Alterar
            </button>
          </td>
        </tr>
      `).join("");
  }

  function setActiveTab(turno) {
    turnoAtual = turno;

    document.querySelectorAll(".app-tab").forEach((b) => b.classList.remove("active"));
    const btn = document.querySelector(`.app-tab[data-turno="${turno}"]`);
    if (btn) btn.classList.add("active");

    renderAgenda();
  }

  function bindAgendaEvents() {
    document.getElementById("fDia").addEventListener("change", renderAgenda);
    document.getElementById("fMes").addEventListener("change", renderAgenda);
    document.getElementById("fAno").addEventListener("change", renderAgenda);

    document.getElementById("btnHoje").addEventListener("click", () => {
      setTodayInSelectors();
      renderAgenda();
    });

    document.getElementById("btnLimparAgenda").addEventListener("click", () => {
      const iso = getSelectedISO();
      ensureDay(iso);
      agenda[iso] = {
        manha: [],
        tarde: [],
        noite: []
      };
      saveAgenda(agenda);
      renderAgenda();
    });

    document.querySelectorAll(".app-tab").forEach((btn) => {
      btn.addEventListener("click", () => setActiveTab(btn.getAttribute("data-turno")));
    });
  }

  fillDateSelectors();
  setTodayInSelectors();
  bindAgendaEvents();
  setActiveTab("manha");
  </script>
</body>

</html>