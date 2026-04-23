 // ====== Carregar componente Footer ======
    async function loadComponent(id, file) {
      const el = document.getElementById(id);
      const res = await fetch(file);
      el.innerHTML = await res.text();
    }
    loadComponent("app-footer", "html/components/footer.html");

    // ====== Carregar componente Header ======
    async function loadComponent(id, file) {
      const el = document.getElementById(id);
      const res = await fetch(file);
      el.innerHTML = await res.text();
    }
    loadComponent("app-header", "html/components/header.html");