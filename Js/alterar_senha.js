function toggleFormSenha() {
    const form = document.getElementById('formSenhaCard');
    form.classList.toggle('aberto');
}

function confirmarExclusao() {
    return confirm("Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.");
}