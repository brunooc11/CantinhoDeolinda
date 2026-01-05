const btnChat = document.getElementById("btnChat");
const chatBox = document.getElementById("chatBox");
const closeChat = document.getElementById("closeChat");

btnChat.addEventListener("click", () => {
  chatBox.classList.toggle("hidden");
});

closeChat.addEventListener("click", () => {
  chatBox.classList.add("hidden");
});
