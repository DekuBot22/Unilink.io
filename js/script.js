// ============================================================
// GLOBAL
// ============================================================
document.addEventListener("DOMContentLoaded", () => {

    // Marcar enlace activo en el nav
    const links = document.querySelectorAll("nav a");
    const params = new URLSearchParams(window.location.search);
    const pagina = params.get("page") || "inicio";
    links.forEach((link) => {
        const href = link.getAttribute("href") || "";
        if (href.includes(`page=${pagina}`)) {
            link.classList.add("active");
        }
    });

    // Animación de aparición al hacer scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity   = "1";
                entry.target.style.transform = "translateY(0)";
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll(".paso-card, .tutor-card, .beneficio-item, .materia-card").forEach(el => {
        el.style.opacity    = "0";
        el.style.transform  = "translateY(20px)";
        el.style.transition = "opacity 0.5s ease, transform 0.5s ease";
        observer.observe(el);
    });

    if (document.getElementById("heroCarousel")) {
        iniciarHeroCarousel();
    }

    iniciarAuthModal();

    const authParam = params.get("auth");
    if (authParam === "login" || authParam === "success") {
        abrirModalAuth();
        if (authParam === "success") {
            setTimeout(() => {
                document.getElementById("authModalOverlay")?.classList.add("hidden");
            }, 1600);
        }
        params.delete("auth");
        const query = params.toString();
        const newUrl = query ? `${window.location.pathname}?${query}${window.location.hash}` : `${window.location.pathname}${window.location.hash}`;
        window.history.replaceState(null, "", newUrl);
    }

    // ============================================================
    // PÁGINA TUTORES (tutores.html)
    // ============================================================
    if (document.getElementById("tutoresLista")) {
        iniciarTutores();
    }

    // ============================================================
    // PÁGINA SER TUTOR (ser-tutor.html)
    // ============================================================
    if (document.getElementById("postulacionForm")) {
        iniciarSerTutor();
    }

    // ============================================================
    // PERFIL TUTOR (perfil-tutor.html)
    // ============================================================
    if (document.getElementById("perfilPage")) {
        cargarPerfil();
    }

    // ============================================================
    // PERFIL USUARIO (editar citas)
    // ============================================================
    if (document.getElementById("modalEditarCita")) {
        iniciarEdicionCitas();
    }
});

document.addEventListener("click", (event) => {
    const btn = event.target.closest("[data-edit-cita]");
    if (!btn) return;

    abrirModalEditarCitaFromButton(btn);
});

document.addEventListener("click", (event) => {
    const btn = event.target.closest("[data-agendar]");
    if (!btn) return;

    abrirModalAgendarFromButton(btn);
});

function iniciarAuthModal() {
    const overlay = document.getElementById("authModalOverlay");
    if (!overlay) return;

    const views = Array.from(overlay.querySelectorAll("[data-auth-view]"));
    const registerPasswordInput = document.getElementById("registerPassword");
    const registerCodigoInput = document.getElementById("registerCodigo");
    const registerTelefonoInput = document.getElementById("registerTelefono");

    const cumpleRequisitosContrasena = (password) => {
        return (
            password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /\d/.test(password) &&
            /[^A-Za-z0-9]/.test(password)
        );
    };

    if (registerPasswordInput) {
        registerPasswordInput.minLength = 8;
        registerPasswordInput.autocomplete = "new-password";

        const passwordHint = document.createElement("p");
        passwordHint.className = "auth-password-hint";
        passwordHint.textContent = "Debe tener mínimo 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.";
        registerPasswordInput.insertAdjacentElement("afterend", passwordHint);

        registerPasswordInput.addEventListener("input", () => {
            if (cumpleRequisitosContrasena(registerPasswordInput.value)) {
                registerPasswordInput.setCustomValidity("");
            } else {
                registerPasswordInput.setCustomValidity("La contraseña debe incluir mayúscula, minúscula, número, símbolo y 8 caracteres como mínimo.");
            }
        });
    }

    const soloDigitos = (input) => {
        if (!input) return;
        input.addEventListener("input", () => {
            input.value = input.value.replace(/\D/g, "").slice(0, 10);
        });
    };

    soloDigitos(registerCodigoInput);
    soloDigitos(registerTelefonoInput);

    const cambiarVistaAuth = (vista) => {
        views.forEach((seccion) => {
            seccion.classList.toggle("hidden", seccion.dataset.authView !== vista);
        });
    };

    document.querySelectorAll("[data-open-auth]").forEach((btn) => {
        btn.addEventListener("click", () => {
            cambiarVistaAuth("login");
            overlay.classList.remove("hidden");
        });
    });

    overlay.querySelectorAll("[data-close-auth]").forEach((btn) => {
        btn.addEventListener("click", () => overlay.classList.add("hidden"));
    });

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            overlay.classList.add("hidden");
        }
    });

    overlay.querySelectorAll("[data-auth-target]").forEach((btn) => {
        btn.addEventListener("click", () => {
            cambiarVistaAuth(btn.dataset.authTarget);
        });
    });

    const loginForm = document.getElementById("loginForm");
    if (loginForm && loginForm.dataset.serverAuth !== "1") {
        loginForm.addEventListener("submit", (event) => {
            event.preventDefault();
            overlay.classList.add("hidden");
            mostrarModalExito(
                "✅",
                "¡Sesión iniciada!",
                "Bienvenido a UniLink. Ya puedes gestionar tus tutorías."
            );
            event.target.reset();
        });
    }

    const registerForm = document.getElementById("registerForm");
    if (registerForm && registerForm.dataset.serverAuth !== "1") {
        registerForm.addEventListener("submit", (event) => {
            event.preventDefault();

            const form = event.target;
            const passwordValue = form.querySelector("#registerPassword")?.value || "";

            if (!cumpleRequisitosContrasena(passwordValue)) {
                mostrarModalExito(
                    "⚠️",
                    "Contraseña insegura",
                    "Usa al menos 8 caracteres e incluye mayúscula, minúscula, número y símbolo."
                );
                return;
            }

            overlay.classList.add("hidden");
            mostrarModalExito(
                "🎉",
                "¡Registro exitoso!",
                "Tu cuenta fue creada. Ahora puedes iniciar sesión con tus credenciales."
            );
            event.target.reset();
        });
    }

    document.getElementById("recoverForm")?.addEventListener("submit", (event) => {
        event.preventDefault();
        overlay.classList.add("hidden");
        mostrarModalExito(
            "📩",
            "Revisa tu correo",
            "Si el correo está registrado, recibirás un enlace para restablecer tu contraseña."
        );
        event.target.reset();
    });
}

function abrirModalAuth() {
    const overlay = document.getElementById("authModalOverlay");
    if (!overlay) return;

    const views = Array.from(overlay.querySelectorAll("[data-auth-view]"));
    views.forEach((seccion) => {
        seccion.classList.toggle("hidden", seccion.dataset.authView !== "login");
    });

    overlay.classList.remove("hidden");
}

function usuarioAutenticado() {
    return document.body && document.body.dataset.auth === "1";
}

function iniciarHeroCarousel() {
    const carousel = document.getElementById("heroCarousel");
    if (!carousel) return;

    const slides = Array.from(carousel.querySelectorAll(".hero-slide"));
    const dots = Array.from(carousel.querySelectorAll(".carousel-dot"));
    const btnPrev = carousel.querySelector(".carousel-prev");
    const btnNext = carousel.querySelector(".carousel-next");
    if (!slides.length) return;

    let index = slides.findIndex((slide) => slide.classList.contains("is-active"));
    if (index < 0) index = 0;

    const setActive = (newIndex) => {
        slides[index].classList.remove("is-active");
        if (dots[index]) dots[index].classList.remove("is-active");
        index = (newIndex + slides.length) % slides.length;
        slides[index].classList.add("is-active");
        if (dots[index]) dots[index].classList.add("is-active");
    };

    btnPrev?.addEventListener("click", () => setActive(index - 1));
    btnNext?.addEventListener("click", () => setActive(index + 1));

    let autoplay = setInterval(() => setActive(index + 1), 4500);
    carousel.addEventListener("mouseenter", () => clearInterval(autoplay));
    carousel.addEventListener("mouseleave", () => {
        autoplay = setInterval(() => setActive(index + 1), 4500);
    });
}

// ============================================================
// MÓDULO: TUTORES
// ============================================================
const fallbackTutoresData = [
    { id: 1, nombre: "María López",   iniciales: "ML", carrera: "Ing. de Sistemas", semestre: "7mo semestre", rating: 4.9, sesiones: 120, materias: ["Cálculo I", "Programación", "Álgebra Lineal"],    tags: ["calculo","programacion","algebra"] },
    { id: 2, nombre: "Carlos Pérez",  iniciales: "CP", carrera: "Medicina",          semestre: "5to semestre", rating: 4.8, sesiones: 95,  materias: ["Química", "Biología", "Anatomía"],                tags: ["quimica","biologia"] },
    { id: 3, nombre: "Ana Gómez",     iniciales: "AG", carrera: "Derecho",           semestre: "6to semestre", rating: 4.9, sesiones: 80,  materias: ["Derecho Constitucional", "Redacción"],             tags: ["derecho"] },
    { id: 4, nombre: "Luis Martínez", iniciales: "LM", carrera: "Ing. Civil",        semestre: "8vo semestre", rating: 4.7, sesiones: 70,  materias: ["Física I", "Cálculo II", "Estática"],              tags: ["fisica","calculo"] },
    { id: 5, nombre: "Sofía Díaz",    iniciales: "SD", carrera: "Administración",    semestre: "6to semestre", rating: 4.8, sesiones: 60,  materias: ["Estadística", "Contabilidad"],                    tags: ["estadistica"] },
    { id: 6, nombre: "Juan Ramos",    iniciales: "JR", carrera: "Ing. de Sistemas",  semestre: "9no semestre", rating: 4.6, sesiones: 110, materias: ["Programación", "Bases de Datos", "Redes"],         tags: ["programacion"] },
    { id: 7, nombre: "Laura Torres",  iniciales: "LT", carrera: "Biología",          semestre: "5to semestre", rating: 4.9, sesiones: 50,  materias: ["Biología", "Química General"],                   tags: ["biologia","quimica"] },
    { id: 8, nombre: "Diego Castro",  iniciales: "DC", carrera: "Ing. Civil",        semestre: "7mo semestre", rating: 4.7, sesiones: 85,  materias: ["Física II", "Cálculo I", "Termodinámica"],        tags: ["fisica","calculo"] },
];

const tutoresData = Array.isArray(window.tutoresData) ? window.tutoresData : fallbackTutoresData;

function iniciarTutores() {
    const params  = new URLSearchParams(window.location.search);
    const materia = params.get("materia");
    if (materia && document.getElementById("filterMateria")) {
        document.getElementById("filterMateria").value = materia;
    }

    renderTutores(tutoresData);
    filtrarTutores();

    document.getElementById("searchInput")   ?.addEventListener("keyup",  filtrarTutores);
    document.getElementById("filterMateria") ?.addEventListener("change", filtrarTutores);
    document.getElementById("filterCarrera") ?.addEventListener("change", filtrarTutores);
    document.getElementById("sortBy")        ?.addEventListener("change", filtrarTutores);
}

function renderTutores(lista) {
    const container = document.getElementById("tutoresLista");
    const count     = document.getElementById("resultadosCount");
    if (!container) return;

    count.textContent = `Mostrando ${lista.length} tutor${lista.length !== 1 ? "es" : ""}`;

    if (lista.length === 0) {
        container.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:60px;color:#888">
                <p style="font-size:40px">😕</p>
                <p style="font-size:18px;margin-top:10px">No encontramos tutores con esos criterios</p>
                <button onclick="resetFiltros()" style="margin-top:20px;padding:10px 24px;background:#1E3A5F;color:white;border:none;border-radius:20px;cursor:pointer;font-size:15px">
                    Ver todos los tutores
                </button>
            </div>`;
        return;
    }

    container.innerHTML = lista.map(t => {
        const fotoUrl = t.foto_perfil ? escaparHtml(t.foto_perfil) : '';
        const avatarHtml = fotoUrl
            ? `<img src="${fotoUrl}" alt="${escaparHtml(t.nombre)}" class="tutor-avatar-img">`
            : `<div class="tutor-avatar">${escaparHtml(t.iniciales)}</div>`;
        return `
        <div class="tutor-card-page">
            <div class="avatar-wrapper">${avatarHtml}</div>
            <h4>${escaparHtml(t.nombre)}</h4>
            <p class="carrera">${escaparHtml(t.carrera)} · ${escaparHtml(t.semestre)}</p>
            <p class="rating">⭐ ${t.rating} / 5</p>
            <div class="tags">${t.materias.map(m => `<span class="tag">${escaparHtml(m)}</span>`).join("")}</div>
            <div class="tutor-stats">
                <span>📚 ${t.sesiones} sesiones</span>
                <span>✅ Verificado</span>
            </div>
            <div class="card-buttons">
                <button class="btn-agendar" data-agendar data-tutor-id="${t.id}" data-tutor-nombre="${escaparHtml(t.nombre)}" data-materias="${serializarMaterias(t.materias)}">📅 Agendar</button>
                <a href="index.php?page=perfil-tutor&id=${t.id}" class="btn-ver-perfil">Ver perfil</a>
            </div>
        </div>`;
    }).join("");
}

function filtrarTutores() {
    const texto   = document.getElementById("searchInput")  ?.value.toLowerCase() || "";
    const materia = document.getElementById("filterMateria")?.value || "";
    const carrera = document.getElementById("filterCarrera")?.value || "";
    const orden   = document.getElementById("sortBy")       ?.value || "rating";

    let lista = tutoresData.filter(t => {
        const matchTexto   = t.nombre.toLowerCase().includes(texto) || t.carrera.toLowerCase().includes(texto) || t.materias.some(m => m.toLowerCase().includes(texto));
        const matchMateria = !materia || t.tags.includes(materia);
        const matchCarrera = !carrera || t.carrera.toLowerCase().includes(carrera);
        return matchTexto && matchMateria && matchCarrera;
    });

    if (orden === "rating")   lista.sort((a, b) => b.rating   - a.rating);
    if (orden === "sesiones") lista.sort((a, b) => b.sesiones - a.sesiones);
    if (orden === "nombre")   lista.sort((a, b) => a.nombre.localeCompare(b.nombre));

    renderTutores(lista);
}

function resetFiltros() {
    document.getElementById("searchInput")  .value = "";
    document.getElementById("filterMateria").value = "";
    document.getElementById("filterCarrera").value = "";
    renderTutores(tutoresData);
}

function obtenerTutoresDisponibles() {
    if (Array.isArray(window.tutoresData)) {
        return window.tutoresData;
    }

    if (Array.isArray(window.tutoresDB)) {
        return window.tutoresDB;
    }

    if (typeof tutoresData !== "undefined" && Array.isArray(tutoresData)) {
        return tutoresData;
    }

    if (typeof tutoresDB !== "undefined" && Array.isArray(tutoresDB)) {
        return tutoresDB;
    }

    return [];
}

function buscarTutorPorId(tutorId) {
    const id = Number(tutorId);
    if (!Number.isFinite(id)) {
        return null;
    }

    return obtenerTutoresDisponibles().find((tutor) => Number(tutor.id) === id) || null;
}

function buscarTutorPorNombre(nombre) {
    const texto = String(nombre || "").trim().toLowerCase();
    if (!texto) {
        return null;
    }

    return obtenerTutoresDisponibles().find((tutor) => String(tutor.nombre || "").toLowerCase() === texto) || null;
}

function serializarMaterias(materias) {
    const lista = Array.isArray(materias) ? materias : [];
    return encodeURIComponent(JSON.stringify(lista));
}

function llenarMateriasSelect(select, materias, selected) {
    if (!select) {
        return;
    }

    const lista = Array.isArray(materias) ? materias.slice() : [];
    if (selected && !lista.includes(selected)) {
        lista.unshift(selected);
    }

    select.innerHTML = "";

    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Selecciona materia";
    select.appendChild(placeholder);

    lista.forEach((materia) => {
        const option = document.createElement("option");
        option.value = materia;
        option.textContent = materia;
        select.appendChild(option);
    });

    if (selected) {
        select.value = selected;
    }
}

function renderAgendaDisponible(tutor) {
    const container = document.getElementById("modalAgendaList");
    if (!container) {
        return;
    }

    const disponibilidad = tutor && Array.isArray(tutor.disponibilidad) ? tutor.disponibilidad : [];
    if (!disponibilidad.length) {
        container.innerHTML = '<div class="agenda-preview-empty">Sin agenda registrada.</div>';
        return;
    }

    container.innerHTML = disponibilidad.map((item) => {
        const dia = escaparHtml(String(item.dia || ""));
        const horas = escaparHtml(String(item.horas || ""));
        return `<div class="agenda-preview-item"><strong>${dia}</strong><span>${horas}</span></div>`;
    }).join("");
}

function normalizarTextoAgenda(texto) {
    return String(texto || "")
        .trim()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

function horaEnMinutos(valor) {
    if (!valor || !/^[0-2]\d:[0-5]\d$/.test(valor)) {
        return null;
    }

    const [h, m] = valor.split(":").map((v) => Number(v));
    if (!Number.isFinite(h) || !Number.isFinite(m)) {
        return null;
    }

    return h * 60 + m;
}

function minutosAHora(minutos) {
    if (!Number.isFinite(minutos)) {
        return "";
    }

    const h = Math.floor(minutos / 60);
    const m = minutos % 60;
    return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
}

function parseRangosAgenda(horas) {
    const texto = String(horas || "").trim();
    if (!texto) {
        return [];
    }

    const match = texto.match(/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/);
    if (match) {
        const inicio = horaEnMinutos(match[1]);
        const fin = horaEnMinutos(match[2]);
        if (inicio !== null && fin !== null && inicio < fin) {
            return [{ start: inicio, end: fin }];
        }
    }

    const mapSegmentos = {
        manana: { start: 8 * 60, end: 12 * 60 },
        tarde: { start: 14 * 60, end: 18 * 60 },
        noche: { start: 18 * 60, end: 21 * 60 },
    };

    const segmentos = normalizarTextoAgenda(texto)
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean);

    const rangos = [];
    segmentos.forEach((segmento) => {
        const rango = mapSegmentos[segmento];
        if (rango) {
            rangos.push({ start: rango.start, end: rango.end });
        }
    });

    return rangos;
}

function construirAgendaMap(tutor) {
    const disponibilidad = tutor && Array.isArray(tutor.disponibilidad) ? tutor.disponibilidad : [];
    const map = {};

    disponibilidad.forEach((item) => {
        const dia = String(item.dia || "").trim();
        const horas = String(item.horas || "").trim();
        if (!dia) {
            return;
        }

        const rangos = parseRangosAgenda(horas);
        if (!rangos.length) {
            return;
        }

        const minStart = Math.min(...rangos.map((r) => r.start));
        const maxEnd = Math.max(...rangos.map((r) => r.end));

        map[dia] = {
            rangos,
            min: minutosAHora(minStart),
            max: minutosAHora(maxEnd),
        };
    });

    return map;
}

function obtenerDiaSemana(fechaIso) {
    if (!fechaIso) {
        return "";
    }

    const date = new Date(`${fechaIso}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return "";
    }

    const dias = ["Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado"];
    return dias[date.getDay()] || "";
}

function obtenerFechaLocalISO(fecha = new Date()) {
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, "0");
    const d = String(fecha.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

function configurarValidacionAgenda(tutor) {
    const modal = document.getElementById("modalAgendar");
    if (!modal) {
        return;
    }

    const fechaInput = modal.querySelector("input[name='cita_fecha']");
    const horaInput = modal.querySelector("input[name='cita_hora']");
    if (!fechaInput || !horaInput) {
        return;
    }

    const agendaMap = construirAgendaMap(tutor);
    window.__agendaValidator = {
        agendaMap,
        fechaInput,
        horaInput,
    };

    const isoHoy = obtenerFechaLocalISO();
    fechaInput.min = isoHoy;

    if (!window.__agendaValidatorBound) {
        window.__agendaValidatorBound = true;
        fechaInput.addEventListener("change", validarAgendaInputs);
        fechaInput.addEventListener("input", validarAgendaInputs);
        horaInput.addEventListener("change", validarAgendaInputs);
        horaInput.addEventListener("input", validarAgendaInputs);
    }

    validarAgendaInputs();
}

function validarAgendaInputs() {
    const state = window.__agendaValidator;
    if (!state) {
        return;
    }

    const { agendaMap, fechaInput, horaInput } = state;
    if (!fechaInput || !horaInput) {
        return;
    }

    const errorBox = document.getElementById("agendaError");
    const setError = (mensaje) => {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = mensaje || "";
        errorBox.classList.toggle("is-visible", Boolean(mensaje));
    };

    const agendaDisponible = agendaMap && Object.keys(agendaMap).length > 0;
    if (!agendaDisponible) {
        fechaInput.setCustomValidity("El tutor no tiene agenda disponible.");
        horaInput.disabled = true;
        horaInput.value = "";
        horaInput.setCustomValidity("");
        setError("El tutor no tiene agenda disponible.");
        return;
    }

    const fechaValue = fechaInput.value;
    if (!fechaValue) {
        fechaInput.setCustomValidity("");
        horaInput.disabled = true;
        horaInput.setCustomValidity("");
        setError("Selecciona una fecha para ver horarios.");
        return;
    }

    const dia = obtenerDiaSemana(fechaValue);
    const agendaDia = agendaMap[dia];
    if (!agendaDia) {
        fechaInput.setCustomValidity("El tutor no esta disponible ese dia.");
        horaInput.disabled = true;
        horaInput.value = "";
        horaInput.setCustomValidity("");
        setError("No hay disponibilidad para el dia seleccionado.");
        return;
    }

    fechaInput.setCustomValidity("");
    horaInput.disabled = false;

    const hoyIso = obtenerFechaLocalISO();
    const maxAgendaMin = horaEnMinutos(agendaDia.max);
    let ahoraMin = null;
    if (fechaValue === hoyIso) {
        const ahora = new Date();
        ahoraMin = ahora.getHours() * 60 + ahora.getMinutes();
        if (maxAgendaMin !== null && ahoraMin >= maxAgendaMin) {
            horaInput.disabled = true;
            horaInput.value = "";
            horaInput.setCustomValidity("Ya no hay horarios disponibles para hoy.");
            setError("Ya no hay horarios disponibles para hoy.");
            return;
        }
    }

    const minAgendaMin = horaEnMinutos(agendaDia.min);
    let minPermitido = minAgendaMin;
    if (fechaValue === hoyIso && ahoraMin !== null) {
        const siguiente = ahoraMin + 1;
        if (minPermitido === null || siguiente > minPermitido) {
            minPermitido = siguiente;
        }
    }

    horaInput.min = minPermitido !== null ? minutosAHora(minPermitido) : "";
    horaInput.max = agendaDia.max || "";
    setError("");

    const horaValue = horaInput.value;
    if (!horaValue) {
        horaInput.setCustomValidity("");
        return;
    }

    const minutos = horaEnMinutos(horaValue);
    if (minutos === null) {
        horaInput.setCustomValidity("La hora no es valida.");
        setError("La hora no es valida.");
        return;
    }

    if (ahoraMin !== null && minutos <= ahoraMin) {
        horaInput.setCustomValidity("Selecciona una hora posterior a la actual.");
        setError("Selecciona una hora posterior a la actual.");
        return;
    }

    const valido = agendaDia.rangos.some((rango) => minutos >= rango.start && minutos <= rango.end);
    if (!valido) {
        horaInput.setCustomValidity("La hora seleccionada no esta dentro de la agenda.");
        setError("La hora seleccionada no esta dentro de la agenda.");
        return;
    }

    horaInput.setCustomValidity("");
    setError("");
}

// ============================================================
// MÓDULO: MODAL AGENDAR (compartido tutores + perfil)
// ============================================================
function abrirModalAgendar(nombre, tutorId, materiasList) {
    if (!usuarioAutenticado()) {
        abrirModalAuth();
        return;
    }
    const span = document.getElementById("modalNombreTutor");
    if (span) span.textContent = nombre;
    const inputTutorNombre = document.getElementById("citaTutorNombre");
    if (inputTutorNombre) inputTutorNombre.value = nombre || "";
    const inputTutorId = document.getElementById("citaTutorId");
    if (inputTutorId) inputTutorId.value = tutorId ? String(tutorId) : "";

    const select = document.getElementById("modalMateriaSelect");
    const selectedMateria = select?.dataset.selected || "";
    let materias = Array.isArray(materiasList) ? materiasList : [];
    const tutor = buscarTutorPorId(tutorId) || buscarTutorPorNombre(nombre);
    if (!materias.length) {
        materias = tutor && Array.isArray(tutor.materias) ? tutor.materias : [];
    }
    llenarMateriasSelect(select, materias, selectedMateria);
    renderAgendaDisponible(tutor);
    configurarValidacionAgenda(tutor);

    document.getElementById("modalAgendar")?.classList.remove("hidden");
}

function abrirModalAgendarFromButton(btn) {
    if (!btn) return;

    const tutorId = btn.dataset.tutorId || "";
    const tutorNombre = btn.dataset.tutorNombre || "";
    const materiasRaw = btn.dataset.materias || "";
    let materias = [];

    if (materiasRaw) {
        try {
            materias = JSON.parse(decodeURIComponent(materiasRaw));
        } catch (error) {
            materias = [];
        }
    }

    abrirModalAgendar(tutorNombre, tutorId, materias);
}

function cerrarModal() {
    document.getElementById("modalAgendar")?.classList.add("hidden");
}

function abrirModalEditarCita(data) {
    const overlay = document.getElementById("modalEditarCita");
    if (!overlay) return;

    const tutor = data.tutor || "";
    const materia = data.materia || "";
    const fecha = data.fecha || "";
    const hora = data.hora || "";
    const estado = data.estado || "pendiente";
    const citaId = data.id || "";
    const tutorId = data.tutorId || "";

    const tutorSpan = document.getElementById("modalEditarTutor");
    if (tutorSpan) tutorSpan.textContent = tutor || "tutor";

    const idInput = document.getElementById("editarCitaId");
    if (idInput) idInput.value = citaId;

    const materiaSelect = document.getElementById("editarCitaMateria");
    const tutorData = buscarTutorPorId(tutorId);
    const materias = tutorData && Array.isArray(tutorData.materias) ? tutorData.materias : [];
    const materiasFinal = materias.length ? materias : (materia ? [materia] : []);
    llenarMateriasSelect(materiaSelect, materiasFinal, materia);

    const fechaInput = document.getElementById("editarCitaFecha");
    if (fechaInput) fechaInput.value = fecha;

    const horaInput = document.getElementById("editarCitaHora");
    if (horaInput) horaInput.value = hora;

    const estadoSelect = document.getElementById("editarCitaEstado");
    if (estadoSelect) {
        const allowed = ["pendiente", "cancelada"];
        estadoSelect.value = allowed.includes(estado) ? estado : "pendiente";
    }

    overlay.classList.remove("hidden");
    materiaSelect?.focus();
}

function abrirModalEditarCitaFromButton(btn) {
    if (!btn) return;
    abrirModalEditarCita({
        id: btn.dataset.citaId || "",
        tutorId: btn.dataset.tutorId || "",
        tutor: btn.dataset.tutor || "",
        materia: btn.dataset.materia || "",
        fecha: btn.dataset.fecha || "",
        hora: btn.dataset.hora || "",
        estado: btn.dataset.estado || "pendiente",
    });
}

function cerrarModalEditarCita() {
    document.getElementById("modalEditarCita")?.classList.add("hidden");
}

function iniciarEdicionCitas() {
    const overlay = document.getElementById("modalEditarCita");
    if (!overlay) return;

    document.querySelectorAll("[data-edit-cita]").forEach((btn) => {
        btn.addEventListener("click", () => {
            abrirModalEditarCitaFromButton(btn);
        });
    });

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            cerrarModalEditarCita();
        }
    });
}

function confirmarSesion(e) {
    e.preventDefault();
    cerrarModal();
    mostrarModalExito(
        "🎉",
        "¡Sesión agendada!",
        "Te enviaremos una confirmación al correo.<br>El tutor se pondrá en contacto contigo pronto."
    );
}

const chatTutorPorId = {};
let chatTutorActualId = null;

function obtenerHoraActual() {
    return new Date().toLocaleTimeString("es-CO", { hour: "2-digit", minute: "2-digit" });
}

function escaparHtml(texto) {
    return String(texto)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function renderChatTutor() {
    const chatBody = document.getElementById("chatTutorBody");
    if (!chatBody || chatTutorActualId === null) return;

    const mensajes = chatTutorPorId[chatTutorActualId] || [];
    const miId = window.chatMiId ? parseInt(window.chatMiId, 10) : null;

    if (mensajes.length === 0) {
        chatBody.innerHTML = '<div class="chat-empty-hint">Sé el primero en enviar un mensaje.</div>';
        chatBody.scrollTop = chatBody.scrollHeight;
        return;
    }

    chatBody.innerHTML = mensajes.map((mensaje) => {
        const esMio = miId !== null
            ? parseInt(mensaje.emisor_id, 10) === miId
            : mensaje.de === "yo";
        const hora = mensaje.hora
            || (mensaje.creado_en
                ? new Date(mensaje.creado_en.replace(" ", "T")).toLocaleTimeString("es-CO", { hour: "2-digit", minute: "2-digit" })
                : obtenerHoraActual());
        return `<div class="chat-msg ${esMio ? "chat-msg-out" : "chat-msg-in"}">
            <p>${escaparHtml(mensaje.texto)}</p>
            <span>${hora}</span>
        </div>`;
    }).join("");

    chatBody.scrollTop = chatBody.scrollHeight;
}

function cargarMensajesTutor(tutorId) {
    fetch(`index.php?action=mensajes/get&tutor_id=${encodeURIComponent(tutorId)}`, {
        credentials: "same-origin"
    })
    .then((r) => r.json())
    .then((data) => {
        if (data.error === "tutor_sin_cuenta") {
            const chatBody = document.getElementById("chatTutorBody");
            if (chatBody) {
                chatBody.innerHTML = '<div class="chat-no-cuenta">Este tutor aún no tiene cuenta activa. No es posible enviarle mensajes en este momento.<br><a href="index.php?page=mensajes" class="chat-link-bandeja">Ver bandeja de mensajes</a></div>';
            }
            return;
        }
        if (!data.ok) return;

        window.chatMiId = data.mi_id;
        const msgs = (data.mensajes || []).map((m) => ({
            ...m,
            hora: new Date(m.creado_en.replace(" ", "T")).toLocaleTimeString("es-CO", { hour: "2-digit", minute: "2-digit" })
        }));
        chatTutorPorId[tutorId] = msgs;
        renderChatTutor();
    })
    .catch(() => {});
}

function abrirModalMensajeTutor(tutorId) {
    if (!usuarioAutenticado()) {
        abrirModalAuth();
        return;
    }

    const tutor = tutoresDB.find((item) => item.id === tutorId);
    if (!tutor) return;

    chatTutorActualId = tutorId;

    const nombre = document.getElementById("chatTutorNombre");
    const carrera = document.getElementById("chatTutorCarrera");
    const avatar = document.getElementById("chatTutorAvatar");

    if (nombre) nombre.textContent = tutor.nombre;
    if (carrera) carrera.textContent = `${tutor.carrera} · ${tutor.semestre}`;
    if (avatar) avatar.textContent = tutor.iniciales;

    const chatBody = document.getElementById("chatTutorBody");
    if (chatBody) chatBody.innerHTML = '<div class="chat-loading">Cargando mensajes...</div>';

    document.getElementById("modalMensajeTutor")?.classList.remove("hidden");
    document.getElementById("chatTutorInput")?.focus();

    cargarMensajesTutor(tutorId);

    if (window._chatPoll) clearInterval(window._chatPoll);
    window._chatPoll = setInterval(() => cargarMensajesTutor(tutorId), 3000);
}

function cerrarModalMensaje() {
    document.getElementById("modalMensajeTutor")?.classList.add("hidden");
    if (window._chatPoll) {
        clearInterval(window._chatPoll);
        window._chatPoll = null;
    }
}

function enviarMensajeTutor(e) {
    e.preventDefault();

    if (chatTutorActualId === null) return;

    const input = document.getElementById("chatTutorInput");
    const texto = input?.value.trim() || "";
    if (!texto) return;

    input.value = "";
    input.focus();

    // Actualización optimista
    if (!chatTutorPorId[chatTutorActualId]) chatTutorPorId[chatTutorActualId] = [];
    chatTutorPorId[chatTutorActualId].push({
        emisor_id: window.chatMiId || 0,
        texto,
        hora: obtenerHoraActual()
    });
    renderChatTutor();

    const fd = new FormData();
    fd.append("tutor_id", String(chatTutorActualId));
    fd.append("texto", texto);

    fetch("index.php?action=mensajes/send", {
        method: "POST",
        credentials: "same-origin",
        body: fd
    })
    .then((r) => r.json())
    .then((data) => {
        if (data.ok) cargarMensajesTutor(chatTutorActualId);
    })
    .catch(() => {});
}

function mostrarModalExito(icono, titulo, texto, redirigir) {
    const overlay = document.createElement("div");
    overlay.className = "modal-overlay";
    overlay.innerHTML = `
        <div class="modal modal-exito">
            <div class="exito-icon">${icono}</div>
            <h3>${titulo}</h3>
            <p>${texto}</p>
            <button class="btn-submit" style="margin-top:15px"
                onclick="${redirigir ? `window.location.href='${redirigir}'` : "this.closest('.modal-overlay').remove()"}">
                ${redirigir ? "Ir al inicio" : "Aceptar"}
            </button>
        </div>`;
    document.body.appendChild(overlay);
}

// ============================================================
// MÓDULO: SER TUTOR (stepper)
// ============================================================
function iniciarSerTutor() {
    const codigoInput = document.getElementById("tutorCodigo");
    const telefonoInput = document.getElementById("tutorTelefono");

    const soloDigitos = (input) => {
        if (!input) return;
        input.addEventListener("input", () => {
            input.value = input.value.replace(/\D/g, "");
        });
    };

    soloDigitos(codigoInput);
    soloDigitos(telefonoInput);

    codigoInput?.addEventListener("input", () => {
        if (codigoInput.value.length > 10) {
            codigoInput.value = codigoInput.value.slice(0, 10);
        }
        if (!codigoInput.value) {
            codigoInput.setCustomValidity("Ingresa tu código estudiantil.");
            return;
        }
        codigoInput.setCustomValidity("");
    });

    telefonoInput?.addEventListener("input", () => {
        if (telefonoInput.value.length > 10) {
            telefonoInput.value = telefonoInput.value.slice(0, 10);
        }
        if (!telefonoInput.value) {
            telefonoInput.setCustomValidity("Ingresa tu número de teléfono.");
            return;
        }
        telefonoInput.setCustomValidity("");
    });

    const materiasChecks = Array.from(document.querySelectorAll("input[name='tutor_materias[]']"));
    if (materiasChecks.length) {
        const actualizarMaterias = () => {
            const selected = materiasChecks.some((cb) => cb.checked);
            materiasChecks.forEach((cb, index) => {
                cb.required = !selected && index === 0;
            });

            if (materiasChecks[0]) {
                materiasChecks[0].setCustomValidity(selected ? "" : "Selecciona al menos una materia.");
            }
        };

        actualizarMaterias();
        materiasChecks.forEach((cb) => cb.addEventListener("change", actualizarMaterias));
    }
}

let pasoActual = 1;

function nextStep(paso) {
    if (!validarPasoTutor(paso)) return;

    document.getElementById(`formStep${paso}`).classList.add("hidden");
    document.getElementById(`stepTab${paso}`).classList.remove("active");
    document.getElementById(`stepTab${paso}`).classList.add("done");

    pasoActual = paso + 1;
    document.getElementById(`formStep${pasoActual}`).classList.remove("hidden");
    document.getElementById(`stepTab${pasoActual}`).classList.add("active");

    window.scrollTo({ top: document.getElementById("postulacion").offsetTop - 20, behavior: "smooth" });
}

function prevStep(paso) {
    document.getElementById(`formStep${paso}`).classList.add("hidden");
    document.getElementById(`stepTab${paso}`).classList.remove("active");

    pasoActual = paso - 1;
    document.getElementById(`formStep${pasoActual}`).classList.remove("hidden");
    document.getElementById(`stepTab${pasoActual}`).classList.add("active");
    document.getElementById(`stepTab${paso}`).classList.remove("done");
}

function enviarPostulacion(e) {
    e.preventDefault();

    if (!validarPasoTutor(4)) return;

    mostrarModalExito(
        "🎉",
        "¡Postulación enviada!",
        "Revisaremos tu información en los próximos <strong>2 días hábiles</strong> y te contactaremos al correo institucional.",
        "index.php?page=inicio"
    );
}

function validarPasoTutor(paso) {
    const pasoElement = document.getElementById(`formStep${paso}`);
    if (!pasoElement) return true;

    if (paso === 1) {
        const requeridos = pasoElement.querySelectorAll("input[required], select[required], textarea[required]");
        for (const campo of requeridos) {
            if (!campo.checkValidity()) {
                campo.reportValidity();
                return false;
            }
        }

        const codigoInput = document.getElementById("tutorCodigo");
        if (!codigoInput.value || codigoInput.value.length > 10 || /\D/.test(codigoInput.value)) {
            codigoInput.setCustomValidity("El código debe tener solo números y máximo 10 dígitos.");
            codigoInput.reportValidity();
            return false;
        }
        codigoInput.setCustomValidity("");

        const telefonoInput = document.getElementById("tutorTelefono");
        if (!telefonoInput.value || /\D/.test(telefonoInput.value) || telefonoInput.value.length > 10) {
            telefonoInput.setCustomValidity("El teléfono debe contener solo números y máximo 10 dígitos.");
            telefonoInput.reportValidity();
            return false;
        }
        telefonoInput.setCustomValidity("");

        return true;
    }

    if (paso === 2) {
        const requeridos = pasoElement.querySelectorAll("input[required], select[required], textarea[required]");
        for (const campo of requeridos) {
            if (!campo.checkValidity()) {
                campo.reportValidity();
                return false;
            }
        }
        return true;
    }

    if (paso === 3) {
        const materiasMarcadas = Array.from(document.querySelectorAll("input[name='materiasTutor']")).some((cb) => cb.checked);
        const otraMateria = document.getElementById("otraMateria")?.value.trim() || "";
        if (!materiasMarcadas && !otraMateria) {
            mostrarModalExito(
                "⚠️",
                "Materia requerida",
                "Selecciona al menos una materia o escribe una materia en el campo opcional."
            );
            return false;
        }
        return true;
    }

    if (paso === 4) {
        const disponibilidadMarcada = Array.from(document.querySelectorAll("input[name='disponibilidadTutor']")).some((cb) => cb.checked);
        if (!disponibilidadMarcada) {
            mostrarModalExito(
                "⚠️",
                "Disponibilidad requerida",
                "Selecciona al menos una opción de disponibilidad para continuar."
            );
            return false;
        }

        const requeridos = pasoElement.querySelectorAll("input[required], select[required], textarea[required]");
        for (const campo of requeridos) {
            if (!campo.checkValidity()) {
                campo.reportValidity();
                return false;
            }
        }
        return true;
    }

    return true;
}

// ============================================================
// MÓDULO: PERFIL TUTOR
// ============================================================
const fallbackTutoresDB = [
    {
        id: 1, nombre: "María López", iniciales: "ML",
        carrera: "Ing. de Sistemas", semestre: "7mo semestre",
        rating: 4.9, sesiones: 120,
        materias: ["Cálculo I", "Programación", "Álgebra Lineal"],
        bio: "Apasionada por las matemáticas y la programación. Tutora desde el 4to semestre con más de 100 compañeros ayudados. Mi método se basa en ejemplos prácticos y paciencia.",
        disponibilidad: [
            { dia: "Lunes",     horas: "Tarde, Noche" },
            { dia: "Miércoles", horas: "Mañana, Tarde" },
            { dia: "Viernes",   horas: "Tarde" },
        ],
        resenas: [
            { nombre: "Juan D.",    estrellas: 5, texto: "Excelente tutora, muy clara explicando integrales. La recomiendo 100%." },
            { nombre: "Paulina R.", estrellas: 5, texto: "Me ayudó mucho con programación, muy paciente y didáctica." },
            { nombre: "Camilo V.",  estrellas: 5, texto: "Gracias a María pude pasar Cálculo I. ¡Buenísima!" },
        ]
    },
    {
        id: 2, nombre: "Carlos Pérez", iniciales: "CP",
        carrera: "Medicina", semestre: "5to semestre",
        rating: 4.8, sesiones: 95,
        materias: ["Química", "Biología", "Anatomía"],
        bio: "Estudiante de Medicina con vocación docente. Me especializo en ciencias básicas de salud y acompaño a estudiantes de primer año.",
        disponibilidad: [
            { dia: "Martes",  horas: "Mañana" },
            { dia: "Jueves",  horas: "Tarde, Noche" },
            { dia: "Sábado",  horas: "Mañana" },
        ],
        resenas: [
            { nombre: "Sara M.",  estrellas: 5, texto: "Carlos explica anatomía como nadie, super didáctico." },
            { nombre: "Tomás P.", estrellas: 4, texto: "Muy bueno para química, puntual y comprometido." },
        ]
    },
    {
        id: 3, nombre: "Ana Gómez", iniciales: "AG",
        carrera: "Derecho", semestre: "6to semestre",
        rating: 4.9, sesiones: 80,
        materias: ["Derecho Constitucional", "Redacción Jurídica"],
        bio: "Estudiante de Derecho comprometida con la enseñanza. Me encanta ayudar a mis compañeros a entender los textos jurídicos.",
        disponibilidad: [
            { dia: "Lunes",   horas: "Mañana" },
            { dia: "Viernes", horas: "Mañana, Tarde" },
            { dia: "Sábado",  horas: "Mañana" },
        ],
        resenas: [
            { nombre: "Luisa C.",  estrellas: 5, texto: "Ana me enseñó a redactar demandas correctamente. ¡Excelente!" },
            { nombre: "Felipe A.", estrellas: 5, texto: "Constitucional es difícil pero con Ana todo se simplifica." },
        ]
    },
    {
        id: 4, nombre: "Luis Martínez", iniciales: "LM",
        carrera: "Ing. Civil", semestre: "8vo semestre",
        rating: 4.7, sesiones: 70,
        materias: ["Física I", "Cálculo II", "Estática"],
        bio: "Pasión por la física y las matemáticas aplicadas a la ingeniería. He tutoreado a más de 70 estudiantes con excelentes resultados.",
        disponibilidad: [
            { dia: "Martes",  horas: "Tarde" },
            { dia: "Jueves",  horas: "Tarde, Noche" },
            { dia: "Sábado",  horas: "Mañana" },
        ],
        resenas: [
            { nombre: "Andrea F.", estrellas: 5, texto: "Luis es increíble explicando Física, muy detallado." },
            { nombre: "Mario C.",  estrellas: 4, texto: "Muy buena metodología, súper recomendado." },
        ]
    },
    {
        id: 5, nombre: "Sofía Díaz", iniciales: "SD",
        carrera: "Administración", semestre: "6to semestre",
        rating: 4.8, sesiones: 60,
        materias: ["Estadística", "Contabilidad"],
        bio: "Me apasiona simplificar la estadística y la contabilidad para que todos puedan entenderlas con facilidad.",
        disponibilidad: [
            { dia: "Lunes",    horas: "Noche" },
            { dia: "Miércoles",horas: "Tarde" },
            { dia: "Viernes",  horas: "Tarde, Noche" },
        ],
        resenas: [
            { nombre: "Carlos M.", estrellas: 5, texto: "Sofía hace que la estadística sea entretenida. ¡Gracias!" },
        ]
    },
    {
        id: 6, nombre: "Juan Ramos", iniciales: "JR",
        carrera: "Ing. de Sistemas", semestre: "9no semestre",
        rating: 4.6, sesiones: 110,
        materias: ["Programación", "Bases de Datos", "Redes"],
        bio: "Desarrollador y tutor. Me especializo en programación web, bases de datos relacionales y fundamentos de redes.",
        disponibilidad: [
            { dia: "Lunes",  horas: "Noche" },
            { dia: "Jueves", horas: "Noche" },
            { dia: "Sábado", horas: "Mañana, Tarde" },
        ],
        resenas: [
            { nombre: "Laura P.", estrellas: 5, texto: "Juan me salvó en Bases de Datos, clarísimo explicando SQL." },
            { nombre: "Diego R.", estrellas: 4, texto: "Muy buen tutor de programación, paciente y metódico." },
        ]
    },
    {
        id: 7, nombre: "Laura Torres", iniciales: "LT",
        carrera: "Biología", semestre: "5to semestre",
        rating: 4.9, sesiones: 50,
        materias: ["Biología", "Química General"],
        bio: "Amante de las ciencias de la vida. Ayudo a mis compañeros a conectar la teoría con ejemplos de la naturaleza.",
        disponibilidad: [
            { dia: "Martes",    horas: "Mañana, Tarde" },
            { dia: "Viernes",   horas: "Mañana" },
        ],
        resenas: [
            { nombre: "Valeria S.", estrellas: 5, texto: "Laura explica Biología de manera muy visual y fácil de entender." },
        ]
    },
    {
        id: 8, nombre: "Diego Castro", iniciales: "DC",
        carrera: "Ing. Civil", semestre: "7mo semestre",
        rating: 4.7, sesiones: 85,
        materias: ["Física II", "Cálculo I", "Termodinámica"],
        bio: "Tutor comprometido con el aprendizaje de las ciencias básicas de ingeniería. Me enfoco en resolución de ejercicios paso a paso.",
        disponibilidad: [
            { dia: "Lunes",     horas: "Tarde" },
            { dia: "Miércoles", horas: "Tarde, Noche" },
            { dia: "Sábado",    horas: "Mañana" },
        ],
        resenas: [
            { nombre: "Natalia G.", estrellas: 5, texto: "Diego es muy paciente y explica cada paso con detalle." },
            { nombre: "Esteban Q.", estrellas: 4, texto: "Buen tutor de Física II, muy recomendado." },
        ]
    }
];

const tutoresDB = Array.isArray(window.tutoresDB) ? window.tutoresDB : fallbackTutoresDB;

function cargarPerfil() {
    const params = new URLSearchParams(window.location.search);
    const id     = parseInt(params.get("id")) || 1;
    if (!Array.isArray(tutoresDB) || tutoresDB.length === 0) {
        document.getElementById("perfilPage").innerHTML = `
            <div class="perfil-container">
                <div class="perfil-main">
                    <div class="perfil-card">
                        <h3>Sin tutores disponibles</h3>
                        <p>No hay tutores registrados en este momento.</p>
                    </div>
                </div>
            </div>`;
        return;
    }
    const tutor  = tutoresDB.find(t => t.id === id) || tutoresDB[0];

    // Llenar opciones del modal
    const select = document.getElementById("modalMateriaSelect");
    if (select) {
        select.innerHTML = `<option value="">Selecciona materia</option>` +
            tutor.materias.map(m => `<option>${m}</option>`).join("");
        const selectedMateria = select.dataset.selected || "";
        if (selectedMateria) {
            select.value = selectedMateria;
        }
    }

    const perfilFotoUrl = tutor.foto_perfil ? escaparHtml(tutor.foto_perfil) : '';
    const perfilAvatarHtml = perfilFotoUrl
        ? `<div class="avatar-wrapper"><img src="${perfilFotoUrl}" alt="${escaparHtml(tutor.nombre)}" class="perfil-avatar-img"></div>`
        : `<div class="perfil-avatar-grande">${escaparHtml(tutor.iniciales)}</div>`;

    document.getElementById("perfilPage").innerHTML = `
        <div class="perfil-container">
            <aside class="perfil-aside">
                ${perfilAvatarHtml}
                <h2>${escaparHtml(tutor.nombre)}</h2>
                <p class="carrera">${tutor.carrera} · ${tutor.semestre}</p>
                <span class="badge-verificado">✅ Tutor verificado</span>
                <div class="perfil-stats">
                    <div class="stat-item">
                        <div class="stat-num">⭐ ${tutor.rating}</div>
                        <div class="stat-label">Calificación</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">${tutor.sesiones}</div>
                        <div class="stat-label">Sesiones</div>
                    </div>
                </div>
                <button class="btn-agendar-perfil" data-agendar data-tutor-id="${tutor.id}" data-tutor-nombre="${escaparHtml(tutor.nombre)}" data-materias="${serializarMaterias(tutor.materias)}">📅 Agendar sesión</button>
                <button class="btn-contactar" onclick="abrirModalMensajeTutor(${tutor.id})">💬 Enviar mensaje</button>
                <button class="btn-calificar" id="btnCalificarPerfil" type="button" data-tutor-id="${tutor.id}">⭐ Calificar tutor</button>
                <button class="btn-hacer-resena" id="btnHacerResena" type="button" data-tutor-id="${tutor.id}">✍️ Hacer reseña</button>
            </aside>
            <div class="perfil-main">
                <div class="perfil-card">
                    <h3>Sobre mí</h3>
                    <p style="color:#555;font-size:15px;line-height:1.7">${tutor.bio}</p>
                </div>
                <div class="perfil-card">
                    <h3>Materias que enseña</h3>
                    <div class="materias-tags">
                        ${tutor.materias.map(m => `<span class="materia-tag">${m}</span>`).join("")}
                    </div>
                </div>
                <div class="perfil-card">
                    <h3>Disponibilidad</h3>
                    <div class="disponibilidad-display">
                        ${tutor.disponibilidad.map(d => `
                            <div class="disponibilidad-item">
                                <strong>${d.dia}</strong>
                                <span>${d.horas}</span>
                            </div>`).join("")}
                    </div>
                </div>
                <div class="perfil-card" id="seccionResenas">
                    <h3>Reseñas de estudiantes</h3>
                    ${(function() {
                        const lista = Array.isArray(window.resenasDB) ? window.resenasDB : [];
                        if (lista.length === 0) {
                            return '<p class="resenas-vacias">Aún no hay reseñas para este tutor.</p>';
                        }
                        return lista.map(r => `
                            <div class="resena-card">
                                <div class="resena-card-header">
                                    <div class="resena-card-avatar">${escaparHtml(r.nombre).charAt(0).toUpperCase()}</div>
                                    <div class="resena-card-meta">
                                        <strong class="resena-card-nombre">${escaparHtml(r.nombre)}</strong>
                                        <span class="resena-card-fecha">${escaparHtml(r.fecha)}</span>
                                    </div>
                                </div>
                                <p class="resena-card-texto">${escaparHtml(r.texto)}</p>
                            </div>`).join("");
                    })()}
                </div>
            </div>

            <div class="modal-overlay hidden" id="modalMensajeTutor">
                <div class="modal modal-chat">
                    <div class="chat-header">
                        <div class="chat-user">
                            <div class="chat-avatar" id="chatTutorAvatar">TU</div>
                            <div>
                                <strong id="chatTutorNombre">Tutor</strong>
                                <span id="chatTutorCarrera">Universidad del Magdalena</span>
                            </div>
                        </div>
                        <button class="modal-close" type="button" onclick="cerrarModalMensaje()">✕</button>
                    </div>

                    <div class="chat-body" id="chatTutorBody"></div>

                    <form class="chat-input-wrap" onsubmit="enviarMensajeTutor(event)">
                        <input id="chatTutorInput" type="text" maxlength="400" placeholder="Escribe tu mensaje..." autocomplete="off" required>
                        <button type="submit" class="chat-send-btn">Enviar</button>
                    </form>
                </div>
            </div>
        </div>`;

    const modalMensaje = document.getElementById("modalMensajeTutor");
    modalMensaje?.addEventListener("click", (event) => {
        if (event.target === modalMensaje) cerrarModalMensaje();
    });

    // Bind dynamic calificar button to open rating modal
    const btnCal = document.getElementById('btnCalificarPerfil');
    if (btnCal) {
        if (window.yaCalificado) {
            btnCal.textContent = '✅ Ya calificaste';
            btnCal.disabled = true;
            btnCal.style.opacity = '0.6';
            btnCal.style.cursor = 'default';
        } else {
            btnCal.addEventListener('click', () => {
                const resenaInput = document.getElementById('resenaTutorId');
                if (resenaInput) resenaInput.value = tutor.id;
                if (window.abrirModalResena) window.abrirModalResena();
            });
        }
    }

    // Bind dynamic hacer-resena button
    const btnResena = document.getElementById('btnHacerResena');
    if (btnResena) {
        if (window.yaReseno) {
            btnResena.textContent = '✅ Ya reseñaste';
            btnResena.disabled = true;
            btnResena.style.opacity = '0.6';
            btnResena.style.cursor = 'default';
        } else {
            btnResena.addEventListener('click', () => {
                if (!usuarioAutenticado()) {
                    abrirModalAuth();
                    return;
                }
                if (window.abrirModalResenaTexto) window.abrirModalResenaTexto(tutor.id);
            });
        }
    }
}