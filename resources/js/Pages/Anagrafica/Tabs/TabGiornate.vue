<script setup>
import SmartGrid from "@/Components/SmartGrid.vue";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    codCliente: { type: String, required: true },
    giornate:   { type: Array,  default: () => [] },
    savedLayout:{ type: Array,  default: null },
});

const queryName = computed(() => `giornate_${props.codCliente}`);

const LABELS = {
    _azioni:     "Azioni",
    DataGiornata:"Data",
    Ore:         "Ore",
    Progetto:    "Progetto",
    Note:        "Note",
    Cliente:     "Cliente",
    Fatturata:   "Fatturata",
};

const rows = computed(() =>
    props.giornate.map(r => ({ _azioni: r.IDGiornata ?? r.id, ...r }))
);

const formatDate = (v) => {
    if (!v) return "—";
    const d = new Date(v);
    if (isNaN(d)) return String(v);
    return `${String(d.getDate()).padStart(2,"0")}/${String(d.getMonth()+1).padStart(2,"0")}/${d.getFullYear()}`;
};
</script>

<template>
    <SmartGrid
        :query-name="queryName"
        :rows="rows"
        :saved-layout="savedLayout"
        :column-labels="LABELS"
        :show-header="true"
    >
        <template #cell-_azioni="{ value }">
            <div class="flex justify-center gap-1">
                <button class="act-btn act-edit"
                    @click.stop="router.visit(`/giornate/${value}/edit`)">✏️</button>
            </div>
        </template>
        <template #cell-DataGiornata="{ value }">
            <span class="tabular-nums text-xs font-semibold text-slate-700">{{ formatDate(value) }}</span>
        </template>
        <template #cell-Fatturata="{ value }">
            <span :class="value ? 'badge-ok' : 'badge-warn'" class="badge-pill">
                {{ value ? "Sì" : "No" }}
            </span>
        </template>
    </SmartGrid>
</template>

<style scoped>
.act-btn   { @apply px-2 py-1 rounded-lg border text-sm cursor-pointer transition hover:-translate-y-px; }
.act-edit  { @apply bg-amber-50 border-amber-200 text-amber-800; }
.badge-pill{ @apply inline-block px-2 py-0.5 rounded-full text-xs font-bold border; }
.badge-ok  { @apply bg-emerald-50 text-emerald-700 border-emerald-200; }
.badge-warn{ @apply bg-slate-100 text-slate-500 border-slate-200; }
</style>
