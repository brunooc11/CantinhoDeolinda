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

  function abrirModalReserva() {
    if (!modal) return;
    modal.classList.remove("fechando");
    modal.classList.add("aberto");
    document.body.classList.add("reserva-aberta");
  }

  function fecharModalReserva() {
    if (!modal || !modal.classList.contains("aberto") || modal.classList.contains("fechando")) {
      return;
    }
    modal.classList.add("fechando");
    document.body.classList.remove("reserva-aberta");
  }

  // Esconde o modal inicialmente
  if (modal) {
    modal.classList.remove("aberto", "fechando");
  }

  if (btn && modal) {
    btn.addEventListener("click", function (event) {
      event.preventDefault();
      abrirModalReserva();
    });
  }

  if (modal) {
    const params = new URLSearchParams(window.location.search);
    if (params.get("abrir_reserva") === "1") {
      abrirModalReserva();

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
      fecharModalReserva();
    });
  }

  window.addEventListener("click", function (event) {
    if (modal && event.target === modal) {
      fecharModalReserva();
    }
  });

  if (modal) {
    const modalContent = modal.querySelector(".reserva-modal-content");
    if (modalContent) {
      modalContent.addEventListener("animationend", function (event) {
        if (event.animationName === "reservaModalOut") {
          modal.classList.remove("aberto", "fechando");
        }
      });
    }
  }

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

  const PERIODOS_RESERVA = [
    { min: 12 * 60,      max: 14 * 60 + 30 }, // almoço 12:00–14:30
    { min: 19 * 60,      max: 22 * 60 + 30 }  // jantar 19:00–22:30
  ];

  function isWithinPeriod(minutes) {
    return PERIODOS_RESERVA.some(p => minutes >= p.min && minutes <= p.max);
  }

  function updateAllowedTimeRange() {
    if (!horaMinInput || !horaMinInput.value) return;

    const validFormat = /^([01]\d|2[0-3]):([0-5]\d)$/.test(horaMinInput.value);
    if (!validFormat) return;

    const valueMin = timeToMinutes(horaMinInput.value);
    const normalizedMin = roundToNearest5(valueMin);
    if (normalizedMin !== valueMin) {
      horaMinInput.value = minutesToTime(normalizedMin);
    }

    if (!isWithinPeriod(normalizedMin)) {
      horaMinInput.value = "";
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
        showReservaPopup("Hora inválida. Use o formato HH:MM.");
        horaMinInput.classList.add("invalid");
        event.preventDefault();
        return;
      }

      const rawMinutes = timeToMinutes(horaMinInput.value);
      const minutes = roundToNearest5(rawMinutes);
      horaMinInput.value = minutesToTime(minutes);

      if (!isWithinPeriod(minutes)) {
        showReservaPopup("Hora fora do horário de funcionamento.\nAlmoço: 12:00–14:30 | Jantar: 19:00–22:30");
        horaMinInput.classList.add("invalid");
        event.preventDefault();
        return;
      }

      const dateVal = dateInput ? dateInput.value : "";
      if (dateVal) {
        const hoje = new Date();
        const hojeStr = `${hoje.getFullYear()}-${String(hoje.getMonth()+1).padStart(2,"0")}-${String(hoje.getDate()).padStart(2,"0")}`;
        if (dateVal === hojeStr) {
          const agoraMin = hoje.getHours() * 60 + hoje.getMinutes();
          if (minutes < agoraMin + 60) {
            showReservaPopup("Reservas no mesmo dia requerem pelo menos 1 hora de antecedência.");
            horaMinInput.classList.add("invalid");
            event.preventDefault();
            return;
          }
        }
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
