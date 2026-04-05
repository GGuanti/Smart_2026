<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, watch, onMounted, nextTick } from "vue";
import Chart from "chart.js/auto";

const props = defineProps({
    kpi: Object,
    ordiniPerTipo: Array,
    filters: Object,
    andamentoMensile: Array,
    andamentoMensileTipo: Array,
    isAdmin: Boolean,
});

const chartRef = ref(null);
let chartInstance = null;

// 🔥 FORMAT DATA
function formatDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
}

// 🔥 SETTIMANA CORRENTE
function getWeekRange() {
    const today = new Date();
    const day = today.getDay() || 7;

    const start = new Date(today);
    start.setDate(today.getDate() - day + 1);

    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    return {
        from: formatDate(start),
        to: formatDate(end),
    };
}

// 🔥 DEFAULT FILTRI
const week = getWeekRange();
const from = ref(props.filters?.from || week.from);
const to = ref(props.filters?.to || week.to);

// 🔥 FORMAT EURO
function euro(val) {
    return new Intl.NumberFormat("it-IT", {
        style: "currency",
        currency: "EUR",
    }).format(val || 0);
}

// 🔥 FILTRO
function filtra() {
    router.get(
        "/dashboard",
        {
            from: from.value,
            to: to.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

const chartTrend = ref(null);
let chartTrendInstance = null;
const coloriTipoDoc = {
    "Preventivo": "#94a3b8",      // grigio
    "Ordine": "#3b82f6",          // blu
    "Ordine inviato": "#f59e0b",  // arancione
    "Consegnato": "#10b981",      // verde
};
function renderTrend() {
    if (!chartTrend.value) return;

    if (chartTrendInstance) {
        chartTrendInstance.destroy();
    }

    const raw = props.andamentoMensileTipo || [];

    if (!raw.length) {
        console.warn("vuoto");
        return;
    }

    // 🔥 mesi unici
    const mesi = [...new Set(raw.map(r => r.mese))];

    // 🔥 tipi documento
    const tipi = [...new Set(raw.map(r => r.TipoDoc))];

    // 🔥 dataset per ogni tipo
    const datasets = tipi.map(tipo => {
    const color = coloriTipoDoc[tipo] || "#6366f1"; // fallback

    return {
        label: tipo,
        data: mesi.map(mese => {
            const row = raw.find(r => r.mese === mese && r.TipoDoc === tipo);
            return row ? Number(row.totale) : 0;
        }),
        borderColor: color,
        backgroundColor: color + "33", // trasparenza
        tension: 0.4,
        fill: false,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2,
    };
});

    chartTrendInstance = new Chart(chartTrend.value, {
        type: "line",
        data: {
            labels: mesi,
            datasets,
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (ctx) => euro(ctx.raw),
                    },
                },
            },
        },
    });
}
// 🔥 RENDER GRAFICO
function renderChart() {
    if (!chartRef.value) return;

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    const labels = (props.ordiniPerTipo || []).map((i) => i.TipoDoc || "N/D");
    const data = (props.ordiniPerTipo || []).map((i) => Number(i.totale || 0));

    if (!data.length) return;

    chartInstance = new Chart(chartRef.value, {
        type: "pie",
        data: {
            labels,
            datasets: [
                {
                    data,
                    backgroundColor: [
                        "#3b82f6",
                        "#10b981",
                        "#f59e0b",
                        "#ef4444",
                        "#8b5cf6",
                    ],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || "";
                            const value = context.raw || 0;

                            const total = context.dataset.data.reduce(
                                (a, b) => a + b,
                                0,
                            );
                            const perc = total
                                ? ((value / total) * 100).toFixed(1)
                                : 0;

                            return `${label}: ${euro(value)} (${perc}%)`;
                        },
                    },
                },
                legend: {
                    position: "bottom",
                },
            },
        },
    });
}

// 🔥 FIX DEFINITIVO

// 👉 1. Primo load
onMounted(async () => {
    await nextTick();
    renderChart();

    renderTrend();
});

// 👉 2. Dopo filtri / reload Inertia
watch(
    () => props.ordiniPerTipo,
    async () => {
        await nextTick();
        renderChart();

        renderTrend();
    },
);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="p-6 space-y-6">
            <!-- KPI -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="text-gray-500">Ordini</h3>
                    <p class="text-2xl font-bold">{{ kpi?.ordini ?? 0 }}</p>
                </div>

                <div v-if="isAdmin" class="bg-white p-4 rounded shadow">
                    <h3 class="text-gray-500">Fatturato Trasporto Escluso</h3>
                    <p class="text-2xl font-bold">
                        {{ euro(kpi?.fatturato) }}
                    </p>
                </div>

                <div class="bg-white p-4 rounded shadow">
                    <h3 class="text-gray-500">Utenti</h3>
                    <p class="text-2xl font-bold">{{ kpi?.utenti ?? 0 }}</p>
                </div>
            </div>

            <!-- FILTRI -->
            <div
                v-if="isAdmin"
                class="bg-white p-4 rounded shadow flex gap-4 items-end"
            >
                <div>
                    <label>Da</label>
                    <input
                        type="date"
                        v-model="from"
                        class="border p-2 rounded"
                    />
                </div>

                <div>
                    <label>A</label>
                    <input
                        type="date"
                        v-model="to"
                        class="border p-2 rounded"
                    />
                </div>

                <button
                    @click="filtra"
                    class="bg-blue-500 text-white px-4 py-2 rounded"
                >
                    Filtra
                </button>
            </div>
            <div v-if="isAdmin" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- 📈 ANDAMENTO MENSILE (PRIMO) -->
    <div class="bg-white p-6 rounded shadow border-l-4 border-blue-500">
        <h2 class="text-xl mb-4 flex items-center gap-2">
            📈 Andamento Mensile
        </h2>

        <div class="w-full h-[300px]">
            <canvas ref="chartTrend"></canvas>
        </div>
    </div>

    <!-- 📊 FATTURATO PER TIPO (DOPO) -->
    <div class="bg-white p-6 rounded shadow border-l-4 border-emerald-500">
        <h2 class="text-xl mb-4 flex items-center gap-2">
            📊 Fatturato per Tipologia Documento
        </h2>

        <div class="w-[300px] h-[300px] mx-auto">
            <canvas ref="chartRef"></canvas>
        </div>
    </div>

</div>
        </div>
    </AuthenticatedLayout>
</template>
