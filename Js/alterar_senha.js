function toggleFormSenha() {
    const form = document.getElementById('formSenhaCard');
    if (!form) {
        return;
    }

    if (form.classList.contains('aberto')) {
        if (!form.classList.contains('closing')) {
            form.classList.remove('aberto');
            void form.offsetWidth;
            form.classList.add('closing');
        }
        return;
    }

    form.classList.remove('closing');
    form.classList.add('aberto');
}

function closeFloatingPanel(panel) {
    if (!panel || !panel.classList.contains('aberto') || panel.classList.contains('closing')) {
        return;
    }

    panel.classList.remove('aberto');
    void panel.offsetWidth;
    panel.classList.add('closing');
}

function toggleFormNome() {
    const form = document.getElementById('formNomeCard');
    if (!form) {
        return;
    }

    if (form.classList.contains('aberto')) {
        if (!form.classList.contains('closing')) {
            form.classList.remove('aberto');
            void form.offsetWidth;
            form.classList.add('closing');
        }
        return;
    }

    form.classList.remove('closing');
    form.classList.add('aberto');
}

(function initAlterarSenhaUI() {
    const form = document.getElementById('formSenhaCard');
    const nomeForm = document.getElementById('formNomeCard');
    if (form) {
        form.addEventListener('animationend', (event) => {
            if (event.animationName === 'dashSenhaOut') {
                form.classList.remove('aberto', 'closing');
            }
        });

        form.addEventListener('click', (event) => {
            if (event.target === form) {
                closeFloatingPanel(form);
            }
        });
    }

    if (nomeForm) {
        nomeForm.addEventListener('animationend', (event) => {
            if (event.animationName === 'dashSenhaOut') {
                nomeForm.classList.remove('aberto', 'closing');
            }
        });

        nomeForm.addEventListener('click', (event) => {
            if (event.target === nomeForm) {
                closeFloatingPanel(nomeForm);
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        closeFloatingPanel(form);
        closeFloatingPanel(nomeForm);
    });

    function setupSenhaUI() {
        const senhaAtualInput = document.querySelector('input[name="senha_atual"]');
        const novaSenhaInput = document.querySelector('input[name="nova_senha"]');
        const confirmarSenhaInput = document.querySelector('input[name="confirmar_senha"]');

        if (!senhaAtualInput || !novaSenhaInput || !confirmarSenhaInput) {
            return;
        }

        let senhaAtualCorreta = false;
        let senhaAtualTimer = null;
        let senhaAtualReqId = 0;

        const rules = {
            ruleLength: () => novaSenhaInput.value.length >= 8,
            ruleUpper: () => /[A-Z]/.test(novaSenhaInput.value),
            ruleLower: () => /[a-z]/.test(novaSenhaInput.value),
            ruleNumber: () => /[0-9]/.test(novaSenhaInput.value),
            ruleSymbol: () => /[^A-Za-z0-9]/.test(novaSenhaInput.value),
            ruleCurrentMatch: () => senhaAtualCorreta,
            ruleMatch: () =>
                confirmarSenhaInput.value.length > 0 &&
                novaSenhaInput.value === confirmarSenhaInput.value
        };

        function updateChecklist() {
            Object.keys(rules).forEach((id) => {
                const el = document.getElementById(id);
                if (!el) {
                    return;
                }
                el.classList.toggle('ok', rules[id]());
            });
        }

        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            if (!input) {
                return;
            }

            const icon = button.querySelector('i');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            if (icon) {
                icon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
            }
        }

        async function validarSenhaAtualNoServidor() {
            const valor = senhaAtualInput.value;
            if (!valor) {
                senhaAtualCorreta = false;
                updateChecklist();
                return;
            }

            const requestId = ++senhaAtualReqId;

            try {
                const body = new URLSearchParams();
                body.append('senha_atual', valor);

                const response = await fetch('Bd/verificar_senha_atual.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body.toString()
                });

                if (requestId !== senhaAtualReqId) {
                    return;
                }

                if (!response.ok) {
                    senhaAtualCorreta = false;
                    updateChecklist();
                    return;
                }

                const data = await response.json();
                senhaAtualCorreta = Boolean(data && data.ok);
            } catch (error) {
                if (requestId !== senhaAtualReqId) {
                    return;
                }
                senhaAtualCorreta = false;
            }

            updateChecklist();
        }

        function agendarValidacaoSenhaAtual() {
            clearTimeout(senhaAtualTimer);
            senhaAtualCorreta = false;
            updateChecklist();

            if (!senhaAtualInput.value) {
                return;
            }

            senhaAtualTimer = setTimeout(validarSenhaAtualNoServidor, 320);
        }

        document.querySelectorAll('.senha-toggle[data-target]').forEach((button) => {
            button.addEventListener('click', () => {
                togglePassword(button.dataset.target, button);
            });
        });

        senhaAtualInput.addEventListener('input', agendarValidacaoSenhaAtual);
        senhaAtualInput.addEventListener('blur', validarSenhaAtualNoServidor);
        novaSenhaInput.addEventListener('input', updateChecklist);
        confirmarSenhaInput.addEventListener('input', updateChecklist);
        updateChecklist();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupSenhaUI);
    } else {
        setupSenhaUI();
    }
})();
