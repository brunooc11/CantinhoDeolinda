document.addEventListener("DOMContentLoaded", function () {
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
  // Validação e arredondamento
  // -------------------------------
  const horaMinInput = document.getElementById("horaMinInput");
  const confirmBtn2 = document.getElementById("confirmBtn2");

  // Formata automaticamente HH:MM enquanto o usuário digita
  if (horaMinInput) {
    horaMinInput.addEventListener("input", (event) => {
      let input = event.target;
      let value = input.value.replace(/\D/g, "");
      let cursorPos = input.selectionStart;

      if (value.length > 4) value = value.slice(0, 4);

      if (value.length > 2) {
        value = value.slice(0, 2) + ":" + value.slice(2);
        if (cursorPos === 3) cursorPos++;
      }

      input.value = value;
      input.setSelectionRange(cursorPos, cursorPos);
    });
  }

  if (confirmBtn2 && horaMinInput) {
    confirmBtn2.addEventListener("click", (event) => {
      let parts = horaMinInput.value.split(":");
      if (parts.length !== 2) {
        alert("Preencha a hora no formato HH:MM");
        horaMinInput.classList.add("invalid");
        event.preventDefault();
        return;
      }

      let hourVal = parseInt(parts[0], 10) || 0;
      let minVal = parseInt(parts[1], 10) || 0;

      if (hourVal < 0 || hourVal > 23) {
        alert("As horas só podem ser entre 0 e 23!");
        horaMinInput.classList.add("invalid");
        event.preventDefault();
        return;
      }

      if (minVal < 0 || minVal > 59) {
        alert("Os minutos só podem ser entre 0 e 59!");
        horaMinInput.classList.add("invalid");
        event.preventDefault();
        return;
      }

      let roundedMin = Math.ceil(minVal / 5) * 5;
      if (roundedMin >= 60) {
        hourVal += 1;
        roundedMin = 0;
        if (hourVal > 23) hourVal = 0;
      }

      horaMinInput.value = `${hourVal.toString().padStart(2, "0")}:${roundedMin
        .toString()
        .padStart(2, "0")}`;
      horaMinInput.classList.remove("invalid");
    });
  }

  // -------------------------------
  // Verificação do número de pessoas
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
