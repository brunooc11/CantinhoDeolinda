(function () {
    const mesas = Array.from(document.querySelectorAll(".mesa"));
    const zonas = Array.from(document.querySelectorAll(".zona-bar, .zona-entrada"));
    const draggables = [...mesas, ...zonas];
    const states = ["livre", "reservada", "ocupada"];
    const initialStates = (window.CDOL_MESA_STATES && typeof window.CDOL_MESA_STATES === "object")
        ? window.CDOL_MESA_STATES
        : {};
    const lockStates = (window.CDOL_MESA_LOCKS && typeof window.CDOL_MESA_LOCKS === "object")
        ? window.CDOL_MESA_LOCKS
        : {};

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
    const mapaReleaseBar = document.getElementById("mapaReleaseBar");
    const mapaReleaseReservaId = document.getElementById("mapaReleaseReservaId");
    const mapaReleaseMesa = document.getElementById("mapaReleaseMesa");
    const mapaReleaseInfo = document.getElementById("mapaReleaseInfo");
    const mapaReleaseCliente = document.getElementById("mapaReleaseCliente");
    const mapaReleasePessoas = document.getElementById("mapaReleasePessoas");
    const mapaReleaseReservaHora = document.getElementById("mapaReleaseReservaHora");
    const mapaReleaseEstado = document.getElementById("mapaReleaseEstado");
    const mapaReleaseFim = document.getElementById("mapaReleaseFim");
    const mapaReleaseBtn = document.getElementById("mapaReleaseBtn");

    let activeDrag = null;
    let moveMode = false;
    let mergeMode = false;
    let mergeCount = 1;
    const selectedMesas = new Set();
    let selectedLockedMesaId = "";

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

    function normalizeState(state) {
        if (state === "livre" || state === "reservada" || state === "ocupada") return state;
        return "livre";
    }

    function getMesaLock(mesa) {
        const mesaId = mesa.dataset.id || "";
        if (!mesaId) return null;
        if (!Object.prototype.hasOwnProperty.call(lockStates, mesaId)) return null;
        return lockStates[mesaId];
    }

    function isMesaLocked(mesa) {
        return !!getMesaLock(mesa);
    }

    function canReleaseMesa(mesa) {
        const lock = getMesaLock(mesa);
        return !!lock && lock.status === "ocupada" && Number(lock.reserva_id || 0) > 0;
    }

    function applyMesaLockUi(mesa) {
        const lock = getMesaLock(mesa);
        mesa.classList.toggle("mesa-locked", !!lock);
        if (!lock) {
            delete mesa.dataset.releaseTime;
            mesa.removeAttribute("title");
            return;
        }

        mesa.dataset.releaseTime = lock.release_time || "--:--";
        mesa.setAttribute(
            "title",
            `${lock.status_label} | ${lock.cliente_nome} | ${lock.numero_pessoas} pessoas | ${lock.data_reserva} ${lock.hora_reserva} | liberta ${lock.release_at}`
        );
    }

    function updateReleaseBar(mesa) {
        if (!mapaReleaseBar || !mapaReleaseReservaId || !mapaReleaseMesa || !mapaReleaseInfo) {
            return;
        }

        if (!mesa || !isMesaLocked(mesa)) {
            selectedLockedMesaId = "";
            mapaReleaseBar.hidden = true;
            mapaReleaseReservaId.value = "";
            mapaReleaseMesa.textContent = "Mesa";
            mapaReleaseInfo.textContent = "Seleciona uma mesa reservada ou ocupada para ver os detalhes.";
            if (mapaReleaseCliente) mapaReleaseCliente.textContent = "-";
            if (mapaReleasePessoas) mapaReleasePessoas.textContent = "-";
            if (mapaReleaseReservaHora) mapaReleaseReservaHora.textContent = "-";
            if (mapaReleaseEstado) mapaReleaseEstado.textContent = "-";
            if (mapaReleaseFim) mapaReleaseFim.textContent = "-";
            if (mapaReleaseBtn) mapaReleaseBtn.hidden = true;
            mesas.forEach((item) => item.classList.remove("mesa-release-selected"));
            return;
        }

        const lock = getMesaLock(mesa);
        const mesaId = mesa.dataset.id || "";
        selectedLockedMesaId = mesaId;
        mapaReleaseBar.hidden = false;
        mapaReleaseReservaId.value = String(lock.reserva_id || "");
        mapaReleaseMesa.textContent = `Mesa ${mesaId.toUpperCase()}`;
        mapaReleaseInfo.textContent = `Reserva #${lock.reserva_id} | ${lock.cliente_email || "-"}`;
        if (mapaReleaseCliente) mapaReleaseCliente.textContent = lock.cliente_nome || "-";
        if (mapaReleasePessoas) mapaReleasePessoas.textContent = String(lock.numero_pessoas || "-");
        if (mapaReleaseReservaHora) mapaReleaseReservaHora.textContent = `${lock.data_reserva || "-"} ${lock.hora_reserva || "-"}`;
        if (mapaReleaseEstado) mapaReleaseEstado.textContent = lock.status_label || "-";
        if (mapaReleaseFim) mapaReleaseFim.textContent = lock.release_at || "-";
        if (mapaReleaseBtn) mapaReleaseBtn.hidden = !canReleaseMesa(mesa);
        mesas.forEach((item) => item.classList.toggle("mesa-release-selected", item === mesa));
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
        if (target.classList.contains("mesa") && isMesaLocked(target)) {
            return;
        }
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
            if (isMesaLocked(target)) {
                activeDrag = null;
                return;
            }
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
                mapaMergeHint.textContent = "Ativa o modo para selecionar mesas e junta-las.";
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
            if (mapaMergeHint) mapaMergeHint.textContent = "So podes juntar mesas da mesma sala.";
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

    document.addEventListener("click", (event) => {
        if (!mapaReleaseBar || mapaReleaseBar.hidden) return;
        if (event.target.closest(".mesa")) return;
        if (event.target.closest("#mapaReleaseBar")) return;
        updateReleaseBar(null);
    });

    mesas.forEach((mesa) => {
        const mesaId = mesa.dataset.id || "";
        if (mesaId && Object.prototype.hasOwnProperty.call(initialStates, mesaId)) {
            setState(mesa, normalizeState(initialStates[mesaId]));
        }
        applyMesaLockUi(mesa);

        mesa.addEventListener("click", () => {
            if (isMesaLocked(mesa)) {
                updateReleaseBar(mesa);
                return;
            }
            updateReleaseBar(null);
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
    updateReleaseBar(null);
})();
