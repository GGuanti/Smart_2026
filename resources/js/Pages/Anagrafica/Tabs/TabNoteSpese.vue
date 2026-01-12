<script setup>
// Importa le API reattive di Vue: ref (stato), onMounted (lifecycle), computed (derivati), watch (osservatori)
import { ref, onMounted, computed, watch } from "vue";

// Axios per chiamate HTTP alle API Laravel
import axios from "axios";

// Tabulator per la griglia
import { TabulatorFull as Tabulator } from "tabulator-tables";



// Definisce le props che arrivano dal parent (Inertia/Vue)
// - codCliente: codice cliente per filtro/chiave preferenze
// - nomeUtente: nome visualizzato in header
// - noteSpese: righe tabella (array)
const props = defineProps({
    codCliente: String,
    nomeUtente: String,
    noteSpese: { type: Array, default: () => [] },
});

// Crea una chiave "logica" per salvare preferenze colonne.
// L’utente è già implicito lato backend (auth()->id()).
// Se codCliente non c'è, usa "all" così hai comunque una chiave stabile.
const prefKey = computed(
    () => `tabulator.note_spese.${props.codCliente || "all"}`
);

// Ref al div dove Tabulator monterà la tabella
const tableRef = ref(null);

// Ref all’istanza Tabulator (per chiamare metodi: setFilter, replaceData, ecc.)
const tabulatorInstance = ref(null);

// Campo di ricerca globale (input)
const quickSearch = ref("");

// -------------------- utils --------------------

// Utility: formatta una data in DD/MM/YYYY.
// Se non c’è valore: "—"
// Se la data non è valida: restituisce la stringa originale (fallback).
const formatDDMMYYYY = (value) => {
    if (!value) return "—";
    const d = new Date(value);
    if (isNaN(d)) return String(value);
    return `${String(d.getDate()).padStart(2, "0")}/${String(
        d.getMonth() + 1
    ).padStart(2, "0")}/${d.getFullYear()}`;
};

// Computed: numero righe (se noteSpese è undefined/null => 0)
const totalCount = computed(() => props.noteSpese?.length ?? 0);

// Computed: somma del campo Neg (importi negativi).
// Number(r.Neg) trasforma stringhe in numero; se NaN => 0.
const totalNeg = computed(() => {
    return (props.noteSpese || []).reduce(
        (acc, r) => acc + (Number(r.Neg) || 0),
        0
    );
});

// Utility: formatta un numero in Euro, locale it-IT.
const fmtEuro = (n) =>
    new Intl.NumberFormat("it-IT", {
        style: "currency",
        currency: "EUR",
    }).format(Number(n) || 0);

// -------------------- preferences API --------------------

// Carica preferenze dal backend (GET).
// Passa key e type: "columns" così il backend sa cosa restituire.
const loadPref = async () => {
    const { data } = await axios.get("/user-preferences", {
        params: { key: prefKey.value, type: "columns" },
    });

    // Ritorna data.data (valore salvato nel DB) oppure null se assente.
    // Ti aspetti una struttura tipo: { columns: [ {field,width,visible}, ... ] }
    return data?.data || null;
};

// Salva preferenze sul backend (POST) con stessa key e type.
// payload tipico: { columns: [...] }
const savePref = async (payload) => {
    await axios.post("/user-preferences", {
        key: prefKey.value,
        type: "columns",
        data: payload,
    });
};

// -------------------- columns layout helpers --------------------

// Legge dall’istanza Tabulator la configurazione corrente delle colonne
// (ordine, width, visibilità) e la converte in un array semplice salvabile nel DB.
const buildLayoutFromTabulator = () => {
    // Se l’istanza non esiste ancora, non fare nulla
    if (!tabulatorInstance.value) return null;

    // getColumns() ritorna ColumnComponent; qui estrai field, width, visible
    return tabulatorInstance.value.getColumns().map((c) => ({
        field: c.getField(), // nome campo colonna
        width: c.getWidth(), // larghezza attuale
        visible: c.isVisible?.() ?? true, // visibilità (fallback true se metodo non esiste)
    }));
};

// Applica un layout salvato (ordine, width, visible) a una lista baseColumns.
// - Mantiene ordine salvato
// - Applica width e visible salvati
// - Aggiunge in coda eventuali colonne nuove non presenti nel layout (compatibilità future)
const applyLayoutToBaseColumns = (baseColumns, layout) => {
    // Se layout vuoto/non valido => usa baseColumns com’è
    if (!Array.isArray(layout) || layout.length === 0) return baseColumns;

    // Mappa field -> colonna base clonata (così non modifichi l’originale)
    const byField = new Map(baseColumns.map((c) => [c.field, { ...c }]));

    // Array finale ordinato
    const ordered = [];

    // 1) Applica l’ordine salvato
    for (const l of layout) {
        // Se manca field, salta
        if (!l?.field) continue;

        // Se field non esiste più tra le baseColumns, salta (colonna rimossa)
        const col = byField.get(l.field);
        if (!col) continue;

        // Applica width se presente
        if (l.width) col.width = l.width;

        // Applica visible solo se boolean (true/false)
        if (typeof l.visible === "boolean") col.visible = l.visible;

        // Inserisci la colonna nell’ordine giusto
        ordered.push(col);

        // Rimuovi dal Map così rimangono solo "colonne nuove"
        byField.delete(l.field);
    }

    // 2) Aggiunge colonne nuove non incluse nel layout salvato
    for (const [, col] of byField) ordered.push(col);

    return ordered;
};

// Debounce del salvataggio: evita mille POST mentre trascini/ridimensioni.
// Timer globale fuori funzione.
let saveTimer = null;
// Salvataggio manuale immediato (bottone)
const saveColumnsNow = async () => {
    const columns = buildLayoutFromTabulator();
    if (!columns) return;
    await savePref({ columns });
};

// Funzione che pianifica il salvataggio tra 250ms dall’ultima modifica
const saveDebounced = () => {
    // Reset del timer precedente
    clearTimeout(saveTimer);

    // Imposta un nuovo timer
    saveTimer = setTimeout(async () => {
        // Legge layout attuale dalle colonne Tabulator
        const columns = buildLayoutFromTabulator();
        if (!columns) return;

        // Salva nel DB (payload con proprietà "columns")
        await savePref({ columns });
    }, 250);
};

// -------------------- init tabulator --------------------

// Inizializza Tabulator
const initTable = async () => {
    // Definizione colonne “base” (default)
    const baseColumns = [
        {
            title: "📄 Data Doc", // titolo colonna
            field: "DataDoc", // campo dataset
            sorter: (a, b) =>
                (a ? new Date(a) : new Date(0)) -
                (b ? new Date(b) : new Date(0)), // ordinamento date
            headerFilter: "input", // filtro in header
            formatter: (cell) =>
                `<span class="text-slate-700 font-medium">${formatDDMMYYYY(
                    cell.getValue()
                )}</span>`, // visualizzazione formattata
            width: 135, // larghezza iniziale
        },
        {
            title: "💳 Data Pag",
            field: "DataPag",
            sorter: (a, b) =>
                (a ? new Date(a) : new Date(0)) -
                (b ? new Date(b) : new Date(0)),
            headerFilter: "input",
            formatter: (cell) =>
                `<span class="text-slate-700">${formatDDMMYYYY(
                    cell.getValue()
                )}</span>`,
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
            field: "note", // attenzione: qui il field è "note" (minuscolo)
            headerFilter: "input",
            formatter: (cell) => {
                // Normalizza valore: stringa trim
                const v = String(cell.getValue() ?? "").trim();
                return v
                    ? `<div class="text-slate-800 line-clamp-2">${v}</div>`
                    : `<span class="text-slate-400">—</span>`;
            },
            minWidth: 240, // non fissata, ma minima
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

    // Layout salvato (se presente)
    let saved = null;

    // Prova a caricare preferenze dal DB
    try {
        saved = await loadPref();
    } catch (e) {
        // Se fallisce, continui con colonne base
        console.warn("⚠️ load user_preferences fallito:", e);
    }

    // Applica layout salvato alle colonne base
    const columns = applyLayoutToBaseColumns(baseColumns, saved?.columns);

    // Crea istanza Tabulator e montala sul div tableRef
    tabulatorInstance.value = new Tabulator(tableRef.value, {
        height: "420px", // altezza fissa (scroll verticale interno)
        layout: "fitColumns", // adatta colonne allo spazio
        data: props.noteSpese, // dataset iniziale
        placeholder: "Nessuna nota spese per questo cliente.", // testo se vuota
        reactiveData: true, // aggiorna se data cambia (con certe API)

        movableColumns: true, // permette drag&drop colonne
        selectable: false, // disabilita selezione righe
        columnHeaderVertAlign: "middle", // allineamento verticale header
        headerSortElement: (column, dir) =>
            dir === "asc" ? "▲" : dir === "desc" ? "▼" : "", // icone sort

        // Applica classe custom a ogni riga (utile per CSS)
        rowFormatter: (row) => row.getElement().classList.add("tab-row"),

        // Colonne finali (base + layout salvato)
        columns,

        // Eventi che scatenano il salvataggio preferenze (debounced)
        columnMoved: saveDebounced,
        columnResized: saveDebounced,
        columnVisibilityChanged: saveDebounced,
    });

    // Watch: quando cambia quickSearch, applica un filtro globale su 3 campi
    watch(
        () => quickSearch.value,
        (val) => {
            // Normalizza la stringa
            const t = String(val ?? "").trim();

            // Se istanza non pronta, esci
            if (!tabulatorInstance.value) return;

            // Se stringa vuota => rimuove filtri
            if (!t) tabulatorInstance.value.clearFilter(true);
            else {
                // setFilter con array di OR conditions (gruppo)
                tabulatorInstance.value.setFilter([
                    [
                        { field: "Coddoc", type: "like", value: t },
                        { field: "CodAtt", type: "like", value: t },
                        { field: "note", type: "like", value: t },
                    ],
                ]);
            }
        },
        { immediate: true } // applica subito alla prima render
    );
};

// Lifecycle: quando il componente è montato, inizializza la tabella
onMounted(() => initTable());

// Watch: se il parent cambia props.noteSpese, aggiorna i dati Tabulator
watch(
    () => props.noteSpese,
    (val) => {
        // replaceData sostituisce tutte le righe
        if (tabulatorInstance.value)
            tabulatorInstance.value.replaceData(val || []);
    },
    { deep: true } // osserva anche mutazioni interne (se l’array cambia “in profondità”)
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
                        <span class="text-slate-500 font-semibold"
                            >• {{ props.nomeUtente }}</span
                        >
                    </h2>
                    <div class="mt-1 text-sm text-slate-500">
                        Cliente:
                        <span class="font-semibold text-slate-700">{{
                            props.codCliente
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- KPI -->
            <div class="flex flex-wrap items-center gap-3">
                <div
                    class="rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200"
                >
                    <div class="text-xs text-slate-500">Righe</div>
                    <div
                        class="text-sm font-extrabold text-slate-900 tabular-nums"
                    >
                        {{ totalCount }}
                    </div>
                </div>

                <div
                    class="rounded-xl bg-rose-50 px-3 py-2 ring-1 ring-rose-100"
                >
                    <div class="text-xs text-rose-700">Totale Importo -</div>
                    <div
                        class="text-sm font-extrabold text-rose-800 tabular-nums"
                    >
                        {{ fmtEuro(totalNeg) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div
            class="px-5 pt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <div class="relative w-full md:max-w-md">
                <div class="searchWrap">
                    <input
                        v-model="quickSearch"
                        type="text"
                        placeholder="🔎 Cerca (Coddoc, CodAtt, note...)"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    />
                </div>
            </div>
            <div class="left flex items-center gap-4">
    <button
        type="button"
        @click="saveColumnsNow"
        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.98] transition"
        title="Salva disposizione colonne"
    >
        💾 <span class="hidden sm:inline">Salva colonne</span>
    </button>

    <div class="text-xs text-slate-500 whitespace-nowrap">
        Filtri colonna disponibili nelle intestazioni della tabella
    </div>
</div>
        </div>

        <!-- Table -->
        <div class="p-5 pt-4">
            <div
                ref="tableRef"
                class="tabWrap smartGrid"
            ></div>
        </div>
    </div>
</template>

