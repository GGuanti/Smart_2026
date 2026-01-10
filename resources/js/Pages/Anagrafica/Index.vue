<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { onMounted, onUnmounted, ref, nextTick, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import axios from "axios";

const props = defineProps({
    records: Array,
    columns: Array,
    nomeTabella: String,
    tipoU: String,
});

const tableRef = ref(null);
let tableInstance = null;

const gridKey = `anagrafica_${props.tipoU}`;

// UI
const search = ref("");
const pageSize = ref(20);

const totalCount = computed(() => props.records?.length ?? 0);

// ✅ Carica preferenze colonne salvate
const loadUserColumns = async () => {
    try {
        const { data } = await axios.get("/preferences", {
            params: { key: gridKey },
        });

        return data.length
            ? data
            : props.columns.map((col) => ({
                  title: col.replace(/_/g, " "),
                  field: col,
                  headerFilter: true,
              }));
    } catch (err) {
        console.error("❌ Errore caricamento colonne:", err);
        return props.columns.map((col) => ({
            title: col.replace(/_/g, " "),
            field: col,
            headerFilter: true,
        }));
    }
};

// ✅ Salva configurazione colonne (esclude "Azioni")
const saveUserColumns = () => {
    if (!tableInstance) return;
    const layout = tableInstance
        .getColumnLayout()
        .filter((col) => col.field !== "azioni");

    fetch("/user/columns", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content"),
        },
        body: JSON.stringify({ key: gridKey, columns: layout }),
    }).catch((error) => console.error("❌ Errore salvataggio colonne:", error));
};

// ✅ Reset colonne
const resetUserColumns = async () => {
    try {
        await axios.post("/user/columns/reset", { key: gridKey });
        location.reload();
    } catch (error) {
        console.error("❌ Errore nel reset colonne:", error);
    }
};

// 🔎 Ricerca globale
const applySearch = () => {
    if (!tableInstance) return;
    const q = String(search.value ?? "").trim();

    if (!q) {
        tableInstance.clearFilter(true);
        return;
    }

    // filtro su QUALSIASI colonna visibile (semplice ed efficace)
    const visibleFields = tableInstance
        .getColumns()
        .map((c) => c.getDefinition())
        .filter((d) => d.field && d.field !== "azioni" && d.visible !== false)
        .map((d) => d.field);

    tableInstance.setFilter((rowData) => {
        const hay = visibleFields
            .map((f) => (rowData?.[f] == null ? "" : String(rowData[f])))
            .join(" ")
            .toLowerCase();
        return hay.includes(q.toLowerCase());
    });
};

// 🔁 reagisci a page size
watch(pageSize, (val) => {
    if (!tableInstance) return;
    tableInstance.setPageSize(Number(val) || 20);
});

// ✅ Setup Tabulator
onMounted(async () => {
    await nextTick();

    let tabulatorColumns = await loadUserColumns();

    // 🔁 Rimuove "Azioni" se già presente
    tabulatorColumns = tabulatorColumns.filter((c) => c.field !== "azioni");

    // ✅ Inserisce colonna "Azioni" all'inizio
    tabulatorColumns.unshift({
        title: "Azioni",
        field: "azioni",
        headerSort: false,
        hozAlign: "center",
        width: 120,
        visible: true,
        cssClass: "col-actions",
        formatter: () => `
  <div class="actWrap">
    <button class="actBtn actEdit" data-action="edit" title="Modifica">✏️</button>
    <button class="actBtn actDel" data-action="delete" title="Elimina">🗑️</button>
  </div>
`,
        cellClick: (e, cell) => {
            const id = cell.getRow().getData().IDAnagrafica;
            const action = e.target?.getAttribute("data-action");
            if (!id || !action) return;

            if (action === "edit") {
                router.visit(`/${props.nomeTabella}/${id}/edit`);
            } else if (action === "delete") {
                // 2-click style “soft” (più sicuro)
                if (confirm("Sei sicuro di voler eliminare questo record?")) {
                    router.delete(`/${props.nomeTabella}/${id}`);
                }
            }
        },
    });

    tableInstance = new Tabulator(tableRef.value, {
        data: props.records,
        columns: tabulatorColumns,
        layout: "fitColumns",
        height: "100%",

        // UX
        reactiveData: true,
        movableColumns: true,
        resizableColumns: true,

        // Paging
        pagination: "local",
        paginationSize: pageSize.value,
        paginationSizeSelector: [10, 20, 50, 100],

        // Filtri
        headerFilterLiveFilterDelay: 250,

        // Placeholder
        placeholder:
            "Nessun record trovato. Prova a cambiare filtri o ricerca.",

        // Persist colonne (tuo)
        columnMoved: saveUserColumns,
        columnResized: saveUserColumns,
        columnVisibilityChanged: saveUserColumns,
    });
});

onUnmounted(() => {
    if (tableInstance) tableInstance.destroy();
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="pageWrap">
            <!-- HERO / HEADER -->
            <div class="hero">
                <div class="heroLeft">
                    <div class="heroIcon">👤</div>
                    <div>
                        <div class="heroTitle">
                            Anagrafica
                            <span class="pill">Filtro: {{ tipoU }}</span>
                        </div>
                        <div class="heroSub">
                            Gestione record • ricerca globale • colonne
                            personalizzabili
                        </div>
                    </div>
                </div>

                <div class="heroRight">
                    <div class="kpi">
                        <div class="kpiLabel">Totale record</div>
                        <div class="kpiValue">{{ totalCount }}</div>
                    </div>

                    <div class="searchBox">
                        <span class="searchIcon">🔎</span>
                        <input
                            v-model="search"
                            class="searchInput"
                            placeholder="Cerca in tutte le colonne..."
                            @input="applySearch"
                        />
                    </div>

                    <div class="selectWrap">
                        <span class="selectLbl">Righe x pagina</span>
                        <select v-model.number="pageSize" class="select">
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>

                    <div class="btnRow">
                        <button @click="saveUserColumns" class="btn btnPrimary">
                            💾 Salva
                        </button>
                        <button @click="resetUserColumns" class="btn btnGhost">
                            ♻️ Reset
                        </button>
                        <button
                            @click="router.visit(`/${nomeTabella}/create`)"
                            class="btn btnGreen"
                        >
                            ➕ Nuovo
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="card">
                <div class="tableWrap">
                    <div ref="tableRef" class="w-full h-full" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "tabulator-tables/dist/css/tabulator.min.css";

/* ===== Layout ===== */
.pageWrap {
    height: calc(100vh - 0px);
    padding: 18px;
    background: linear-gradient(180deg, #f8fafc, #ffffff);
}

/* ===== Hero ===== */
.hero {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    padding: 14px;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    background: linear-gradient(90deg, #ffffff, #f8fafc);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    margin-bottom: 12px;
}

.heroLeft {
    display: flex;
    gap: 12px;
    align-items: center;
}

.heroIcon {
    width: 46px;
    height: 46px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: #eef2ff;
    border: 1px solid #e0e7ff;
    font-size: 20px;
}

.heroTitle {
    font-size: 18px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.heroSub {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.pill {
    display: inline-flex;
    padding: 3px 10px;
    border-radius: 999px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    color: #1d4ed8;
    font-weight: 900;
    font-size: 12px;
}

.heroRight {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* KPI */
.kpi {
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #fff;
    min-width: 140px;
}
.kpiLabel {
    font-size: 11px;
    font-weight: 900;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.kpiValue {
    font-size: 18px;
    font-weight: 900;
    color: #0f172a;
    margin-top: 2px;
}

/* Search */
.searchBox {
    position: relative;
    min-width: 280px;
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
    background: #fff;
    outline: none;
    font-weight: 800;
    color: #0f172a;
}
.searchInput:focus {
    border-color: #93c5fd;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

/* Select */
.selectWrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #fff;
}
.selectLbl {
    font-size: 12px;
    font-weight: 900;
    color: #475569;
}
.select {
    border: 0;
    outline: none;
    font-weight: 900;
    color: #0f172a;
    background: transparent;
}

/* Buttons */
.btnRow {
    display: flex;
    gap: 8px;
    align-items: center;
}
.btn {
    padding: 10px 12px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-weight: 900;
    font-size: 12px;
    color: #0f172a;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    transition: 0.15s;
}
.btn:hover {
    transform: translateY(-1px);
}
.btnOrange {
    background: #fffbeb;
    border-color: #fed7aa;
    color: #9a3412;
}
.btnGreen {
    background: #ecfdf5;
    border-color: #bbf7d0;
    color: #166534;
}
.btnGhost {
    background: #f8fafc;
}

/* Card */
.card {
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    overflow: hidden;
    height: calc(100vh - 170px);
}
.tableWrap {
    height: 100%;
}

/* ===== Tabulator skin ===== */
.tabulator {
    border: 0 !important;
    font-size: 0.9rem;
}

.tabulator .tabulator-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

.tabulator .tabulator-col .tabulator-col-title {
    font-weight: 900 !important;
    color: #334155 !important;
}

.tabulator .tabulator-row {
    border-bottom: 1px solid #f1f5f9 !important;
}

.tabulator .tabulator-row:hover {
    background: #f8fafc !important;
}

/* Azioni */
.actWrap {
    display: flex;
    justify-content: center;
    gap: 8px;
}
.actBtn {
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 12px;
    padding: 6px 10px;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    transition: 0.15s;
}
.actBtn:hover {
    transform: translateY(-1px);
}
.actEdit {
    background: #fffbeb;
    border-color: #fed7aa;
}
.actDel {
    background: #fff1f2;
    border-color: #fecaca;
}
:root {
    /* Smart 2026 */
    --s-primary-700: #1d4ed8; /* blu forte */
    --s-primary-600: #2563eb; /* blu */
    --s-primary-500: #3b82f6; /* blu chiaro */
    --s-hero-1: #3f3fd6; /* viola/blu come header screenshot */
    --s-hero-2: #1f60ff; /* blu come header screenshot */

    --s-bg: #f8fafc;
    --s-card: #ffffff;
    --s-border: #e2e8f0;
    --s-text: #0f172a;
    --s-muted: #64748b;

    --s-success-bg: #ecfdf5;
    --s-success-br: #bbf7d0;
    --s-success-tx: #166534;

    --s-danger-bg: #fff1f2;
    --s-danger-br: #fecaca;
    --s-danger-tx: #991b1b;

    --s-warn-bg: #fffbeb;
    --s-warn-br: #fed7aa;
    --s-warn-tx: #9a3412;
}
.pageWrap {
    background: var(--s-bg);
}

.hero {
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: linear-gradient(90deg, var(--s-hero-1), var(--s-hero-2));
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
    color: #fff;
}

.heroTitle,
.heroSub {
    color: #fff;
}

.heroIcon {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.22);
    color: #fff;
}

.pill {
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
    color: #fff;
}
.btn {
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    font-weight: 900;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
}
.btn:hover {
    transform: translateY(-1px);
}

.btnOrange {
    background: rgba(245, 158, 11, 0.18);
    border-color: rgba(245, 158, 11, 0.35);
}

.btnGreen {
    background: rgba(16, 185, 129, 0.18);
    border-color: rgba(16, 185, 129, 0.35);
}

.btnGhost {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.18);
}
.btnPrimary {
    background: rgba(255, 255, 255, 0.92);
    color: var(--s-primary-700);
    border-color: rgba(255, 255, 255, 0.35);
}
.tabulator{
  border: 0 !important;
  font-size: 0.9rem;
}

.tabulator .tabulator-header{
  background: #f1f5f9 !important;
  border-bottom: 1px solid var(--s-border) !important;
}

.tabulator .tabulator-col .tabulator-col-title{
  font-weight: 900 !important;
  color: #334155 !important;
}

.tabulator .tabulator-row{
  border-bottom: 1px solid #f1f5f9 !important;
}

.tabulator .tabulator-row:hover{
  background: #f8fafc !important;
}

/* riga selezionata/attiva (stile blu) */
.tabulator .tabulator-row.tabulator-selected{
  background: rgba(59,130,246,.12) !important;
}
.actWrap{ display:flex; justify-content:center; gap:8px; }
.actBtn{
  border-radius: 12px;
  padding: 6px 10px;
  font-weight: 900;
  border: 1px solid var(--s-border);
  background: #fff;
  box-shadow: 0 10px 22px rgba(15,23,42,.08);
  cursor:pointer;
  transition:.15s;
}
.actBtn:hover{ transform: translateY(-1px); }

.actEdit{ background: var(--s-warn-bg); border-color: var(--s-warn-br); color: var(--s-warn-tx); }
.actDel{  background: var(--s-danger-bg); border-color: var(--s-danger-br); color: var(--s-danger-tx); }

</style>
