<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator.min.css";
import { usePage } from "@inertiajs/vue3";



const toast = useToast();
const page = usePage();

const props = defineProps({
    codCliente: String,
    progetti: { type: Array, default: () => [] },
    progettiDisponibili: { type: Array, default: () => [] },
});

// ----------------------------------------------------
// ✅ KEY per tabella + profilo + cliente (Isomax/Nurith/...)
// ----------------------------------------------------
const profilo = computed(() => page.props?.auth?.user?.profilo ?? "default");
const gridName = "tab_progetti";

const TABLE_KEY = computed(() => {
    return `tabulator.${gridName}`;
});

// ----------------------------------------------------
// ✅ EXCLUDE (non salvare ID e colonna azioni)
// ----------------------------------------------------
const EXCLUDE_FIELDS = new Set(["IdProgetto", "__actions"]);

// ----------------------------------------------------
// Tabulator refs/state
// ----------------------------------------------------
const tableRef = ref(null);
let tableInstance = null;

const isReady = ref(false);
const search = ref("");
const onlyWithImporto = ref(false);
const totalCount = computed(() => props.progetti?.length ?? 0);

// ---------- helpers ----------
function formatDateIT(val) {
    if (!val) return "";
    const d = new Date(val);
    if (Number.isNaN(d.getTime())) return String(val);
    const dd = String(d.getDate()).padStart(2, "0");
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const yy = d.getFullYear();
    return `${dd}/${mm}/${yy}`;
}

function parseNumber(val) {
    if (val == null) return 0;
    const n = Number(String(val).replace(",", "."));
    return Number.isFinite(n) ? n : 0;
}

function moneyEUR(val) {
    const n = parseNumber(val);
    return n.toLocaleString("it-IT", { style: "currency", currency: "EUR" });
}

function escapeHtml(unsafe) {
    return String(unsafe)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

// ----------------------------------------------------
// ✅ Column menu (show/hide + save/reset)
// ----------------------------------------------------
function buildColumnsMenu() {
    if (!tableInstance) return [];

    const cols = tableInstance.getColumns().filter((c) => {
        const def = c.getDefinition();
        if (!def?.field) return false;
        if (EXCLUDE_FIELDS.has(def.field)) return false;
        if (def.title === "⚙️") return false;
        return true;
    });

    const menu = [
        { label: "💾 Salva layout", action: saveColumnsLayout },
        { label: "♻️ Ripristina layout", action: resetColumnsLayout },
        { separator: true },
    ];

    cols.forEach((col) => {
        const def = col.getDefinition();
        menu.push({
            label: `${col.isVisible() ? "✅" : "⬜"} ${def.title}`,
            action: () => {
                col.toggle(); // show/hide
                queueSaveLayout(); // autosave soft
            },
        });
    });

    return menu;
}

// ----------------------------------------------------
// ✅ Default columns (per ripristino layout)
// ----------------------------------------------------
const defaultColumns = [
    { title: "ID", field: "IdProgetto", visible: false },

    {
        title: "🧾 Committente",
        field: "RagioneSocialeCommittenti",
        editable: false, // 🔒 non editabile
        minWidth: 260,
        headerMenu: () => buildColumnsMenu(),
        formatter: (cell) => {
            const data = cell.getRow().getData();

            const cod = String(data.CodCommittente ?? "").trim();
            const rag = String(data.RagioneSocialeCommittenti ?? "").trim();

            if (!cod && !rag) {
                return `<span style="color:#94a3b8;">—</span>`;
            }

            return `
            <div style="line-height:1.2">
                <div style="font-weight:600;color:#334155;">
                    ${escapeHtml(rag)}
                </div>
                <div style="font-size:12px;color:#64748b;">
                    ${escapeHtml(cod)}
                </div>
            </div>
        `;
        },
    },

    {
        title: "📅 Data Contratto",
        field: "DataContratto",
        editor: "input",
        width: 120,
        hozAlign: "center",
        formatter: (cell) => {
            const s = formatDateIT(cell.getValue());
            return s ? `<span style="font-weight:700;">${s}</span>` : "—";
        },
    },
    {
        title: "📅 Data inizio",
        field: "DataInzProgetto",
        editor: "input",
        width: 120,
        hozAlign: "center",
        formatter: (cell) => {
            const s = formatDateIT(cell.getValue());
            return s ? `<span style="font-weight:700;">${s}</span>` : "—";
        },
    },
    {
        title: "📅 Data Fine",
        field: "DataFineProgetto",
        editor: "input",
        width: 120,
        hozAlign: "center",
        formatter: (cell) => {
            const s = formatDateIT(cell.getValue());
            return s ? `<span style="font-weight:700;">${s}</span>` : "—";
        },
    },
    {
        title: "📅 Data Pagamento",
        field: "DataPagamento",
        editor: "input",
        width: 120,
        hozAlign: "center",
        formatter: (cell) => {
            const s = formatDateIT(cell.getValue());
            return s ? `<span style="font-weight:700;">${s}</span>` : "—";
        },
    },


{
        title: "🧾 Des. Progetto + IdProgetto",
        field: "DescrizioneProgetto",
        editable: false, // 🔒 non editabile
        minWidth: 260,
        headerMenu: () => buildColumnsMenu(),
        formatter: (cell) => {
            const data = cell.getRow().getData();

            const ipr = String(data.IdProgetto ?? "").trim();
            const dpr = String(data.DescrizioneProgetto ?? "").trim();

            if (!ipr && !dpr) {
                return `<span style="color:#94a3b8;">—</span>`;
            }

            return `
            <div style="line-height:1.2">
                <div style="font-weight:600;color:#334155;">
                    ${escapeHtml(dpr)}
                </div>
                <div style="font-size:12px;color:#64748b;">
                    ${escapeHtml(ipr)}
                </div>
            </div>
        `;
        },
    },
    {
        title: "🧾 Stato Progetto",
        field: "StatoProgetto",
        editor: "input",
        minWidth: 140,
        formatter: (cell) => {
            const v = String(cell.getValue() ?? "").trim();
            return v
                ? `<span style="color:#334155;">${escapeHtml(v)}</span>`
                : `<span style="color:#94a3b8;">—</span>`;
        },
    },

    {
        title: "🤝 Accordi",
        field: "Accordi",
        editor: "input",
        minWidth: 120,
        formatter: (cell) => {
            const v = String(cell.getValue() ?? "").trim();
            return v
                ? `<span style="color:#334155;">${escapeHtml(v)}</span>`
                : `<span style="color:#94a3b8;">—</span>`;
        },
    },

    {
        title: "💶 Importo",
        field: "ImportoNettoConcordato",
        hozAlign: "right",
        editor: "input",
        width: 160,
        formatter: (cell) => {
            const n = parseNumber(cell.getValue());
            const label = moneyEUR(n);
            return n > 0
                ? `<span style="display:inline-flex;align-items:center;gap:6px;
              padding:3px 10px;border-radius:999px;font-weight:800;
              border:1px solid #bbf7d0;background:#ecfdf5;color:#166534;">
            ${label}
          </span>`
                : `<span style="color:#94a3b8;">—</span>`;
        },
    },

    // ✅ Azioni (finto field, MA escluso dai salvataggi)
    {
        title: "⚙️",
        field: "__actions",
        headerSort: false,
        width: 110,
        hozAlign: "center",
        headerMenu: () => [
            { label: "💾 Salva layout", action: saveColumnsLayout },
            { label: "♻️ Ripristina layout", action: resetColumnsLayout },
        ],
        formatter: () => `
      <div class="actWrap">
        <button class="actBtn actDanger" data-act="del" title="Elimina">🗑️</button>
      </div>
    `,
        cellClick: (e, cell) => {
            const act = e.target?.getAttribute?.("data-act");
            const row = cell.getRow().getData();

            if (act === "del") {
                if (!row?.IdProgetto) return;
                if (
                    confirm(
                        `Eliminare il progetto "${row.DescrizioneProgetto}"?`
                    )
                ) {
                    deleteProgetto(row.IdProgetto);
                }
            }
        },
    },
];

// ----------------------------------------------------
// ✅ Layout (salva: field, width, visible, order)
// FIX: niente move() => niente "No matching column found: false"
// ----------------------------------------------------
function getColumnsLayout() {
    if (!tableInstance) return [];

    return tableInstance
        .getColumns()
        .map((col, index) => {
            const def = col.getDefinition();
            const field = def?.field;

            if (!field || field === false) return null; // ✅ evita field:false
            if (EXCLUDE_FIELDS.has(field)) return null; // ✅ ID + actions
            if (def?.title === "⚙️") return null; // extra safety

            return {
                field,
                order: index,
                width: col.getWidth?.() ?? def.width ?? null,
                visible: col.isVisible?.() ?? def.visible !== false,
            };
        })
        .filter(Boolean);
}

function applyColumnsLayout(layout) {
    if (!tableInstance || !Array.isArray(layout) || !layout.length) return;

    // filtro: solo colonne che esistono adesso
    const currentFields = new Set(
        tableInstance
            .getColumns()
            .map((c) => c.getDefinition()?.field)
            .filter((f) => f && f !== false && !EXCLUDE_FIELDS.has(f))
    );

    const sanitized = layout
        .filter((x) => x && x.field && x.field !== false)
        .filter((x) => currentFields.has(x.field))
        .sort((a, b) => (a.order ?? 9999) - (b.order ?? 9999));

    // ✅ Applica con setColumnLayout (gestisce ordine senza move error)
    const layoutForTabulator = sanitized.map((x) => ({
        field: x.field,
        visible: x.visible !== false,
        width: x.width ?? undefined,
    }));

    try {
        tableInstance.setColumnLayout(layoutForTabulator);
    } catch (e) {
        console.error("❌ setColumnLayout failed", e, layoutForTabulator);
    }
}

// ----------------------------------------------------
// ✅ API: load/save/reset
// ----------------------------------------------------
async function loadColumnsLayout() {
    const key = TABLE_KEY.value;
    console.log("🔑 loadColumnsLayout key =", key);

    try {
        const res = await axios.get("/preferences", {
            params: { key, type: "columns" },
        });

        const layout = res.data?.data;
        console.log("📦 /preferences layout =", layout);

        if (Array.isArray(layout) && layout.length) {
            applyColumnsLayout(layout);
            console.log("✅ Layout applicato");
        } else {
            console.log("ℹ️ Nessun layout salvato");
        }
    } catch (e) {
        console.error(
            "❌ Errore loadColumnsLayout:",
            e?.response?.status,
            e?.response?.data || e
        );
    }
}

async function saveColumnsLayout() {
    if (!tableInstance) return;

    const layout = getColumnsLayout();

    await axios.post("/preferences", {
        key: TABLE_KEY.value,
        type: "columns",
        data: layout,
    });

    toast.success("💾 Layout colonne salvato");
}

async function resetColumnsLayout() {
    try {
        await axios.delete("/preferences", {
            data: { key: TABLE_KEY.value },
        });

        // ripristino colonne default senza reload
        tableInstance.setColumns(defaultColumns);
        tableInstance.redraw(true);

        toast.info("♻️ Layout ripristinato");
    } catch (e) {
        console.error(e);
        toast.error("❌ Errore ripristino layout");
    }
}

// ----------------------------------------------------
// ✅ Autosave (debounced)
// ----------------------------------------------------
let saveTimer = null;
function queueSaveLayout() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
        saveColumnsLayout().catch(() => {});
    }, 600);
}

function wireAutoSaveLayout() {
 //   if (!tableInstance) return;

    // evita doppio bind se richiami wireAutoSaveLayout
 //   tableInstance.off?.("columnMoved");
  //  tableInstance.off?.("columnResized");
  //  tableInstance.off?.("columnVisibilityChanged");

//    tableInstance.on("columnMoved", queueSaveLayout);
 //   tableInstance.on("columnResized", queueSaveLayout);
  //  tableInstance.on("columnVisibilityChanged", queueSaveLayout);
}

// ----------------------------------------------------
// Lifecycle
// ----------------------------------------------------
onMounted(async () => {
    await nextTick();

    // init tabulator una sola volta
    if (props.progetti?.length) {
        initTabulator(props.progetti);
    } else {
        watch(
            () => props.progetti,
            async (val) => {
                if (val?.length && !tableInstance) {
                    await nextTick();
                    initTabulator(val);
                }
            },
            { immediate: true }
        );
    }
});

watch(
    () => props.progetti,
    (val) => {
        if (!tableInstance) return;
        tableInstance.replaceData(val ?? []);
    },
    { deep: true }
);

// ----------------------------------------------------
// Tabulator init
// ----------------------------------------------------
function initTabulator(data) {
    if (!tableRef.value) return;
    if (tableInstance) return;

    isReady.value = false; // reset quando inizializzi

    tableInstance = new Tabulator(tableRef.value, {
        height: "520px",
        data,
        layout: "fitColumns",
        responsiveLayout: "collapse",
        resizableColumns: true,
        resizableColumnFit: true,
        movableColumns: true,
        pagination: "local",
        paginationSize: 20,
        paginationSizeSelector: [10, 20, 50],
        placeholder:
            "Nessun progetto trovato. Aggiungi un progetto o cambia i filtri.",
        rowHeight: 44,
        columns: defaultColumns,

        cellEdited: async (cell) => {
            const updated = cell.getRow().getData();
            try {
                await axios.put(`/progetti/${updated.IdProgetto}`, updated);
                toast.success("✅ Progetto aggiornato");
            } catch (err) {
                console.error(err);
                toast.error("❌ Errore aggiornamento");
                const original = (props.progetti ?? []).find(
                    (p) => Number(p.IdProgetto) === Number(updated.IdProgetto)
                );
                if (original) cell.getRow().update(original);
            }
        },
    });

    // ✅ evento ufficiale: scatta quando la tabella è davvero pronta
    tableInstance.on("tableBuilt", async () => {
        try {
            await loadColumnsLayout();
            wireAutoSaveLayout();
        } catch (e) {
            console.error("❌ tableBuilt handler error:", e);
        } finally {
            isReady.value = true; // ✅ NON resta mai su "Caricamento"
        }
    });
}
// ----------------------------------------------------
// Filtri UI
// ----------------------------------------------------
function applySearch() {
    if (!tableInstance) return;
    const q = String(search.value ?? "").trim();

    if (!q) {
        tableInstance.clearFilter(true);
        if (onlyWithImporto.value) applyOnlyWithImporto();
        return;
    }

    tableInstance.setFilter([
        [
            { field: "RagioneSocialeCommittenti", type: "like", value: q },
            { field: "DescrizioneProgetto", type: "like", value: q },
            { field: "Accordi", type: "like", value: q },
        ],
    ]);

    if (onlyWithImporto.value) applyOnlyWithImporto();
}

function applyOnlyWithImporto() {
    if (!tableInstance) return;
    tableInstance.addFilter(
        "ImportoNettoConcordato",
        "function",
        (val) => parseNumber(val) > 0
    );
}

function toggleOnlyWithImporto() {
    onlyWithImporto.value = !onlyWithImporto.value;
    applySearch();
}

function resetFilters() {
    search.value = "";
    onlyWithImporto.value = false;
    if (!tableInstance) return;
    tableInstance.clearFilter(true);
}

// ----------------------------------------------------
// Delete
// ----------------------------------------------------
const deleteProgetto = async (id) => {
    try {
        await axios.delete(`/progetti/${id}`);
        toast.success("🗑️ Progetto eliminato");

        const row = tableInstance?.getRow(id);
        if (row) row.delete();
        else tableInstance?.deleteRow(id);
    } catch (err) {
        console.error(err);
        toast.error("❌ Errore durante l'eliminazione");
    }
};
</script>

<template>
    <div class="wrap">
        <!-- Header -->
        <div class="headCard">
            <div class="left">
                <div class="titleRow">
                    <div class="pillIcon">📋</div>
                    <div>
                        <h2 class="title">Progetti</h2>
                        <p class="sub">
                            Gestione progetti collegati al cliente
                            <span v-if="props.codCliente" class="subCode"
                                >#{{ props.codCliente }}</span
                            >
                        </p>
                    </div>
                </div>

                <div class="kpis">
                    <div class="kpi">
                        <div class="kpiLabel">Totale</div>
                        <div class="kpiValue">{{ totalCount }}</div>
                    </div>
                    <div class="kpi kpiBlue">
                        <div class="kpiLabel">Stato</div>
                        <div class="kpiValue">
                            <span v-if="isReady">✅ Pronto</span>
                            <span v-else>⏳ Caricamento</span>
                        </div>
                    </div>
                </div>

                <div class="searchWrap">
                    <span class="searchIcon">🔎</span>
                    <input
                        v-model="search"
                        class="searchInput"
                        placeholder="Cerca descrizione o accordi..."
                        @input="applySearch"
                    />
                </div>

                <button
                    type="button"
                    class="pillBtn"
                    :class="{ pillOn: onlyWithImporto }"
                    @click="toggleOnlyWithImporto"
                >
                    💶 Solo con importo
                </button>
                <button
                    type="button"
                    class="pillBtn"
                    @click="loadColumnsLayout"
                >
                    📥 Carica layout
                </button>
                <button
                    type="button"
                    class="pillBtn"
                    @click="saveColumnsLayout"
                >
                    💾 Salva colonne
                </button>

                <button
                    type="button"
                    class="pillBtn pillGhost"
                    @click="resetColumnsLayout"
                >
                    ♻️ Ripristina layout
                </button>

                <button
                    type="button"
                    class="pillBtn pillGhost"
                    @click="resetFilters"
                >
                    ♻️ Reset filtri
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="tableCard">
            <div ref="tableRef" class="tabWrap"></div>
        </div>
    </div>
</template>

<style scoped>
/* (stili identici ai tuoi, non li cambio) */
.wrap {
    width: 100%;
}

.headCard {
    display: flex;
    gap: 14px;
    justify-content: space-between;
    align-items: stretch;
    flex-wrap: wrap;
    padding: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    margin-bottom: 12px;
}

.left {
    display: flex;
    gap: 14px;
    align-items: center;
    flex-wrap: wrap;
}

.titleRow {
    display: flex;
    gap: 10px;
    align-items: center;
}

.pillIcon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: #eef2ff;
    border: 1px solid #e0e7ff;
    font-size: 18px;
}

.title {
    margin: 0;
    font-weight: 900;
    letter-spacing: -0.02em;
    color: #0f172a;
    font-size: 18px;
}

.sub {
    margin: 2px 0 0;
    font-size: 12px;
    color: #64748b;
}

.subCode {
    display: inline-flex;
    padding: 2px 8px;
    border-radius: 999px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    color: #1d4ed8;
    font-weight: 800;
    margin-left: 8px;
}

.kpis {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.kpi {
    min-width: 110px;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
}

.kpiBlue {
    background: #eff6ff;
    border-color: #dbeafe;
}

.kpiLabel {
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.kpiValue {
    font-size: 16px;
    font-weight: 900;
    color: #0f172a;
    margin-top: 2px;
}

.right {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.searchWrap {
    position: relative;
    min-width: 260px;
}

.searchIcon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.7;
}

.searchInput {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    outline: none;
    font-weight: 700;
    color: #0f172a;
}

.searchInput:focus {
    border-color: #93c5fd;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.pillBtn {
    padding: 10px 12px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    font-weight: 900;
    font-size: 12px;
    color: #0f172a;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    transition: 0.15s;
}
.pillBtn:hover {
    transform: translateY(-1px);
}

.pillOn {
    background: #ecfdf5;
    border-color: #bbf7d0;
    color: #166534;
}

.pillGhost {
    background: #f8fafc;
}

.tableCard {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.tabWrap :deep(.tabulator) {
    border: 0;
    font-size: 0.9rem;
}
.tabWrap :deep(.tabulator-header) {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.tabWrap :deep(.tabulator-col-title) {
    font-weight: 900;
    color: #334155;
}
.tabWrap :deep(.tabulator-row) {
    border-bottom: 1px solid #f1f5f9;
}
.tabWrap :deep(.tabulator-row:hover) {
    background: #f8fafc;
}

/* Azioni */
.tabWrap :deep(.actWrap) {
    display: flex;
    justify-content: center;
}
.tabWrap :deep(.actBtn) {
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 12px;
    padding: 6px 10px;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    transition: 0.15s;
}
.tabWrap :deep(.actBtn:hover) {
    transform: translateY(-1px);
}
.tabWrap :deep(.actDanger) {
    border-color: #fecaca;
    background: #fff1f2;
}
.tableCard {
    overflow: visible; /* oppure rimuovi proprio la riga */
}
.tabWrap :deep(.tabulator-header) {
    overflow: visible;
}
.tabWrap :deep(.tabulator-header .tabulator-col) {
    overflow: visible;
}
.tabWrap :deep(.tabulator-col-resize-handle) {
    right: 0; /* evita che vada “fuori” e venga clippata */
}
</style>
