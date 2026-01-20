const backBtn = document.getElementById("backHomeBtn");
const triggerSection = document.getElementById("banner");

function checkButton() {
    const rect = triggerSection.getBoundingClientRect();
    const offset = 100; // pixels depois de entrar no banner

    // Quando já estamos dentro do banner (não logo no início)
    // OU quando já passámos completamente o banner
    if (
        (rect.top <= window.innerHeight - offset && rect.bottom >= 0) ||
        rect.bottom < 0
    ) {
        backBtn.classList.add("show");
    } 
    else {
        // quando ainda estamos no topo do banner
        backBtn.classList.remove("show");
    }
}

window.addEventListener("scroll", checkButton);
window.addEventListener("load", checkButton); // quando a pagina é aberta , o btt fica ativo , se já estiver abaixo do menu
