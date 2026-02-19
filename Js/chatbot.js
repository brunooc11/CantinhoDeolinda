// ===============================
// ABRIR / FECHAR CHAT
// ===============================
const btnChat = document.getElementById("btnChat");
const chatBox = document.getElementById("chatBox");
const closeChat = document.getElementById("closeChat");

btnChat.addEventListener("click", () => {
  chatBox.classList.toggle("hidden");
});

closeChat.addEventListener("click", () => {
  chatBox.classList.add("hidden");
});

// ===============================
// ZONA DAS MENSAGENS
// ===============================
const chatMessages = document.querySelector(".chat-messages");

// Função para adicionar mensagem
function addMessage(text, sender) {
  const msg = document.createElement("div");
  msg.classList.add("msg", sender);
  msg.innerText = text;
  chatMessages.appendChild(msg);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

// ===============================
// GUARDAR NA BD (BACKGROUND)
// ===============================
function guardarChat(texto, remetente) {
  fetch("Bd/guardar_chat.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: `mensagem=${encodeURIComponent(texto)}&remetente=${remetente}`
  });
}

// ===============================
// FUNÇÃO DE RESPOSTA DO BOT
// ===============================
function respostaBot(texto) {
  const msg = texto.toLowerCase();

  if (msg.includes("reserva")) {
    return "📅 Para reservas, usa o botão 'Reservar Agora' no site.";
  }
  if (msg.includes("menu")) {
    return "🍽️ O menu está disponível na secção Menu.";
  }
  if (msg.includes("localização") || msg.includes("localizacao")) {
    return "📍 Estamos em Alenquer.";
  }
  if (msg.includes("contacto") || msg.includes("telefone")) {
    return "📞 Telefone: +511 442-2777\n✉️ cantinhodeolina@gmail.com";
  }

  return "🤖 Ainda estou a aprender 🙂 Usa os botões rápidos!";
}

// ===============================
// INPUT DE TEXTO
// ===============================
const chatInput = document.querySelector(".chat-input input");

chatInput.addEventListener("keypress", function (e) {
  if (e.key === "Enter" && chatInput.value.trim() !== "") {

    const texto = chatInput.value;

    // User
    addMessage(texto, "user");
    guardarChat(texto, "user");
    chatInput.value = "";

    // Bot
    setTimeout(() => {
      const resposta = respostaBot(texto);
      addMessage(resposta, "bot");
      guardarChat(resposta, "bot");
    }, 600);
  }
});

// ===============================
// BOTÕES RÁPIDOS
// ===============================
document.querySelectorAll(".quick-btns button").forEach(btn => {
  btn.addEventListener("click", () => {

    const texto = btn.innerText;

    // User
    addMessage(texto, "user");
    guardarChat(texto, "user");

    // Bot
    setTimeout(() => {
      const resposta = respostaBot(texto);
      addMessage(resposta, "bot");
      guardarChat(resposta, "bot");
    }, 400);
  });
});
