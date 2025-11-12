document.addEventListener("DOMContentLoaded", () => {
  const tabs = document.querySelectorAll(".tab-btn");
  const contents = document.querySelectorAll(".menu-content");

  tabs.forEach(tab => {
    tab.addEventListener("click", () => {
      // Remove estado ativo de tudo
      tabs.forEach(t => t.classList.remove("active"));
      contents.forEach(c => c.classList.remove("active"));

      // Ativa o botão e o conteúdo correspondente
      tab.classList.add("active");
      const target = document.getElementById(tab.dataset.target);
      target.classList.add("active");
    });
  });
});
