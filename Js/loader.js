// js/loader.js (corrigido)
window.addEventListener("load", () => {
  const loader = document.getElementById("loaderWrapper");
  const mainContent = document.getElementById("mainContent");

  if (!loader || !mainContent) return;

  // garante estado inicial do conteúdo
  mainContent.classList.remove("visible");
  mainContent.style.opacity = 0;
  mainContent.style.visibility = "hidden";
  document.body.style.overflow = "hidden";

  // tempo da intro (3 segundos)
  setTimeout(() => {
    // inicia fade-out do loader
    loader.classList.add("fade-out");

    // mostra conteúdo do site com fade-in
    mainContent.style.visibility = "visible";
    mainContent.style.transition = "opacity 600ms ease";
    mainContent.style.opacity = "1";
    mainContent.classList.add("visible");

    // remove o loader do DOM quando a transição acabar
    loader.addEventListener("transitionend", () => {
      if (loader.parentNode) loader.parentNode.removeChild(loader);
      document.body.style.overflow = "auto"; // reativa scroll
    });
  }, 3000); // duração da intro
});
