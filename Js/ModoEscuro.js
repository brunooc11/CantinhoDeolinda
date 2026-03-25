const lightBtn = document.getElementById("claro-btn");
const darkBtn = document.getElementById("escuro-btn");
const body = document.body;
const themeControl = document.querySelector(".controlo");
let themeTransitionTimer = null;

if (lightBtn && darkBtn && body) {
  const THEME_KEY = "cdol_theme";
  const VALID_THEMES = new Set(["claro", "escuro"]);

  function applyTheme(theme) {
    const nextTheme = VALID_THEMES.has(theme) ? theme : "escuro";
    const isDark = nextTheme === "escuro";

    if (themeTransitionTimer) {
      clearTimeout(themeTransitionTimer);
    }

    body.classList.add("theme-switching");

    body.classList.remove("claro", "escuro");
    body.classList.add(nextTheme);
    body.dataset.theme = nextTheme;
    if (themeControl) {
      themeControl.dataset.theme = nextTheme;
    }

    darkBtn.classList.toggle("active", isDark);
    lightBtn.classList.toggle("active", !isDark);
    darkBtn.setAttribute("aria-pressed", String(isDark));
    lightBtn.setAttribute("aria-pressed", String(!isDark));
    darkBtn.title = isDark ? "Modo escuro ativo" : "Ativar modo escuro";
    lightBtn.title = isDark ? "Ativar modo claro" : "Modo claro ativo";

    localStorage.setItem(THEME_KEY, nextTheme);

    themeTransitionTimer = window.setTimeout(() => {
      body.classList.remove("theme-switching");
      themeTransitionTimer = null;
    }, 520);
  }

  const savedTheme = localStorage.getItem(THEME_KEY);
  applyTheme(savedTheme);

  lightBtn.addEventListener("click", () => applyTheme("claro"));
  darkBtn.addEventListener("click", () => applyTheme("escuro"));
} else {
  console.warn("ModoEscuro: botoes de tema nao encontrados.");
}
