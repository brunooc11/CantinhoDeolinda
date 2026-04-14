const lightBtn = document.getElementById("claro-btn");
const darkBtn = document.getElementById("escuro-btn");
const body = document.body;
const root = document.documentElement;
const themeControl = document.querySelector(".controlo");
let themeTransitionTimer = null;
const THEME_KEY = "cdol_theme";
const VALID_THEMES = new Set(["claro", "escuro"]);

if (body) {
  const hasThemeButtons = Boolean(lightBtn && darkBtn);

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
    if (root) {
      root.classList.remove("claro", "escuro");
      root.classList.add(nextTheme);
      root.dataset.theme = nextTheme;
    }
    if (themeControl) {
      themeControl.dataset.theme = nextTheme;
    }

    if (hasThemeButtons) {
      darkBtn.classList.toggle("active", isDark);
      lightBtn.classList.toggle("active", !isDark);
      darkBtn.setAttribute("aria-pressed", String(isDark));
      lightBtn.setAttribute("aria-pressed", String(!isDark));
      darkBtn.title = isDark ? "Modo escuro ativo" : "Ativar modo escuro";
      lightBtn.title = isDark ? "Ativar modo claro" : "Modo claro ativo";
    }

    if (localStorage.getItem(THEME_KEY) !== nextTheme) {
      localStorage.setItem(THEME_KEY, nextTheme);
    }

    themeTransitionTimer = window.setTimeout(() => {
      body.classList.remove("theme-switching");
      themeTransitionTimer = null;
    }, 520);
  }

  const savedTheme = localStorage.getItem(THEME_KEY);
  applyTheme(savedTheme);

  if (hasThemeButtons) {
    lightBtn.addEventListener("click", () => applyTheme("claro"));
    darkBtn.addEventListener("click", () => applyTheme("escuro"));
  }

  window.addEventListener("storage", (event) => {
    if (event.key === THEME_KEY) {
      applyTheme(event.newValue);
    }
  });
}
