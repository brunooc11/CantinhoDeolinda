console.log("CONTACTO.JS CARREGADO");

const form = document.getElementById("contactForm");

form.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    fetch("contacto.php", {
        method: "POST",
        body: formData
    })
    .then(r => r.text())
    .then(data => {

        if (data.trim() !== "OK") {
            alert("Erro ao enviar mensagem.");
            return;
        }

        // 🔥 ENVIO DE EMAIL SEM LAG E SEM CANCELAMENTO
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

        alert("Mensagem enviada com sucesso!");
        form.reset();
    });
});
