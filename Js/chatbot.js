const btnChat = document.getElementById("btnChat");
const chatBox = document.getElementById("chatBox");
const closeChat = document.getElementById("closeChat");
const chatMessages = document.querySelector(".chat-messages");
const chatInput = document.querySelector(".chat-input input");

if (!btnChat || !chatBox || !closeChat || !chatMessages || !chatInput) {
  console.warn("Chatbot: elementos nao encontrados.");
} else {
  const conversationHistory = [];
  let waitingForBot = false;

  btnChat.addEventListener("click", () => {
    chatBox.classList.toggle("hidden");
    if (!chatBox.classList.contains("hidden")) {
      chatInput.focus();
    }
  });

  closeChat.addEventListener("click", () => {
    chatBox.classList.add("hidden");
  });

  function addMessage(text, sender) {
    const msg = document.createElement("div");
    msg.classList.add("msg", sender);
    msg.innerText = text;
    chatMessages.appendChild(msg);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function addTypingIndicator() {
    const typing = document.createElement("div");
    typing.classList.add("msg", "bot");
    typing.id = "typing-indicator";
    typing.innerText = "A escrever...";
    chatMessages.appendChild(typing);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function removeTypingIndicator() {
    const typing = document.getElementById("typing-indicator");
    if (typing) {
      typing.remove();
    }
  }

  function guardarChat(texto, remetente) {
    fetch("Bd/guardar_chat.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: `mensagem=${encodeURIComponent(texto)}&remetente=${remetente}`
    }).catch(() => {
      console.warn("Chatbot: erro ao guardar mensagem.");
    });
  }

  function respostaLocal(texto) {
    const msg = texto
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");

    if (msg.includes("reserva")) {
      return "Para reservas, usa o botao 'Reserva Agora' no site.";
    }
    if (msg.includes("menu")) {
      return "O menu esta disponivel na secao Menu.";
    }
    if (msg.includes("localizacao")) {
      return "Estamos em Alenquer.";
    }
    if (msg.includes("contacto") || msg.includes("telefone")) {
      return "Telefone: +351 966 545 510 | Email: cantinhodeolina@gmail.com";
    }

    return "No momento nao consegui responder por IA. Tenta reformular a pergunta ou usa os botoes rapidos.";
  }

  async function pedirRespostaIA(texto) {
    const payload = {
      message: texto,
      history: conversationHistory.slice(-8)
    };

    const response = await fetch("Bd/chatbot_ai.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload)
    });

    const raw = await response.text();
    let data = null;
    try {
      data = JSON.parse(raw);
    } catch (e) {
      throw new Error(`Resposta invalida do servidor (${response.status}).`);
    }

    if (!response.ok || !data.reply) {
      throw new Error(data.error || "Erro ao obter resposta da IA.");
    }

    return data.reply;
  }

  async function enviarMensagem(texto) {
    if (waitingForBot) {
      return;
    }

    waitingForBot = true;
    chatInput.disabled = true;

    addMessage(texto, "user");
    guardarChat(texto, "user");
    conversationHistory.push({ role: "user", content: texto });
    chatInput.value = "";

    addTypingIndicator();

    let resposta = "";
    try {
      resposta = await pedirRespostaIA(texto);
    } catch (error) {
      console.warn(error);
      resposta = respostaLocal(texto);
    } finally {
      removeTypingIndicator();
      addMessage(resposta, "bot");
      guardarChat(resposta, "bot");
      conversationHistory.push({ role: "assistant", content: resposta });
      chatInput.disabled = false;
      chatInput.focus();
      waitingForBot = false;
    }
  }

  chatInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter" && chatInput.value.trim() !== "") {
      enviarMensagem(chatInput.value.trim());
    }
  });

  document.querySelectorAll(".quick-btns button").forEach((btn) => {
    btn.addEventListener("click", () => {
      const texto = btn.innerText.trim();
      if (texto) {
        enviarMensagem(texto);
      }
    });
  });
}
