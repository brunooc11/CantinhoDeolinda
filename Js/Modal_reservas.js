document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("reservaModal");
  const btn = document.getElementById("openReservaModal"); // Order Online
  const closeBtn = document.getElementById("closeReserva");
  const horaInput = document.getElementById("horaInput");
  const minInput = document.getElementById("minInput");
  const confirmBtn = document.getElementById("confirmBtn"); // Botão "Submit" do modal

  // Esconde o modal inicialmente
  modal.style.display = "none";

  if (btn) {
    btn.addEventListener("click", function (event) {
      event.preventDefault();
      modal.style.display = "flex"; // mostra o modal
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      modal.style.display = "none"; // fecha o modal
    });
  }

  window.addEventListener("click", function (event) {
    if (event.target === modal) {
      modal.style.display = "none"; // fecha clicando fora
    }
  });

  // -------------------------------
  // Validação e arredondamento
  // -------------------------------
  const horaMinInput = document.getElementById("horaMinInput"); // input único
  const confirmBtn2 = document.getElementById("confirmBtn2");

  // Formata automaticamente HH:MM enquanto o usuário digita
  horaMinInput.addEventListener("input", (event) => {
    let input = event.target;
    let value = input.value.replace(/\D/g, ""); // remove tudo que não for número
    let cursorPos = input.selectionStart;

    if (value.length > 4) value = value.slice(0, 4); // limita a 4 dígitos

    if (value.length > 2) {
      value = value.slice(0, 2) + ":" + value.slice(2);
      if (cursorPos === 3) cursorPos++; // ajusta posição do cursor
    }

    input.value = value;
    input.setSelectionRange(cursorPos, cursorPos);
  });

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

    // Arredonda minutos para múltiplos de 5
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

  // -------------------------------
  // Verificação do número de pessoas
  // -------------------------------
  const numeroPessoasInput = document.getElementById("numero_pessoas");
  const aviso = document.getElementById("avisoPessoas");
  const submitBtn = document.getElementById("confirmBtn2");

  if (numeroPessoasInput) {
    numeroPessoasInput.addEventListener("input", () => {
      const valor = parseInt(numeroPessoasInput.value);

      if (valor > 30) {
        aviso.style.display = "block";
        submitBtn.disabled = true; // bloqueia o botão enquanto o valor for > 30
      } else {
        aviso.style.display = "none";
        submitBtn.disabled = false; // desbloqueia o botão
      }
    });
  }
});
