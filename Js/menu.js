document.addEventListener("DOMContentLoaded", () => {
  const tabs = document.querySelectorAll(".tab-btn");
  let isSwitching = false;

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const target = document.getElementById(tab.dataset.target);
      const current = document.querySelector(".menu-content.active");

      if (!target || isSwitching || current === target) {
        return;
      }

      tabs.forEach((item) => item.classList.remove("active"));
      tab.classList.add("active");

      if (!current) {
        target.classList.add("active");
        return;
      }

      isSwitching = true;
      current.classList.remove("active");
      current.classList.add("closing");

      const finishSwitch = (event) => {
        if (event.animationName !== "fadeOut") {
          return;
        }

        current.classList.remove("closing");
        current.removeEventListener("animationend", finishSwitch);
        target.classList.add("active");
        isSwitching = false;
      };

      current.addEventListener("animationend", finishSwitch);
    });
  });
});
