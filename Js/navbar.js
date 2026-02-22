const navbar = document.querySelector(".navbar");
const navLinks = document.querySelectorAll('.menu a[href^="#"]');
const sections = Array.from(navLinks)
  .map((link) => document.querySelector(link.getAttribute("href")))
  .filter(Boolean);

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

  const offset = 140;
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

window.addEventListener("scroll", () => {
  updateNavbarState();
  updateActiveLink();
});

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
  });
});

window.addEventListener("load", () => {
  updateNavbarState();
  updateActiveLink();
});
