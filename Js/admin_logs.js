document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".logs-filters-grid");
  const quickHoje = document.getElementById("logsQuickHoje");
  const quick7 = document.getElementById("logsQuick7");
  const quick30 = document.getElementById("logsQuick30");
  const dataFrom = form ? form.querySelector('input[name="data_from"]') : null;
  const dataTo = form ? form.querySelector('input[name="data_to"]') : null;
  const storageKey = "cdol_logs_filters";
  const sortKey = "cdol_sort_logs_table";

  function fallbackCopy(text) {
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

  async function copyText(text) {
    if (!text) return false;
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return true;
      }
    } catch (_) {}
    return fallbackCopy(text);
  }

  function setIsoDate(input, dateObj) {
    if (!input || !(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) return;
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, "0");
    const d = String(dateObj.getDate()).padStart(2, "0");
    input.value = `${y}-${m}-${d}`;
  }

  function parseCellValue(raw) {
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

    return { type: "text", value: text.toLowerCase() };
  }

  function sortTable(table, columnIndex, direction) {
    const tbody = table.querySelector("tbody");
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll("tr"));
    rows.sort((a, b) => {
      const aText = a.children[columnIndex] ? a.children[columnIndex].innerText : "";
      const bText = b.children[columnIndex] ? b.children[columnIndex].innerText : "";
      const aVal = parseCellValue(aText);
      const bVal = parseCellValue(bText);

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

  function enableSorting() {
    const table = document.querySelector(".logs-table");
    if (!table) return;
    const headers = Array.from(table.querySelectorAll("thead th"));
    let state = { index: -1, direction: "asc" };

    try {
      const saved = localStorage.getItem(sortKey);
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
        sortTable(table, state.index, state.direction);
        try {
          localStorage.setItem(sortKey, JSON.stringify(state));
        } catch (_) {}
      });
    });

    if (state.index >= 0 && headers[state.index]) {
      headers[state.index].setAttribute("data-sort", state.direction);
      sortTable(table, state.index, state.direction);
    }
  }

  function saveFilters() {
    if (!form) return;
    const state = {};
    form.querySelectorAll("input[name], select[name]").forEach((el) => {
      state[el.name] = el.value || "";
    });
    try {
      localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (_) {}
  }

  function loadFilters() {
    if (!form) return false;
    let loaded = false;
    try {
      const raw = localStorage.getItem(storageKey);
      if (!raw) return false;
      const state = JSON.parse(raw);
      form.querySelectorAll("input[name], select[name]").forEach((el) => {
        if (typeof state[el.name] === "string") {
          el.value = state[el.name];
          loaded = true;
        }
      });
    } catch (_) {
      return false;
    }
    return loaded;
  }

  function applyQuickRange(days) {
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const start = new Date(now);
    start.setDate(start.getDate() - (days - 1));
    setIsoDate(dataFrom, start);
    setIsoDate(dataTo, now);
    if (form) form.requestSubmit();
  }

  const hasQueryString = window.location.search && window.location.search.length > 1;
  const loadedFilters = loadFilters();
  if (!hasQueryString && loadedFilters && form) {
    form.requestSubmit();
    return;
  }
  if (form) {
    form.addEventListener("submit", () => saveFilters());
    form.querySelectorAll("input[name], select[name]").forEach((el) => {
      el.addEventListener("change", () => saveFilters());
    });
  }

  if (quickHoje) quickHoje.addEventListener("click", () => applyQuickRange(1));
  if (quick7) quick7.addEventListener("click", () => applyQuickRange(7));
  if (quick30) quick30.addEventListener("click", () => applyQuickRange(30));

  const resetLink = document.querySelector(".logs-reset-link");
  if (resetLink) {
    resetLink.addEventListener("click", () => {
      try {
        localStorage.removeItem(storageKey);
      } catch (_) {}
    });
  }

  document.querySelectorAll(".logs-email-text, .admin-email-text").forEach((el) => {
    el.classList.add("email-copyable");
    el.title = `${el.textContent.trim()} (clique para copiar)`;
    el.addEventListener("click", async (event) => {
      event.preventDefault();
      const value = el.textContent.trim();
      if (!value || value === "-") return;
      const copied = await copyText(value);
      if (copied) {
        el.classList.add("copied");
        setTimeout(() => el.classList.remove("copied"), 900);
      }
    });
  });

  enableSorting();
});
