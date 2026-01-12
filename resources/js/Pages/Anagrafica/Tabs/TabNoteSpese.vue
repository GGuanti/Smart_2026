<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator.min.css";

const props = defineProps({
  codCliente: String,
  nomeUtente: String,
  noteSpese: { type: Array, default: () => [] },
});

// ✅ key per utente+tabella (utente è già implicito nel controller via auth()->id())
const prefKey = computed(() => `tabulator.note_spese.${props.codCliente || "all"}`);

const tableRef = ref(null);
const tabulatorInstance = ref(null);
const quickSearch = ref("");

// -------------------- utils --------------------
const formatDDMMYYYY = (value) => {
  if (!value) return "—";
  const d = new Date(value);
  if (isNaN(d)) return String(value);
  return `${String(d.getDate()).padStart(2, "0")}/${String(d.getMonth() + 1).padStart(
    2,
    "0"
  )}/${d.getFullYear()}`;
};

const totalCount = computed(() => props.noteSpese?.length ?? 0);

const totalNeg = computed(() => {
  return (props.noteSpese || []).reduce((acc, r) => acc + (Number(r.Neg) || 0), 0);
});

const fmtEuro = (n) =>
  new Intl.NumberFormat("it-IT", { style: "currency", currency: "EUR" }).format(
    Number(n) || 0
  );

// -------------------- preferences API --------------------
const loadPref = async () => {
  const { data } = await axios.get("/user-preferences", {
    params: { key: prefKey.value, type: "columns" },
  });
  return data?.data || null; // value dal DB (castato ad array) oppure null
};

const savePref = async (payload) => {
  await axios.post("/user-preferences", {
    key: prefKey.value,
    type: "columns",
    data: payload,
  });
};

// -------------------- columns layout helpers --------------------
const buildLayoutFromTabulator = () => {
  if (!tabulatorInstance.value) return null;

  return tabulatorInstance.value.getColumns().map((c) => ({
    field: c.getField(),
    width: c.getWidth(),                // ✅ width
    visible: c.isVisible?.() ?? true,   // ✅ visible
  }));
};

const applyLayoutToBaseColumns = (baseColumns, layout) => {
  if (!Array.isArray(layout) || layout.length === 0) return baseColumns;

  const byField = new Map(baseColumns.map((c) => [c.field, { ...c }]));
  const ordered = [];

  // ordine salvato
  for (const l of layout) {
    if (!l?.field) continue;
    const col = byField.get(l.field);
    if (!col) continue;

    if (l.width) col.width = l.width;
    if (typeof l.visible === "boolean") col.visible = l.visible;

    ordered.push(col);
    byField.delete(l.field);
  }

  // colonne nuove non in layout
  for (const [, col] of byField) ordered.push(col);

  return ordered;
};

// debounced save
let saveTimer = null;
const saveDebounced = () => {
  clearTimeout(saveTimer);
  saveTimer = setTimeout(async () => {
    const columns = buildLayoutFromTabulator();
    if (!columns) return;
    await savePref({ columns });
  }, 250);
};

// -------------------- init tabulator --------------------
const initTable = async () => {
  const baseColumns = [
    {
      title: "📄 Data Doc",
      field: "DataDoc",
      sorter: (a, b) => (a ? new Date(a) : new Date(0)) - (b ? new Date(b) : new Date(0)),
      headerFilter: "input",
      formatter: (cell) =>
        `<span class="text-slate-700 font-medium">${formatDDMMYYYY(
          cell.getValue()
        )}</span>`,
      width: 135,
    },
    {
      title: "💳 Data Pag",
      field: "DataPag",
      sorter: (a, b) => (a ? new Date(a) : new Date(0)) - (b ? new Date(b) : new Date(0)),
      headerFilter: "input",
      formatter: (cell) =>
        `<span class="text-slate-700">${formatDDMMYYYY(cell.getValue())}</span>`,
      width: 135,
    },
    {
      title: "🔖 Coddoc",
      field: "Coddoc",
      headerFilter: "input",
      formatter: (cell) =>
        cell.getValue()
          ? `<span class="inline-flex items-center rounded-lg bg-slate-900 px-2 py-1 text-xs font-bold text-white">#${cell.getValue()}</span>`
          : `<span class="text-slate-400">—</span>`,
      width: 120,
    },
    {
      title: "🏷️ CodAtt",
      field: "CodAtt",
      headerFilter: "input",
      formatter: (cell) =>
        cell.getValue()
          ? `<span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">${cell.getValue()}</span>`
          : `<span class="text-slate-400">—</span>`,
      width: 120,
    },
    {
      title: "📝 Note",
      field: "note",
      headerFilter: "input",
      formatter: (cell) => {
        const v = String(cell.getValue() ?? "").trim();
        return v
          ? `<div class="text-slate-800 line-clamp-2">${v}</div>`
          : `<span class="text-slate-400">—</span>`;
      },
      minWidth: 240,
    },
    {
      title: "💸 Importo -",
      field: "Neg",
      sorter: "number",
      hozAlign: "right",
      headerFilter: "input",
      formatter: (cell) => {
        const v = Number(cell.getValue()) || 0;
        return `<span class="font-extrabold text-rose-700 tabular-nums">${fmtEuro(
          v
        )}</span>`;
      },
      width: 150,
    },
  ];

  // ✅ carico layout dal DB e lo applico
  let saved = null;
  try {
    saved = await loadPref();
  } catch (e) {
    console.warn("⚠️ load user_preferences fallito:", e);
  }

  const columns = applyLayoutToBaseColumns(baseColumns, saved?.columns);

  tabulatorInstance.value = new Tabulator(tableRef.value, {
    height: "420px",
    layout: "fitColumns",
    data: props.noteSpese,
    placeholder: "Nessuna nota spese per questo cliente.",
    reactiveData: true,

    movableColumns: true,
    selectable: false,
    columnHeaderVertAlign: "middle",
    headerSortElement: (column, dir) => (dir === "asc" ? "▲" : dir === "desc" ? "▼" : ""),

    rowFormatter: (row) => row.getElement().classList.add("tab-row"),

    // ✅ qui entra il layout ordinato+width+visible
    columns,

    // ✅ salva ordine e larghezza
    columnMoved: saveDebounced,
    columnResized: saveDebounced,
    columnVisibilityChanged: saveDebounced,
  });

  // ✅ quick search (global)
  watch(
    () => quickSearch.value,
    (val) => {
      const t = String(val ?? "").trim();
      if (!tabulatorInstance.value) return;

      if (!t) tabulatorInstance.value.clearFilter(true);
      else {
        tabulatorInstance.value.setFilter([
          [
            { field: "Coddoc", type: "like", value: t },
            { field: "CodAtt", type: "like", value: t },
            { field: "note", type: "like", value: t },
          ],
        ]);
      }
    },
    { immediate: true }
  );
};

onMounted(() => initTable());

// ✅ aggiorna dati se props cambia
watch(
  () => props.noteSpese,
  (val) => {
    if (tabulatorInstance.value) tabulatorInstance.value.replaceData(val || []);
  },
  { deep: true }
);
</script>

<template>
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <!-- Header -->
    <div
      class="flex flex-col gap-3 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between"
    >
      <div class="flex items-start gap-3">
        <div
          class="h-10 w-10 rounded-2xl bg-rose-50 ring-1 ring-rose-100 flex items-center justify-center"
        >
          <span class="text-rose-700">🧾</span>
        </div>

        <div>
          <h2 class="text-lg font-extrabold text-slate-900">
            Note Spese
            <span class="text-slate-500 font-semibold">• {{ props.nomeUtente }}</span>
          </h2>
          <div class="mt-1 text-sm text-slate-500">
            Cliente:
            <span class="font-semibold text-slate-700">{{ props.codCliente }}</span>
          </div>
        </div>
      </div>

      <!-- KPI -->
      <div class="flex flex-wrap items-center gap-3">
        <div class="rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200">
          <div class="text-xs text-slate-500">Righe</div>
          <div class="text-sm font-extrabold text-slate-900 tabular-nums">
            {{ totalCount }}
          </div>
        </div>

        <div class="rounded-xl bg-rose-50 px-3 py-2 ring-1 ring-rose-100">
          <div class="text-xs text-rose-700">Totale Importo -</div>
          <div class="text-sm font-extrabold text-rose-800 tabular-nums">
            {{ fmtEuro(totalNeg) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="px-5 pt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div class="relative w-full md:max-w-md">
        <input
          v-model="quickSearch"
          type="text"
          placeholder="🔎 Cerca (Coddoc, CodAtt, note...)"
          class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200"
        />
         <button
      type="button"
      @click="saveColumnsNow"
      class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.98] transition"
      title="Salva disposizione colonne"
    >
      💾 <span class="hidden sm:inline">Salva colonne</span>
    </button>
      </div>

      <div class="text-xs text-slate-500">
        Filtri colonna disponibili nelle intestazioni della tabella
      </div>
    </div>

    <!-- Table -->
    <div class="p-5 pt-4">
      <div ref="tableRef" class="rounded-2xl overflow-hidden ring-1 ring-slate-200"></div>
    </div>
  </div>
</template>

<style scoped>
:deep(.tabulator) {
  border: none;
  font-family: inherit;
}
:deep(.tabulator .tabulator-header) {
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
:deep(.tabulator .tabulator-header .tabulator-col) {
  background: transparent;
  border-right: 1px solid #eef2f7;
}
:deep(.tabulator .tabulator-header .tabulator-col .tabulator-col-title) {
  font-weight: 800;
  color: #0f172a;
}
:deep(.tabulator .tabulator-row) {
  border-bottom: 1px solid #f1f5f9;
}
:deep(.tabulator .tabulator-row:nth-child(even)) {
  background: #fcfcfd;
}
:deep(.tabulator .tabulator-row:hover) {
  background: #f8fafc;
}
:deep(.tabulator .tabulator-cell) {
  padding: 12px;
}
:deep(.tabulator input) {
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  padding: 6px 10px;
}
</style>
