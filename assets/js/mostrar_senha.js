 // ====== Mostrar/Ocultar senha ======
    const btnTogglePass = document.getElementById("btnTogglePass");
    const passwordInput = document.getElementById("password");
    btnTogglePass.addEventListener("click", () => {
      const isPass = passwordInput.type === "password";
      passwordInput.type = isPass ? "text" : "password";
      btnTogglePass.innerHTML = isPass
        ? '<i class="bi bi-eye-slash"></i>'
        : '<i class="bi bi-eye"></i>';
    });