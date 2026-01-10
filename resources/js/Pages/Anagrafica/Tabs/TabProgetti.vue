<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator.min.css";

const toast = useToast();

const props = defineProps({
  codCliente: String,
  progetti: { type: Array, default: () => [] },
  progettiDisponibili: { type: Array, default: () => [] },
});

const tableRef = ref(null);
let tableInstance = null;

// UI state
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

// ---------- lifecycle ----------
onMounted(async () => {
  await nextTick();

  if (!props.progetti.length) {
    watch(
      () => props.progetti,
      async (val) => {
        if (val?.length) {
          await nextTick();

          console.log("PROGETTO[0] keys:", Object.keys(val[0] ?? {}));
          console.log("PROGETTO[0] row:", val[0]);

          initTabulator(val);
        }
      },
      { immediate: true }
    );
  } else {
    console.log("PROGETTO[0] keys:", Object.keys(props.progetti[0] ?? {}));
    console.log("PROGETTO[0] row:", props.progetti[0]);

    initTabulator(props.progetti);
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

// ---------- Tabulator ----------
function initTabulator(data) {
  if (!tableRef.value) return;

  tableInstance = new Tabulator(tableRef.value, {
    height: "520px",
    data: data,
    layout: "fitColumns",
    responsiveLayout: "collapse",
    movableColumns: true,

    pagination: "local",
    paginationSize: 10,
    paginationSizeSelector: [10, 20, 50],

    placeholder:
      "Nessun progetto trovato. Aggiungi un progetto o cambia i filtri.",

    rowHeight: 44,

    columns: [
      { title: "ID", field: "IdProgetto", visible: false },
{
        title: "🧾 Committente",
        field: "RagioneSocialeCommittenti",
        editor: "input",
        minWidth: 220,
        formatter: (cell) => {
          const v = String(cell.getValue() ?? "").trim();
          return v
            ? `<span style="color:#334155;">${escapeHtml(v)}</span>`
            : `<span style="color:#94a3b8;">—</span>`;
        },
      },


      {
        title: "📅 Data Contratto",
        field: "DataContratto",
        editor: "input", // se vuoi editor date: serve modulo date (si può fare)
        width: 120,
        hozAlign: "center",
        formatter: (cell) => {
          const v = cell.getValue();
          const s = formatDateIT(v);
          return s ? `<span style="font-weight:700;">${s}</span>` : "—";
        },
      },
      {
        title: "📅 Data inizio",
        field: "DataInzProgetto",
        editor: "input", // se vuoi editor date: serve modulo date (si può fare)
        width: 120,
        hozAlign: "center",
        formatter: (cell) => {
          const v = cell.getValue();
          const s = formatDateIT(v);
          return s ? `<span style="font-weight:700;">${s}</span>` : "—";
        },
      },
      {
        title: "📅 Data Fine",
        field: "DataFineProgetto",
        editor: "input", // se vuoi editor date: serve modulo date (si può fare)
        width: 120,
        hozAlign: "center",
        formatter: (cell) => {
          const v = cell.getValue();
          const s = formatDateIT(v);
          return s ? `<span style="font-weight:700;">${s}</span>` : "—";
        },
      },
            {
        title: "📅 Data Pagamento",
        field: "DataPagamento",
        editor: "input", // se vuoi editor date: serve modulo date (si può fare)
        width: 120,
        hozAlign: "center",
        formatter: (cell) => {
          const v = cell.getValue();
          const s = formatDateIT(v);
          return s ? `<span style="font-weight:700;">${s}</span>` : "—";
        },
      },


{
        title: "🧾 DescrizioneProgetto",
        field: "DescrizioneProgetto",
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
          const badge =
            n > 0
              ? `<span style="display:inline-flex;align-items:center;gap:6px;
                        padding:3px 10px;border-radius:999px;font-weight:800;
                        border:1px solid #bbf7d0;background:#ecfdf5;color:#166534;">
                    ${label}
                  </span>`
              : `<span style="color:#94a3b8;">—</span>`;
          return badge;
        },
      },

      {
        title: "⚙️",
        headerSort: false,
        width: 110,
        hozAlign: "center",
        formatter: () => {
          return `
            <div class="actWrap">
              <button class="actBtn actDanger" title="Elimina">
                🗑️
              </button>
            </div>`;
        },
        cellClick: (e, cell) => {
          const row = cell.getRow().getData();
          if (!row?.IdProgetto) return;

          if (confirm(`Eliminare il progetto "${row.DescrizioneProgetto}"?`)) {
            deleteProgetto(row.IdProgetto);
          }
        },
      },
    ],

    cellEdited: async (cell) => {
      const updated = cell.getRow().getData();

      try {
        await axios.put(`/progetti/${updated.IdProgetto}`, updated);
        toast.success("✅ Progetto aggiornato");
      } catch (err) {
        console.error(err);
        toast.error("❌ Errore aggiornamento");

        // rollback: ricarico riga dalla sorgente props
        const original = (props.progetti ?? []).find(
          (p) => Number(p.IdProgetto) === Number(updated.IdProgetto)
        );
        if (original) cell.getRow().update(original);
      }
    },
  });

  isReady.value = true;
}

// ---------- UI actions ----------
function applySearch() {
  if (!tableInstance) return;
  const q = String(search.value ?? "").trim();

  if (!q) {
    tableInstance.clearFilter(true);
    if (onlyWithImporto.value) applyOnlyWithImporto();
    return;
  }

  // filtro globale su più campi
  tableInstance.setFilter([
    [
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

const deleteProgetto = async (id) => {
  try {
    await axios.delete(`/progetti/${id}`);
    toast.success("🗑️ Progetto eliminato");

    // Tabulator: cancella la riga per IdProgetto
    const row = tableInstance?.getRow(id);
    if (row) row.delete();
    else tableInstance?.deleteRow(id);
  } catch (err) {
    console.error(err);
    toast.error("❌ Errore durante l'eliminazione");
  }
};

// ---------- tiny util ----------
function escapeHtml(unsafe) {
  return String(unsafe)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
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
              <span v-if="props.codCliente" class="subCode">#{{ props.codCliente }}</span>
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
      </div>

      <div class="right">
        <div class="searchWrap">
          <span class="searchIcon">🔎</span>
          <input
            v-model="search"
            class="searchInput"
            placeholder="Cerca descrizione o accordi..."
            @input="applySearch"
          />
        </div>

        <button type="button" class="pillBtn" :class="{ pillOn: onlyWithImporto }" @click="toggleOnlyWithImporto">
          💶 Solo con importo
        </button>

        <button type="button" class="pillBtn pillGhost" @click="resetFilters">
          ♻️ Reset
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
</style>
