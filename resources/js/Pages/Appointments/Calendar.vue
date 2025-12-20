<script setup>
// Tooltip
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import itLocale from "@fullcalendar/core/locales/it";
import { ref, computed, onMounted, watch, nextTick } from "vue";
import tippy from "tippy.js";
import "tippy.js/dist/tippy.css";

const props = defineProps({ appointments: Array });

const refreshTick = ref(0);
const calendarRef = ref(null);

const search = ref("");
const selectedStatus = ref("tutti"); // tutti, scheduled, active, completed, cancelled
const SceltaProdotto = ref("Tutti");
const appointmentsLocal = ref([]);

// ✅ Totali sempre visibili
const pezziGiorno = ref(0);
const pezziMese = ref(0);

// -----------------------------
// ✅ PARSE DATE ROBUSTO (MySQL / ISO / Date)
// -----------------------------
function parseAnyDate(dateLike) {
    if (!dateLike) return null;

    if (dateLike instanceof Date) {
        return isNaN(dateLike) ? null : dateLike;
    }

    const s = String(dateLike).trim();

    // MySQL: "YYYY-MM-DD HH:mm:ss" -> "YYYY-MM-DDTHH:mm:ss"
    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/.test(s)) {
        const d = new Date(s.replace(" ", "T"));
        return isNaN(d) ? null : d;
    }

    // MySQL: "YYYY-MM-DD HH:mm" -> "YYYY-MM-DDTHH:mm:00"
    if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/.test(s)) {
        const d = new Date(s.replace(" ", "T") + ":00");
        return isNaN(d) ? null : d;
    }

    // Date only: "YYYY-MM-DD"
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
        const d = new Date(`${s}T00:00:00`);
        return isNaN(d) ? null : d;
    }

    // ISO o altri formati
    const d = new Date(s);
    return isNaN(d) ? null : d;
}

// ✅ helper: YYYY-MM-DD in ORA LOCALE
function dateKeyLocal(dateLike) {
    const d = parseAnyDate(dateLike);
    if (!d) return null;

    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
}

const giornoSelezionato = ref(dateKeyLocal(new Date())); // default: oggi

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

// ✅ helper: DATETIME locale "YYYY-MM-DD HH:mm:ss" (NO Z / NO UTC)
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

// ✅ Filtro combinato (ricerca + stato + prodotto)
const filteredAppointments = computed(() => {
    refreshTick.value;

    return (appointmentsLocal.value || []).filter((appointment) => {
        const s = search.value?.toLowerCase() || "";

        const matchSearch =
            !s ||
            (appointment.title || "").toLowerCase().includes(s) ||
            (appointment.client?.name || "").toLowerCase().includes(s);

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

// ✅ somma pezzi per giorno
function sumPezziByDayKey(dayKey) {
    let tot = 0;
    for (const a of filteredAppointments.value) {
        const k = dateKeyLocal(a.DataInizio);
        if (k === dayKey) tot += Number(a.Pezzi ?? 0) || 0;
    }
    return tot;
}

// ✅ somma pezzi in un range
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

// ✅ aggiorna totali
function updateTotals() {
    const api = calendarRef.value?.getApi();
    if (!api) return;

    pezziGiorno.value = sumPezziByDayKey(giornoSelezionato.value);

    const v = api.view;
    pezziMese.value = sumPezziInRange(v.currentStart, v.currentEnd);
}

// ✅ aggiorna totali dopo reattività Vue
function updateTotalsSoon(api) {
    nextTick(() => {
        api?.rerenderDates?.();
        updateTotals();
    });
}

// ✅ Totale pezzi per ogni giorno
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

// Trasforma in eventi
const getFilteredEvents = () =>
    filteredAppointments.value.map((appointment) => ({
        id: appointment.id,
        title: appointment.title,
        start: appointment.DataInizio,
        end: appointment.DataFine,
        backgroundColor:
            appointment.status === "completed"
                ? "#10B981"
                : appointment.status === "cancelled"
                ? "#EF4444"
                : "#3B82F6",
        extendedProps: {
            client: appointment.client?.name,
            description: appointment.description,
            status: appointment.status,
            StatoMagazzino: appointment.StatoMagazzino ?? null,
            ordine: appointment.NOrdine ?? appointment.Nordine ?? null,
            pezzi: appointment.Pezzi ?? 0,
        },
    }));

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    locale: itLocale,
    editable: true,
    weekNumbers: true,
    displayEventTime: false,

    // ✅ BLOCCA LA CRESCITA DELLE RIGHE
    fixedWeekCount: true,
    expandRows: true,
    contentHeight: "auto",

    // ✅ MOSTRA POCHI EVENTI + “+X altri”
    dayMaxEventRows: 15,
    moreLinkClick: "popover",

    eventContent: (arg) => {
        const p = arg.event.extendedProps || {};
        const ordine = p.ordine ? String(p.ordine) : "";
        const title = arg.event.title || "";
        const statoMagazzino = p.StatoMagazzino || "";

        if (!ordine) {
            return { html: `<div style="font-weight:600;">${title}</div>` };
        }

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
          ">
            ${ordine}
          </span>

          <span style="
            font-weight:600;
            min-width:0;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
          ">
            ${title}
          </span>
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

    datesSet: () => {
        updateTotalsSoon(calendarRef.value?.getApi());
    },

    eventDidMount: (arg) => {
        if (arg.el._tippy) arg.el._tippy.destroy();

        const p = arg.event.extendedProps || {};
        const title = arg.event.title || "";

        const StatoMagazzino = p.StatoMagazzino
            ? `<div><b>Magazzino:</b> ${badgeStatoMagazzinoHtml(p.StatoMagazzino)}</div>`
            : "";

        const ordine = p.ordine ? `<div><b>N° Ordine:</b> ${p.ordine}</div>` : "";
        const pezzi =
            p.pezzi !== null && p.pezzi !== undefined
                ? `<div><b>Pezzi:</b> ${p.pezzi}</div>`
                : "";

        const status = p.status ? `<div><b>Stato:</b> ${p.status}</div>` : "";
        const desc = p.description ? `<div><b>Note:</b> ${p.description}</div>` : "";

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

        const idx = appointmentsLocal.value.findIndex((a) => String(a.id) === String(ev.id));
        const oldStart = idx !== -1 ? appointmentsLocal.value[idx].DataInizio : null;
        const oldEnd = idx !== -1 ? appointmentsLocal.value[idx].DataFine : null;

        if (idx !== -1) {
            appointmentsLocal.value[idx].DataInizio = payload.start;
            appointmentsLocal.value[idx].DataFine = payload.end ?? payload.start;
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
            onFinish: () => updateTotalsSoon(api),
        });
    },

    dateClick: (info) => {
        giornoSelezionato.value = info.dateStr;
        updateTotalsSoon(calendarRef.value?.getApi());

        router.visit(route("appointments.create"), {
            data: { DataInizio: info.dateStr },
            method: "get",
        });
    },
});

// Aggiorna eventi quando cambiano filtri o appointments
watch(filteredAppointments, () => {
    const calendarApi = calendarRef.value?.getApi();
    if (!calendarApi) return;

    calendarApi.removeAllEvents();
    getFilteredEvents().forEach((event) => calendarApi.addEvent(event));
    updateTotalsSoon(calendarApi);
});

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

        <div class="h-[calc(100vh-4rem)] flex flex-col bg-white">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b">
                <h2 class="text-2xl font-semibold">
                    📅 {{ $page.props.auth.user.name }}
                </h2>

                <Link
                    :href="route('appointments.create')"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                    Aggiungi Appuntamento
                </Link>
            </div>

            <!-- Filtri -->
            <div class="flex gap-4 px-6 py-3 border-b bg-gray-50">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cerca per titolo o cliente..."
                    class="border px-3 py-2 rounded w-full"
                />

                <div class="px-6 py-2 border-b bg-white text-sm font-semibold">
                    Scelta Prodotto
                </div>

                <select v-model="SceltaProdotto" class="border px-3 py-2 rounded">
                    <option value="Tutti">Tutti</option>
                    <option value="PA">Persiane</option>
                    <option value="SC">Scuroni</option>
                    <option value="CA">Cover Alluminio</option>
                    <option value="IA">Infissi Alluminio</option>
                </select>

                <div class="px-6 py-2 border-b bg-white text-sm font-semibold">
                    Stato produzione
                </div>

                <select v-model="selectedStatus" class="border px-3 py-2 rounded">
                    <option value="tutti">Tutti</option>
                    <option value="scheduled">In programma</option>
                    <option value="active">Attivi</option>
                    <option value="completed">Completati</option>
                    <option value="cancelled">Cancellati</option>
                </select>
            </div>

            <!-- Totali -->
            <div class="px-6 py-2 border-b bg-white text-sm font-semibold">
                Totale pezzi giorno ({{ giornoSelezionato }}): {{ pezziGiorno }}
                — Totale pezzi mese: {{ pezziMese }}
            </div>

            <!-- Calendario -->
            <div class="flex-1">
                <FullCalendar
                    ref="calendarRef"
                    :options="calendarOptions"
                    class="h-full"
                />
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

/* titolo */
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
    font-size: 11px; /* <-- NON 1px */
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    line-height: 1.2;
    white-space: nowrap;
}
</style>
