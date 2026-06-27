<script setup>
import SmartGrid from "@/Components/SmartGrid.vue";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    codCliente:  { type: String, required: true },
    Visite:      { type: Array,  default: () => [] },
    savedLayout: { type: Array,  default: null },
});

const queryName = computed(() => `visite_${props.codCliente}`);

const LABELS = {
    _azioni:   "Azioni",
    DataVisita:"Data Visita",
    Scadenza:  "Scadenza",
    TipoVisita:"Tipo",
    Medico:    "Medico",
    Esito:     "Esito",
    Note:      "Note",
};

const rows = computed(() =>
    props.Visite.map(r => ({ _azioni: r.IDVisita ?? r.id, ...r }))
);

const formatDate = (v) => {
    if (!v) return "—";
    const d = new Date(v);
    if (isNaN(d)) return String(v);
    return `${String(d.getDate()).padStart(2,"0")}/${String(d.getMonth()+1).padStart(2,"0")}/${d.getFullYear()}`;
};

const isScaduto = (v) => v && new Date(v) < new Date();
</script>

<template>
    <SmartGrid
        :query-name="queryName"
        :rows="rows"
        :saved-layout="savedLayout"
        :column-labels="LABELS"
    >
        <template #cell-_azioni="{ value }">
            <div class="flex justify-center">
                <button class="act-btn act-edit"
                    @click.stop="router.visit(`/visite/${value}/edit`)">✏️</button>
            </div>
        </template>
        <template #cell-DataVisita="{ value }">
            <span class="tabular-nums text-xs font-semibold text-slate-700">{{ formatDate(value) }}</span>
        </template>
        <template #cell-Scadenza="{ value }">
            <span class="tabular-nums text-xs font-semibold flex items-center gap-1"
                :class="isScaduto(value) ? 'text-red-600' : 'text-emerald-700'">
                {{ formatDate(value) }}
                <span v-if="isScaduto(value)">⚠️</span>
            </span>
        </template>
        <template #cell-Esito="{ value }">
            <span class="badge-pill" :class="{
                'badge-ok':  value === 'Idoneo',
                'badge-warn':value === 'Idoneo con prescrizioni',
                'badge-err': value === 'Non idoneo',
            }">{{ value || "—" }}</span>
        </template>
    </SmartGrid>
</template>

<style scoped>
.act-btn   { @apply px-2 py-1 rounded-lg border text-sm cursor-pointer transition hover:-translate-y-px; }
.act-edit  { @apply bg-amber-50 border-amber-200 text-amber-800; }
.badge-pill{ @apply inline-block px-2 py-0.5 rounded-full text-xs font-bold border; }
.badge-ok  { @apply bg-emerald-50 text-emerald-700 border-emerald-200; }
.badge-warn{ @apply bg-amber-50 text-amber-700 border-amber-200; }
.badge-err { @apply bg-red-50 text-red-700 border-red-200; }
</style>
