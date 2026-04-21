<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed } from "vue";
import VChart from "vue-echarts";
import { use } from "echarts/core";
import { CanvasRenderer } from "echarts/renderers";
import { MapChart, ScatterChart } from "echarts/charts";
import {
    TooltipComponent,
    VisualMapComponent,
    TitleComponent,
    LegendComponent,
} from "echarts/components";
import * as echarts from "echarts/core";
import italyRegionsRaw from "@/geo/limits_IT_regions.geojson?raw";

const italyRegions = JSON.parse(italyRegionsRaw);

use([
    CanvasRenderer,
    MapChart,
    ScatterChart,
    TooltipComponent,
    VisualMapComponent,
    TitleComponent,
    LegendComponent,
]);

const props = defineProps({
    ordiniPerRegione: { type: Array, default: () => [] },
    dettaglioPerRegioneTipoDoc: { type: Object, default: () => ({}) },
    tipiDoc: { type: Array, default: () => [] },
    totaleOrdini: { type: Number, default: 0 },
    totaleRegioniAttive: { type: Number, default: 0 },
    regioneTop: { type: String, default: "" },
    maxOrdini: { type: Number, default: 0 },
    filters: {
        type: Object,
        default: () => ({
            from: "",
            to: "",
            TipoDoc: "Tutti",
        }),
    },
});

echarts.registerMap("italy-regions", italyRegions);

const mapData = computed(() =>
    props.ordiniPerRegione.map((r) => ({
        name: r.regione,
        value: Number(r.totale),
    })),
);

const topTable = computed(() =>
    [...props.ordiniPerRegione]
        .sort((a, b) => Number(b.totale) - Number(a.totale))
        .map((r, index) => ({
            posizione: index + 1,
            regione: r.regione,
            totale: Number(r.totale),
            percentuale:
                props.totaleOrdini > 0
                    ? ((Number(r.totale) / props.totaleOrdini) * 100).toFixed(1)
                    : "0.0",
        })),
);

const normalizeName = (v) =>
    String(v ?? "")
        .trim()
        .toLowerCase()
        .replaceAll("-", " ")
        .replaceAll("'", " ")
        .replace(/\s+/g, " ");

function flattenCoords(coords, out = []) {
    if (!Array.isArray(coords)) return out;

    if (
        coords.length &&
        typeof coords[0] === "number" &&
        typeof coords[1] === "number"
    ) {
        out.push(coords);
        return out;
    }

    for (const item of coords) {
        flattenCoords(item, out);
    }

    return out;
}

function getFeatureName(feature) {
    const p = feature?.properties ?? {};
    return (
        p.name ||
        p.NAME_1 ||
        p.reg_name ||
        p.REGIONE ||
        p.denominazione_regione ||
        ""
    );
}

function getFeatureCenter(feature) {
    const geometry = feature?.geometry;
    if (!geometry?.coordinates) return null;

    const pts = flattenCoords(geometry.coordinates, []);
    if (!pts.length) return null;

    let minX = Infinity;
    let maxX = -Infinity;
    let minY = Infinity;
    let maxY = -Infinity;

    for (const [x, y] of pts) {
        if (x < minX) minX = x;
        if (x > maxX) maxX = x;
        if (y < minY) minY = y;
        if (y > maxY) maxY = y;
    }

    return [(minX + maxX) / 2, (minY + maxY) / 2];
}

const featureIndex = computed(() => {
    const map = new Map();

    for (const feature of italyRegions.features ?? []) {
        const name = getFeatureName(feature);
        if (!name) continue;
        map.set(normalizeName(name), feature);
    }

    return map;
});

const badgeData = computed(() =>
    props.ordiniPerRegione
        .map((r) => {
            const feature = featureIndex.value.get(normalizeName(r.regione));
            if (!feature) return null;

            const center = getFeatureCenter(feature);
            if (!center) return null;

            return {
                name: r.regione,
                value: [...center, Number(r.totale)],
            };
        })
        .filter(Boolean),
);

function getBreakdownHtml1(regione) {
    const dettaglio = props.dettaglioPerRegioneTipoDoc?.[regione] ?? [];
    if (!dettaglio.length) {
        return `<div style="margin-top:6px;color:#6b7280;">Nessun dettaglio TipoDoc</div>`;
    }

    const rows = dettaglio
        .map(
            (item) => `
                <div style="display:flex;justify-content:space-between;gap:16px;margin-top:4px;">
                    <span>${item.tipoDoc}</span>
                    <b>${item.totale}</b>
                </div>
            `,
        )
        .join("");

    return `
        <div style="margin-top:8px;padding-top:8px;border-top:1px solid #e5e7eb;">
            <div style="font-weight:700;margin-bottom:4px;">N. tot x TipoDoc</div>
            ${rows}
        </div>
    `;
}
function getBreakdownHtml(regione) {
    const dettaglio = props.dettaglioPerRegioneTipoDoc?.[regione] ?? [];

    if (!dettaglio.length) return "";

    return dettaglio
        .map(
            (d) => `
        <div style="display:flex;justify-content:space-between">
            <span>${d.tipoDoc}</span>
            <b>${d.totale}</b>
        </div>
    `,
        )
        .join("");
}
const option = computed(() => ({
    backgroundColor: "#ffffff",

    title: {
        text: "Distribuzione Ordini per Regione",
        left: "center",
        top: 10,
        textStyle: {
            fontSize: 18,
            fontWeight: "600",
            color: "#1f2937",
        },
    },

    tooltip: {
        trigger: "item",
        formatter: (params) => {
            const regione = params.name;
            const totale =
                params.seriesType === "scatter"
                    ? (params.value?.[2] ?? 0)
                    : (params.value ?? 0);

            return `
                <div style="min-width:220px">
                    <div style="font-weight:700;font-size:14px;margin-bottom:4px;">
                        ${regione}
                    </div>
                    <div><b>Totale ordini:</b> ${totale}</div>
                    ${getBreakdownHtml(regione)}
                </div>
            `;
        },
    },

    visualMap: {
        min: 0,
        max: props.maxOrdini || 10,
        left: 20,
        bottom: 20,
        text: ["Molti", "Pochi"],
        calculable: true,
        inRange: {
            color: [
                "#e0f2fe", // chiaro
                "#60a5fa",
                "#2563eb",
                "#1e3a8a", // scuro
            ],
        },
        textStyle: {
            color: "#374151",
        },
    },

    geo: {
        map: "italy-regions",
        roam: true,
        zoom: 1.15,
        center: [12.5, 42.5],

        itemStyle: {
            borderColor: "#ffffff",
            borderWidth: 1.2,
            areaColor: "#f3f4f6",
        },

        emphasis: {
            itemStyle: {
                areaColor: "#93c5fd",
            },
        },
    },

    series: [
        // 🔵 MAPPA BASE
        {
            name: "Ordini",
            type: "map",
            map: "italy-regions",
            geoIndex: 0,
            data: mapData.value,
        },

        // 🔥 BADGE NUMERICI
        {
            name: "Badge",
            type: "scatter",
            coordinateSystem: "geo",
            geoIndex: 0,
            data: badgeData.value,

            symbol: "circle",

            symbolSize: (val) => {
                const n = val[2];
                if (n > 100) return 40;
                if (n > 20) return 34;
                return 28;
            },

            itemStyle: {
                color: "#ffffff",
                borderColor: "#111827",
                borderWidth: 1.5,
                shadowBlur: 6,
                shadowColor: "rgba(0,0,0,0.2)",
            },

            label: {
                show: true,
                formatter: (p) => p.value[2],
                color: "#111827",
                fontWeight: "bold",
            },

            zlevel: 3,
        },
    ],
}));

function onMapClick(params) {
    if (!params?.name) return;

    router.get(route("ordini.index"), {
        regione: params.name,
        TipoDoc: props.filters?.TipoDoc ?? "Tutti",
        from: props.filters?.from,
        to: props.filters?.to,
    });
}
function applyFilters(partial = {}) {
    router.get(
        route("dashboard.ordini-regioni"),
        {
            from: partial.from ?? props.filters?.from ?? "",
            to: partial.to ?? props.filters?.to ?? "",
            TipoDoc: partial.TipoDoc ?? props.filters?.TipoDoc ?? "Tutti",
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}


</script>

<template>
    <Head title="Ordini per Regione" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Ordini per Regione
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <!-- FILTRI -->
        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                    Dal
                </label>
                <input
                    type="date"
                    :value="filters.from"
                    @change="applyFilters({ from: $event.target.value })"
                    class="rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm"
                />
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                    Al
                </label>
                <input
                    type="date"
                    :value="filters.to"
                    @change="applyFilters({ to: $event.target.value })"
                    class="rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm"
                />
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                    TipoDoc
                </label>
                <select
                    :value="filters.TipoDoc || 'Tutti'"
                    @change="applyFilters({ TipoDoc: $event.target.value })"
                    class="rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm"
                >
                    <option value="Tutti">Tutti</option>
                    <option
                        v-for="tipo in tipiDoc"
                        :key="tipo"
                        :value="tipo"
                    >
                        {{ tipo }}
                    </option>
                </select>
            </div>
        </div>

        <!-- KPI COMPATTI -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:min-w-[520px]">
            <div class="rounded-xl border border-gray-200 bg-slate-50 px-4 py-3">
                <div class="text-xs text-gray-500">Totale ordini</div>
                <div class="text-2xl font-bold text-blue-600">
                    {{ totaleOrdini }}
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-slate-50 px-4 py-3">
                <div class="text-xs text-gray-500">Regioni attive</div>
                <div class="text-2xl font-bold text-green-600">
                    {{ totaleRegioniAttive }}
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-slate-50 px-4 py-3">
                <div class="text-xs text-gray-500">Regione top</div>
                <div class="text-xl font-bold text-amber-600 truncate">
                    {{ regioneTop || "-" }}
                </div>
            </div>
        </div>
    </div>
</div>



                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <div
                        class="rounded-2xl bg-white p-5 shadow-sm xl:col-span-2"
                    >
                        <VChart
                            :option="option"
                            autoresize
                            style="height: 560px"
                            @click="onMapClick"
                        />
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="mb-4 text-2xl font-bold text-gray-800">
                            Ordini per Regione
                        </div>

                        <div
                            class="overflow-hidden rounded-xl border border-gray-200"
                        >
                            <table class="min-w-full">
                                <thead class="bg-slate-100">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-sm font-semibold text-gray-700"
                                        >
                                            #
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-sm font-semibold text-gray-700"
                                        >
                                            Regione
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-sm font-semibold text-gray-700"
                                        >
                                            Ordini
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-sm font-semibold text-gray-700"
                                        >
                                            %
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in topTable"
                                        :key="row.regione"
                                        class="border-t border-gray-200"
                                    >
                                        <td
                                            class="px-4 py-3 text-sm text-gray-500"
                                        >
                                            {{ row.posizione }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm font-medium text-gray-800"
                                        >
                                            {{ row.regione }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right text-sm font-bold text-gray-900"
                                        >
                                            {{ row.totale }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right text-sm text-gray-600"
                                        >
                                            {{ row.percentuale }}%
                                        </td>
                                    </tr>

                                    <tr v-if="!topTable.length">
                                        <td
                                            colspan="4"
                                            class="px-4 py-6 text-center text-sm text-gray-500"
                                        >
                                            Nessun dato disponibile
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
