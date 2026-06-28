<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import Chart from "chart.js/auto";

const props = defineProps({
    settimane: { type: Array, default: () => [] },
});

/* -------------------- KPI riepilogo -------------------- */
const piccoMassimo = computed(() =>
    props.settimane.reduce((max, r) => Math.max(max, Number(r.picco || 0)), 0),
);
const mediaUtenti = computed(() => {
    if (!props.settimane.length) return 0;
    const tot = props.settimane.reduce((s, r) => s + Number(r.utenti || 0), 0);
    return Math.round(tot / props.settimane.length);
});
const ultimaSettimana = computed(
    () => props.settimane[props.settimane.length - 1] ?? null,
);

/* -------------------- Grafico utenti distinti -------------------- */
const chartUtenti = ref(null);
let chartUtentiInstance = null;

function renderUtenti() {
    if (!chartUtenti.value) return;
    if (chartUtentiInstance) chartUtentiInstance.destroy();

    const labels = props.settimane.map((r) => r.settimana);
    const data = props.settimane.map((r) => Number(r.utenti || 0));

    chartUtentiInstance = new Chart(chartUtenti.value, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    label: "Utenti distinti",
                    data,
                    backgroundColor: "#6366f1",
                    borderRadius: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
}

/* -------------------- Grafico picco concorrenza -------------------- */
const chartPicco = ref(null);
let chartPiccoInstance = null;

function renderPicco() {
    if (!chartPicco.value) return;
    if (chartPiccoInstance) chartPiccoInstance.destroy();

    const labels = props.settimane.map((r) => r.settimana);
    const data = props.settimane.map((r) => Number(r.picco || 0));

    chartPiccoInstance = new Chart(chartPicco.value, {
        type: "line",
        data: {
            labels,
            datasets: [
                {
                    label: "Picco concorrenza",
                    data,
                    borderColor: "#10b981",
                    backgroundColor: "#10b98133",
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
}

function renderAll() {
    renderUtenti();
    renderPicco();
}

onMounted(async () => {
    await nextTick();
    renderAll();
});

watch(() => props.settimane, async () => {
    await nextTick();
    renderAll();
});

onBeforeUnmount(() => {
    if (chartUtentiInstance) chartUtentiInstance.destroy();
    if (chartPiccoInstance) chartPiccoInstance.destroy();
});
</script>

<template>
    <Head title="Report presenze" />

    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Report presenze</h1>
                <p class="text-sm text-slate-500">
                    Andamento settimanale per dimensionare le risorse del server
                </p>
            </div>

            <!-- KPI riepilogo -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Picco massimo concorrenza</div>
                    <div class="text-3xl font-extrabold text-emerald-600">{{ piccoMassimo }}</div>
                    <div class="text-[11px] text-slate-400">utenti contemporanei (max sul periodo)</div>
                </div>
                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Media utenti / settimana</div>
                    <div class="text-3xl font-extrabold text-indigo-600">{{ mediaUtenti }}</div>
                    <div class="text-[11px] text-slate-400">persone distinte collegate</div>
                </div>
                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Ultima settimana</div>
                    <div class="text-3xl font-extrabold text-slate-900">
                        {{ ultimaSettimana ? ultimaSettimana.utenti : 0 }}
                    </div>
                    <div class="text-[11px] text-slate-400">
                        utenti · picco {{ ultimaSettimana ? ultimaSettimana.picco : 0 }}
                    </div>
                </div>
            </div>

            <!-- Grafici -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Utenti distinti -->
                <div class="rounded-2xl border bg-white p-6 shadow-sm border-l-4 border-indigo-500">
                    <h2 class="mb-1 text-lg font-semibold text-slate-900">👥 Utenti distinti per settimana</h2>
                    <p class="mb-4 text-xs text-slate-500">Quante persone diverse si sono collegate</p>
                    <div class="h-[300px] w-full">
                        <canvas ref="chartUtenti"></canvas>
                    </div>
                </div>

                <!-- Picco concorrenza -->
                <div class="rounded-2xl border bg-white p-6 shadow-sm border-l-4 border-emerald-500">
                    <h2 class="mb-1 text-lg font-semibold text-slate-900">📈 Picco di concorrenza per settimana</h2>
                    <p class="mb-4 text-xs text-slate-500">Massimo utenti contemporanei (slot da 5 min) — la metrica per dimensionare il server</p>
                    <div class="h-[300px] w-full">
                        <canvas ref="chartPicco"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabella dettaglio -->
            <div class="rounded-2xl border bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-base font-semibold text-slate-900">Dettaglio settimanale</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-4">Settimana</th>
                                <th class="py-2 pr-4 text-right">Utenti distinti</th>
                                <th class="py-2 text-right">Picco concorrenza</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in settimane" :key="r.settimana" class="border-b last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-4 font-medium text-slate-700">{{ r.settimana }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums text-indigo-700 font-semibold">{{ r.utenti }}</td>
                                <td class="py-2 text-right tabular-nums text-emerald-700 font-semibold">{{ r.picco }}</td>
                            </tr>
                            <tr v-if="!settimane.length">
                                <td colspan="3" class="py-6 text-center text-slate-400">Nessun dato ancora raccolto</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
