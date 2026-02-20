document.addEventListener("DOMContentLoaded", () => {
  function cdolAdminNormalize(value) {
    return (value || "")
      .toString()
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  function cdolAdminSetupGrid(config) {
    const table = document.getElementById(config.tableId);
    const input = document.getElementById(config.searchInputId);
    const searchBtn = document.getElementById(config.searchButtonId);
    const clearBtn = document.getElementById(config.clearButtonId);
    const emptyMsg = document.getElementById(config.emptyMessageId);
    const prevBtn = document.getElementById(config.prevButtonId);
    const nextBtn = document.getElementById(config.nextButtonId);
    const pageInfo = document.getElementById(config.pageInfoId);

    if (!table) return;

    const rows = Array.from(table.querySelectorAll("tbody tr"));
    let filteredRows = [...rows];
    let page = 1;
    const pageSize = config.pageSize || 12;

    const filters = (config.filters || []).map((f) => ({
      el: document.getElementById(f.selectId),
      attr: f.attribute,
      normalize: f.normalize !== false
    }));

    function rowMatches(row, query) {
      const indexText = cdolAdminNormalize(
        row.getAttribute("data-admin-search") || row.textContent
      );
      if (query && !indexText.includes(query)) return false;

      for (const f of filters) {
        if (!f.el) continue;
        const selected = cdolAdminNormalize(f.el.value);
        if (!selected) continue;
        const raw = row.getAttribute(f.attr) || "";
        const current = f.normalize ? cdolAdminNormalize(raw) : raw;
        if (current !== selected) return false;
      }
      return true;
    }

    function updatePagination() {
      // If pagination controls are not present, show all filtered rows.
      if (!prevBtn && !nextBtn && !pageInfo) {
        rows.forEach((row) => {
          row.style.display = filteredRows.includes(row) ? "" : "none";
        });
        if (emptyMsg) emptyMsg.style.display = filteredRows.length ? "none" : "block";
        return;
      }

      const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
      if (page > totalPages) page = totalPages;

      const start = (page - 1) * pageSize;
      const end = start + pageSize;
      const pageRows = new Set(filteredRows.slice(start, end));

      rows.forEach((row) => {
        row.style.display = pageRows.has(row) ? "" : "none";
      });

      if (pageInfo) pageInfo.textContent = `Página ${page} de ${totalPages}`;
      if (prevBtn) prevBtn.disabled = page <= 1;
      if (nextBtn) nextBtn.disabled = page >= totalPages;
      if (emptyMsg) emptyMsg.style.display = filteredRows.length ? "none" : "block";
    }

    function applyFilters(resetPage = true) {
      const query = input ? cdolAdminNormalize(input.value) : "";
      filteredRows = rows.filter((row) => rowMatches(row, query));
      if (resetPage) page = 1;
      updatePagination();
    }

    if (searchBtn) searchBtn.addEventListener("click", () => applyFilters(true));
    if (input) {
      input.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
          event.preventDefault();
          applyFilters(true);
        }
      });
      input.addEventListener("input", () => applyFilters(true));
    }

    filters.forEach((f) => {
      if (!f.el) return;
      f.el.addEventListener("change", () => applyFilters(true));
    });

    if (clearBtn) {
      clearBtn.addEventListener("click", () => {
        if (input) input.value = "";
        filters.forEach((f) => {
          if (f.el) f.el.value = "";
        });
        applyFilters(true);
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", () => {
        page = Math.max(1, page - 1);
        updatePagination();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
        page = Math.min(totalPages, page + 1);
        updatePagination();
      });
    }

    applyFilters(true);
  }

  cdolAdminSetupGrid({
    tableId: "adminUsersTable",
    searchInputId: "adminUsersSearchInput",
    searchButtonId: "adminUsersSearchBtn",
    clearButtonId: "adminUsersClearBtn",
    emptyMessageId: "adminUsersSearchEmpty",
    pageSize: 10,
    filters: [
      { selectId: "adminUsersEstadoFilter", attribute: "data-estado" },
      { selectId: "adminUsersTipoFilter", attribute: "data-tipo" },
      { selectId: "adminUsersListaNegraFilter", attribute: "data-lista-negra" }
    ]
  });

  cdolAdminSetupGrid({
    tableId: "adminReservasTable",
    searchInputId: "adminReservasSearchInput",
    searchButtonId: "adminReservasSearchBtn",
    clearButtonId: "adminReservasClearBtn",
    emptyMessageId: "adminReservasSearchEmpty",
    prevButtonId: "adminReservasPrevBtn",
    nextButtonId: "adminReservasNextBtn",
    pageInfoId: "adminReservasPageInfo",
    pageSize: 12,
    filters: [
      { selectId: "adminReservasConfirmacaoFilter", attribute: "data-confirmacao" },
      { selectId: "adminReservasEstadoFilter", attribute: "data-estado" }
    ]
  });
});
