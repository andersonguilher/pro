document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  const erroMsg = document.getElementById("erroLogin");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const usuario = document.getElementById("usuario").value.trim();
    const senha = document.getElementById("senha").value;

    try {
      const response = await fetch("php/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ usuario, senha })
      });

      if (!response.ok) {
        throw new Error(`Erro HTTP: ${response.status}`);
      }

      const data = await response.json();

      if (data.sucesso) {
        localStorage.setItem("usuario", usuario);
        window.location.href = "pages/home.html";
      } else {
        erroMsg.textContent = "Usuário ou senha inválidos.";
        erroMsg.classList.remove("hidden");
      }
    } catch (err) {
      console.error("Erro no login:", err);
      erroMsg.textContent = "Erro na conexão com o servidor.";
      erroMsg.classList.remove("hidden");
    }
  });
});
