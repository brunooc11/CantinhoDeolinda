(function () {
    const mesas = Array.from(document.querySelectorAll(".mesa"));
    const zonas = Array.from(document.querySelectorAll(".zona-bar, .zona-entrada"));
    const draggables = [...mesas, ...zonas];
    const states = ["livre", "reservada", "ocupada"];

    const kpiLivre = document.getElementById("kpiLivre");
    const kpiReservada = document.getElementById("kpiReservada");
    const kpiOcupada = document.getElementById("kpiOcupada");
    const kpiTotalMesas = document.getElementById("kpiTotalMesas");
    const mapaResetBtn = document.getElementById("mapaResetBtn");
    const mapaMoveModeBtn = document.getElementById("mapaMoveModeBtn");
    const mapaMergeModeBtn = document.getElementById("mapaMergeModeBtn");
    const mapaMergeCreateBtn = document.getElementById("mapaMergeCreateBtn");
    const mapaMergeClearBtn = document.getElementById("mapaMergeClearBtn");
    const mapaMergeHint = document.getElementById("mapaMergeHint");

    let activeDrag = null;
    let moveMode = false;
    let mergeMode = false;
    let mergeCount = 1;
    const selectedMesas = new Set();

    function nextState(current) {
        const idx = states.indexOf(current);
        return states[(idx + 1) % states.length];
    }

    function getState(el) {
        if (el.classList.contains("livre")) return "livre";
        if (el.classList.contains("reservada")) return "reservada";
        return "ocupada";
    }

    function setState(el, state) {
        el.classList.remove("livre", "reservada", "ocupada");
        el.classList.add(state);
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function getMesaCapacidade(mesa) {
        if (mesa.classList.contains("cap-8")) return 8;
        if (mesa.classList.contains("cap-6")) return 6;
        if (mesa.classList.contains("cap-4")) return 4;
        return 2;
    }

    function updateKpis() {
        let lugaresLivres = 0;
        let lugaresReservados = 0;
        let lugaresOcupados = 0;

        mesas.forEach((mesa) => {
            const state = getState(mesa);
            const capacidade = getMesaCapacidade(mesa);
            if (state === "livre") lugaresLivres += capacidade;
            if (state === "reservada") lugaresReservados += capacidade;
            if (state === "ocupada") lugaresOcupados += capacidade;
        });

        if (kpiLivre) kpiLivre.textContent = String(lugaresLivres);
        if (kpiReservada) kpiReservada.textContent = String(lugaresReservados);
        if (kpiOcupada) kpiOcupada.textContent = String(lugaresOcupados);
        if (kpiTotalMesas) kpiTotalMesas.textContent = String(mesas.length);
    }

    function onPointerDown(event) {
        const target = event.currentTarget;
        if (mergeMode && target.classList.contains("mesa")) {
            return;
        }
        if (!moveMode) {
            return;
        }
        const canvas = target.closest(".restaurante-canvas");
        if (!canvas) return;

        const canvasRect = canvas.getBoundingClientRect();
        const rect = target.getBoundingClientRect();
        const isMesa = target.classList.contains("mesa");

        activeDrag = {
            target,
            isMesa,
            moved: false,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            offsetX: isMesa ? 0 : event.clientX - rect.left,
            offsetY: isMesa ? 0 : event.clientY - rect.top,
            canvasLeft: canvasRect.left,
            canvasTop: canvasRect.top,
            canvasWidth: canvasRect.width,
            canvasHeight: canvasRect.height,
            width: rect.width,
            height: rect.height
        };

        target.classList.add("dragging");
        target.setPointerCapture(event.pointerId);
        event.preventDefault();
    }

    function onPointerMove(event) {
        if (!activeDrag || event.pointerId !== activeDrag.pointerId) return;
        const drag = activeDrag;
        const target = drag.target;

        const dx = Math.abs(event.clientX - drag.startX);
        const dy = Math.abs(event.clientY - drag.startY);
        if (dx > 3 || dy > 3) drag.moved = true;

        if (drag.isMesa) {
            const left = clamp(event.clientX - drag.canvasLeft, drag.width / 2, drag.canvasWidth - drag.width / 2);
            const top = clamp(event.clientY - drag.canvasTop, drag.height / 2, drag.canvasHeight - drag.height / 2);
            target.style.left = `${left}px`;
            target.style.top = `${top}px`;
        } else {
            const left = clamp(event.clientX - drag.canvasLeft - drag.offsetX, 0, drag.canvasWidth - drag.width);
            const top = clamp(event.clientY - drag.canvasTop - drag.offsetY, 0, drag.canvasHeight - drag.height);
            target.style.left = `${left}px`;
            target.style.top = `${top}px`;
        }
    }

    function onPointerUp(event) {
        if (!activeDrag || event.pointerId !== activeDrag.pointerId) return;
        const drag = activeDrag;
        const target = drag.target;

        target.classList.remove("dragging");
        target.releasePointerCapture(event.pointerId);

        if (!drag.moved && drag.isMesa) {
            setState(target, nextState(getState(target)));
            updateKpis();
        } else if (drag.moved) {
            target.dataset.dragMoved = "1";
            setTimeout(() => {
                delete target.dataset.dragMoved;
            }, 120);
        }

        activeDrag = null;
    }

    function clearSelection() {
        selectedMesas.forEach((mesa) => mesa.classList.remove("merge-select"));
        selectedMesas.clear();
    }

    function updateMergeUi() {
        if (mapaMoveModeBtn) {
            mapaMoveModeBtn.textContent = `Modo mover: ${moveMode ? "ON" : "OFF"}`;
            mapaMoveModeBtn.classList.toggle("is-active", moveMode);
        }
        if (mapaMergeModeBtn) {
            mapaMergeModeBtn.textContent = `Modo juntar mesas: ${mergeMode ? "ON" : "OFF"}`;
            mapaMergeModeBtn.classList.toggle("is-active", mergeMode);
        }
        if (mapaMergeCreateBtn) {
            mapaMergeCreateBtn.disabled = !mergeMode || selectedMesas.size < 2;
        }
        if (mapaMergeClearBtn) {
            const hasGroups = mesas.some((mesa) => mesa.classList.contains("merge-grouped"));
            mapaMergeClearBtn.disabled = !hasGroups;
        }
        if (mapaMergeHint) {
            if (!mergeMode) {
                mapaMergeHint.textContent = "Ativa o modo para selecionar mesas e juntá-las.";
            } else if (selectedMesas.size < 2) {
                mapaMergeHint.textContent = "Seleciona pelo menos 2 mesas da mesma sala para criar um conjunto.";
            } else {
                mapaMergeHint.textContent = `Selecionadas: ${selectedMesas.size} mesas. Clica em "Criar conjunto".`;
            }
        }
    }

    function toggleMesaSelection(mesa) {
        if (selectedMesas.has(mesa)) {
            selectedMesas.delete(mesa);
            mesa.classList.remove("merge-select");
        } else {
            selectedMesas.add(mesa);
            mesa.classList.add("merge-select");
        }
        updateMergeUi();
    }

    function createMergeGroup() {
        if (selectedMesas.size < 2) return;
        const firstCanvas = Array.from(selectedMesas)[0].closest(".restaurante-canvas");
        const sameCanvas = Array.from(selectedMesas).every((mesa) => mesa.closest(".restaurante-canvas") === firstCanvas);
        if (!sameCanvas) {
            if (mapaMergeHint) mapaMergeHint.textContent = "Só podes juntar mesas da mesma sala.";
            return;
        }

        const groupCode = `G${mergeCount}`;
        selectedMesas.forEach((mesa) => {
            mesa.classList.remove("merge-select");
            mesa.classList.add("merge-grouped");
            mesa.dataset.group = groupCode;
        });
        selectedMesas.clear();
        mergeCount += 1;
        updateMergeUi();
    }

    function clearMergeGroups() {
        mesas.forEach((mesa) => {
            mesa.classList.remove("merge-grouped", "merge-select");
            delete mesa.dataset.group;
        });
        selectedMesas.clear();
        mergeCount = 1;
        updateMergeUi();
    }

    if (draggables.length === 0) return;

    draggables.forEach((el) => {
        el.addEventListener("pointerdown", onPointerDown);
        el.addEventListener("pointermove", onPointerMove);
        el.addEventListener("pointerup", onPointerUp);
        el.addEventListener("pointercancel", onPointerUp);
    });

    if (mapaResetBtn) {
        mapaResetBtn.addEventListener("click", () => {
            window.location.reload();
        });
    }

    mesas.forEach((mesa) => {
        mesa.addEventListener("click", () => {
            if (mergeMode) {
                toggleMesaSelection(mesa);
                return;
            }
            if (moveMode) {
                if (mesa.dataset.dragMoved === "1") return;
                return;
            }
            setState(mesa, nextState(getState(mesa)));
            updateKpis();
        });
    });

    if (mapaMoveModeBtn) {
        mapaMoveModeBtn.addEventListener("click", () => {
            moveMode = !moveMode;
            updateMergeUi();
        });
    }

    if (mapaMergeModeBtn) {
        mapaMergeModeBtn.addEventListener("click", () => {
            mergeMode = !mergeMode;
            if (!mergeMode) clearSelection();
            updateMergeUi();
        });
    }
    if (mapaMergeCreateBtn) {
        mapaMergeCreateBtn.addEventListener("click", createMergeGroup);
    }
    if (mapaMergeClearBtn) {
        mapaMergeClearBtn.addEventListener("click", clearMergeGroups);
    }

    updateMergeUi();
    updateKpis();
})();
