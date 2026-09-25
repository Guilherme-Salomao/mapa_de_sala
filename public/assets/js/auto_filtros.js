(function () {
  const DEBOUNCE_MS = 500;
  const FILTER_FIELD_NAMES = new Set([
    "ano",
    "busca",
    "categoria",
    "cidade_id",
    "curso_id",
    "curso_oferta_id",
    "data",
    "data_fim",
    "data_inicio",
    "dia_semana",
    "docente_id",
    "mes",
    "periodo",
    "sala_id",
    "status",
    "turma_id",
    "uc_id",
    "usuario_id",
  ]);

  function formMethod(form) {
    return (form.getAttribute("method") || "get").toLowerCase();
  }

  function buttonText(button) {
    return (button.textContent || button.value || "").trim().toLowerCase();
  }

  function hasFilterButton(form) {
    return Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'))
      .some((button) => buttonText(button).includes("filtrar"));
  }

  function hasFilterField(form) {
    return Array.from(form.elements).some((field) => FILTER_FIELD_NAMES.has(field.name));
  }

  function isAutoFilterForm(form) {
    if (form.dataset.autoFilter === "off") {
      return false;
    }

    if (form.dataset.autoFilter === "true") {
      return true;
    }

    if (formMethod(form) !== "get") {
      return false;
    }

    return hasFilterButton(form) || (form.querySelector('input[name="page"]') && hasFilterField(form));
  }

  function hasVisibleControl(element) {
    return Array.from(element.querySelectorAll("a, button, input, select, textarea")).some((control) => {
      if (control.classList.contains("d-none") || control.type === "hidden") {
        return false;
      }

      return true;
    });
  }

  function hideEmptyFilterWrapper(form, button) {
    const wrapper = button.parentElement;

    if (!wrapper || wrapper === form) {
      return;
    }

    if (!hasVisibleControl(wrapper)) {
      wrapper.classList.add("d-none");
    }
  }

  function hideFilterButton(form) {
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
      if (!buttonText(button).includes("filtrar")) {
        return;
      }

      button.classList.add("d-none");
      button.setAttribute("aria-hidden", "true");
      button.tabIndex = -1;
      hideEmptyFilterWrapper(form, button);
    });
  }

  function serializeForm(form) {
    return new URLSearchParams(new FormData(form)).toString();
  }

  function isValidFilterState(form, field) {
    if (field && typeof field.checkValidity === "function" && !field.checkValidity()) {
      return false;
    }

    return Array.from(form.elements).every((control) => {
      if (!control.willValidate) {
        return true;
      }

      return control.checkValidity();
    });
  }

  function submitFilter(form, field = null) {
    if (!isValidFilterState(form, field)) {
      return;
    }

    const state = serializeForm(form);

    if (form.dataset.lastAutoFilterState === state) {
      return;
    }

    form.dataset.lastAutoFilterState = state;

    if (typeof form.requestSubmit === "function") {
      form.requestSubmit();
      return;
    }

    form.submit();
  }

  function isIgnoredField(field) {
    return !field || field.name === "page" || field.dataset.autoFilter === "off";
  }

  function attachAutoFilter(form) {
    if (form.dataset.autoFilterReady === "true") {
      return;
    }

    form.dataset.autoFilterReady = "true";
    form.dataset.lastAutoFilterState = serializeForm(form);
    hideFilterButton(form);

    let debounceTimer = null;

    form.addEventListener("input", (event) => {
      const field = event.target;

      if (isIgnoredField(field) || !(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) {
        return;
      }

      window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(() => submitFilter(form, field), DEBOUNCE_MS);
    });

    form.addEventListener("change", (event) => {
      const field = event.target;

      if (isIgnoredField(field)) {
        return;
      }

      window.clearTimeout(debounceTimer);
      submitFilter(form, field);
    });

    form.addEventListener("keydown", (event) => {
      const field = event.target;

      if (event.key !== "Enter" || isIgnoredField(field)) {
        return;
      }

      if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
        event.preventDefault();
        window.clearTimeout(debounceTimer);
        submitFilter(form, field);
      }
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form").forEach((form) => {
      if (isAutoFilterForm(form)) {
        attachAutoFilter(form);
      }
    });
  });
}());




