document.addEventListener("DOMContentLoaded", () => {
  const cdolMainContent = document.getElementById("mainContent");
  if (!cdolMainContent) return;

  const selectors = [
    "section",
    ".landing-page .left",
    ".landing-page .right",
    ".menu-tabs",
    ".menu-content .tab-pane",
    ".event-card",
    ".catering-info",
    ".catering-box",
    ".info_adicionais-card",
    ".contact-card",
    ".prefooter",
    ".footer"
  ];

  const cdolTargets = new Set();
  selectors.forEach((selector) => {
    cdolMainContent.querySelectorAll(selector).forEach((el) => cdolTargets.add(el));
  });

  const cdolRevealList = Array.from(cdolTargets).filter((el) => {
    if (!(el instanceof HTMLElement)) return false;
    if (el.closest("#reservaModal")) return false;
    return true;
  });

  cdolRevealList.forEach((el, idx) => {
    el.classList.add("cdol-reveal-on-scroll");
    el.style.setProperty("--cdol-reveal-delay", `${(idx % 5) * 60}ms`);
  });

  if (!("IntersectionObserver" in window)) {
    cdolRevealList.forEach((el) => el.classList.add("cdol-reveal-visible"));
    return;
  }

  const cdolRevealObserver = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("cdol-reveal-visible");
        obs.unobserve(entry.target);
      });
    },
    {
      threshold: 0.14,
      rootMargin: "0px 0px -8% 0px"
    }
  );

  cdolRevealList.forEach((el) => cdolRevealObserver.observe(el));
});
