const navbar = document.querySelector(".navbar");
const menu = document.querySelector(".menu");
const navLinks = document.querySelectorAll('.menu a[href^="#"]');
const sections = Array.from(navLinks)
  .map((link) => document.querySelector(link.getAttribute("href")))
  .filter(Boolean);

let indicator = null;
let rafPending = false;

function ensureIndicator() {
  if (!menu || indicator) {
    return;
  }

  indicator = document.createElement("span");
  indicator.className = "menu-indicator";
  menu.appendChild(indicator);
}

function updateIndicatorPosition() {
  if (!menu || !indicator || !navLinks.length) {
    return;
  }

  const activeLink = document.querySelector(".menu a.active") || navLinks[0];
  if (!activeLink) {
    indicator.classList.remove("is-visible");
    return;
  }

  const menuRect = menu.getBoundingClientRect();
  const linkRect = activeLink.getBoundingClientRect();
  const x = linkRect.left - menuRect.left + menu.scrollLeft + 12;
  const width = Math.max(0, linkRect.width - 24);

  indicator.style.transform = `translateX(${x}px)`;
  indicator.style.width = `${width}px`;
  indicator.classList.add("is-visible");
}

function scheduleNavUpdate() {
  if (rafPending) {
    return;
  }

  rafPending = true;
  requestAnimationFrame(() => {
    rafPending = false;
    updateNavbarState();
    updateActiveLink();
    updateIndicatorPosition();
  });
}

function getNavbarOffset() {
  if (!navbar) {
    return 0;
  }

  return navbar.getBoundingClientRect().height + 14;
}

function updateNavbarState() {
  if (!navbar) {
    return;
  }

  const isCompact = window.scrollY > 28;
  navbar.classList.toggle("navbar-compact", isCompact);
}

function updateActiveLink() {
  if (!navLinks.length || !sections.length) {
    return;
  }

  const offset = getNavbarOffset() + Math.min(window.innerHeight * 0.22, 140);
  let activeId = sections[0].id;

  for (const section of sections) {
    if (window.scrollY + offset >= section.offsetTop) {
      activeId = section.id;
    }
  }

  navLinks.forEach((link) => {
    const targetId = link.getAttribute("href").replace("#", "");
    link.classList.toggle("active", targetId === activeId);
  });
}

window.addEventListener("scroll", scheduleNavUpdate, { passive: true });
window.addEventListener("resize", scheduleNavUpdate);
window.addEventListener("orientationchange", scheduleNavUpdate);
if (menu) {
  menu.addEventListener("scroll", scheduleNavUpdate, { passive: true });
}

navLinks.forEach((link) => {
  link.addEventListener("click", (event) => {
    const targetSelector = link.getAttribute("href");
    const target = targetSelector ? document.querySelector(targetSelector) : null;

    if (!target) {
      return;
    }

    event.preventDefault();

    const targetTop = target.getBoundingClientRect().top + window.scrollY - getNavbarOffset();
    window.scrollTo({
      top: Math.max(0, targetTop),
      behavior: "smooth"
    });

    scheduleNavUpdate();
  });
});

window.addEventListener("load", () => {
  ensureIndicator();
  scheduleNavUpdate();
});
