<script setup>
import SmartGrid from "@/Components/SmartGrid.vue";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    codCliente:  { type: String, required: true },
    noteSpese:   { type: Array,  default: () => [] },
    nomeUtente:  { type: String, default: "" },
    savedLayout: { type: Array,  default: null },
});

const queryName = computed(() => `notespese_${props.codCliente}`);

const LABELS = {
    _azioni:    "Azioni",
    Data:       "Data",
    Descrizione:"Descrizione",
    Importo:    "Importo",
    Categoria:  "Categoria",
    Stato:      "Stato",
};

const rows = computed(() =>
    props.noteSpese.map(r => ({ _azioni: r.IDNotaSpese ?? r.id, ...r }))
);

const formatDate = (v) => {
    if (!v) return "—";
    const d = new Date(v);
    if (isNaN(d)) return String(v);
    return `${String(d.getDate()).padStart(2,"0")}/${String(d.getMonth()+1).padStart(2,"0")}/${d.getFullYear()}`;
};

const formatEuro = (v) =>
    v != null ? new Intl.NumberFormat("it-IT", { style:"currency", currency:"EUR" }).format(v) : "—";
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
                    @click.stop="router.visit(`/note-spese/${value}/edit`)">✏️</button>
            </div>
        </template>
        <template #cell-Data="{ value }">
            <span class="tabular-nums text-xs font-semibold text-slate-700">{{ formatDate(value) }}</span>
        </template>
        <template #cell-Importo="{ value }">
            <span class="font-bold text-slate-800">{{ formatEuro(value) }}</span>
        </template>
        <template #cell-Stato="{ value }">
            <span class="badge-pill" :class="{
                'badge-ok':   value === 'Approvata',
                'badge-warn': value === 'In attesa',
                'badge-err':  value === 'Rifiutata',
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
