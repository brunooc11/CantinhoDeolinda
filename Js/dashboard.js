function openTab(tabName) {
    var i, tabcontent, tablinks;

    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("ativo");
    }

    tablinks = document.getElementsByClassName("tablink");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    document.getElementById(tabName).classList.add("ativo");
    document.querySelector(".tablink[onclick=\"openTab('" + tabName + "')\"]").classList.add("active");
}

document.addEventListener("DOMContentLoaded", function () {
    openTab("Conta");
});

function showConfirmPopup(options = {}) {
    let popup = document.getElementById("cd-confirm-popup");
    if (!popup) {
        popup = document.createElement("div");
        popup.id = "cd-confirm-popup";
        popup.style.cssText =
            "position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;" +
            "background:rgba(4,4,4,.62);backdrop-filter:blur(4px);z-index:99999;opacity:0;pointer-events:none;" +
            "transition:opacity .24s ease";

        popup.innerHTML =
            '<div id="cd-confirm-popup-box" role="dialog" aria-modal="true" style="position:relative;width:min(92vw,480px);border-radius:18px;border:1px solid rgba(255,255,255,.1);background:linear-gradient(160deg,#1b1b1b,#0f0f0f 72%);box-shadow:0 24px 58px rgba(0,0,0,.62);padding:20px 18px 18px;transform:translateY(18px) scale(.97);opacity:0;transition:transform .26s cubic-bezier(.2,.8,.2,1),opacity .22s ease">' +
            '<div style="position:absolute;left:16px;right:16px;top:0;height:3px;border-radius:999px;background:linear-gradient(90deg, rgba(219, 91, 91, 0.5), rgba(245, 166, 35, 0.35));"></div>' +
            '<button id="cd-confirm-popup-close" type="button" aria-label="Fechar popup" style="position:absolute;top:10px;right:12px;border:0;background:transparent;color:#f5f5f5;cursor:pointer;font-size:25px;line-height:1;padding:0;opacity:.88">&#10005;</button>' +
            '<div style="display:grid;grid-template-columns:auto 1fr;gap:12px;align-items:center;padding-right:34px">' +
            '<div style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px;box-shadow:0 10px 24px rgba(0,0,0,.34);background:linear-gradient(145deg, #db5b5b, #ab3434)">!</div>' +
            '<div style="min-width:0;text-align:center"><p id="cd-confirm-popup-title" style="margin:0;color:#ffe2dd;font-size:1.04rem;font-weight:700;letter-spacing:.2px;line-height:1.2;text-align:center"></p><p style="margin:3px 0 0;color:#b8aa8c;font-size:.76rem;letter-spacing:.4px;text-transform:uppercase;line-height:1.15;text-align:center">Cantinho Deolinda</p></div>' +
            "</div>" +
            '<p id="cd-confirm-popup-message" style="color:#f5ead6;font-size:.97rem;line-height:1.55;margin:14px 0 20px;white-space:pre-line;text-align:center;text-wrap:balance"></p>' +
            '<div style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap">' +
            '<button id="cd-confirm-popup-cancel" type="button" style="display:inline-flex;align-items:center;justify-content:center;min-width:112px;border:1px solid rgba(255,255,255,.2);border-radius:999px;padding:10px 22px;font-weight:700;cursor:pointer;color:#f5ead6;background:rgba(255,255,255,.05);transition:transform .18s ease, filter .18s ease, box-shadow .2s ease">Cancelar</button>' +
            '<button id="cd-confirm-popup-confirm" type="button" style="display:inline-flex;align-items:center;justify-content:center;min-width:112px;border:0;border-radius:999px;padding:10px 22px;font-weight:700;cursor:pointer;color:#101010;background:linear-gradient(135deg,#f5a623,#ffcf45);box-shadow:0 10px 22px rgba(245,166,35,.35);transition:transform .18s ease, filter .18s ease, box-shadow .2s ease">Excluir</button>' +
            "</div>" +
            "</div>";

        document.body.appendChild(popup);
    }

    const box = document.getElementById("cd-confirm-popup-box");
    const title = document.getElementById("cd-confirm-popup-title");
    const message = document.getElementById("cd-confirm-popup-message");
    const cancelBtn = document.getElementById("cd-confirm-popup-cancel");
    const confirmBtn = document.getElementById("cd-confirm-popup-confirm");
    const closeBtn = document.getElementById("cd-confirm-popup-close");

    title.textContent = options.title || "Confirmacao";
    message.textContent = options.message || "Tem a certeza?";

    const open = () => {
        popup.style.opacity = "1";
        popup.style.pointerEvents = "auto";
        box.style.transform = "translateY(0) scale(1)";
        box.style.opacity = "1";
        confirmBtn.focus();
    };

    const close = () => {
        popup.style.opacity = "0";
        popup.style.pointerEvents = "none";
        box.style.transform = "translateY(18px) scale(.97)";
        box.style.opacity = "0";
    };

    return new Promise((resolve) => {
        let done = false;

        const finish = (value) => {
            if (done) return;
            done = true;
            cancelBtn.removeEventListener("click", onCancel);
            confirmBtn.removeEventListener("click", onConfirm);
            closeBtn.removeEventListener("click", onCancel);
            popup.removeEventListener("click", onOutside);
            document.removeEventListener("keydown", onEsc);
            close();
            resolve(value);
        };

        const onCancel = () => finish(false);
        const onConfirm = () => finish(true);
        const onOutside = (e) => { if (e.target === popup) finish(false); };
        const onEsc = (e) => { if (e.key === "Escape") finish(false); };

        cancelBtn.addEventListener("click", onCancel);
        confirmBtn.addEventListener("click", onConfirm);
        closeBtn.addEventListener("click", onCancel);
        popup.addEventListener("click", onOutside);
        document.addEventListener("keydown", onEsc);

        open();
    });
}

function confirmarExclusao(event) {
    const evt = event || window.event;
    if (evt && typeof evt.preventDefault === "function") {
        evt.preventDefault();
    }

    const form = evt && evt.target ? evt.target : document.querySelector("form button[name='excluir']")?.closest("form");
    if (!form) return false;

    showConfirmPopup({
        title: "Excluir conta",
        message: "Tem a certeza que deseja excluir a sua conta?\nEsta ação não pode ser desfeita."
    }).then((confirmed) => {
        if (confirmed) {
            form.submit();
        }
    });

    return false;
}

function confirmarRemoverTodosFavoritos(event) {
    const evt = event || window.event;
    if (evt && typeof evt.preventDefault === "function") {
        evt.preventDefault();
    }

    const form = evt && evt.target ? evt.target : document.querySelector(".favoritos-header-form");
    if (!form) return false;

    showConfirmPopup({
        title: "Remover favoritos",
        message: "Tem a certeza que quer remover todos os favoritos?"
    }).then((confirmed) => {
        if (confirmed) {
            form.submit();
        }
    });

    return false;
}
