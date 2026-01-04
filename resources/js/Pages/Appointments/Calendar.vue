<script setup>
// Inertia / Layout
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// FullCalendar
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import itLocale from "@fullcalendar/core/locales/it";

// Vue
import {
    ref,
    computed,
    onMounted,
    watch,
    nextTick,
    onBeforeUnmount,
} from "vue";

// Tooltip
import tippy from "tippy.js";
import "tippy.js/dist/tippy.css";

// Tabulator
import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator.min.css";
function safeRedraw() {
    if (!tab || !tabBuilt) return;

    // se l'elemento è nascosto (v-show), redraw crasha
    const el = tableEl.value;
    if (!el) return;
    if (el.offsetParent === null) return; // nascosto

    tab.redraw?.(true);
}
function waitTabBuilt() {
    // già pronto
    if (tab && tabBuilt) return Promise.resolve(true);

    // se stiamo già aspettando
    if (tabBuildPromise) return tabBuildPromise;

    // fallback: evita deadlock
    tabBuildPromise = new Promise((resolve) => {
        const t = setInterval(() => {
            if (tab && tabBuilt) {
                clearInterval(t);
                resolve(true);
            }
        }, 20);
        // sicurezza: dopo 2s sblocca comunque
        setTimeout(() => {
            clearInterval(t);
            resolve(false);
        }, 2000);
    });

    return tabBuildPromise;
}
const search = ref("");
const searchDebounced = ref("");
const showColumnPanel = ref(false);
function getColumnDefsForPanel() {
    // QUI metti ESATTAMENTE gli stessi field che usi in columns:
    return [
        { title: "Itinerario", field: "description" },
        { title: "Data Produzione", field: "DataInizio" },
        { title: "Data Consegna", field: "DataConsegna" },
        { title: "Ordine", field: "Nordine" },
        { title: "Cliente", field: "Cliente" },
        { title: "Riferimento", field: "Riferimento" }, // se esiste davvero in buildRows/columns
        { title: "Prodotto", field: "Prodotto" },
        { title: "Stato", field: "Stato" },
        { title: "Magazzino", field: "StatoMagazzino" },
        { title: "Pezzi", field: "Pezzi" },
    ];
}
const columnDefs = getColumnDefsForPanel();

const visibleCols = ref(
    columnDefs.reduce((acc, c) => {
        acc[c.field] = true;
        return acc;
    }, {})
);
function applyColumnVisibility() {
    if (!tab || !tabBuilt) return;

    for (const [field, isVisible] of Object.entries(visibleCols.value)) {
        const col = tab.getColumn(field);
        if (!col) continue; // se non esiste, ignora (niente errori)
        isVisible ? col.show() : col.hide();
    }

    // tab.redraw?.(true);
}

function resetColumnVisibility() {
    for (const c of columnDefs) visibleCols.value[c.field] = true;
    applyColumnVisibility();
}

function clearAllColumnFilters3() {
    //  if (!tab || !tabBuilt) return;

    // 1) reset header filters (input/select in testata)
    if (typeof tab.clearHeaderFilter === "function") {
        tab.clearHeaderFilter(); // ✅ Tabulator 5/6
    } else if (typeof tab.clearHeaderFilter === "undefined") {
        // fallback: prova a svuotare manualmente i filtri di colonna
        tab.getColumns()?.forEach((col) => {
            try {
                col.setHeaderFilterValue?.("");
            } catch {}
        });
    }

    // 2) reset filtri "normali" (tab.setFilter, tab.addFilter, ecc)
    if (typeof tab.clearFilter === "function") {
        tab.clearFilter(true); // true = include header filters (in alcune build)
        tab.clearFilter(); // doppio colpo (alcune versioni ignorano il parametro)
    }

    // 3) reset sort (spesso sembra “filtro” ma è sort)
    if (typeof tab.clearSort === "function") {
        tab.clearSort();
    }

    // 4) forza refresh UI
    tab.replaceData(buildRows()); // oppure tab.setData(buildRows());
    safeRedraw();
}

function clearAllColumnFilters() {
    //if (!tab || !tabBuilt) return;

    // ✅ header filter (input/select in testata)
    tab.clearHeaderFilter();

    // ✅ filtri normali (se li usi)
    tab.clearFilter();

    // ✅ ordina e paginazione, opzionale
    // tab.clearSort();
    tab.setPage(1);

    safeRedraw();
}
let tSearch = null;
watch(search, (v) => {
    clearTimeout(tSearch);
    tSearch = setTimeout(() => {
        searchDebounced.value = (v || "").toLowerCase().trim();
    }, 200); // 150-300ms va benissimo
});

// -----------------------------
// ✅ Tabs
// -----------------------------
const activeTab = ref("calendar"); // "calendar" | "list"

// -----------------------------
// Inertia flash / import
// -----------------------------
const page = usePage();
const EtImportaDati = ref(false);

const ImportaDati = () => {
    EtImportaDati.value = true;
    router.post(
        route("appointments.ImportaDati"),
        {},
        {
            preserveScroll: true,
            onFinish: () => (EtImportaDati.value = false),
        }
    );
};

const EtEseguiAccess = ref(false);

const c = () => {
    EtEseguiAccess.value = true;
    router.post(
        route("appointments.EseguiAccess"),
        {},
        {
            preserveScroll: true,
            onFinish: () => (EtEseguiAccess.value = false),
        }
    );
};


const importReport = computed(() => page.props.flash?.import_report || null);

// (se ti serve)
const aggiornaDati = () => {
    router.post(
        route("appointments.runExe"),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                router.reload({ only: ["appointments"], preserveState: true });
            },
        }
    );
};

// -----------------------------
// Props
// -----------------------------
const props = defineProps({ appointments: Array });

// -----------------------------
// Calendar refs/state
// -----------------------------
const refreshTick = ref(0);
const calendarRef = ref(null);

//const search = ref("");
const selectedStatus = ref("tutti");
const SceltaProdotto = ref("Tutti");
const appointmentsLocal = ref([]);

// Totali
const pezziGiorno = ref(0);
const pezziMese = ref(0);

// popup settimana
const showWeekPicker = ref(false);
const selectedWeek = ref("");

// -----------------------------
// ✅ Helpers Date
// -----------------------------
function parseAnyDate(dateLike) {
    if (!dateLike) return null;

    if (dateLike instanceof Date) {
        return isNaN(dateLike) ? null : dateLike;
    }

    const s = String(dateLike).trim();

    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/.test(s)) {
        const d = new Date(s.replace(" ", "T"));
        return isNaN(d) ? null : d;
    }

    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/.test(s)) {
        const d = new Date(s.replace(" ", "T") + ":00");
        return isNaN(d) ? null : d;
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
        const d = new Date(`${s}T00:00:00`);
        return isNaN(d) ? null : d;
    }

    const d = new Date(s);
    return isNaN(d) ? null : d;
}

function dateKeyLocal(dateLike) {
    const d = parseAnyDate(dateLike);
    if (!d) return null;

    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
}

function toLocalMySql(dt) {
    if (!dt) return null;
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, "0");
    const d = String(dt.getDate()).padStart(2, "0");
    const hh = String(dt.getHours()).padStart(2, "0");
    const mm = String(dt.getMinutes()).padStart(2, "0");
    const ss = String(dt.getSeconds()).padStart(2, "0");
    return `${y}-${m}-${d} ${hh}:${mm}:${ss}`;
}

function formatDateIt(dateLike) {
    const d = parseAnyDate(dateLike);
    if (!d) return "";
    return d.toLocaleDateString("it-IT", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    });
}
function normYmd(value) {
    const d = parseAnyDate(value);
    if (!d) return "";
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
}

// accetta: "19/12/2025" oppure "19-12-2025" oppure "2025-12-19"
function parseItToYmd(s) {
    s = String(s || "").trim();
    if (!s) return "";

    // già yyyy-mm-dd
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;

    // dd/mm/yyyy o dd-mm-yyyy
    const m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
    if (m) {
        const dd = String(m[1]).padStart(2, "0");
        const mm = String(m[2]).padStart(2, "0");
        const yy = m[3];
        return `${yy}-${mm}-${dd}`;
    }

    // prova fallback: se uno scrive "19/12" non filtriamo (ritorna "")
    return "";
}

/**
 * HeaderFilter custom per date:
 * - se input vuoto => true
 * - se input valido => confronta YYYY-MM-DD contenuto (match esatto o parziale)
 */
function headerFilterDateIt(headerValue, rowValue, rowData, filterParams) {
    const hv = String(headerValue || "").trim();
    if (!hv) return true;

    const typed = parseItToYmd(hv);
    if (!typed) return true; // se input non valido, non blocco tutto

    const rv = normYmd(rowValue);
    if (!rv) return false;

    // match esatto (consigliato)
    return rv === typed;

    // se preferisci anche parziale (es. "2025-12") usa:
    // return rv.startsWith(typed);
}
function formatTimeIt(dateLike) {
    const d = parseAnyDate(dateLike);
    if (!d) return "";
    return d.toLocaleTimeString("it-IT", {
        hour: "2-digit",
        minute: "2-digit",
    });
}

// -----------------------------
// Week helpers
// -----------------------------
function parseWeekValue(val) {
    const m = String(val || "").match(/^(\d{4})-W(\d{2})$/);
    if (!m) return null;
    return { year: Number(m[1]), week: Number(m[2]) };
}

function getIsoWeekYear(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);

    const day = (d.getDay() + 6) % 7; // lun=0..dom=6
    d.setDate(d.getDate() - day + 3);

    const isoYear = d.getFullYear();

    const firstThu = new Date(isoYear, 0, 4);
    const firstDay = (firstThu.getDay() + 6) % 7;
    firstThu.setDate(firstThu.getDate() - firstDay + 3);

    const week = 1 + Math.round((d - firstThu) / (7 * 24 * 60 * 60 * 1000));
    return { isoYear, week };
}

function apriSceltaSettimana() {
    const api = calendarRef.value?.getApi();
    const refDate = api?.getDate ? api.getDate() : new Date();
    const { isoYear, week } = getIsoWeekYear(refDate);
    selectedWeek.value = `${isoYear}-W${String(week).padStart(2, "0")}`;
    showWeekPicker.value = true;
}

function stampaSettimanaSelezionata() {
    const parsed = parseWeekValue(selectedWeek.value);
    if (!parsed) {
        alert("Seleziona una settimana valida (es. 2025-W51).");
        return;
    }

    const url =
        route("print.week.pdf", { year: parsed.year, week: parsed.week }) +
        "?download=0";
    window.open(url, "_blank");
    showWeekPicker.value = false;
}

function stampaSettimanaCorrente() {
    const api = calendarRef.value?.getApi();
    if (!api) return;

    const refDate = api.getDate ? api.getDate() : new Date();
    const { isoYear, week } = getIsoWeekYear(refDate);

    const url =
        route("print.week.pdf", { year: isoYear, week }) + "?download=0";
    window.open(url, "_blank");
}

// -----------------------------
// Stato / colori
// -----------------------------
function isTodayOrPast(dateLike) {
    const d = parseAnyDate(dateLike);
    if (!d) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    d.setHours(0, 0, 0, 0);
    return d <= today;
}

function IconaStatoConProduzione(status, dataInizio) {
    const v = String(status || "")
        .trim()
        .toLowerCase();
    if (v === "completato") return "🚚";
    if (v === "cancellato") return "❌";
    if (v === "sospeso") return "⏸️";
    if (v === "da pianificare") return "🗓️";
    if (v === "pianificato") return isTodayOrPast(dataInizio) ? "🏭" : "⏳";
    return "";
}

function normStatus(status) {
    return String(status || "")
        .trim()
        .toLowerCase();
}

function ColoriStato(status) {
    const v = normStatus(status);

    if (v === "da pianificare")
        return { bg: "#8B5CF6", border: "#7C3AED", text: "#FFFFFF" };
    if (v === "pianificato")
        return { bg: "#3B82F6", border: "#2563EB", text: "#FFFFFF" };
    if (v === "completato")
        return { bg: "#10B981", border: "#059669", text: "#FFFFFF" };
    if (v === "sospeso")
        return { bg: "#F59E0B", border: "#D97706", text: "#111827" };
    if (v === "cancellato")
        return { bg: "#9CA3AF", border: "#6B7280", text: "#FFFFFF" };

    return { bg: "#3B82F6", border: "#2563EB", text: "#FFFFFF" };
}

function ColoriStatoMagazzino(value) {
    const v = String(value || "").toLowerCase();

    let bg = "#e5e7eb";
    let border = "#d1d5db";
    let color = "#111827";

    if (v.includes("arrivato") || v.includes("magazzino")) {
        bg = "#dcfce7";
        border = "#86efac";
        color = "#065f46";
    } else if (v.includes("ordinato")) {
        bg = "#fffbeb";
        border = "#fde68a";
        color = "#92400e";
    } else if (v.includes("in ritardo")) {
        bg = "#fee2e2";
        border = "#fecaca";
        color = "#991b1b";
    }

    return { bg, border, color };
}

function badgeStatoMagazzinoHtml(value) {
    if (!value) return "";
    const v = String(value).toLowerCase();

    let bg = "#F3F4F6",
        border = "#D1D5DB",
        color = "#111827";

    if (v.includes("arrivato") || v.includes("magazzino")) {
        bg = "#ECFDF5";
        border = "#A7F3D0";
        color = "#065F46";
    } else if (v.includes("ordinato")) {
        bg = "#FFFBEB";
        border = "#FDE68A";
        color = "#92400E";
    } else if (v.includes("in ritardo")) {
        bg = "#FEF2F2";
        border = "#FECACA";
        color = "#991B1B";
    }

    return `
    <span style="
      display:inline-block;
      padding:2px 8px;
      border-radius:9999px;
      border:1px solid ${border};
      background:${bg};
      color:${color};
      font-weight:700;
      font-size:11px;
    ">${value}</span>
  `;
}

// -----------------------------
// Data copy props -> local
// -----------------------------
const giornoSelezionato = ref(dateKeyLocal(new Date()));

watch(
    () => props.appointments,
    (val) => {
        appointmentsLocal.value = Array.isArray(val)
            ? JSON.parse(JSON.stringify(val))
            : [];
        refreshTick.value++;

        nextTick(() => {
            const api = calendarRef.value?.getApi();
            api?.rerenderDates?.();
            updateTotals();
        });
    },
    { immediate: true }
);

// -----------------------------
// Filtri
// -----------------------------
const filteredAppointments = computed(() => {
    refreshTick.value;

    return (appointmentsLocal.value || []).filter((appointment) => {
        // const s = search.value?.toLowerCase().trim() || "";
        const s = searchDebounced.value || "";
        const ordine = String(appointment.NOrdine ?? appointment.Nordine ?? "");
        const riferimento = String(appointment.Riferimento ?? "");

        const matchSearch =
            !s ||
            (appointment.title || "").toLowerCase().includes(s) ||
            (appointment.client?.name || "").toLowerCase().includes(s) ||
            ordine.toLowerCase().includes(s) ||
            riferimento.toLowerCase().includes(s);

        const matchStatus =
            selectedStatus.value === "tutti" ||
            appointment.status === selectedStatus.value;

        const prodotti = Array.isArray(appointment.Prodotto)
            ? appointment.Prodotto
            : [];
        const matchProdotto =
            SceltaProdotto.value === "Tutti" ||
            prodotti.includes(SceltaProdotto.value);

        return matchSearch && matchStatus && matchProdotto;
    });
});

// ✅ ordinamento + totali elenco (DEVONO stare QUI prima di Tabulator)
const elencoOrdinato = computed(() => {
    const arr = [...(filteredAppointments.value || [])];
    arr.sort((a, b) => {
        const da = parseAnyDate(a.DataInizio)?.getTime() ?? 0;
        const db = parseAnyDate(b.DataInizio)?.getTime() ?? 0;
        return da - db;
    });
    return arr;
});

const totalePezziElenco = computed(() => {
    return (filteredAppointments.value || []).reduce(
        (sum, a) => sum + (Number(a.Pezzi ?? 0) || 0),
        0
    );
});

// -----------------------------
// Totali calendario
// -----------------------------
function sumPezziByDayKey(dayKey) {
    let tot = 0;
    for (const a of filteredAppointments.value) {
        const k = dateKeyLocal(a.DataInizio);
        if (k === dayKey) tot += Number(a.Pezzi ?? 0) || 0;
    }
    return tot;
}

function sumPezziInRange(start, end) {
    const s = parseAnyDate(start);
    const e = parseAnyDate(end);
    if (!s || !e) return 0;

    let tot = 0;
    for (const a of filteredAppointments.value) {
        const d = parseAnyDate(a.DataInizio);
        const p = Number(a.Pezzi ?? 0) || 0;
        if (d && d >= s && d < e) tot += p;
    }
    return tot;
}

function updateTotals() {
    const api = calendarRef.value?.getApi();
    if (!api) return;

    pezziGiorno.value = sumPezziByDayKey(giornoSelezionato.value);

    const v = api.view;
    pezziMese.value = sumPezziInRange(v.currentStart, v.currentEnd);
}

function updateTotalsSoon(api) {
    nextTick(() => {
        api?.rerenderDates?.();
        updateTotals();
    });
}

const pezziByDay = computed(() => {
    const map = {};
    for (const a of filteredAppointments.value) {
        const key = dateKeyLocal(a.DataInizio);
        const p = Number(a.Pezzi ?? 0) || 0;
        if (!key) continue;
        map[key] = (map[key] || 0) + p;
    }
    return map;
});

// -----------------------------
// FullCalendar events
// -----------------------------
const getFilteredEvents = () =>
    filteredAppointments.value.map((appointment) => ({
        id: appointment.id,
        title: appointment.title,
        start: appointment.DataInizio,
        end: appointment.DataFine,
        backgroundColor:
            appointment.status === "Da Pianificare"
                ? "#F59E0B"
                : appointment.status === "Pianificato"
                ? "#F59E0B"
                : appointment.status === "Completato"
                ? "#10B981"
                : "#EF4444",
        extendedProps: {
            client: appointment.client?.name,
            description: appointment.description,
            status: appointment.status,
            StatoMagazzino: appointment.StatoMagazzino ?? null,
            ordine: appointment.NOrdine ?? appointment.Nordine ?? null,
            pezzi: appointment.Pezzi ?? 0,
            DataConsegna: appointment.DataConsegna ?? null,
        },
    }));

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    locale: itLocale,
    editable: true,
    weekNumbers: true,
    weekText: "S",
    weekNumberContent: (arg) => ({ html: `S${arg.num}` }),

    displayEventTime: false,

    fixedWeekCount: true,
    expandRows: true,
    contentHeight: "auto",

    dayMaxEventRows: 13,
    moreLinkClick: "popover",

    eventContent: (arg) => {
        const p = arg.event.extendedProps || {};
        const ordine = p.ordine ? String(p.ordine) : "";
        const icon = IconaStatoConProduzione(p.status, arg.event.start);
        const ordineLabel = ordine ? `${icon ? icon + " " : ""}${ordine}` : "";
        const title = arg.event.title || "";
        const statoMagazzino = p.StatoMagazzino || "";

        if (!ordine)
            return { html: `<div style="font-weight:600;">${title}</div>` };

        const { bg, border, color } = ColoriStatoMagazzino(statoMagazzino);

        return {
            html: `
        <div style="display:flex; align-items:center; gap:6px; min-width:0;">
          <span style="
            font-size:11px;
            font-weight:800;
            padding:1px 6px;
            border-radius:9999px;
            border:1px solid ${border};
            background:${bg};
            color:${color};
            line-height:1.2;
            white-space:nowrap;
          ">${ordineLabel}</span>

          <span style="
            font-weight:600;
            min-width:0;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
          ">${title}</span>
        </div>
      `,
        };
    },

    dayCellDidMount: (arg) => {
        const api = calendarRef.value?.getApi();
        if (api?.view?.type !== "dayGridMonth") return;

        const key = dateKeyLocal(arg.date);
        const tot = key ? pezziByDay.value[key] || 0 : 0;

        const old = arg.el.querySelector(".pezzi-day");
        if (old) old.remove();
        if (tot <= 0) return;

        const badge = document.createElement("div");
        badge.className = "pezzi-day";
        badge.textContent = `Pezzi: ${tot}`;

        const top = arg.el.querySelector(".fc-daygrid-day-top") || arg.el;
        top.appendChild(badge);
    },

    headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
    },

    events: getFilteredEvents(),

    datesSet: () => updateTotalsSoon(calendarRef.value?.getApi()),

    eventDidMount: (arg) => {
        if (arg.el._tippy) arg.el._tippy.destroy();

        const p = arg.event.extendedProps || {};
        const title = arg.event.title || "";

        const StatoMagazzino = p.StatoMagazzino
            ? `<div><b>Magazzino:</b> ${badgeStatoMagazzinoHtml(
                  p.StatoMagazzino
              )}</div>`
            : "";

        const ordine = p.ordine
            ? `<div><b>N° Ordine:</b> ${p.ordine}</div>`
            : "";
        const pezzi =
            p.pezzi !== null && p.pezzi !== undefined
                ? `<div><b>Pezzi:</b> ${p.pezzi}</div>`
                : "";

        const status = p.status ? `<div><b>Stato:</b> ${p.status}</div>` : "";
        const desc = p.description
            ? `<div><b>Itinerario:</b> ${p.description}</div>`
            : "";

        tippy(arg.el, {
            content: `
        <div style="font-size:12px; line-height:1.3; max-width:320px">
          <div style="font-weight:700; margin-bottom:6px">${title}</div>
          ${StatoMagazzino}
          ${ordine}
          ${pezzi}
          ${status}
          ${desc}
        </div>
      `,
            allowHTML: true,
            placement: "top",
            appendTo: document.body,
            delay: [150, 0],
        });
    },

    eventWillUnmount: (arg) => {
        if (arg.el?._tippy) arg.el._tippy.destroy();
    },

    eventClick: (info) => {
        window.location.href = route("appointments.edit", info.event.id);
    },

    eventDrop: (info) => {
        const ev = info.event;

        const newStart = ev.start ? toLocalMySql(ev.start) : null;
        const newEnd = ev.allDay ? null : ev.end ? toLocalMySql(ev.end) : null;

        const payload = { start: newStart, end: newEnd, allDay: !!ev.allDay };
        const api = calendarRef.value?.getApi();

        const idx = appointmentsLocal.value.findIndex(
            (a) => String(a.id) === String(ev.id)
        );
        const oldStart =
            idx !== -1 ? appointmentsLocal.value[idx].DataInizio : null;
        const oldEnd =
            idx !== -1 ? appointmentsLocal.value[idx].DataFine : null;

        if (idx !== -1) {
            appointmentsLocal.value[idx].DataInizio = payload.start;
            appointmentsLocal.value[idx].DataFine =
                payload.end ?? payload.start;
            refreshTick.value++;
        }

        giornoSelezionato.value = dateKeyLocal(payload.start);
        updateTotalsSoon(api);

        router.put(`/appointments/${ev.id}/move`, payload, {
            preserveScroll: true,
            onError: () => {
                alert("Errore nel salvataggio");
                if (typeof info.revert === "function") info.revert();

                if (idx !== -1) {
                    appointmentsLocal.value[idx].DataInizio = oldStart;
                    appointmentsLocal.value[idx].DataFine = oldEnd;
                    refreshTick.value++;
                }

                giornoSelezionato.value = dateKeyLocal(oldStart);
                updateTotalsSoon(api);
            },
            onSuccess: () => window.location.reload(),
            onFinish: () => updateTotalsSoon(api),
        });
    },

    dateClick: (info) => {
        giornoSelezionato.value = info.dateStr;
        updateTotalsSoon(calendarRef.value?.getApi());

        router.visit(route("appointments.create"), {
            data: { DataInizio: info.dateStr, DataConsegna: info.dateStr },
            method: "get",
        });
    },
});

// Aggiorna eventi quando cambiano filtri/appointments
watch(filteredAppointments, () => {
    const api = calendarRef.value?.getApi();
    if (!api) return;

    api.setOption("events", getFilteredEvents()); // ✅ molto più leggero
    updateTotalsSoon(api);
});

// watch(filteredAppointments, () => {
//   const calendarApi = calendarRef.value?.getApi();
//   if (!calendarApi) return;

//   calendarApi.removeAllEvents();
//   getFilteredEvents().forEach((event) => calendarApi.addEvent(event));

//   updateTotalsSoon(calendarApi);
// });

// -----------------------------
// ✅ Tabulator (Lista)
// -----------------------------
const tableEl = ref(null);
let tab = null;
let tabBuilt = false;
let tabBuildPromise = null;

function goEdit(id) {
    window.location.href = route("appointments.edit", id);
}

function buildRows() {
    return (elencoOrdinato.value || []).map((a) => ({
        id: a.id,
        DataInizio: a.DataInizio,
        DataConsegna: a.DataConsegna,
        Nordine: a.NOrdine ?? a.Nordine ?? null,
        Cliente: a.title ?? "",
        description: a.description ?? "",
        Riferimento: a.Riferimento ?? "",
        Prodotto: Array.isArray(a.Prodotto) ? a.Prodotto.join(", ") : "",
        Stato: a.status ?? "",
        StatoMagazzino: a.StatoMagazzino ?? "",
        Pezzi: Number(a.Pezzi ?? 0) || 0,
        _raw: a,
    }));
}

function initTabulator() {
    const cols = [
        {
            title: "Itinerario",
            field: "description",
            minWidth: 140,
            headerFilter: "input",
            headerFilterPlaceholder: "Filtra…",
            formatter: (cell) => {
                const v = cell.getValue() ?? "";
                return `<div style="font-weight:600;color:#111827;">${String(
                    v
                )}</div>`;
            },
        },
        {
            title: "Data Produzione",
            field: "DataInizio",
            sorter: "datetime",
            width: 100,
            headerFilter: "input",
            headerFilterPlaceholder: "gg/mm/aaaa",
            headerFilterFunc: headerFilterDateIt,
            formatter: (cell) => formatDateIt(cell.getValue()),
        },
        {
            title: "Data Consegna",
            field: "DataConsegna",
            sorter: "datetime",
            width: 100,
            headerFilter: "input",
            headerFilterFunc: headerFilterDateIt,
            headerFilterPlaceholder: "gg/mm/aaaa",
            formatter: (cell) => formatDateIt(cell.getValue()),
        },
        {
            title: "Ordine",
            field: "Nordine",
            width: 110,
            sorter: "number",
            headerFilter: "input",
            headerFilterPlaceholder: "N°…",
        },
        {
            title: "Cliente",
            field: "Cliente",
            minWidth: 240,
            headerFilter: "input",
            headerFilterPlaceholder: "Filtra…",
            formatter: (cell) => {
                const v = cell.getValue() ?? "";
                return `<div style="font-weight:600;color:#111827;">${String(
                    v
                )}</div>`;
            },
        },
        {
            title: "Riferimento",
            field: "Riferimento",
            minWidth: 240,
            headerFilter: "input",
            headerFilterPlaceholder: "Filtra…",
            formatter: (cell) => {
                const v = cell.getValue() ?? "";
                return `<div style="font-weight:600;color:#111827;">${String(
                    v
                )}</div>`;
            },
        },
        {
            title: "Stato",
            field: "Stato",
            width: 180,

            headerFilter: "input",
            headerFilterPlaceholder: "Filtra…",

        },
        {
            title: "Prodotto",
            field: "Prodotto",
            width: 160,
            headerFilter: "input",
            headerFilterPlaceholder: "PA / IA…",
        },

        {
            title: "Magazzino",
            field: "StatoMagazzino",
            width: 170,
            headerFilter: "input",
            headerFilterPlaceholder: "Filtra…",
            formatter: (cell) => badgeStatoMagazzinoHtml(cell.getValue()),
        },
        {
            title: "Pezzi",
            field: "Pezzi",
            hozAlign: "right",
            width: 90,
            sorter: "number",
            headerFilter: "input",
            headerFilterPlaceholder: ">= …",
        },
        {
            title: "Azioni",
            field: "id",
            width: 110,
            hozAlign: "center",
            headerSort: false,
            formatter: (cell) => {
                const id = cell.getValue();
                return `<button class="tab-btn-edit" data-id="${id}">Modifica</button>`;
            },
            cellClick: (e, cell) => goEdit(cell.getValue()),
        },
    ];

    cols.forEach((c) => {
        if ("headerFilter" in c) {
            console.log("HF:", c.title, c.field, c.headerFilter);
        }
    });

    // ✅ SANITIZE: evita "editor undefined" (Tabulator explode)
    const allowed = new Set([
        "input",
        "select",
        "number",
        "tickCross",
        "textarea",
    ]);
    cols.forEach((c) => {
        if ("headerFilter" in c) {
            if (
                typeof c.headerFilter !== "string" ||
                !allowed.has(c.headerFilter)
            ) {
                console.warn(
                    "Fix headerFilter non valido:",
                    c.title,
                    c.field,
                    c.headerFilter
                );
                delete c.headerFilter;
                delete c.headerFilterParams;
                delete c.headerFilterFunc;
                delete c.headerFilterPlaceholder;
            }
        }
    });

    if (!tableEl.value || tab) return;
    cols.forEach((c, i) => {
        if (
            "headerFilter" in c &&
            (c.headerFilter === undefined || c.headerFilter === null)
        ) {
            console.error(
                "❌ headerFilter UNDEFINED su colonna:",
                i,
                c.title,
                c.field,
                c
            );
        }
        if ("headerFilter" in c && typeof c.headerFilter !== "string") {
            console.error(
                "❌ headerFilter NON string su colonna:",
                i,
                c.title,
                c.field,
                c.headerFilter
            );
        }
    });
    tab = new Tabulator(tableEl.value, {
        layout: "fitColumns",
        height: "600px",
        index: "id",
        reactiveData: false,

        placeholder: "Nessun record trovato con i filtri attuali.",
        movableColumns: true,
        resizableColumnFit: true,

        pagination: true,
        paginationSize: 100,
        paginationSizeSelector: [10, 25, 50, 100],

        data: buildRows(),
        columns: cols,

        tableBuilt: function () {
            tabBuilt = true;
            applyColumnVisibility();
            setTimeout(() => safeRedraw(), 50);
        },
        rowClick: (e, row) => {
            const data = row.getData();
            if (data?.id) goEdit(data.id);
        },
    });
    // applyColumnVisibility();
}

async function refreshTabulatorData() {
    if (!tab) return;
    await waitTabBuilt();
    if (!tabBuilt) return;

    tab.setData(buildRows());
}

// init / redraw al cambio tab
watch(activeTab, (t) => {
    if (t === "list") {
        nextTick(async () => {
            initTabulator();
            await waitTabBuilt();
            await refreshTabulatorData();
            tab?.redraw?.(true);
            setTimeout(() => safeRedraw(), 50);
        });
    }
    if (t === "calendar") {
        nextTick(() => {
            calendarRef.value?.getApi()?.updateSize();
            calendarRef.value?.getApi()?.rerenderDates?.();
        });
    }
});

// aggiorna dati tab al variare elenco
watch(
    () => elencoOrdinato.value,
    () => nextTick(() => refreshTabulatorData()),
    { deep: true }
);

// cleanup
onBeforeUnmount(() => {
    if (tab) {
        tab.destroy();
        tab = null;
    }
});

// -----------------------------
// Mounted (Echo)
// -----------------------------
onMounted(() => {
    updateTotalsSoon(calendarRef.value?.getApi());

    if (window.Echo) {
        window.Echo.channel("appointments").listen(".updated", (e) => {
            const calendarApi = calendarRef.value?.getApi();
            if (!calendarApi) return;

            const event = calendarApi.getEventById(e.appointment.id);
            const color =
                e.appointment.status === "completed"
                    ? "#10B981"
                    : e.appointment.status === "cancelled"
                    ? "#EF4444"
                    : "#3B82F6";

            if (event) {
                event.setProp("title", e.appointment.title);
                event.setStart(e.appointment.DataInizio);
                event.setEnd(e.appointment.DataFine);
                event.setExtendedProp("client", e.appointment.client?.name);
                event.setExtendedProp("description", e.appointment.description);
                event.setExtendedProp("status", e.appointment.status);
                event.setProp("backgroundColor", color);
            } else {
                calendarApi.addEvent({
                    id: e.appointment.id,
                    title: e.appointment.title,
                    start: e.appointment.DataInizio,
                    end: e.appointment.DataFine,
                    backgroundColor: color,
                    extendedProps: {
                        client: e.appointment.client?.name,
                        description: e.appointment.description,
                        status: e.appointment.status,
                    },
                });
            }

            updateTotalsSoon(calendarApi);
        });
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Calendario" />

        <!-- Import report -->
        <div
            v-if="importReport"
            class="mx-6 mt-3 p-4 rounded-lg border bg-gray-50"
        >
            <div class="font-semibold">{{ importReport.summary }}</div>

            <div v-if="importReport.details?.length" class="mt-3">
                <div class="text-sm font-semibold mb-2">
                    Dettagli (max {{ importReport.detail_limit }})
                </div>

                <div class="max-h-64 overflow-auto border rounded bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left p-2">Riga</th>
                                <th class="text-left p-2">Tipo</th>
                                <th class="text-left p-2">Messaggio</th>
                                <th class="text-left p-2">Dati</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(d, i) in importReport.details"
                                :key="i"
                                class="border-t"
                            >
                                <td class="p-2">{{ d.row }}</td>
                                <td class="p-2">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-bold"
                                        :class="
                                            d.type === 'error'
                                                ? 'bg-red-100 text-red-700'
                                                : 'bg-amber-100 text-amber-700'
                                        "
                                    >
                                        {{ d.type }}
                                    </span>
                                </td>
                                <td class="p-2">{{ d.message }}</td>
                                <td class="p-2 text-xs text-gray-600">
                                    {{ JSON.stringify(d.data) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="h-[calc(100vh-4rem)] flex flex-col bg-white">
            <!-- Filtri -->
            <div class="flex gap-4 px-6 py-3 border-b bg-gray-50">
                <button
                    type="button"
                    @click="apriSceltaSettimana"
                    class="px-4 py-2 bg-gray-900 text-white rounded hover:bg-gray-800"
                >
                    🖨️ Stampa settimana…
                </button>

                <div
                    v-if="showWeekPicker"
                    class="fixed inset-0 bg-black/30 flex items-center justify-center z-50"
                >
                    <div
                        class="bg-white rounded-xl shadow-xl p-5 w-full max-w-md"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold">
                                Seleziona settimana
                            </h3>
                            <button
                                type="button"
                                class="px-2 py-1 rounded hover:bg-gray-100"
                                @click="showWeekPicker = false"
                            >
                                ✕
                            </button>
                        </div>

                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2"
                            >Settimana</label
                        >

                        <input
                            v-model="selectedWeek"
                            type="week"
                            class="w-full border rounded-lg px-3 py-2"
                        />

                        <div class="flex justify-end gap-2 mt-4">
                            <button
                                type="button"
                                class="px-3 py-2 rounded-lg bg-gray-200 hover:bg-gray-300"
                                @click="showWeekPicker = false"
                            >
                                Annulla
                            </button>

                            <button
                                type="button"
                                class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                                @click="stampaSettimanaSelezionata"
                            >
                                Stampa
                            </button>
                        </div>
                    </div>
                </div>

                <Link
                    :href="
                        route('appointments.create', {
                            DataConsegna: giornoSelezionato,
                            DataInizio: giornoSelezionato,
                        })
                    "
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                    Aggiungi Evento
                </Link>
                <button
                v-if="isAdmin"
                    class="px-4 py-2 rounded bg-blue-600 text-white disabled:opacity-60"
                    :disabled="EtEseguiAccess"
                    @click="EseguiAccess"
                >
                    {{
                        EseguiAccess
                            ? "Import in corso..."
                            : "Import Ordini + Prodotti"
                    }}
                </button>
                <button
                 v-if="$page.props.auth.user.profilo === 'admin'"
                    class="px-4 py-2 rounded bg-blue-600 text-white disabled:opacity-60"
                    :disabled="EtImportaDati"
                    @click="ImportaDati"
                >
                    {{
                        EtImportaDati
                            ? "Import in corso..."
                            : "Import Ordini + Prodotti"
                    }}
                </button>

                <input
                    v-model="search"
                    type="text"
                    placeholder="Cerca per Cliente, N° ordine, riferimento..."
                    class="border px-3 py-2 rounded w-full"
                />

                <select
                    v-model="SceltaProdotto"
                    class="border px-3 py-2 rounded"
                >
                    <option value="Tutti">Tutti</option>
                    <option value="AR">Archi</option>
                    <option value="CP">Coprifili</option>
                    <option value="CA">Cover Alluminio</option>
                    <option value="IA">Infissi Alluminio</option>
                    <option value="PAF">Persiane Fisse</option>
                    <option value="PA">Persiane</option>
                    <option value="SG">Sghembi</option>
                    <option value="SC">Scuroni</option>
                    <option value="VA">Varie</option>
                </select>

                <select
                    v-model="selectedStatus"
                    class="border px-3 py-2 rounded"
                >
                    <option value="tutti">Tutti</option>
                    <option value="Da Pianificare">Da Pianificare</option>
                    <option value="Pianificato">Pianificato</option>
                    <option value="Completato">Completato</option>
                    <option value="Sospeso">Sospeso</option>
                    <option value="Cancellato">Cancellato</option>
                </select>
            </div>

            <!-- Legenda -->
            <div class="flex gap-4 px-6 py-2 text-sm bg-gray-50 border-b">
                Totale pezzi mese: {{ pezziMese }}
                <span>🗓️ Da Pianificare</span>
                <span>⏳ Programmato</span>
                <span>🏭 In Lavorazione</span>
                <span>🚚 Completato</span>
                <span>📦 In magazzino</span>
                <span>🚛 In Arrivo</span>
                <span>⚠️ In ritardo</span>
                <span>❌ Annullato</span>
            </div>

            <!-- Tabs -->
            <div class="px-6 pt-3 bg-white border-b">
                <div class="inline-flex rounded-lg border bg-gray-50 p-1">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-md"
                        :class="
                            activeTab === 'calendar'
                                ? 'bg-white shadow'
                                : 'text-gray-600 hover:text-gray-900'
                        "
                        @click="activeTab = 'calendar'"
                    >
                        📅 Calendario
                    </button>

                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-md"
                        :class="
                            activeTab === 'list'
                                ? 'bg-white shadow'
                                : 'text-gray-600 hover:text-gray-900'
                        "
                        @click="activeTab = 'list'"
                    >
                        📋 Lista ordini ({{ elencoOrdinato.length }})
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-auto">
                <!-- TAB 1 -->
                <div v-show="activeTab === 'calendar'">
                    <FullCalendar
                        ref="calendarRef"
                        :options="calendarOptions"
                        class="h-full"
                    />
                </div>

                <!-- TAB 2 -->
                <div v-show="activeTab === 'list'" class="bg-white">
                    <div class="mt-4 bg-white border-t">
                        <div
                            class="px-6 py-3 flex items-center justify-between gap-4"
                        >
                            <div class="font-semibold">
                                Elenco record ({{ elencoOrdinato.length }}) —
                                Totale pezzi: {{ totalePezziElenco }}
                            </div>

                            <div class="text-sm text-gray-600">
                                Giorno selezionato:
                                <span class="font-semibold">{{
                                    giornoSelezionato
                                }}</span>
                                — Pezzi giorno:
                                <span class="font-semibold">{{
                                    pezziGiorno
                                }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="px-3 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-sm font-semibold"
                                    @click="clearAllColumnFilters"
                                >
                                    🧹 Reset filtri colonne
                                </button>

                                <button
                                    type="button"
                                    class="px-3 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800 text-sm font-semibold"
                                    @click="showColumnPanel = !showColumnPanel"
                                >
                                    🧩 Colonne
                                </button>

                                <!-- Pannello colonne -->
                                <div v-if="showColumnPanel" class="relative">
                                    <div
                                        class="absolute right-0 mt-2 w-72 bg-white border rounded-xl shadow-lg p-3 z-50"
                                    >
                                        <div
                                            class="flex items-center justify-between mb-2"
                                        >
                                            <div class="font-semibold">
                                                Mostra/Nascondi colonne
                                            </div>
                                            <button
                                                class="px-2 py-1 rounded hover:bg-gray-100"
                                                @click="showColumnPanel = false"
                                            >
                                                ✕
                                            </button>
                                        </div>

                                        <div
                                            class="space-y-2 max-h-64 overflow-auto pr-1"
                                        >
                                            <label
                                                v-for="c in columnDefs"
                                                :key="c.field"
                                                class="flex items-center gap-2 text-sm"
                                            >
                                                <input
                                                    type="checkbox"
                                                    v-model="
                                                        visibleCols[c.field]
                                                    "
                                                    @change="
                                                        applyColumnVisibility
                                                    "
                                                />
                                                <span>{{ c.title }}</span>
                                            </label>
                                        </div>

                                        <div
                                            class="flex justify-end gap-2 mt-3"
                                        >
                                            <button
                                                type="button"
                                                class="px-3 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-sm font-semibold"
                                                @click="resetColumnVisibility"
                                            >
                                                Ripristina
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 pb-5">
                            <div ref="tableEl" class="border rounded-lg"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* ✅ Celle mese: altezza controllata */
.fc .fc-daygrid-day-frame {
    min-height: 90px;
    padding-bottom: 4px;
}

/* eventi */
.fc .fc-daygrid-day-events {
    margin-top: 2px;
}

/* ✅ evento compatto */
.fc-daygrid-event {
    margin: 1px 0 !important;
    padding: 0 6px !important;
    font-size: 11px !important;
    line-height: 1.2 !important;
    border-radius: 6px !important;
}

/* titolo evento */
.fc-daygrid-event .fc-event-title {
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* +X altri */
.fc-daygrid-more-link {
    font-size: 11px;
    font-weight: 600;
    color: #2563eb;
}

/* oggi */
.fc-day-today {
    background-color: #fefce8 !important;
}

/* ✅ Badge pezzi */
.pezzi-day {
    margin-top: 4px;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    line-height: 1.2;
    white-space: nowrap;
}

/* Tabulator button */
.tab-btn-edit {
    padding: 4px 8px;
    border-radius: 6px;
    background: #2563eb;
    color: white;
    font-size: 12px;
    font-weight: 700;
}
.tab-btn-edit:hover {
    background: #1d4ed8;
}
/* Header filter più leggibili */
.tabulator .tabulator-header .tabulator-col .tabulator-header-filter {
    padding: 3px 4px;
}

.tabulator .tabulator-header .tabulator-col .tabulator-header-filter input,
.tabulator .tabulator-header .tabulator-col .tabulator-header-filter select {
    width: 100% !important;
    min-width: 70px;
    height: 26px;
    padding: 2px 6px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 12px;
    background: #fff;
}
</style>
