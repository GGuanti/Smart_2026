<script setup>
import SmartGrid from "@/Components/SmartGrid.vue";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    codCliente:   { type: String, required: true },
    contratti:    { type: Array,  default: () => [] },
    tipiContratto:{ type: Array,  default: () => [] },
    Professione:  { type: Array,  default: () => [] },
    nomeUtente:   { type: String, default: "" },
    CodFiscale:   { type: String, default: "" },
    savedLayout:  { type: Array,  default: null },
});

const queryName = computed(() => `contratti_${props.codCliente}`);

const LABELS = {
    _azioni:     "Azioni",
    TipoContratto:"Tipo",
    DataInizio:  "Inizio",
    DataFine:    "Fine",
    Professione: "Professione",
    Livello:     "Livello",
    Note:        "Note",
};

const rows = computed(() =>
    props.contratti.map(r => ({ _azioni: r.IDContratto ?? r.id, ...r }))
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
    >
        <template #cell-_azioni="{ value }">
            <div class="flex justify-center gap-1">
                <button class="act-btn act-edit"
                    @click.stop="router.visit(`/contratti/${value}/edit`)">✏️</button>
            </div>
        </template>
        <template #cell-DataInizio="{ value }">
            <span class="tabular-nums text-xs font-semibold text-slate-700">{{ formatDate(value) }}</span>
        </template>
        <template #cell-DataFine="{ value }">
            <span class="tabular-nums text-xs font-semibold" :class="value ? 'text-slate-700' : 'text-slate-400'">
                {{ value ? formatDate(value) : "In corso" }}
            </span>
        </template>
    </SmartGrid>
</template>

<style scoped>
.act-btn  { @apply px-2 py-1 rounded-lg border text-sm cursor-pointer transition hover:-translate-y-px; }
.act-edit { @apply bg-amber-50 border-amber-200 text-amber-800; }
</style>
