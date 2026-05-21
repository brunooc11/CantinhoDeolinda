let index = 0;
const carousel = document.getElementById("carousel");
let cards = carousel ? Array.from(carousel.querySelectorAll(".card")) : [];
let totalCards = cards.length;
const isLoggedIn = document.body.dataset.loggedIn === "1";
let originalOrder = new Map();
let dotsContainer = null;
let slideAnimTimeout = null;

const sectionImages = {
  especialidades: [
    "https://images.unsplash.com/photo-1544025162-d76694265947",
    "https://images.unsplash.com/photo-1600891964092-4316c288032e"
  ],
  "menu-estudante": [
    "https://images.unsplash.com/photo-1546069901-ba9599a7e63c"
  ],
  sopas: [
    "https://images.unsplash.com/photo-1547592180-85f173990554"
  ],
  bebidas: [
    "https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd"
  ],
  default: [
    "https://images.unsplash.com/photo-1504674900247-0877df9cc836"
  ]
};

const itemImages = {
  "bacalhau-a-casa": "https://images.unsplash.com/photo-1764333580740-b327847301b1",
  "bacalhau-a-lagareiro": "https://images.unsplash.com/photo-1729553310340-02b916e68ed5",
  "acorda-de-bacalhau-com-gambas-no-pao": "https://images.unsplash.com/photo-1614549099339-729737b3cfd9",
  "polvo-a-lagareiro": "https://images.unsplash.com/photo-1731601816614-2423297fe97b",
  "bife-a-casa": "https://images.unsplash.com/photo-1657143375273-75371e23f7f0",
  "espetadas-de-porco-preto": "https://images.unsplash.com/photo-1717809184558-597a0f1b9eb0",
  picanha: "https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba",
  "costeleta-de-novilho": "https://images.unsplash.com/photo-1546964124-0cce460f38ef",
  secretos: "https://images.unsplash.com/photo-1569352304334-6e422cd70ecd",
  "cozido-a-portuguesa": "https://images.unsplash.com/photo-1770164678239-89706708a496",
  "mini-prato-bebida": "https://images.unsplash.com/photo-1546069901-ba9599a7e63c",
  "sopa-de-legumes": "https://images.unsplash.com/photo-1547592180-85f173990554",
  "sopa-de-peixe": "https://images.unsplash.com/photo-1547592166-23ac45744acd",
  "vinho-da-casa": "https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd",
  "sumo-natural": "https://images.unsplash.com/photo-1640625488786-ac3975ac3bf4"
};

function slugify(text) {
  return text
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function getVisibleCards() {
  if (window.innerWidth <= 680) return 1;
  if (window.innerWidth <= 1024) return 2;
  return 4;
}

function refreshCardsAndOrder() {
  cards = carousel ? Array.from(carousel.querySelectorAll(".card")) : [];
  totalCards = cards.length;
  originalOrder = new Map();
  cards.forEach((card, idx) => originalOrder.set(card, idx));
}

function getMaxIndex() {
  const visibleCards = getVisibleCards();
  return Math.max(0, totalCards - visibleCards);
}

function ensureIndicators() {
  if (!carousel) return;
  const parent = carousel.parentElement;
  if (!parent) return;

  if (!dotsContainer) {
    dotsContainer = parent.querySelector(".carousel-indicators");
  }

  if (!dotsContainer) {
    dotsContainer = document.createElement("div");
    dotsContainer.className = "carousel-indicators";
    parent.appendChild(dotsContainer);
  }
}

function updateIndicators() {
  if (!carousel) return;
  ensureIndicators();
  if (!dotsContainer) return;

  const maxIndex = getMaxIndex();
  const totalSteps = maxIndex + 1;
  const currentDots = Array.from(dotsContainer.querySelectorAll(".carousel-dot"));

  if (currentDots.length !== totalSteps) {
    dotsContainer.innerHTML = "";
    for (let i = 0; i < totalSteps; i += 1) {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = "carousel-dot";
      dot.setAttribute("aria-label", `Ir para slide ${i + 1}`);
      dot.dataset.index = String(i);
      dotsContainer.appendChild(dot);
    }
  }

  Array.from(dotsContainer.querySelectorAll(".carousel-dot")).forEach((dot, i) => {
    const active = i === index;
    dot.classList.toggle("active", active);
    if (active) dot.setAttribute("aria-current", "true");
    else dot.removeAttribute("aria-current");
  });
}

function markSlideAnimating() {
  if (!carousel) return;
  carousel.classList.add("is-sliding");
  if (slideAnimTimeout) clearTimeout(slideAnimTimeout);
  slideAnimTimeout = setTimeout(() => {
    carousel.classList.remove("is-sliding");
  }, 560);
}

function applySlidePosition() {
  if (!carousel) return;
  const visibleCards = getVisibleCards();
  const maxIndex = getMaxIndex();
  index = Math.min(index, maxIndex);
  carousel.style.transform = `translate3d(${-index * (100 / visibleCards)}%, 0, 0)`;
  updateIndicators();
}

function moveSlide(step) {
  if (!carousel) return;
  const visibleCards = getVisibleCards();
  const maxIndex = getMaxIndex();
  index = Math.min(Math.max(index + step, 0), maxIndex);
  carousel.style.transform = `translate3d(${-index * (100 / visibleCards)}%, 0, 0)`;
  markSlideAnimating();
  updateIndicators();
}

function pickImage(sectionId, idx) {
  const pool = sectionImages[sectionId] || sectionImages.default;
  return pool[idx % pool.length];
}

function getImageForItem(nome, sectionId, idx) {
  const id = slugify(nome);
  const raw = itemImages[id] || pickImage(sectionId, idx);
  const hasQuery = raw.includes("?");
  return `${raw}${hasQuery ? "&" : "?"}auto=format&fit=crop&w=900&q=70`;
}

function extractMenuItems() {
  const sections = document.querySelectorAll(".menu-categorias .menu-content");
  const items = [];
  const seen = new Set();

  sections.forEach((section) => {
    const sectionId = section.id || "default";
    let localIndex = 0;

    section.querySelectorAll(".item").forEach((itemBox) => {
      const listRows = itemBox.querySelectorAll(".menu-list li");
      if (listRows.length > 0) {
        listRows.forEach((li) => {
          const nameEl = li.querySelector("span");
          const priceEl = li.querySelector("strong");
          if (!nameEl) return;

          const nome = nameEl.textContent.trim();
          const preco = priceEl ? priceEl.textContent.trim() : "";
          const id = slugify(nome);
          if (!id || seen.has(id)) return;
          seen.add(id);

          items.push({
            id,
            label: preco ? `${nome} - ${preco}` : nome,
            image: getImageForItem(nome, sectionId, localIndex++)
          });
        });
        return;
      }

      const h3 = itemBox.querySelector("h3");
      if (!h3) return;
      const h3Clone = h3.cloneNode(true);
      h3Clone.querySelectorAll(".fav-btn").forEach((btn) => btn.remove());
      const priceEl = h3Clone.querySelector("span");
      const nome = priceEl
        ? h3Clone.textContent.replace(priceEl.textContent, "").trim()
        : h3Clone.textContent.trim();
      const preco = priceEl ? priceEl.textContent.trim() : "";
      const id = slugify(nome);
      if (!id || seen.has(id)) return;
      seen.add(id);

      items.push({
        id,
        label: preco ? `${nome} - ${preco}` : nome,
        image: getImageForItem(nome, sectionId, localIndex++)
      });
    });
  });

  return items;
}

function buildCarouselFromMenu() {
  if (!carousel) return;
  const items = extractMenuItems();
  if (!items.length) return;

  const fragment = document.createDocumentFragment();
  items.forEach((item, idx) => {
    const card = document.createElement("div");
    card.className = "card";
    card.dataset.itemId = item.id;
    card.innerHTML = `
      <img
        src="${item.image}"
        alt="${item.label}"
        loading="${idx < 4 ? "eager" : "lazy"}"
        decoding="async"
        fetchpriority="${idx < 2 ? "high" : "low"}"
      >
      <div class="overlay">${item.label}</div>
    `;
    fragment.appendChild(card);
  });

  carousel.innerHTML = "";
  carousel.appendChild(fragment);
  refreshCardsAndOrder();
  index = 0;
  applySlidePosition();
}

function reorderCarouselByFavorites(favIds) {
  if (!carousel || !Array.isArray(favIds)) return;
  refreshCardsAndOrder();

  const favSet = new Set(favIds);
  const isFavCard = (cardId) => {
    if (!cardId) return false;
    if (favSet.has(cardId)) return true;

    for (const id of favSet) {
      if (typeof id !== "string") continue;
      if (id.endsWith(`-${cardId}`) || id.startsWith(`${cardId}-`)) return true;
    }
    return false;
  };

  const ordered = cards.slice().sort((a, b) => {
    const aFav = isFavCard(a.dataset.itemId);
    const bFav = isFavCard(b.dataset.itemId);
    if (aFav === bFav) return (originalOrder.get(a) ?? 0) - (originalOrder.get(b) ?? 0);
    return aFav ? -1 : 1;
  });

  ordered.forEach((card) => carousel.appendChild(card));
  cards = ordered;
  totalCards = cards.length;
  originalOrder = new Map();
  cards.forEach((card, idx) => originalOrder.set(card, idx));
  index = 0;
  applySlidePosition();
}

async function loadCarouselFavorites() {
  if (!isLoggedIn || !carousel) return;
  try {
    const res = await fetch("Bd/favoritos.php", {
      method: "POST",
      cache: "no-store",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ acao: "listar", ts: Date.now() })
    });
    if (!res.ok) return;
    const data = await res.json();
    if (data.ok && Array.isArray(data.ids)) reorderCarouselByFavorites(data.ids);
  } catch (e) {
    console.error("Erro ao carregar favoritos do carrossel:", e);
  }
}

let resizeRaf = null;
window.addEventListener("resize", () => {
  if (resizeRaf) cancelAnimationFrame(resizeRaf);
  resizeRaf = requestAnimationFrame(() => {
    resizeRaf = null;
    applySlidePosition();
  });
});
window.addEventListener("load", async () => {
  buildCarouselFromMenu();
  applySlidePosition();
  await loadCarouselFavorites();
});

window.addEventListener("click", (event) => {
  const dot = event.target.closest(".carousel-dot");
  if (!dot) return;
  const targetIndex = parseInt(dot.dataset.index || "0", 10);
  if (Number.isNaN(targetIndex)) return;
  index = targetIndex;
  markSlideAnimating();
  applySlidePosition();
});

window.addEventListener("favoritos:updated", (event) => {
  const ids = event.detail?.ids || [];
  reorderCarouselByFavorites(ids);
});

window.addEventListener("favoritos:refresh-required", async () => {
  await loadCarouselFavorites();
});

window.CD_refreshCarouselFavorites = loadCarouselFavorites;
