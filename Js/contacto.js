console.log("CONTACTO.JS CARREGADO");

const form = document.getElementById("contactForm");

form.addEventListener("submit", function (e) {
    e.preventDefault();

    console.log("SUBMIT DETETADO");

    const formData = new FormData(form);

    // 1️⃣ Pedido rápido (BD)
    fetch("contacto.php", {
        method: "POST",
        body: formData
    })
    .then(r => r.text())
    .then(data => {
        console.log("RESPOSTA DO contacto.php:", data);

        if (data.trim() === "OK") {
            alert("Mensagem enviada com sucesso!");
            form.reset();

            // 2️⃣ Email em background (não bloqueia)
            fetch("email_contacto.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    nome: formData.get("nome"),
                    email: formData.get("email"),
                    assunto: formData.get("assunto"),
                    mensagem: formData.get("mensagem")
                })
            });

        } else {
            alert("Erro ao enviar mensagem.\n\n" + data);
        }
    })
    .catch(err => {
        console.error("ERRO NO FETCH:", err);
        alert("Erro ao comunicar com o servidor.");
    });
});
