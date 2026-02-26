console.log("CONTACTO.JS CARREGADO");

const form = document.getElementById("contactForm");
const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
const defaultSubmitLabel = submitBtn ? submitBtn.textContent.trim() : "";
const statusEl = document.getElementById("contactFormStatus");

function initSectionCardReveal() {
    if (!document.body) return;

    const targets = Array.from(
        document.querySelectorAll(
            "#localizacao .info_adicionais-wrapper, #localizacao .info_adicionais-card, #localizacao .contact-map, #contacto .contact-card"
        )
    );

    if (targets.length === 0) return;
    document.body.classList.add("js-reveal-enabled");

    if (!("IntersectionObserver" in window)) {
        targets.forEach((el) => el.classList.add("is-revealed"));
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add("is-revealed");
                obs.unobserve(entry.target);
            });
        },
        {
            root: null,
            threshold: 0.08,
            rootMargin: "0px 0px -8% 0px"
        }
    );

    targets.forEach((el) => observer.observe(el));
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSectionCardReveal);
} else {
    initSectionCardReveal();
}

function showFeedbackPopup(message, type = "info", onClose = null) {
    if (typeof window.__cdShowPopup === "function") {
        window.__cdShowPopup(message, type, onClose);
        return;
    }
    alert(message);
    if (typeof onClose === "function") onClose();
}

function setContactSubmitLoading(isLoading) {
    if (!submitBtn) return;
    submitBtn.disabled = isLoading;
    submitBtn.classList.toggle("is-loading", isLoading);
    submitBtn.setAttribute("aria-busy", isLoading ? "true" : "false");
    submitBtn.textContent = isLoading ? "A enviar..." : defaultSubmitLabel;
    if (statusEl) {
        statusEl.textContent = isLoading ? "A enviar mensagem." : "";
    }
}

if (!form) {
    console.log("FORMULARIO DE CONTACTO INDISPONIVEL NESTA SESSAO");
} else {

form.addEventListener("submit", function (e) {
    e.preventDefault();
    setContactSubmitLoading(true);

    const formData = new FormData(form);

    fetch("contacto.php", {
        method: "POST",
        body: formData
    })
    .then(r => r.text())
    .then(data => {
        const result = data.trim();

        if (result !== "OK") {
            if (statusEl) {
                statusEl.textContent = "Nao foi possivel enviar a mensagem.";
            }
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
        if (statusEl) {
            statusEl.textContent = "Mensagem enviada com sucesso.";
        }
        form.reset();
    })
    .catch(() => {
        showFeedbackPopup("Erro de rede. Tente novamente.", "error");
        if (statusEl) {
            statusEl.textContent = "Erro de rede ao enviar mensagem.";
        }
    })
    .finally(() => {
        setContactSubmitLoading(false);
    });
});
}


