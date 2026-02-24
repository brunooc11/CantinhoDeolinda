document.addEventListener("DOMContentLoaded", () => {
  function cdolAdminNormalize(value) {
    return (value || "")
      .toString()
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  function cdolSetIsoDate(input, dateObj) {
    if (!input || !(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) return;
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, "0");
    const day = String(dateObj.getDate()).padStart(2, "0");
    input.value = `${year}-${month}-${day}`;
  }

  function cdolCsvEscape(value) {
    const text = (value ?? "").toString().replace(/\r?\n|\r/g, " ").trim();
    if (text.includes(";") || text.includes('"')) {
      return `"${text.replace(/"/g, '""')}"`;
    }
    return text;
  }

  function cdolDownloadCsv(filename, header, rows) {
    const lines = [header.map(cdolCsvEscape).join(";")];
    rows.forEach((row) => lines.push(row.map(cdolCsvEscape).join(";")));
    const csv = "\uFEFF" + lines.join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function cdolFallbackCopy(text) {
    const ta = document.createElement("textarea");
    ta.value = text;
    ta.setAttribute("readonly", "");
    ta.style.position = "fixed";
    ta.style.left = "-9999px";
    ta.style.top = "0";
    document.body.appendChild(ta);
    ta.select();
    let ok = false;
    try {
      ok = document.execCommand("copy");
    } catch (_) {
      ok = false;
    }
    document.body.removeChild(ta);
    return ok;
  }

  async function cdolCopyText(text) {
    if (!text) return false;
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return true;
      }
    } catch (_) {}
    return cdolFallbackCopy(text);
  }

  function cdolParseCellValue(raw) {
    const text = (raw || "").toString().trim();
    if (!text) return { type: "text", value: "" };

    const dateMatch = text.match(/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}))?$/);
    if (dateMatch) {
      const dd = Number(dateMatch[1]);
      const mm = Number(dateMatch[2]) - 1;
      const yyyy = Number(dateMatch[3]);
      const hh = Number(dateMatch[4] || "0");
      const mi = Number(dateMatch[5] || "0");
      const ts = new Date(yyyy, mm, dd, hh, mi).getTime();
      return { type: "number", value: Number.isNaN(ts) ? 0 : ts };
    }

    const numeric = Number(text.replace(/[^\d.-]/g, ""));
    if (!Number.isNaN(numeric) && /[\d]/.test(text)) {
      return { type: "number", value: numeric };
    }

    return { type: "text", value: cdolAdminNormalize(text) };
  }

  function cdolSortTable(table, columnIndex, direction) {
    const tbody = table.querySelector("tbody");
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll("tr"));
    rows.sort((a, b) => {
      const aText = a.children[columnIndex] ? a.children[columnIndex].innerText : "";
      const bText = b.children[columnIndex] ? b.children[columnIndex].innerText : "";
      const aVal = cdolParseCellValue(aText);
      const bVal = cdolParseCellValue(bText);

      let cmp = 0;
      if (aVal.type === "number" && bVal.type === "number") {
        cmp = aVal.value - bVal.value;
      } else {
        cmp = String(aVal.value).localeCompare(String(bVal.value), "pt", { sensitivity: "base" });
      }
      return direction === "asc" ? cmp : -cmp;
    });
    rows.forEach((row) => tbody.appendChild(row));
  }

  function cdolEnableSorting(tableId, storageKey) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const headers = Array.from(table.querySelectorAll("thead th"));
    let state = { index: -1, direction: "asc" };

    try {
      const saved = localStorage.getItem(storageKey);
      if (saved) {
        const parsed = JSON.parse(saved);
        if (typeof parsed.index === "number" && (parsed.direction === "asc" || parsed.direction === "desc")) {
          state = parsed;
        }
      }
    } catch (_) {}

    headers.forEach((th, index) => {
      th.classList.add("sortable-th");
      th.addEventListener("click", () => {
        if (state.index === index) {
          state.direction = state.direction === "asc" ? "desc" : "asc";
        } else {
          state.index = index;
          state.direction = "asc";
        }

        headers.forEach((h) => h.removeAttribute("data-sort"));
        th.setAttribute("data-sort", state.direction);
        cdolSortTable(table, state.index, state.direction);
        try {
          localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (_) {}
      });
    });

    if (state.index >= 0 && headers[state.index]) {
      headers[state.index].setAttribute("data-sort", state.direction);
      cdolSortTable(table, state.index, state.direction);
    }
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
    const extraMatcher = typeof config.extraMatcher === "function" ? config.extraMatcher : null;
    const persistenceKey = config.persistenceKey || null;
    const persistIds = Array.isArray(config.persistIds) ? config.persistIds : [];

    function saveState() {
      if (!persistenceKey) return;
      const state = {};
      if (input) state[config.searchInputId] = input.value || "";
      filters.forEach((f) => {
        if (f.el && f.el.id) state[f.el.id] = f.el.value || "";
      });
      persistIds.forEach((id) => {
        const el = document.getElementById(id);
        if (el) state[id] = el.value || "";
      });
      try {
        localStorage.setItem(persistenceKey, JSON.stringify(state));
      } catch (_) {}
    }

    function loadState() {
      if (!persistenceKey) return;
      try {
        const raw = localStorage.getItem(persistenceKey);
        if (!raw) return;
        const state = JSON.parse(raw);
        if (input && typeof state[config.searchInputId] === "string") {
          input.value = state[config.searchInputId];
        }
        filters.forEach((f) => {
          if (f.el && f.el.id && typeof state[f.el.id] === "string") {
            f.el.value = state[f.el.id];
          }
        });
        persistIds.forEach((id) => {
          const el = document.getElementById(id);
          if (el && typeof state[id] === "string") {
            el.value = state[id];
          }
        });
      } catch (_) {}
    }

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
      if (extraMatcher && !extraMatcher(row)) return false;
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
      saveState();
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
        if (typeof config.onClear === "function") {
          config.onClear();
        }
        if (persistenceKey) {
          try {
            localStorage.removeItem(persistenceKey);
          } catch (_) {}
        }
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

    loadState();
    applyFilters(true);
  }

  cdolAdminSetupGrid({
    tableId: "adminUsersTable",
    searchInputId: "adminUsersSearchInput",
    searchButtonId: "adminUsersSearchBtn",
    clearButtonId: "adminUsersClearBtn",
    emptyMessageId: "adminUsersSearchEmpty",
    pageSize: 10,
    persistenceKey: "cdol_admin_users_filters",
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
    persistenceKey: "cdol_admin_reservas_filters",
    persistIds: ["adminReservasCriacaoPeriodoFilter", "adminReservasDataFromFilter", "adminReservasDataToFilter"],
    filters: [
      { selectId: "adminReservasConfirmacaoFilter", attribute: "data-confirmacao" },
      { selectId: "adminReservasEstadoFilter", attribute: "data-estado" }
    ],
    extraMatcher: (row) => {
      const createdPeriod = document.getElementById("adminReservasCriacaoPeriodoFilter");
      const fromInput = document.getElementById("adminReservasDataFromFilter");
      const toInput = document.getElementById("adminReservasDataToFilter");

      const createdDate = row.getAttribute("data-criada-em") || "";
      const reserveDate = row.getAttribute("data-data-reserva") || "";

      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (createdPeriod && createdPeriod.value) {
        if (!createdDate) return false;
        const created = new Date(`${createdDate}T00:00:00`);
        if (Number.isNaN(created.getTime())) return false;

        if (createdPeriod.value === "hoje") {
          if (created.toDateString() !== today.toDateString()) return false;
        } else {
          const days = Number(createdPeriod.value);
          if (!Number.isNaN(days) && days > 0) {
            const from = new Date(today);
            from.setDate(from.getDate() - (days - 1));
            if (created < from || created > today) return false;
          }
        }
      }

      const fromDateValue = fromInput ? fromInput.value : "";
      const toDateValue = toInput ? toInput.value : "";
      if ((fromDateValue || toDateValue) && !reserveDate) return false;

      if (fromDateValue && reserveDate < fromDateValue) return false;
      if (toDateValue && reserveDate > toDateValue) return false;

      return true;
    },
    onClear: () => {
      const createdPeriod = document.getElementById("adminReservasCriacaoPeriodoFilter");
      const fromInput = document.getElementById("adminReservasDataFromFilter");
      const toInput = document.getElementById("adminReservasDataToFilter");
      if (createdPeriod) createdPeriod.value = "";
      if (fromInput) fromInput.value = "";
      if (toInput) toInput.value = "";
    }
  });

  ["adminReservasCriacaoPeriodoFilter", "adminReservasDataFromFilter", "adminReservasDataToFilter"].forEach((id) => {
    const el = document.getElementById(id);
    const trigger = document.getElementById("adminReservasSearchBtn");
    if (el && trigger) {
      el.addEventListener("change", () => trigger.click());
    }
  });

  const reservasSearchTrigger = document.getElementById("adminReservasSearchBtn");
  const reservasFrom = document.getElementById("adminReservasDataFromFilter");
  const reservasTo = document.getElementById("adminReservasDataToFilter");
  const quickHoje = document.getElementById("adminReservasQuickHoje");
  const quick7 = document.getElementById("adminReservasQuick7");
  const quick30 = document.getElementById("adminReservasQuick30");

  function applyQuickRange(days) {
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const start = new Date(now);
    start.setDate(start.getDate() - (days - 1));
    cdolSetIsoDate(reservasFrom, start);
    cdolSetIsoDate(reservasTo, now);
    if (reservasSearchTrigger) reservasSearchTrigger.click();
  }

  if (quickHoje) quickHoje.addEventListener("click", () => applyQuickRange(1));
  if (quick7) quick7.addEventListener("click", () => applyQuickRange(7));
  if (quick30) quick30.addEventListener("click", () => applyQuickRange(30));

  const exportReservasBtn = document.getElementById("adminReservasExportCsvBtn");
  if (exportReservasBtn) {
    exportReservasBtn.addEventListener("click", () => {
      const table = document.getElementById("adminReservasTable");
      if (!table) return;

      const headers = [
        "ID",
        "Cliente",
        "Email",
        "Telefone",
        "Data Reserva",
        "Hora Reserva",
        "Pessoas",
        "Criada em",
        "Confirmação",
        "Estado"
      ];
      const rows = Array.from(table.querySelectorAll("tbody tr"))
        .filter((tr) => tr.style.display !== "none")
        .map((tr) => {
          const tds = Array.from(tr.querySelectorAll("td"));
          return tds
            .slice(0, 10)
            .map((td) => td.innerText.replace(/\s+/g, " ").trim());
        });

      if (rows.length === 0) return;

      const filename = `reservas_filtradas_${new Date().toISOString().slice(0, 19).replace(/[-:T]/g, "")}.csv`;
      cdolDownloadCsv(filename, headers, rows);
    });
  }

  cdolEnableSorting("adminUsersTable", "cdol_sort_admin_users");
  cdolEnableSorting("adminReservasTable", "cdol_sort_admin_reservas");
  cdolEnableSorting("adminAuditTable", "cdol_sort_admin_audit");
  cdolEnableSorting("adminBlacklistTable", "cdol_sort_admin_blacklist");

  document.querySelectorAll(".admin-email-text, .logs-email-text").forEach((el) => {
    el.classList.add("email-copyable");
    el.title = `${el.textContent.trim()} (clique para copiar)`;
    el.addEventListener("click", async (event) => {
      event.preventDefault();
      const value = el.textContent.trim();
      if (!value) return;
      const copied = await cdolCopyText(value);
      if (copied) {
        el.classList.add("copied");
        setTimeout(() => el.classList.remove("copied"), 900);
      }
    });
  });
});
