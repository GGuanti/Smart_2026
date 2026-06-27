<script setup>
import SmartGrid from "@/Components/SmartGrid.vue";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    codCliente:      { type: String, required: true },
    corsi:           { type: Array,  default: () => [] },
    corsiDisponibili:{ type: Array,  default: () => [] },
    savedLayout:     { type: Array,  default: null },
});

// Layout salvato per utente + persona
const queryName = computed(() => `sicurezza_${props.codCliente}`);

// Label colonne dalla query reale
const LABELS = {
    _azioni:         "Azioni",
    IdTabCorso:      "ID",
    TipoCorso:       "Corso",
    DataAttestato:   "Data Attestato",
    DurataAttestato: "Durata (gg)",
    Stato:           "Stato",
    Note:            "Note",
    UtenteMod:       "Modificato da",
    DataModifica:    "Data Modifica",
};

// Aggiunge _azioni come prima colonna (valore = IdTabCorso per navigazione)
const rows = computed(() =>
    props.corsi.map(r => ({ _azioni: r.IdTabCorso, ...r }))
);

// Calcola data scadenza: DataAttestato + DurataAttestato giorni
const scadenza = (dataAttestato, durata) => {
    if (!dataAttestato || !durata) return null;
    const d = new Date(dataAttestato);
    if (isNaN(d)) return null;
    d.setDate(d.getDate() + Number(durata));
    return d;
};

const isScaduto = (dataAttestato, durata) => {
    const s = scadenza(dataAttestato, durata);
    return s ? s < new Date() : false;
};

const formatDate = (v) => {
    if (!v) return "—";
    const d = new Date(v);
    if (isNaN(d)) return String(v);
    return `${String(d.getDate()).padStart(2,"0")}/${String(d.getMonth()+1).padStart(2,"0")}/${d.getFullYear()}`;
};

const formatScadenza = (dataAttestato, durata) => {
    const s = scadenza(dataAttestato, durata);
    return s ? formatDate(s) : "—";
};
</script>

<template>
    <SmartGrid
        :query-name="queryName"
        :rows="rows"
        :saved-layout="savedLayout"
        :column-labels="LABELS"
    >
        <!-- Azioni -->
        <template #cell-_azioni="{ value }">
            <div class="flex justify-center gap-1">
                <button
                    class="act-btn act-edit"
                    title="Modifica"
                    @click.stop="router.visit(`/corsi-formazione/${value}/edit`)"
                >✏️</button>
            </div>
        </template>

        <!-- Tipo corso — in grassetto -->
        <template #cell-TipoCorso="{ value }">
            <span class="font-semibold text-slate-800 text-xs">{{ value || "—" }}</span>
        </template>

        <!-- Data attestato -->
        <template #cell-DataAttestato="{ value }">
            <span class="tabular-nums text-xs font-medium text-slate-700">
                {{ formatDate(value) }}
            </span>
        </template>

        <!-- Durata + scadenza calcolata -->
        <template #cell-DurataAttestato="{ value, row }">
            <div class="flex flex-col leading-tight">
                <span class="text-xs text-slate-600">{{ value ? `${value} gg` : "—" }}</span>
                <span
                    class="text-[10px] font-semibold"
                    :class="isScaduto(row.DataAttestato, value)
                        ? 'text-red-600'
                        : 'text-emerald-700'"
                >
                    Scad: {{ formatScadenza(row.DataAttestato, value) }}
                    <span v-if="isScaduto(row.DataAttestato, value)"> ⚠️</span>
                </span>
            </div>
        </template>

        <!-- Stato badge -->
        <template #cell-Stato="{ value }">
            <span
                class="badge-pill"
                :class="{
                    'badge-ok':   value === 'Valido'    || value === 'Attivo',
                    'badge-warn': value === 'In scadenza',
                    'badge-err':  value === 'Scaduto'   || value === 'Non valido',
                    'badge-gray': !value,
                }"
            >{{ value || "—" }}</span>
        </template>

        <!-- Data modifica -->
        <template #cell-DataModifica="{ value }">
            <span class="tabular-nums text-xs text-slate-500">{{ formatDate(value) }}</span>
        </template>
    </SmartGrid>
</template>

<style scoped>
.act-btn  { @apply px-2 py-1 rounded-lg border text-sm cursor-pointer transition hover:-translate-y-px; }
.act-edit { @apply bg-amber-50 border-amber-200 text-amber-800; }

.badge-pill { @apply inline-block px-2 py-0.5 rounded-full text-xs font-bold border; }
.badge-ok   { @apply bg-emerald-50 text-emerald-700 border-emerald-200; }
.badge-warn { @apply bg-amber-50   text-amber-700   border-amber-200;   }
.badge-err  { @apply bg-red-50     text-red-700     border-red-200;     }
.badge-gray { @apply bg-slate-100  text-slate-500   border-slate-200;   }
</style>
