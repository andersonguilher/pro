document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  const erroMsg = document.getElementById("erroLogin");
  const lembrarCheckbox = document.getElementById("lembrar");
  const usuarioInput = document.getElementById("usuario");
  const senhaInput = document.getElementById("senha");

  // Redireciona se já estiver logado
  if (localStorage.getItem("logado") === "1") {
    window.location.href = "pages/home.php";
    return;
  }

  // Preenche campos se marcado "lembrar"
  if (localStorage.getItem("lembrar") === "true") {
    usuarioInput.value = localStorage.getItem("usuario") || "";
    senhaInput.value = localStorage.getItem("senha") || "";
    lembrarCheckbox.checked = true;
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    erroMsg.classList.add("hidden");

    const usuario = usuarioInput.value.trim();
    const senha = senhaInput.value;

    // Se estiver offline, tenta login local
    if (!navigator.onLine) {
      return tentarLoginOffline(usuario, senha);
    }

    try {
      const response = await fetch("./php/login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ usuario, senha })
      });

      if (!response.ok) throw new Error("Falha na resposta do servidor.");

      const data = await response.json();

      if (data.sucesso) {
        if (lembrarCheckbox.checked) {
          localStorage.setItem("usuario", usuario);
          localStorage.setItem("senha", senha);
          localStorage.setItem("lembrar", "true");
        } else {
          localStorage.removeItem("usuario");
          localStorage.removeItem("senha");
          localStorage.removeItem("lembrar");
        }

        localStorage.setItem("logado", "1");
        window.location.href = "pages/home.php";
      } else {
        mostrarErro("Usuário ou senha inválidos.");
      }
    } catch (err) {
      // Falhou online: tenta offline
      tentarLoginOffline(usuario, senha);
    }
  });

  function tentarLoginOffline(usuario, senha) {
    const salvoUsuario = localStorage.getItem("usuario");
    const salvoSenha = localStorage.getItem("senha");

    if (usuario === salvoUsuario && senha === salvoSenha) {
      localStorage.setItem("logado", "1");
      window.location.href = "pages/home.php";
    } else {
      mostrarErro("⚠️ Sem conexão e credenciais não salvas.");
    }
  }

  function mostrarErro(mensagem) {
    erroMsg.textContent = mensagem;
    erroMsg.classList.remove("hidden");
  }
});
