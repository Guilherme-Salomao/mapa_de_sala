document.addEventListener("DOMContentLoaded", function () {
  inicializarQuantidadeRecursos();
});

function inicializarQuantidadeRecursos() {
  const checks = document.querySelectorAll(".recurso-check");

  if (!checks.length) {
    return;
  }

  checks.forEach(function (check) {
    const targetId = check.getAttribute("data-target");
    const inputQtd = document.getElementById(targetId);

    if (!inputQtd) {
      return;
    }

    inputQtd.disabled = !check.checked;

    check.addEventListener("change", function () {
      inputQtd.disabled = !this.checked;

      if (this.checked && (!inputQtd.value || Number(inputQtd.value) < 1)) {
        inputQtd.value = 1;
      }

      if (!this.checked) {
        inputQtd.value = 1;
      }
    });
  });
}
