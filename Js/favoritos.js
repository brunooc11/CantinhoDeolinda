(() => {
  const menuRoot = document.querySelector(".menu-categorias");
  if (!menuRoot) return;

  const isLoggedIn = document.body.dataset.loggedIn === "1";
  const favoritos = new Set();

  function slugify(text) {
    return text
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "");
  }

  function makeFavButton(itemId, itemNome) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "fav-btn";
    btn.dataset.itemId = itemId;
    btn.dataset.itemNome = itemNome;
    btn.setAttribute("aria-label", `Favoritar ${itemNome}`);
    btn.innerHTML = "♡";
    return btn;
  }

  function emitFavoritosUpdated() {
    window.dispatchEvent(
      new CustomEvent("favoritos:updated", { detail: { ids: Array.from(favoritos) } })
    );
  }

  function setFavState(btn, active) {
    btn.classList.toggle("is-favorite", active);
    btn.innerHTML = active ? "♥" : "♡";
  }

  function isMatchingCarouselCard(cardId, itemId) {
    if (!cardId || !itemId) return false;
    if (cardId === itemId) return true;
    return cardId.endsWith(`-${itemId}`) || cardId.startsWith(`${itemId}-`);
  }

  function forceCarouselCardPosition(itemId, shouldBeFavorite) {
    const carousel = document.getElementById("carousel");
    if (!carousel) return;

    const cards = Array.from(carousel.querySelectorAll(".card"));
    const target = cards.find((card) =>
      isMatchingCarouselCard(card.dataset.itemId || "", itemId)
    );
    if (!target) return;

    if (shouldBeFavorite) {
      carousel.prepend(target);
      carousel.style.transform = "translate3d(0%, 0, 0)";
      return;
    }

    window.dispatchEvent(new CustomEvent("favoritos:refresh-required"));
  }

  async function api(acao, payload) {
    const res = await fetch("Bd/favoritos.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ acao, ...payload })
    });
    return res.json();
  }

  function attachButtons() {
    const seen = new Set();

    menuRoot.querySelectorAll(".menu-content .item").forEach((card) => {
      const heading = card.querySelector("h3");

      const listRows = card.querySelectorAll(".menu-list li");
      if (listRows.length > 0) {
        listRows.forEach((li) => {
          const nameEl = li.querySelector("span");
          const priceEl = li.querySelector("strong");
          if (!nameEl) return;

          const name = nameEl.textContent.trim();
          const price = priceEl ? priceEl.textContent.trim() : "";
          const itemNome = price ? `${name} - ${price}` : name;
          const itemId = slugify(name);
          if (seen.has(itemId)) return;
          seen.add(itemId);

          const btn = makeFavButton(itemId, itemNome);
          li.appendChild(btn);
        });
        return;
      }

      if (heading) {
        const spanPrice = heading.querySelector("span");
        const itemName = spanPrice
          ? heading.textContent.replace(spanPrice.textContent, "").trim()
          : heading.textContent.trim();
        const itemPrice = spanPrice ? spanPrice.textContent.trim() : "";
        if (!itemName) return;

        const itemNome = itemPrice ? `${itemName} - ${itemPrice}` : itemName;
        const itemId = slugify(itemName);
        if (seen.has(itemId)) return;
        seen.add(itemId);

        const btn = makeFavButton(itemId, itemNome);
        heading.appendChild(btn);
      }
    });
  }

  async function syncFavoriteStates() {
    const buttons = document.querySelectorAll(".fav-btn");
    buttons.forEach((btn) => setFavState(btn, favoritos.has(btn.dataset.itemId)));
  }

  async function loadFavoritos() {
    if (!isLoggedIn) return;
    const res = await fetch(`Bd/favoritos.php?acao=listar&ts=${Date.now()}`, {
      cache: "no-store"
    });
    if (!res.ok) return;
    const data = await res.json();
    if (!data.ok || !Array.isArray(data.ids)) return;
    favoritos.clear();
    data.ids.forEach((id) => favoritos.add(id));
  }

  async function syncFavoritosFromServer() {
    await loadFavoritos();
    await syncFavoriteStates();
    emitFavoritosUpdated();
  }

  async function onClick(event) {
    const btn = event.target.closest(".fav-btn");
    if (!btn) return;

    if (!isLoggedIn) {
      window.location.href = "login.php";
      return;
    }

    const itemId = btn.dataset.itemId;
    const itemNome = btn.dataset.itemNome || itemId;
    const isFav = favoritos.has(itemId);

    btn.disabled = true;
    try {
      // Atualizacao otimista para o carrossel responder imediatamente.
      if (isFav) favoritos.delete(itemId);
      else favoritos.add(itemId);
      setFavState(btn, !isFav);
      forceCarouselCardPosition(itemId, !isFav);
      emitFavoritosUpdated();

      const data = await api(isFav ? "remover" : "adicionar", {
        item_id: itemId,
        item_nome: itemNome
      });
      if (data.ok) {
        await syncFavoritosFromServer();
        window.dispatchEvent(new CustomEvent("favoritos:refresh-required"));
        if (typeof window.CD_refreshCarouselFavorites === "function") {
          await window.CD_refreshCarouselFavorites();
        }
      } else {
        // Reverte estado otimista caso falhe no backend.
        if (isFav) favoritos.add(itemId);
        else favoritos.delete(itemId);
        setFavState(btn, isFav);
        forceCarouselCardPosition(itemId, isFav);
        emitFavoritosUpdated();
      }
    } catch (e) {
      if (isFav) favoritos.add(itemId);
      else favoritos.delete(itemId);
      setFavState(btn, isFav);
      forceCarouselCardPosition(itemId, isFav);
      emitFavoritosUpdated();
    } finally {
      btn.disabled = false;
    }
  }

  async function init() {
    attachButtons();
    await syncFavoritosFromServer();
    menuRoot.addEventListener("click", onClick);
  }

  init();
})();
