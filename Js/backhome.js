const backBtn = document.getElementById("backHomeBtn");
const triggerSection = document.getElementById("banner");

function checkButton() {
    const rect = triggerSection.getBoundingClientRect();

    // Quando a secção banner entrou no ecrã (mesmo só um bocadinho)
    if (rect.top <= window.innerHeight && rect.bottom >= 0) {
        backBtn.classList.add("show");
    } 
    else if (window.scrollY < triggerSection.offsetTop) {
        // enquanto não estiveres acima da secção, mantém o botão
        backBtn.classList.remove("show");
    }
}

window.addEventListener("scroll", checkButton);
