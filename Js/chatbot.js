const btnChat = document.getElementById("btnChat");
const chatBox = document.getElementById("chatBox");
const closeChat = document.getElementById("closeChat");
const chatMessages = document.querySelector(".chat-messages");
const chatInput = document.querySelector(".chat-input input");
const sendChat = document.getElementById("sendChat");
const bodyDataset = document.body && document.body.dataset ? document.body.dataset : {};
const csrfToken = bodyDataset.csrfToken || "";

if (window.CDOL_CHAT_INLINE) {
  console.info("Chatbot: a usar fallback inline.");
} else {

function chatbotReady() {
  return !!(btnChat && chatBox && closeChat && chatMessages && chatInput && sendChat);
}

if (!chatbotReady()) {
  console.warn("Chatbot: elementos nao encontrados.");
} else {
  (function () {
    const conversationHistory = [];
    let waitingForBot = false;

    function openChatBox() {
      chatBox.classList.remove("hidden", "closing");
      chatInput.focus();
    }

    function closeChatBox() {
      if (chatBox.classList.contains("hidden") || chatBox.classList.contains("closing")) {
        return;
      }
      chatBox.classList.add("closing");
    }

    function toggleChatBox() {
      if (chatBox.classList.contains("hidden")) {
        openChatBox();
      } else {
        closeChatBox();
      }
    }

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
        body: `mensagem=${encodeURIComponent(texto)}&remetente=${encodeURIComponent(remetente)}`
      }).catch(function () {
        console.warn("Chatbot: erro ao guardar mensagem.");
      });
    }

    function respostaLocal(texto) {
      const msg = String(texto || "")
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
      if (!csrfToken) {
        throw new Error("csrf_missing");
      }

      const payload = {
        message: texto,
        history: conversationHistory.slice(-8),
        csrf_token: csrfToken
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
      } catch (_error) {
        throw new Error(`Resposta invalida do servidor (${response.status}).`);
      }

      if (!response.ok || !data || !data.reply) {
        throw new Error((data && data.error) || "Erro ao obter resposta da IA.");
      }

      return data.reply;
    }

    async function enviarMensagem(texto) {
      const textoLimpo = String(texto || "").trim();
      if (textoLimpo === "" || waitingForBot) {
        return;
      }

      waitingForBot = true;
      chatInput.disabled = true;
      sendChat.disabled = true;

      addMessage(textoLimpo, "user");
      guardarChat(textoLimpo, "user");
      conversationHistory.push({ role: "user", content: textoLimpo });
      chatInput.value = "";
      addTypingIndicator();

      let resposta = "";
      try {
        resposta = await pedirRespostaIA(textoLimpo);
      } catch (error) {
        console.warn(error);
        resposta = respostaLocal(textoLimpo);
      } finally {
        removeTypingIndicator();
        addMessage(resposta, "bot");
        guardarChat(resposta, "bot");
        conversationHistory.push({ role: "assistant", content: resposta });
        chatInput.disabled = false;
        sendChat.disabled = false;
        chatInput.focus();
        waitingForBot = false;
      }
    }

    btnChat.addEventListener("click", toggleChatBox);
    closeChat.addEventListener("click", closeChatBox);

    chatBox.addEventListener("animationend", function (event) {
      if (event.animationName === "chat-fall") {
        chatBox.classList.add("hidden");
        chatBox.classList.remove("closing");
      }
    });

    chatInput.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        enviarMensagem(chatInput.value);
      }
    });

    sendChat.addEventListener("click", function () {
      enviarMensagem(chatInput.value);
    });

    document.querySelectorAll(".quick-btns button").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const texto = (btn.dataset.message || btn.innerText || "").trim();
        enviarMensagem(texto);
      });
    });
  })();
}
}
