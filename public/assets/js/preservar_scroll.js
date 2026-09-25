(function () {
  const STORAGE_KEY = "sigha:scroll-position";

  function currentPage() {
    return new URLSearchParams(window.location.search).get("page") || "";
  }

  function saveScrollPosition() {
    try {
      window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
        page: currentPage(),
        path: window.location.pathname,
        x: window.scrollX,
        y: window.scrollY,
        time: Date.now(),
      }));
    } catch (error) {
      // Ignore storage errors; the normal navigation should continue.
    }
  }

  function restoreScrollPosition() {
    let saved = null;

    try {
      saved = JSON.parse(window.sessionStorage.getItem(STORAGE_KEY) || "null");
    } catch (error) {
      saved = null;
    }

    if (!saved || saved.path !== window.location.pathname || saved.page !== currentPage()) {
      return;
    }

    if (Date.now() - Number(saved.time || 0) > 30000) {
      window.sessionStorage.removeItem(STORAGE_KEY);
      return;
    }

    window.sessionStorage.removeItem(STORAGE_KEY);

    window.requestAnimationFrame(() => {
      window.scrollTo(Number(saved.x || 0), Number(saved.y || 0));
    });
  }

  document.addEventListener("submit", (event) => {
    const form = event.target;

    if (form instanceof HTMLFormElement && (form.getAttribute("method") || "get").toLowerCase() === "post") {
      saveScrollPosition();
    }
  }, true);

  document.addEventListener("click", (event) => {
    const link = event.target.closest("a[href]");

    if (!link) {
      return;
    }

    const href = link.getAttribute("href") || "";

    if (href.includes("action=excluir") || href.includes("action=salvar") || href.includes("action=atualizar")) {
      saveScrollPosition();
    }
  }, true);

  document.addEventListener("DOMContentLoaded", restoreScrollPosition);
}());
