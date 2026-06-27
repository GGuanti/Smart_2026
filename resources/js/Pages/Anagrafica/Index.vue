<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import SmartGrid from "@/Components/SmartGrid.vue";
import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

// ─── PROPS ───────────────────────────────────────────────────
const props = defineProps({
    records:     { type: Array,  default: () => [] },
    nomeTabella: { type: String, required: true    },
    tipoU:       { type: String, default: ""       },
    savedLayout: { type: Array,  default: null     }, // da GridLayout::forUser() nel controller
});

// ─── COSTANTI ────────────────────────────────────────────────
const COLUMN_LABELS = {
    _azioni:               "Azioni",
    IDAnagrafica:          "ID",
    CodCliente:            "Codice",
    B_TipoU:               "Tipo",
    A_NomeVisualizzato:    "Nome",
    AI_PartitaIVA:         "Partita IVA",
    AH_CodiceFiscalePG:    "CF (PG)",
    AG_CodiceFiscalePF:    "CF (PF)",
    AE_IndirizzoEmail:     "Email",
    AD_Cellulare:          "Cellulare",
    AM_Professione1:       "Professione",
    AN_Professione2:       "Professione 2",
    AN_Professione3:       "Professione 3",
    R_ComuneResidenza:     "Comune",
    AR_DataDomanda:        "Data Domanda",
    AS_DataApprovazioneCDA:"Data CDA",
    AT_DataVersamento:     "Data Vers.",
    AU_DataRatifica:       "Data Ratifica",
    Stato:                 "Stato",
    VisitaMedica:          "Visita Medica",
};

const DATE_FIELDS = [
    "AR_DataDomanda",
    "AS_DataApprovazioneCDA",
    "AT_DataVersamento",
    "AU_DataRatifica",
];

// ─── HELPERS ─────────────────────────────────────────────────
const formatDate = (value) => {
    if (!value) return "—";
    const d = new Date(value);
    if (isNaN(d)) return String(value);
    return `${String(d.getDate()).padStart(2,"0")}/${String(d.getMonth()+1).padStart(2,"0")}/${d.getFullYear()}`;
};

// ─── DATI ────────────────────────────────────────────────────
// Aggiunge _azioni come primo campo di ogni riga (valore = ID per la navigazione)
const rowsEnhanced = computed(() =>
    props.records.map(r => ({ _azioni: r.IDAnagrafica, ...r }))
);

const gridKey   = computed(() => `anagrafica_${props.tipoU}`);
const gridRef   = ref(null);
</script>

<template>
    <AuthenticatedLayout>
        <div class="an-wrap">

            <!-- ── HERO ──────────────────────────────────── -->
            <div class="an-hero">
                <div class="an-hero-left">
                    <div class="an-icon">👤</div>
                    <div>
                        <div class="an-title">
                            Anagrafica
                            <span class="an-pill">{{ tipoU }}</span>
                        </div>
                        <div class="an-sub">
                            {{ records.length }} record · colonne personalizzabili · layout salvato per utente
                        </div>
                    </div>
                </div>

                <button
                    class="an-btn-new"
                    @click="router.visit(`/${nomeTabella}/create`)"
                >
                    ➕ Nuovo
                </button>
            </div>

            <!-- ── SMARTGRID ──────────────────────────────── -->
            <div class="an-grid-wrap">
                <SmartGrid
                    ref="gridRef"
                    :query-name="gridKey"
                    :rows="rowsEnhanced"
                    :saved-layout="savedLayout"
                    :column-labels="COLUMN_LABELS"
                >
                    <!-- ── Colonna Azioni ─────────────────── -->
                    <template #cell-_azioni="{ value }">
                        <div class="cell-actions">
                            <button
                                class="cell-btn cell-edit"
                                title="Modifica"
                                @click.stop="router.visit(`/${nomeTabella}/${value}/edit`)"
                            >
                                ✏️ Modifica
                            </button>
                        </div>
                    </template>

                    <!-- ── Date formattate ────────────────── -->
                    <template #cell-AR_DataDomanda="{ value }">
                        <span class="cell-date">{{ formatDate(value) }}</span>
                    </template>
                    <template #cell-AS_DataApprovazioneCDA="{ value }">
                        <span class="cell-date">{{ formatDate(value) }}</span>
                    </template>
                    <template #cell-AT_DataVersamento="{ value }">
                        <span class="cell-date">{{ formatDate(value) }}</span>
                    </template>
                    <template #cell-AU_DataRatifica="{ value }">
                        <span class="cell-date">{{ formatDate(value) }}</span>
                    </template>

                    <!-- ── Stato con badge colore ─────────── -->
                    <template #cell-Stato="{ value }">
                        <span
                            class="cell-badge"
                            :class="{
                                'badge-green':  value === 'Attivo',
                                'badge-red':    value === 'Cessato',
                                'badge-yellow': value === 'Sospeso',
                                'badge-gray':   !['Attivo','Cessato','Sospeso'].includes(value),
                            }"
                        >{{ value || '—' }}</span>
                    </template>

                    <!-- ── Email cliccabile ───────────────── -->
                    <template #cell-AE_IndirizzoEmail="{ value }">
                        <a
                            v-if="value"
                            :href="`mailto:${value}`"
                            class="cell-link"
                            @click.stop
                        >{{ value }}</a>
                        <span v-else class="cell-empty">—</span>
                    </template>

                    <!-- ── ID compatto ────────────────────── -->
                    <template #cell-IDAnagrafica="{ value }">
                        <span class="cell-id">#{{ value }}</span>
                    </template>
                </SmartGrid>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ─── LAYOUT ────────────────────────────────────────────────── */
.an-wrap {
    height:        calc(100vh - 64px);
    padding:       16px;
    background:    #f1f5f9;
    display:       flex;
    flex-direction:column;
    gap:           12px;
    overflow:      hidden;
}

/* ─── HERO ──────────────────────────────────────────────────── */
.an-hero {
    display:         flex;
    align-items:     center;
    justify-content: space-between;
    flex-wrap:       wrap;
    gap:             12px;
    padding:         14px 20px;
    border-radius:   20px;
    background:      linear-gradient(90deg, #3f3fd6, #1f60ff);
    box-shadow:      0 12px 32px rgba(31,96,255,0.28);
    flex-shrink:     0;
}
.an-hero-left { display:flex; align-items:center; gap:14px; }

.an-icon {
    width:48px; height:48px; border-radius:16px; font-size:22px;
    display:grid; place-items:center;
    background:rgba(255,255,255,0.15);
    border:1px solid rgba(255,255,255,0.25);
}
.an-title {
    font-size:18px; font-weight:900; color:#fff;
    letter-spacing:-0.02em;
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
}
.an-sub { font-size:12px; color:rgba(255,255,255,0.68); margin-top:2px; }

.an-pill {
    padding:3px 12px; border-radius:999px; font-size:12px; font-weight:700;
    background:rgba(255,255,255,0.18);
    border:1px solid rgba(255,255,255,0.30);
    color:#fff;
}

.an-btn-new {
    padding:10px 20px; border-radius:999px; font-size:13px; font-weight:700;
    background:rgba(255,255,255,0.92); color:#1d4ed8;
    border:1px solid rgba(255,255,255,0.40);
    cursor:pointer; transition:.15s;
    box-shadow:0 4px 14px rgba(0,0,0,0.15);
}
.an-btn-new:hover { background:#fff; transform:translateY(-1px); }

/* ─── GRID WRAPPER ──────────────────────────────────────────── */
.an-grid-wrap {
    flex:      1;
    min-height:0;         /* ← fondamentale per flex fill */
    border-radius:18px;
    overflow:  hidden;
    box-shadow:0 8px 24px rgba(15,23,42,0.08);
}

/* SmartGrid occuperà tutto lo spazio disponibile */
.an-grid-wrap :deep(.sg-wrap) {
    height:100%;
    border-radius:18px;
}
.an-grid-wrap :deep(.sg-table-wrap) {
    max-height: calc(100% - 90px); /* header + footer SmartGrid */
}

/* ─── SLOT CELLS ────────────────────────────────────────────── */
.cell-actions { display:flex; justify-content:center; }

.cell-btn {
    padding:4px 12px; border-radius:8px; font-size:12px; font-weight:600;
    cursor:pointer; border:1px solid; transition:.12s;
}
.cell-btn:hover { transform:translateY(-1px); }

.cell-edit {
    background:#fffbeb; border-color:#fed7aa; color:#92400e;
}

.cell-date {
    font-weight:600; font-size:12px; color:#374151;
    font-variant-numeric:tabular-nums;
}

.cell-id {
    font-family:monospace; font-size:11px; font-weight:700;
    color:#6366f1; letter-spacing:.02em;
}

.cell-link {
    color:#2563eb; text-decoration:none; font-size:12px;
}
.cell-link:hover { text-decoration:underline; }

.cell-empty { color:#9ca3af; }

/* Badge stato */
.cell-badge {
    display:inline-block; padding:2px 10px; border-radius:999px;
    font-size:11px; font-weight:700;
}
.badge-green  { background:#dcfce7; color:#15803d; }
.badge-red    { background:#fee2e2; color:#b91c1c; }
.badge-yellow { background:#fef9c3; color:#a16207; }
.badge-gray   { background:#f1f5f9; color:#64748b; }
</style>
