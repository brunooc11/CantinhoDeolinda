function openTab(tabName) {
    var i, tabcontent, tablinks;

    // Remove a classe ativo de todas as abas
    tabcontent = document.getElementsByClassName("tabcontent");
    for(i=0;i<tabcontent.length;i++){
        tabcontent[i].classList.remove("ativo");
    }

    // Remove a classe active dos botões
    tablinks = document.getElementsByClassName("tablink");
    for(i=0;i<tablinks.length;i++){
        tablinks[i].classList.remove("active");
    }

    // Adiciona a classe ativo à aba que foi clicada
    document.getElementById(tabName).classList.add("ativo");

    // Adiciona a classe active ao botão correspondente
    document.querySelector(".tablink[onclick=\"openTab('" + tabName + "')\"]").classList.add("active");
}

// Abre a aba Conta ao carregar a página
document.addEventListener("DOMContentLoaded", function(){ openTab('Conta'); });


function confirmarExclusao(){
    return confirm('Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.');
}
