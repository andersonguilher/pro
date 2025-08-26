document.addEventListener("DOMContentLoaded", () => {
  const sairLink = document.querySelector(".bottom-nav a[href*='logout.php']");

  if (sairLink) {
    sairLink.addEventListener("click", (e) => {
      e.preventDefault();

      // Limpa o localStorage para evitar auto-login
      localStorage.removeItem("logado");
      localStorage.removeItem("usuario");
      localStorage.removeItem("senha");
      localStorage.removeItem("lembrar");

      // Redireciona para o logout real no PHP
      window.location.href = sairLink.href;
    });
  }
});
