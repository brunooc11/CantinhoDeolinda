console.log("CONTACTO.JS CARREGADO");

document.getElementById("contactForm").addEventListener("submit", function (e) {
    e.preventDefault();

    console.log("SUBMIT DETETADO");

    let formData = new FormData(this);

    fetch("contacto.php", {
        method: "POST",
        body: formData
    })
    .then(r => r.text())
    .then(data => {
        console.log("RESPOSTA DO PHP:", data);

        if (data.trim() === "OK") {
            alert("Mensagem enviada com sucesso!");
            this.reset();
        } else {
            alert("Erro ao enviar mensagem.\n\n" + data);
        }
    })
    .catch(err => {
        console.error("ERRO NO FETCH:", err);
        alert("Erro ao comunicar com o servidor.");
    });
});
