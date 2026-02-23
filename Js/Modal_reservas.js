document.addEventListener("DOMContentLoaded", function () {
  function showReservaPopup(message, type = "error") {
    if (typeof window.__cdShowPopup === "function") {
      window.__cdShowPopup(message, type);
      return;
    }
    window.alert(message);
  }

  const modal = document.getElementById("reservaModal");
  const btn = document.getElementById("openReservaModal");
  const closeBtn = document.getElementById("closeReserva");
  const horaInput = document.getElementById("horaInput");
  const minInput = document.getElementById("minInput");
  const confirmBtn = document.getElementById("confirmBtn");

  // Esconde o modal inicialmente
  if (modal) {
    modal.style.display = "none";
  }

  if (btn && modal) {
    btn.addEventListener("click", function (event) {
      event.preventDefault();
      modal.style.display = "flex";
    });
  }

  if (modal) {
    const params = new URLSearchParams(window.location.search);
    if (params.get("abrir_reserva") === "1") {
      modal.style.display = "flex";

      // Limpa o parametro para evitar reabrir no refresh
      if (window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.delete("abrir_reserva");
        const newUrl = `${url.pathname}${url.search}${url.hash}`;
        window.history.replaceState({}, "", newUrl);
      }
    }
  }

  if (closeBtn && modal) {
    closeBtn.addEventListener("click", function () {
      modal.style.display = "none";
    });
  }

  window.addEventListener("click", function (event) {
    if (modal && event.target === modal) {
      modal.style.display = "none";
    }
  });

  // -------------------------------
  // Validacoes de data/hora
  // -------------------------------
  const horaMinInput = document.getElementById("horaMinInput");
  const dateInput = document.getElementById("dateInput");
  const confirmBtn2 = document.getElementById("confirmBtn2");

  if (dateInput) {
    const hoje = new Date();
    const yyyy = hoje.getFullYear();
    const mm = String(hoje.getMonth() + 1).padStart(2, "0");
    const dd = String(hoje.getDate()).padStart(2, "0");
    dateInput.min = `${yyyy}-${mm}-${dd}`;
  }

  function timeToMinutes(hhmm) {
    const [h, m] = hhmm.split(":").map(Number);
    return h * 60 + m;
  }

  function minutesToTime(totalMinutes) {
    const h = String(Math.floor(totalMinutes / 60)).padStart(2, "0");
    const m = String(totalMinutes % 60).padStart(2, "0");
    return `${h}:${m}`;
  }

  function roundToNearest5(totalMinutes) {
    return Math.round(totalMinutes / 5) * 5;
  }

  function getMaxTimeForDate(dateStr) {
    if (!dateStr) return "23:55";
    const date = new Date(`${dateStr}T00:00:00`);
    const day = date.getDay(); // 0 = domingo
    return day === 0 ? "17:00" : "23:55";
  }

  function updateAllowedTimeRange() {
    if (!horaMinInput) return;

    const minTime = "08:00";
    const maxTime = getMaxTimeForDate(dateInput ? dateInput.value : "");
    if (horaMinInput.value) {
      const validFormat = /^([01]\d|2[0-3]):([0-5]\d)$/.test(horaMinInput.value);
      if (!validFormat) {
        return;
      }

      const valueMin = timeToMinutes(horaMinInput.value);
      const normalizedMin = roundToNearest5(valueMin);
      if (normalizedMin !== valueMin) {
        horaMinInput.value = minutesToTime(normalizedMin);
      }

      if (
        normalizedMin < timeToMinutes(minTime) ||
        normalizedMin > timeToMinutes(maxTime)
      ) {
        horaMinInput.value = "";
      }
    }
  }

  if (dateInput) {
    dateInput.addEventListener("change", updateAllowedTimeRange);
  }
  if (horaMinInput) {
    horaMinInput.addEventListener("input", (event) => {
      let value = event.target.value.replace(/\D/g, "");
      if (value.length > 4) value = value.slice(0, 4);
      if (value.length > 2) {
        value = `${value.slice(0, 2)}:${value.slice(2)}`;
      }
      event.target.value = value;
    });
    horaMinInput.addEventListener("change", updateAllowedTimeRange);
    horaMinInput.addEventListener("blur", updateAllowedTimeRange);
  }
  updateAllowedTimeRange();

  if (confirmBtn2 && horaMinInput) {
    confirmBtn2.addEventListener("click", (event) => {
      if (!horaMinInput.value) {
        showReservaPopup("Selecione uma hora para a reserva.");
        horaMinInput.classList.add("invalid");
        event.preventDefault();
        return;
      }

      const validFormat = /^([01]\d|2[0-3]):([0-5]\d)$/.test(horaMinInput.value);
      if (!validFormat) {
        showReservaPopup("Hora invalida. Use o formato HH:MM.");
        horaMinInput.classList.add("invalid");
        event.preventDefault();
        return;
      }

      const rawMinutes = timeToMinutes(horaMinInput.value);
      const minutes = roundToNearest5(rawMinutes);
      horaMinInput.value = minutesToTime(minutes);
      const minAllowed = timeToMinutes("08:00");
      const maxAllowed = timeToMinutes(getMaxTimeForDate(dateInput ? dateInput.value : ""));

      if (minutes < minAllowed || minutes > maxAllowed) {
        showReservaPopup("Hora fora do horario permitido para reservas.");
        horaMinInput.classList.add("invalid");
        event.preventDefault();
        return;
      }

      horaMinInput.classList.remove("invalid");
    });
  }

  // -------------------------------
  // Verificacao do numero de pessoas
  // -------------------------------
  const numeroPessoasInput = document.getElementById("numero_pessoas");
  const aviso = document.getElementById("avisoPessoas");
  const submitBtn = document.getElementById("confirmBtn2");

  if (numeroPessoasInput && aviso && submitBtn) {
    numeroPessoasInput.addEventListener("input", () => {
      const valor = parseInt(numeroPessoasInput.value);

      if (valor > 30) {
        aviso.style.display = "block";
        submitBtn.disabled = true;
      } else {
        aviso.style.display = "none";
        submitBtn.disabled = false;
      }
    });
  }
});
