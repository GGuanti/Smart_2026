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
    totaleOrdini: { type: Number, default: 0 },
    totaleRegioniAttive: { type: Number, default: 0 },
    regioneTop: { type: String, default: "" },
    maxOrdini: { type: Number, default: 0 },
    filters: {
        type: Object,
        default: () => ({
            from: "",
            to: "",
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

// normalizza nomi per confronto robusto
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

// punti badge numerici leggibili
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

// focus automatico sulla zona con dati
const focus = computed(() => {
    if (!badgeData.value.length) {
        return {
            center: [12.5, 42.5],
            zoom: 1.1,
        };
    }

    const xs = badgeData.value.map((d) => d.value[0]);
    const ys = badgeData.value.map((d) => d.value[1]);

    const minX = Math.min(...xs);
    const maxX = Math.max(...xs);
    const minY = Math.min(...ys);
    const maxY = Math.max(...ys);

    const center = [(minX + maxX) / 2, (minY + maxY) / 2];

    const spanX = Math.max(0.8, maxX - minX);
    const spanY = Math.max(0.8, maxY - minY);
    const span = Math.max(spanX, spanY);

    let zoom = 1.8;
    if (span <= 2) zoom = 4.5;
    else if (span <= 3.5) zoom = 3.6;
    else if (span <= 5) zoom = 3.0;
    else if (span <= 7) zoom = 2.5;
    else if (span <= 9) zoom = 2.1;

    return { center, zoom };
});

const option = computed(() => ({
    title: {
        text: "Numero ordini per regione",
        left: "center",
        top: 10,
        textStyle: {
            fontSize: 20,
            fontWeight: "bold",
            color: "#111827",
        },
    },
    tooltip: {
        trigger: "item",
        formatter: (params) => {
            if (params.seriesType === "scatter") {
                return `
                    <div style="min-width:160px">
                        <div style="font-weight:700;font-size:14px;margin-bottom:4px;">
                            ${params.name}
                        </div>
                        <div><b>Ordini:</b> ${params.value?.[2] ?? 0}</div>
                    </div>
                `;
            }

            const value = params.value ?? 0;
            return `
                <div style="min-width:160px">
                    <div style="font-weight:700;font-size:14px;margin-bottom:4px;">
                        ${params.name}
                    </div>
                    <div><b>Ordini:</b> ${value}</div>
                </div>
            `;
        },
    },
    visualMap: {
        min: 0,
        max: props.maxOrdini || 10,
        left: 20,
        bottom: 20,
        text: ["Alto", "Basso"],
        calculable: true,
        textStyle: {
            color: "#374151",
        },
    },
    series: [
        {
            name: "Ordini",
            type: "map",
            map: "italy-regions",
            roam: true,
            center: [12.5, 42.5],
            zoom: 1.1,
            selectedMode: false,
            itemStyle: {
                borderColor: "#ffffff",
                borderWidth: 1.2,
                areaColor: "#e5e7eb",
            },
            emphasis: {
                itemStyle: {
                    areaColor: "#60a5fa",
                },
                label: {
                    show: false,
                },
            },
            label: {
                show: false,
            },
            data: mapData.value,
        },
        {
            name: "Badge ordini",
            type: "scatter",
            coordinateSystem: "geo",
            geoIndex: 0,
            data: badgeData.value,
            symbol: "circle",
            symbolSize: (val) => {
                const n = Number(val?.[2] ?? 0);
                if (n >= 100) return 36;
                if (n >= 10) return 32;
                return 28;
            },
            itemStyle: {
                color: "#ffffff",
                borderColor: "#1f2937",
                borderWidth: 1.5,
                shadowBlur: 8,
                shadowColor: "rgba(0,0,0,0.18)",
            },
            label: {
                show: true,
                formatter: ({ value }) => `${value?.[2] ?? ""}`,
                color: "#111827",
                fontSize: 12,
                fontWeight: "bold",
            },
            emphasis: {
                scale: true,
                itemStyle: {
                    borderColor: "#2563eb",
                    borderWidth: 2,
                },
            },
            zlevel: 3,
        },
    ],
    geo: {
        map: "italy-regions",
        roam: true,
        center: [12.5, 42.5],
        zoom: 1.1,
        silent: true,
    },
}));

function onMapClick(params) {
    if (!params?.name) return;

    router.get(route("ordini.index"), {
        regione: params.name,
    });
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">Totale ordini</div>
                        <div class="mt-2 text-4xl font-bold text-blue-600">
                            {{ totaleOrdini }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">Regioni attive</div>
                        <div class="mt-2 text-4xl font-bold text-green-600">
                            {{ totaleRegioniAttive }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">Regione top</div>
                        <div class="mt-2 text-3xl font-bold text-amber-600">
                            {{ regioneTop || "-" }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <div class="rounded-2xl bg-white p-5 shadow-sm xl:col-span-2">
                        <VChart
                            :option="option"
                            autoresize
                            style="height: 760px"
                            @click="onMapClick"
                        />
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="mb-4 text-2xl font-bold text-gray-800">
                            Ordini per Regione
                        </div>

                        <div class="overflow-hidden rounded-xl border border-gray-200">
                            <table class="min-w-full">
                                <thead class="bg-slate-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                            #
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                            Regione
                                        </th>
                                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                            Ordini
                                        </th>
                                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
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
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ row.posizione }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                            {{ row.regione }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                            {{ row.totale }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-600">
                                            {{ row.percentuale }}%
                                        </td>
                                    </tr>

                                    <tr v-if="!topTable.length">
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
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
