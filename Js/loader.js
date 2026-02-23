window.addEventListener("load", () => {
  const loader = document.getElementById("loaderWrapper");
  const mainContent = document.getElementById("mainContent");
  if (!loader || !mainContent) {
    return;
  }

  const INTRO_DURATION_MS = 3000;
  const CLEANUP_DELAY_MS = 900;

  mainContent.classList.remove("visible");
  document.body.style.overflow = "hidden";

  setTimeout(() => {
    loader.classList.add("fade-out");
    mainContent.classList.add("visible");

    const cleanup = () => {
      if (loader.parentNode) {
        loader.parentNode.removeChild(loader);
      }
      document.body.style.overflow = "auto";
    };

    loader.addEventListener("transitionend", cleanup, { once: true });
    setTimeout(cleanup, CLEANUP_DELAY_MS + 120);
  }, INTRO_DURATION_MS);
});
