console.log("CONTACTO.JS CARREGADO");

const form = document.getElementById("contactForm");

function showFeedbackPopup(message, type = "info", onClose = null) {
    if (typeof window.__cdShowPopup === "function") {
        window.__cdShowPopup(message, type, onClose);
        return;
    }

    let popup = document.getElementById("feedbackPopup");

    if (!popup) {
        popup = document.createElement("div");
        popup.id = "feedbackPopup";
        popup.className = "feedback-popup";
        popup.innerHTML = `
            <div class="feedback-popup__box" role="alertdialog" aria-live="polite" aria-modal="false">
                <div class="feedback-popup__icon" aria-hidden="true"></div>
                <p class="feedback-popup__message"></p>
                <button type="button" class="feedback-popup__btn">Fechar</button>
            </div>
        `;
        document.body.appendChild(popup);
    }

    const box = popup.querySelector(".feedback-popup__box");
    const messageEl = popup.querySelector(".feedback-popup__message");
    const closeBtn = popup.querySelector(".feedback-popup__btn");

    box.classList.remove("is-success", "is-error", "is-info");
    box.classList.add(`is-${type}`);
    messageEl.textContent = message;

    popup.classList.add("is-visible");

    const closePopup = () => {
        popup.classList.remove("is-visible");
        closeBtn.removeEventListener("click", closePopup);
        popup.removeEventListener("click", outsideClick);
        if (typeof onClose === "function") onClose();
    };

    const outsideClick = (event) => {
        if (event.target === popup) closePopup();
    };

    closeBtn.addEventListener("click", closePopup);
    popup.addEventListener("click", outsideClick);
}

if (!form) {
    console.log("FORMULARIO DE CONTACTO INDISPONIVEL NESTA SESSAO");
} else {

form.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    fetch("contacto.php", {
        method: "POST",
        body: formData
    })
    .then(r => r.text())
    .then(data => {
        const result = data.trim();

        if (result !== "OK") {
            if (result === "LOGIN_REQUIRED") {
                showFeedbackPopup("Precisa de iniciar sessão para enviar feedback.", "error", () => {
                    window.location.href = "login.php";
                });
                return;
            }

            if (result === "RESERVA_REQUIRED") {
                showFeedbackPopup("Para enviar feedback, precisa de ter uma reserva confirmada e já comparecida.", "error");
                return;
            }

            if (result === "INVALID_DATA") {
                showFeedbackPopup("Preencha todos os campos do feedback.", "error");
                return;
            }

            showFeedbackPopup("Erro ao enviar mensagem.", "error");
            return;
        }

        // ENVIO DE EMAIL SEM LAG E SEM CANCELAMENTO
        const payload = JSON.stringify({
            nome: formData.get("nome"),
            email: formData.get("email"),
            assunto: formData.get("assunto"),
            mensagem: formData.get("mensagem")
        });

        navigator.sendBeacon(
            "Bd/email_contacto.php",
            new Blob([payload], { type: "application/json" })
        );

        showFeedbackPopup("Mensagem enviada com sucesso!", "success");
        form.reset();
    });
});
}
