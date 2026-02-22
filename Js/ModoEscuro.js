const lightBtn = document.getElementById("claro-btn");
const darkBtn = document.getElementById("escuro-btn");
const body = document.body;

if (lightBtn && darkBtn && body) {
  const THEME_KEY = "cdol_theme";

  function applyTheme(theme) {
    const isDark = theme === "escuro";

    body.classList.toggle("escuro", isDark);
    body.classList.toggle("claro", !isDark);

    darkBtn.classList.toggle("active", isDark);
    lightBtn.classList.toggle("active", !isDark);

    localStorage.setItem(THEME_KEY, isDark ? "escuro" : "claro");
  }

  const savedTheme = localStorage.getItem(THEME_KEY);
  applyTheme(savedTheme === "escuro" ? "escuro" : "claro");

  lightBtn.addEventListener("click", () => applyTheme("claro"));
  darkBtn.addEventListener("click", () => applyTheme("escuro"));
} else {
  console.warn("ModoEscuro: botoes de tema nao encontrados.");
}
