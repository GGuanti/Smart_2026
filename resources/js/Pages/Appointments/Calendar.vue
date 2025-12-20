<script setup>
// Tooltip
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import itLocale from "@fullcalendar/core/locales/it";
import { ref, computed, onMounted, watch } from "vue";
import tippy from "tippy.js";
import "tippy.js/dist/tippy.css";
const refreshTick = ref(0);
const props = defineProps({ appointments: Array });
const calendarRef = ref(null);
const search = ref("");
const selectedStatus = ref("tutti"); // tutti, scheduled, active, completed, cancelled
const SceltaProdotto = ref("Tutti");
const appointmentsLocal = ref([]);
// ✅ Totali sempre visibili
const pezziGiorno = ref(0);
const pezziMese = ref(0);
const giornoSelezionato = ref(dateKeyLocal(new Date())); // default: oggi
// ✅ Totale pezzi per ogni giorno: { "2025-12-18": 25, ... }
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
// Filtro combinato
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
            SceltaProdotto.value === "Tutti" ||
            appointment.status === selectedStatus.value;

        return matchSearch && matchStatus;
    });
});

watch(
    () => props.appointments,
    (val) => {
        appointmentsLocal.value = Array.isArray(val)
            ? JSON.parse(JSON.stringify(val))
            : [];
    },
    { immediate: true }
);

function badgeStatoMagazzinoHtml(value) {
    if (!value) return "";
    const v = String(value).toLowerCase();
    // 🔧 mappa (adatta le parole chiave ai tuoi stati reali)
    let bg = "#F3F4F6",
        border = "#D1D5DB",
        color = "#111827"; // default grigio

    // VERDE: ok / disponibile / evaso
    if (v.includes("arrivato") || v.includes("magazzino")) {
        bg = "#ECFDF5";
        border = "#A7F3D0";
        color = "#065F46";
    }

    // ARANCIONE: parziale / in attesa / da verificare
    if (v.includes("in arrivo")) {
        bg = "#FFFBEB";
        border = "#FDE68A";
        color = "#92400E";
    }

    // ROSSO: bloccato / non disponibile / errore
    if (v.includes("in ritardo")) {
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
    // default grigio
    let bg = "#e5e7eb";
    let border = "#d1d5db";
    let color = "#111827";
    // VERDE: ok / disponibile / evaso
    if (v.includes("arrivato") || v.includes("magazzino")) {
        bg = "#dcfce7";
        border = "#86efac";
        color = "#065f46";
    }
    // ARANCIONE: in attesa / parziale / verificare
    else if (v.includes("in arrivo")) {
        bg = "#fffbeb";
        border = "#fde68a";
        color = "#92400e";
    }
    // ROSSO: bloccato / non disponibile / errore
    else if (v.includes("in ritardo")) {
        bg = "#fee2e2";
        border = "#fecaca";
        color = "#991b1b";
    }
    return { bg, border, color };
}
// ✅ helper: YYYY-MM-DD in ORA LOCALE (evita slittamenti UTC)
function dateKeyLocal(dateLike) {
    const d = dateLike instanceof Date ? dateLike : new Date(dateLike);
    if (isNaN(d)) return null;

    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");

    return `${y}-${m}-${day}`;
}

// ✅ somma pezzi per giorno (chiave YYYY-MM-DD locale)
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
    const s = new Date(start);
    const e = new Date(end);
    let tot = 0;

    for (const a of filteredAppointments.value) {
        const d = new Date(a.DataInizio);
        const p = Number(a.Pezzi ?? 0) || 0;
        if (!isNaN(d) && d >= s && d < e) tot += p;
    }
    return tot;
}

// ✅ aggiorna totali (giorno selezionato + mese corrente in vista)
function updateTotals() {
    const api = calendarRef.value?.getApi();
    if (!api) return;
    pezziGiorno.value = sumPezziByDayKey(giornoSelezionato.value);
    const v = api.view;
    // per il mese usiamo il mese “reale” della vista
    pezziMese.value = sumPezziInRange(v.currentStart, v.currentEnd);
}

// Trasforma in eventi FullCalendar
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
        if (api?.view?.type !== "dayGridMonth") return; // solo in vista mese

        const key = dateKeyLocal(arg.date);
        const tot = key ? pezziByDay.value[key] || 0 : 0;

        // evita doppioni
        const old = arg.el.querySelector(".pezzi-day");
        if (old) old.remove();

        // se vuoi mostrarlo anche quando è 0, togli questo if
        if (tot <= 0) return;

        const badge = document.createElement("div");
        badge.className = "pezzi-day";
        badge.textContent = `Pezzi: ${tot}`;

        badge.style.marginTop = "4px";
        badge.style.fontSize = "11px";
        badge.style.fontWeight = "700";
        badge.style.padding = "2px 8px";
        badge.style.borderRadius = "9999px";
        badge.style.display = "inline-block";
        badge.style.border = "1px solid #e5e7eb";
        badge.style.background = "#f9fafb";

        const top = arg.el.querySelector(".fc-daygrid-day-top") || arg.el;
        top.appendChild(badge);
    },
    headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
    },

    events: getFilteredEvents(),

    // ✅ quando cambi mese/vista aggiorna i totali mese + giorno selezionato
    datesSet: () => {
        updateTotals();
    },

    // ✅ Tooltip su hover
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
            ? `<div><b>Note:</b> ${p.description}</div>`
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


        const newStart = info.event.start
            ? info.event.start.toISOString()
            : null;
        const newEnd = info.event.end ? info.event.end.toISOString() : null;

        // ✅ Optimistic update: aggiorna subito la lista locale
        const idx = appointmentsLocal.value.findIndex(
            (a) => String(a.id) === String(info.event.id)
        );
        const oldStart =
            idx !== -1 ? appointmentsLocal.value[idx].DataInizio : null;
        const oldEnd =
            idx !== -1 ? appointmentsLocal.value[idx].DataFine : null;

        if (idx !== -1) {
            appointmentsLocal.value[idx].DataInizio = newStart;
            appointmentsLocal.value[idx].DataFine = newEnd;
            refreshTick.value++;

        }

        // ✅ se trascini su un altro giorno, aggiorna anche il giorno selezionato
        giornoSelezionato.value = dateKeyLocal(info.event.start);

        // ✅ ricalcola subito i totali (giorno + mese)
        updateTotals();

        // (opzionale ma consigliato) ridisegna le celle giorno se hai badge “pezzi”
        const api = calendarRef.value?.getApi();
        api?.rerenderDates?.();

        // Salvataggio backend
        router.put(
            `/appointments/${info.event.id}/move`,
            { start: newStart, end: newEnd },
            {
                onError: () => {
                    alert("Errore nel salvataggio");

                    // rollback locale
                    if (idx !== -1) {
                        appointmentsLocal.value[idx].DataInizio = oldStart;
                        appointmentsLocal.value[idx].DataFine = oldEnd;
                        refreshTick.value++;

                    }

                    updateTotals();
                    api?.rerenderDates?.();
                },
            }
        );
    },

    // ✅ click su giorno: seleziona il giorno per il totale + poi apre create come prima
    dateClick: (info) => {
        giornoSelezionato.value = info.dateStr; // YYYY-MM-DD
        updateTotals();

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
    // ✅ IMPORTANTISSIMO: ridisegna le celle => aggiorna i "Pezzi" nei giorni
    calendarApi.rerenderDates();
    updateTotals();
});

// Realtime updates
onMounted(() => {
    // ✅ iniziale
    updateTotals();

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
            // ✅ aggiorna totali anche su realtime
            updateTotals();
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

                <select
                    v-model="SceltaProdotto"
                    class="border px-3 py-2 rounded"
                >
                    <option value="Tutti">Tutti</option>
                    <option value="PA">Persiane</option>
                    <option value="SC">Scuroni</option>
                    <option value="CA">Cover Alluminio</option>
                    <option value="IA">Infissi Alluminiio</option>
                </select>
                <div class="px-6 py-2 border-b bg-white text-sm font-semibold">
                    Stato produzione
                </div>
                <select
                    v-model="selectedStatus"
                    class="border px-3 py-2 rounded"
                >
                    <option value="tutti">Tutti</option>
                    <option value="scheduled">In programma</option>
                    <option value="active">Attivi</option>
                    <option value="completed">Completati</option>
                    <option value="cancelled">Cancellati</option>
                </select>
            </div>

            <!-- ✅ Totali pezzi -->
            <div class="px-6 py-2 border-b bg-white text-sm font-semibold">
                Totale pezzi mese: {{ pezziMese }}
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
