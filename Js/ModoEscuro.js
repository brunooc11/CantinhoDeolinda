const lightBtn = document.getElementById("claro-btn");
const darkBtn = document.getElementById("escuro-btn");
const body = document.body;

lightBtn.addEventListener("click", () => {
  body.classList.add("claro");
  body.classList.remove("escuro");
  lightBtn.classList.add("active");
  darkBtn.classList.remove("active");
});

darkBtn.addEventListener("click", () => {
  body.classList.add("escuro");
  body.classList.remove("claro");
  darkBtn.classList.add("active");
  lightBtn.classList.remove("active");
});
