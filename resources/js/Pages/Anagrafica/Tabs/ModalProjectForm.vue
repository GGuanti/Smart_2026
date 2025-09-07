<script setup>
import { ref, watch, computed, onBeforeUnmount } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import AutoComplete from "primevue/autocomplete";
import FlatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Italian } from "flatpickr/dist/l10n/it.js";
import { useToast } from "vue-toastification";
import { TabulatorFull as Tabulator } from "tabulator-tables";
import "tabulator-tables/dist/css/tabulator.min.css";

/**
 * PROPS
 * - show/editMode: apertura modale e modalità
 * - project: record da modificare (o null)
 * - codCliente/idAttivita: contesto padre
 * - clienti: mappa { codiceCliente: "Ragione sociale" } oppure array di oggetti
 */

const props = defineProps({
    show: { type: Boolean, default: false },

    editMode: { type: Boolean, default: false },
    project: { type: Object, default: null },
    codCliente: { type: String, default: "" },
    idAttivita: { type: [String, Number], default: "" },
    formData: { type: Object, default: () => ({}) },
    clienti: { type: [Object, Array], default: () => ({}) },
});
const toast = useToast();
const emit = defineEmits(["close", "saved"]);

const formattedImporto = computed({
    get() {
        const raw = form.ImportoNettoConcordato;
        if (raw === null || raw === "") return "";
        // riusa il parser robusto
        const value = parseAmount(raw);
        return value.toLocaleString("it-IT", {
            style: "currency",
            currency: "EUR",
        });
    },
    set(val) {
        // togli simbolo €, punti spazi e converti a numero
        const numeric = parseFloat(
            String(val)
                .replace(/[€\s]/g, "")
                .replace(/\./g, "")
                .replace(",", ".")
        );
        form.ImportoNettoConcordato = isNaN(numeric) ? "" : numeric;
    },
});

/* ---------- Normalizzazione clienti (se arrivano via props) ---------- */
const normalizeCode = (s) => (s ?? "").toString().replace(/\s+/g, " ").trim();

const clientiMap = computed(() => {
    const c = props.clienti ?? {};
    if (Array.isArray(c)) {
        return Object.fromEntries(
            c
                .map((row) => [
                    normalizeCode(
                        row.CodCliente ?? row.codice ?? row.code ?? ""
                    ),
                    (row.RagioneSociale ?? row.nome ?? row.name ?? "").toString(),
                ])
                .filter(([k, v]) => k && v)
        );
    }
    // oggetto chiave->valore
    const out = {};
    for (const k in c) out[normalizeCode(k)] = c[k];
    return out;
});

/* ---------- UI state ---------- */
const activeTab = ref("generali"); // generali | info | giornate | allegati
const saving = ref(false);
const FormatoData = {
    altInput: true,
    altFormat: "d/m/Y",
    dateFormat: "Y-m-d",
    locale: Italian,
    allowInput: true,
};

/* ---------- FORM PROGETTO (scheda Generali) ---------- */
const form = useForm({
    IdProg: "",
    IdProgetto: "",
    CodCliente: "",
    IDAttivita: "",
    RagioneSocialeCommittenti: "",
    TipologiaSimulatore: "",
    DescrizioneProgetto: "",
    Accordi: "",
    Percentuale: "",
    GGPag: "",
    DataEmissionePrevFattura: "",
    ImportoNettoConcordato: "",
    AliquotaIVA: "",
    EsenzioneIva: "",
    CIG: "",
    coproduzione: "",
    DescrCausaleFattura: "",
    IndirizzoEmailFattura: "",
    IndirizzoEmailContatto: "",
    StatoProgetto: "",
    Consigliere: "",
    ImportGiornate: "",
    Pranzo: "",
    Cena: "",
    Alloggio: "",
    NNotti: "",
    Viaggio: "",
    CUP: "",
    NOrdineCup: "",
    DtOrdineCup: "",
    Titolo: "",
    Autore: "",
    RegiaCoreografia: "",
    NumRepliche: "",
    DataInzProgetto: "",
    DataFineProgetto: "",
    DataPagamento: "",
    Note: "",
});

function toYMD(v) {
    if (!v) return "";
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
    const d = new Date(v);
    if (isNaN(d)) return "";
    const p = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}

/* ---------- Autocomplete Cliente (fetch remoto) ---------- */
const selectedCliente = ref(null); // { label, value } oppure null
const clientiSuggerimenti = ref([]); // risultati per AutoComplete

// utility per costruire options da una mappa locale (fallback)
const clientiOptionsFromMap = computed(() =>
    Object.entries(clientiMap.value).map(([code, label]) => ({
        value: code,
        label,
    }))
);

// pre-popolo i suggerimenti con i primi N della mappa (qualora l’utente apra il dropdown senza digitare)
clientiSuggerimenti.value = clientiOptionsFromMap.value.slice(0, 50);

/**
 * Completa lato server:
 * - chiama la rotta Ziggy 'api.clienti.search' se disponibile
 * - fallback su '/api/clienti/search?q='
 * Atteso JSON: [{ codice: 'C   861', nome: 'ACME srl' }, ...]
 */
async function completaClienti(e) {
    const q = (e?.query ?? "").trim();
    // se query vuota: mostra primi N da mappa locale
    if (!q) {
        clientiSuggerimenti.value = clientiOptionsFromMap.value.slice(0, 50);
        return;
    }
    try {
        let url = "";
        // tenta route() di Ziggy se presente
        try {
            url = route("api.clienti.search", { q });
        } catch {
            url = `/api/clienti/search?q=${encodeURIComponent(q)}`;
        }

        const res = await fetch(url, {
            headers: { Accept: "application/json" },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        // mappa risposta server -> [{value,label}]
        const rem = Array.isArray(data)
            ? data
                  .map((r) => ({
                      value: normalizeCode(
                          r.codice ?? r.CodCliente ?? r.code ?? ""
                      ),
                      label: (r.nome ?? r.RagioneSociale ?? r.name ?? "").toString(),
                  }))
                  .filter((o) => o.value && o.label)
            : [];

        // fallback: se vuoto, filtra localmente
        clientiSuggerimenti.value = rem.length
            ? rem.slice(0, 100)
            : clientiOptionsFromMap.value
                  .filter(
                      (o) =>
                          o.label.toLowerCase().includes(q.toLowerCase()) ||
                          o.value.toLowerCase().includes(q.toLowerCase())
                  )
                  .slice(0, 50);
    } catch (err) {
        console.error("Errore fetch clienti:", err);
        // fallback locale
        const ql = q.toLowerCase();
        clientiSuggerimenti.value = clientiOptionsFromMap.value
            .filter(
                (o) => o.label.toLowerCase().includes(ql) || o.value.toLowerCase().includes(ql)
            )
            .slice(0, 50);
    }
}

// Sync: quando scelgo un cliente dal widget → aggiorno il form
watch(selectedCliente, (opt) => {
    const code = opt?.value ?? "";
    form.CodCliente = code;
    form.RagioneSocialeCommittenti =
        (code && (opt?.label ?? clientiMap.value[code])) || "";
});

// Sync inversa: se CodCliente cambia (fillForm/props) → aggiorno il widget
watch(
    () => form.CodCliente,
    (code) => {
        if (!code) {
            selectedCliente.value = null;
            return;
        }
        // prova a cercare in suggerimenti correnti
        let found = clientiSuggerimenti.value.find((o) => o.value === code);
        if (!found) {
            // cerca nella mappa locale
            const label = clientiMap.value[code];
            if (label) found = { value: code, label };
        }
        selectedCliente.value = found || null;
    }
);

function fillForm(p) {
    form.CodCliente = props.codCliente || "";
    form.IDAttivita = props.idAttivita || "";
    activeTab.value = "generali";

    if (props.editMode && p) {
        form.IdProg = p.IdProg ?? "";
        form.IdProgetto = p.IdProgetto ?? "";
        form.CodCliente = p?.CodCliente ?? form.CodCliente;
        form.RagioneSocialeCommittenti =
            p?.RagioneSocialeCommittenti ?? clientiMap.value?.[form.CodCliente] ?? "";
        form.TipologiaSimulatore = p?.TipologiaSimulatore ?? "";
        form.DescrizioneProgetto = p?.DescrizioneProgetto ?? "";
        form.Accordi = p?.Accordi ?? "";
        form.Percentuale = p?.Percentuale ?? "";
        form.GGPag = p?.GGPag ?? "";
        form.DataEmissionePrevFattura = toYMD(p?.DataEmissionePrevFattura);
        form.ImportoNettoConcordato = p?.ImportoNettoConcordato ?? "";
        form.AliquotaIVA = p?.AliquotaIVA ?? "";
        form.EsenzioneIva = p?.EsenzioneIva ?? "";
        form.CIG = p?.CIG ?? "";
        form.coproduzione = p?.coproduzione ?? "";
        form.DescrCausaleFattura = p?.DescrCausaleFattura ?? "";
        form.IndirizzoEmailFattura = p?.IndirizzoEmailFattura ?? "";
        form.IndirizzoEmailContatto = p?.IndirizzoEmailContatto ?? "";
        form.StatoProgetto = p?.StatoProgetto ?? "";
        form.Consigliere = p?.Consigliere ?? "";
        form.ImportGiornate = p?.ImportGiornate ?? "";
        form.Pranzo = p?.Pranzo ?? "";
        form.Cena = p?.Cena ?? "";
        form.Alloggio = p?.Alloggio ?? "";
        form.NNotti = p?.NNotti ?? "";
        form.Viaggio = p?.Viaggio ?? "";
        form.CUP = p?.CUP ?? "";
        form.NOrdineCup = p?.NOrdineCup ?? "";
        form.DtOrdineCup = toYMD(p?.DtOrdineCup);
        form.Titolo = p?.Titolo ?? "";
        form.Autore = p?.Autore ?? "";
        form.RegiaCoreografia = p?.RegiaCoreografia ?? "";
        form.NumRepliche = p?.NumRepliche ?? "";
        form.DataInzProgetto = toYMD(p?.DataInzProgetto);
        form.DataFineProgetto = toYMD(p?.DataFineProgetto);
        form.DataPagamento = toYMD(p?.DataPagamento);
        form.Note = p?.Note ?? "";
    } else {
        form.reset();
        form.CodCliente = props.codCliente || "";
        form.IDAttivita = props.idAttivita || "";
        form.RagioneSocialeCommittenti = clientiMap.value?.[form.CodCliente] ?? "";
    }
    form.clearErrors();

    // sync widget dopo aver impostato CodCliente
    const code = form.CodCliente;
    selectedCliente.value = code ? { value: code, label: clientiMap.value[code] ?? "" } : null;
}

// quando apro/modo cambiano/arriva project
watch(
    () => [props.show, props.editMode, props.project, props.codCliente, props.idAttivita],
    () => {
        if (props.show) fillForm(props.project);
    },
    { immediate: true }
);

const title = computed(
    () =>
        (props.editMode ? "Modifica Progetto" : "Nuovo Progetto") +
        (form.IdProg ? ` #${form.IdProg} ${form.DescrizioneProgetto}` : "")
);

function saveProject(closeAfter = false) {
    saving.value = true;

    const onSuccess = () => {
        toast.success("✅ Dati salvati con successo!");
        emit("saved");
        if (closeAfter) emit("close");
    };

    const common = {
        preserveScroll: true,
        onError: () => toast.error("❌ Errore durante il salvataggio!"),
        onFinish: () => (saving.value = false),
        onSuccess,
    };

    if (props.editMode) {
        const id = form.IdProg || props.project?.IdProg;
        form.put(route("progetti.update", id), common);
    } else {
        form.post(route("progetti.store"), common);
    }
}

/* ---------- ALLEGATI (scheda) ---------- */
const allegati = ref([]);
const uploading = ref(false);

function isAllowedFile(file) {
    const okExt = /\.(pdf|jpe?g)$/i.test(file.name);
    const okMime = ["application/pdf", "image/jpeg"].includes(file.type);
    // consenti se coincide estensione O MIME (alcuni browser non forniscono sempre mime corretto)
    return okExt || okMime;
}

async function loadAllegati() {
    const idProg = (form.IdProg || props.project?.IdProg || "").toString().trim();
    if (!idProg) {
        allegati.value = [];
        return;
    }

    let url = "";
    try {
        url = route("allegati.index", { idProg });
    } catch {
        url = `/allegati/${encodeURIComponent(idProg)}`;
    }

    try {
        const res = await fetch(url, {
            headers: { Accept: "application/json" },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        allegati.value = await res.json(); // atteso: [{id,nome,url,size,mime,created_at}]
    } catch (e) {
        console.error("Errore loadAllegati:", e);
        allegati.value = [];
        toast.error("❌ Impossibile caricare gli allegati");
    }
}

async function onUpload(e) {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;

    if (!isAllowedFile(file)) {
        toast.error("Ammessi solo file PDF o JPG");
        return;
    }

    const idProg = (form.IdProg || props.project?.IdProg || "").toString().trim();
    if (!idProg) {
        toast.error("IdProg mancante");
        return;
    }

    const data = new FormData();
    data.append("file", file);

    uploading.value = true;
    try {
        let url = "";
        try {
            url = route("allegati.store", { idProg });
        } catch {
            url = `/allegati/${encodeURIComponent(idProg)}`;
        }

        await axios.post(url, data, {
            headers: {
                "Content-Type": "multipart/form-data",
                Accept: "application/json",
            },
        });

        toast.success("✅ File caricato");
        await loadAllegati(); // ricarica elenco
    } catch (err) {
        console.error("Upload error:", err);
        toast.error("❌ Errore durante il caricamento");
    } finally {
        uploading.value = false;
    }
}

function apriAllegato(a) {
    // backend fornisce URL pubblico o rotta download

    window.open(a.url, "_blank");
}

async function eliminaAllegato(a) {
  if (!confirm("Eliminare il file?")) return;

  const url = (() => {
    try { return route("allegati.destroy", { allegato: a.id }); }
    catch { return `/allegati/${a.id}`; }
  })();

  try {
    await axios.delete(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
    allegati.value = allegati.value.filter(x => x.id !== a.id);
    toast.success("🗑️ Allegato eliminato");
  } catch (e) {
    console.error(e);
    toast.error("❌ Errore eliminazione");
  }
}

// carica elenco quando entro nel tab "allegati" o cambia IdProg
watch(
    () => activeTab.value,
    (t) => {
        if (t === "allegati") loadAllegati();
    }
);
watch(
    () => form.IdProg,
    () => {
        if (activeTab.value === "allegati") loadAllegati();
    }
);

// ================== GIORNATE (UNICA VERSIONE) ==================
const page = usePage();
const currentUser = computed(() => page.props?.auth?.user ?? null);
const isAdmin = computed(() => (currentUser.value?.profilo ?? "").toLowerCase() === "admin");

const giornate = ref([]);
const giornateLoading = ref(false);
const giornateError = ref("");
const giornateTableEl = ref(null);
let giornateTable = null;

function fmtIT(ymd) {
    if (!ymd) return "";
    const m = String(ymd).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return m ? `${m[3]}/${m[2]}/${m[1]}` : ymd;
}
function toNumberIT(v) {
    if (v === null || v === undefined || v === "") return 0;
    const s = String(v).replace(/\./g, "").replace(",", ".");
    const n = Number(s);
    return isNaN(n) ? 0 : n;
}
function parseAmount(raw) {
    // accetta numero o stringa "7.800,00"
    if (raw === null || raw === undefined || raw === "") return 0;
    if (typeof raw === "number") {
        let n = raw;
        // se sembra centesimi (es. 7800) converto in euro
        if (Number.isInteger(n) && Math.abs(n) >= 1000 && n % 100 === 0) n = n / 100;
        return n;
    }
    const s = String(raw).replace(/[^\d,\-\.]/g, ""); // lascia solo cifre e segni
    const n = Number(s.replace(/\./g, "").replace(",", "."));
    if (isNaN(n)) return 0;
    // euristica centesimi
    if (Number.isInteger(n) && Math.abs(n) >= 1000 && n % 100 === 0) return n / 100;
    return n;
}

const formatEuro = (n) =>
    new Intl.NumberFormat("it-IT", { style: "currency", currency: "EUR" }).format(Number(n || 0));

// Carica dalla vista "vistagiornate" filtrando per IdProg
async function loadGiornate() {
    giornateLoading.value = true;
    giornateError.value = "";

    const idProg = (form.IdProg || props.project?.IdProg || "").toString().trim();
    if (!idProg) {
        giornate.value = [];
        giornateLoading.value = false;
        if (giornateTable) giornateTable.setData(giornate.value);
        return;
    }

    try {
        let url = "";
        try {
            url = route("vistagiornate.index", { IdProg: idProg });
        } catch {
            url = `/vistagiornate?IdProg=${encodeURIComponent(idProg)}`;
        }

        const res = await fetch(url, {
            headers: { Accept: "application/json" },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const raw = await res.json();
        const rows = Array.isArray(raw) ? raw : raw?.data ?? [];

        // Campi richiesti
        const wanted = [
            "IdGiornate",
            "IdProg",
            "IDContratto",
            "CodCliente",
            "A_NomeVisualizzato",
            "Data",
            "Diaria",
            "Retribuzione",
            "CodiceAttivita",
        ];

        giornate.value = rows.map((g, i) => {
            const row = {};
            wanted.forEach((k) => {
                row[k] = g?.[k] ?? null;
            });

            const iso = toYMD(g?.Data);
            row.Data = iso; // ISO per sort
            row._rowId = row.IdGiornate ?? g?.id ?? `${idProg}-${i}`;
            row.Retribuzione = parseAmount(g?.Retribuzione);
            row.Diaria = Number(g?.Diaria ?? g?.DIARIA ?? 0);
            return row;
        });
    } catch (e) {
        console.error("Errore caricamento giornate:", e);
        giornateError.value = "Errore durante il caricamento delle giornate.";
        toast.error("❌ Errore caricamento giornate");
    } finally {
        giornateLoading.value = false;
        if (giornateTable) giornateTable.setData(giornate.value);
    }
}

// Crea/ricrea la griglia
function buildGiornateTable() {
    if (!giornateTableEl.value) return;
    if (giornateTable) {
        giornateTable.destroy();
        giornateTable = null;
    }

    giornateTable = new Tabulator(giornateTableEl.value, {
        data: giornate.value,
        index: "_rowId",
        height: "420px",
        layout: "fitColumns",
        placeholder: "Nessuna giornata",
        columns: [
            { title: "IdGiornate", field: "IdGiornate", hozAlign: "right" },
            {
                title: "Nome",
                field: "A_NomeVisualizzato",
                headerFilter: "input",
                widthShrink: 1,
            },
            { title: "IdProg", field: "IdProg" },
            { title: "IDContratto", field: "IDContratto" },
            { title: "CodCliente", field: "CodCliente", headerFilter: "input" },
            {
                title: "Data",
                field: "Data", // ISO YYYY-MM-DD per sort corretto
                sorter: "date",
                formatter: (cell) => fmtIT(cell.getValue()),
            },
            {
                title: "Diaria",
                field: "Diaria",
                hozAlign: "center",
                sorter: "number",
                formatter: function (cell) {
                    const val = parseInt(cell.getValue());
                    return val === -1 ? "✅" : "❌";
                },
            },
            {
                title: "Retribuzione",
                field: "Retribuzione",
                hozAlign: "right",
                width: 140,
                formatter: (cell) => formatEuro(cell.getValue()),
                bottomCalc: "sum",
                bottomCalcFormatter: (cell) => formatEuro(cell.getValue()),
            },
            {
                title: "Codice Attività",
                field: "CodiceAttivita",
                width: 140,
                headerFilter: "input",
            },
            ...(isAdmin.value
                ? [
                      {
                          title: "Azioni",
                          field: "actions",
                          hozAlign: "center",
                          width: 100,
                          formatter: () => "🗑️",
                          cellClick: (e, cell) => destroyGiornata(cell.getRow().getData()),
                      },
                  ]
                : []),
        ],
    });
}

// Elimina (usa IdGiornate come PK)
function buildDestroyUrl(row) {
    const id = row?.IdGiornate ?? row?._rowId;
    try {
        return route("giornate.destroy", id);
    } catch {
        return `/giornate/${encodeURIComponent(id)}`;
    }
}

function destroyGiornata(row) {
    const id = row?.IdGiornate ?? row?._rowId;
    if (!id) return;
    if (!isAdmin.value) return toast.error("Permesso negato");
    if (!confirm("Eliminare la giornata selezionata?")) return;

    const url = buildDestroyUrl(row);
    router.delete(url, {
        preserveScroll: true,
        onSuccess: () => {
            giornate.value = giornate.value.filter((r) => (r.IdGiornate ?? r._rowId) !== id);
            toast.success("🗑️ Giornata eliminata");
        },
        onError: () => toast.error("❌ Errore eliminazione"),
    });
}

// Entrando nel tab -> carica e costruisci la griglia UNA volta
watch(
    () => activeTab.value,
    async (t) => {
        if (t !== "giornate") return;
        await loadGiornate();
        buildGiornateTable();
    }
);

// Se cambia IdProg e sono nel tab -> ricarica i dati
watch(
    () => form.IdProg,
    async () => {
        if (activeTab.value === "giornate") await loadGiornate();
    }
);

// cleanup
onBeforeUnmount(() => {
    if (giornateTable) {
        giornateTable.destroy();
        giornateTable = null;
    }
});
// ================== FINE SEZIONE GIORNATE ==================
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
        <div
            class="relative bg-white w-[1100px] max-w-[95vw] h-[80vh] max-h-[95vh] rounded-2xl shadow-xl flex flex-col overflow-hidden"
        >
            <!-- HEADER / TOOLBAR -->
            <div class="px-4 py-3 border-b flex items-center gap-3">
                <div class="text-lg font-semibold truncate min-w-0">
                    {{ title }}
                </div>

                <div class="ml-auto inline-flex items-center gap-2 whitespace-nowrap">
                    <button type="button" class="px-3 py-1.5 rounded border shrink-0" @click="$emit('close')">
                        Chiudi
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded border shrink-0"
                        @click="saveProject(false)"
                        :disabled="saving"
                    >
                        Salva
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded bg-blue-600 text-white shrink-0"
                        @click="saveProject(true)"
                        :disabled="saving"
                    >
                        Salva & Chiudi
                    </button>
                </div>
            </div>

            <!-- TABS -->
            <div class="px-4 pt-3">
                <div class="flex gap-2 text-sm">
                    <button
                        v-for="t in [
                            { k: 'generali', l: 'Generali' },
                            { k: 'info', l: 'Info Progetto' },
                            { k: 'giornate', l: 'Giornate' },
                            { k: 'allegati', l: 'Elenco Allegati' },
                        ]"
                        :key="t.k"
                        type="button"
                        class="px-3 py-1.5 rounded-t border-b-0 border"
                        :class="activeTab === t.k ? 'bg-white border-gray-300 -mb-px' : 'bg-gray-100 hover:bg-gray-200 border-gray-200'"
                        @click="activeTab = t.k"
                    >
                        {{ t.l }}
                    </button>
                </div>
            </div>

            <!-- BODY -->
            <div class="px-4 pb-4 overflow-auto">
                <!-- TAB: GENERALI -->
                <div v-show="activeTab === 'generali'" class="mt-3 space-y-4" @keydown.enter.stop.prevent>
                    <input type="hidden" :value="form.CodCliente" />
                    <input type="hidden" :value="form.IDAttivita" />

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-3">
                            <label class="block text-xs font-semibold">IdProg</label>
                            <input
                                v-model="form.IdProg"
                                class="w-full border rounded px-2 py-1.5 bg-gray-100"
                                :readonly="true"
                                :disabled="!editMode"
                            />
                            <p v-if="form.errors.IdProg" class="text-xs text-red-600 mt-1">
                                {{ form.errors.IdProg }}
                            </p>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs font-semibold">IdProgetto</label>
                            <input
                                v-model="form.IdProgetto"
                                class="w-full border rounded px-2 py-1.5 bg-gray-100"
                                :readonly="true"
                                :disabled="!editMode"
                            />
                            <p v-if="form.errors.IdProgetto" class="text-xs text-red-600 mt-1">
                                {{ form.errors.IdProgetto }}
                            </p>
                        </div>

                        <!-- Cliente (AutoComplete: mostra nome, salva codice) -->
                        <div class="col-span-9">
                            <label class="block text-xs font-semibold">Cliente</label>
                            <AutoComplete
                                v-model="selectedCliente"
                                :suggestions="clientiSuggerimenti"
                                optionLabel="label"
                                :dropdown="true"
                                :forceSelection="true"
                                :class="[
                                    'w-full',
                                    { 'p-invalid border-red-500': !!form.errors.CodCliente || (!selectedCliente && !form.CodCliente) },
                                ]"
                                placeholder="Digita le iniziali del cliente…"
                                @complete="completaClienti"
                            />
                            <p v-if="form.errors.CodCliente" class="text-xs text-red-600 mt-1">
                                {{ form.errors.CodCliente }}
                            </p>
                        </div>

                        <!-- Tipologia Simulatore -->
                        <div class="col-span-12">
                            <label class="block text-sm font-medium mb-1">Tipologia Simulatore</label>
                            <input
                                v-model="form.TipologiaSimulatore"
                                class="w-full border rounded px-2 py-1.5"
                                placeholder="Tipologia Simulatore"
                            />
                            <p v-if="form.errors.TipologiaSimulatore" class="text-xs text-red-600 mt-1">
                                {{ form.errors.TipologiaSimulatore }}
                            </p>
                        </div>

                        <!-- Ragione Sociale (auto-compilata) -->
                        <div class="col-span-12">
                            <label class="block text-sm font-medium mb-1">Ragione Sociale (auto)</label>
                            <input
                                v-model="form.RagioneSocialeCommittenti"
                                class="w-full border rounded-lg p-2 bg-gray-50"
                                readonly
                            />
                        </div>

                        <!-- Descrizione -->
                        <div class="col-span-12">
                            <label class="block text-sm font-medium mb-1">Descrizione Progetto</label>
                            <textarea
                                v-model.trim="form.DescrizioneProgetto"
                                rows="3"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.DescrizioneProgetto }"
                                placeholder="Breve descrizione/accordi…"
                            />
                            <p v-if="form.errors.DescrizioneProgetto" class="text-red-600 text-xs mt-1">
                                {{ form.errors.DescrizioneProgetto }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Data Inizio</label>
                            <FlatPickr
                                v-model="form.DataInzProgetto"
                                :config="FormatoData"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.DataInzProgetto }"
                            />
                            <p v-if="form.errors.DataInzProgetto" class="text-red-600 text-xs mt-1">
                                {{ form.errors.DataInzProgetto }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Data Fine</label>
                            <FlatPickr
                                v-model="form.DataFineProgetto"
                                :config="FormatoData"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.DataFineProgetto }"
                            />
                            <p v-if="form.errors.DataFineProgetto" class="text-red-600 text-xs mt-1">
                                {{ form.errors.DataFineProgetto }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Data Pagamento</label>
                            <FlatPickr
                                v-model="form.DataPagamento"
                                :config="FormatoData"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.DataPagamento }"
                            />
                            <p v-if="form.errors.DataPagamento" class="text-red-600 text-xs mt-1">
                                {{ form.errors.DataPagamento }}
                            </p>
                        </div>
                    </div>

                    <!-- Accordi -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Accordi</label>
                            <input
                                v-model.trim="form.Accordi"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.Accordi }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Percentuale</label>
                            <input
                                v-model.trim="form.Percentuale"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.Percentuale }"
                                placeholder="%"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Giorni Pagamento</label>
                            <input
                                v-model.trim="form.GGPag"
                                type="number"
                                inputmode="numeric"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.GGPag }"
                                placeholder="30"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Data Emissione Prev Fattura</label>
                            <FlatPickr
                                v-model="form.DataEmissionePrevFattura"
                                :config="FormatoData"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.DataEmissionePrevFattura }"
                            />
                            <p v-if="form.errors.DataEmissionePrevFattura" class="text-red-600 text-xs mt-1">
                                {{ form.errors.DataEmissionePrevFattura }}
                            </p>
                        </div>
                    </div>

                    <!-- Importo/IVA/Esenzione -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Importo Netto Concordato</label>
                            <input
                                v-model="formattedImporto"
                                type="text"
                                class="w-full border rounded-lg p-2 text-right"
                                :class="{ 'border-red-500': form.errors.ImportoNettoConcordato }"
                            />
                            <p v-if="form.errors.ImportoNettoConcordato" class="text-xs text-red-600 mt-1">
                                {{ form.errors.ImportoNettoConcordato }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Aliquota IVA</label>
                            <input
                                v-model.trim="form.AliquotaIVA"
                                type="number"
                                step="0.01"
                                inputmode="decimal"
                                class="w-full border rounded-lg p-2 text-right"
                                :class="{ 'border-red-500': form.errors.AliquotaIVA }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Esenzione IVA</label>
                            <input
                                v-model.trim="form.EsenzioneIva"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.EsenzioneIva }"
                            />
                        </div>
                    </div>

                    <!-- CIG/Coproduzione/Descr. Causale -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">CIG</label>
                            <input
                                v-model.trim="form.CIG"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.CIG }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Coproduzione</label>
                            <input
                                v-model.trim="form.coproduzione"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.coproduzione }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Descr. Causale Fattura</label>
                            <input
                                v-model.trim="form.DescrCausaleFattura"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.DescrCausaleFattura }"
                            />
                        </div>
                    </div>

                    <!-- Email fattura/contatto -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Indirizzo Email Fattura</label>
                            <input
                                v-model.trim="form.IndirizzoEmailFattura"
                                type="email"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.IndirizzoEmailFattura }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Indirizzo Email Contatto</label>
                            <input
                                v-model.trim="form.IndirizzoEmailContatto"
                                type="email"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.IndirizzoEmailContatto }"
                            />
                        </div>
                    </div>

                    <!-- Stato/Consigliere/Giornate -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Stato Ordine</label>
                            <input
                                v-model.trim="form.StatoProgetto"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.StatoProgetto }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Consigliere</label>
                            <input
                                v-model.trim="form.Consigliere"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.Consigliere }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Giornate Importate</label>
                            <input
                                v-model.trim="form.ImportGiornate"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.ImportGiornate }"
                            />
                        </div>
                    </div>

                    <!-- Rimborsi/logistica -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Pranzo</label>
                            <input
                                v-model.trim="form.Pranzo"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.Pranzo }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Cena</label>
                            <input
                                v-model.trim="form.Cena"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.Cena }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Alloggio</label>
                            <input
                                v-model.trim="form.Alloggio"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.Alloggio }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">N. Notti</label>
                            <input
                                v-model.trim="form.NNotti"
                                type="number"
                                inputmode="numeric"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.NNotti }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Viaggio</label>
                            <input
                                v-model.trim="form.Viaggio"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.Viaggio }"
                            />
                        </div>
                    </div>

                    <!-- CUP/N. Ordine/Data Ordine -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">CUP</label>
                            <input
                                v-model.trim="form.CUP"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.CUP }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">N. Ordine</label>
                            <input
                                v-model.trim="form.NOrdineCup"
                                type="text"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.NOrdineCup }"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Data Ordine</label>
                            <FlatPickr
                                v-model="form.DtOrdineCup"
                                :config="FormatoData"
                                class="w-full border rounded-lg p-2"
                                :class="{ 'border-red-500': form.errors.DtOrdineCup }"
                            />
                            <p v-if="form.errors.DtOrdineCup" class="text-red-600 text-xs mt-1">
                                {{ form.errors.DtOrdineCup }}
                            </p>
                        </div>
                    </div>

                    <div class="col-span-12">
                        <label class="block text-xs font-semibold">Note</label>
                        <textarea v-model="form.Note" rows="3" class="w-full border rounded px-2 py-1.5"></textarea>
                        <p v-if="form.errors.Note" class="text-xs text-red-600 mt-1">
                            {{ form.errors.Note }}
                        </p>
                    </div>
                </div>

                <!-- TAB: INFO PROGETTO -->
                <div v-show="activeTab === 'info'" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-4" @keydown.enter.stop.prevent>
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium mb-1">Titolo</label>
                        <input
                            v-model.trim="form.Titolo"
                            type="text"
                            class="w-full border rounded-lg p-2"
                            :class="{ 'border-red-500': form.errors.Titolo }"
                        />
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium mb-1">Autore</label>
                        <input
                            v-model.trim="form.Autore"
                            type="text"
                            class="w-full border rounded-lg p-2"
                            :class="{ 'border-red-500': form.errors.Autore }"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Regia</label>
                        <input
                            v-model.trim="form.RegiaCoreografia"
                            type="text"
                            class="w-full border rounded-lg p-2"
                            :class="{ 'border-red-500': form.errors.RegiaCoreografia }"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">N. Repliche</label>
                        <input
                            v-model.trim="form.NumRepliche"
                            type="text"
                            class="w-full border rounded-lg p-2"
                            :class="{ 'border-red-500': form.errors.NumRepliche }"
                        />
                    </div>
                </div>

                <!-- TAB: GIORNATE -->
                <div v-show="activeTab === 'giornate'" class="mt-3 space-y-3">
                    <!-- Header / Info contesto -->
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            <span class="font-semibold">Giornate</span>
                            <span v-if="form.IdProg || (project && project.IdProg)" class="ml-2">
                                (IdProg: {{ form.IdProg || project?.IdProg }})
                            </span>
                        </div>

                        <!-- Toolbar -->
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded border"
                                :disabled="giornateLoading"
                                @click="loadGiornate"
                                title="Ricarica elenco"
                            >
                                🔄 Aggiorna
                            </button>

                            <span class="text-sm text-gray-600" v-if="!giornateLoading">
                                {{ giornate.length }} record
                            </span>
                            <span class="text-sm text-gray-500" v-if="giornateLoading"> Caricamento… </span>
                        </div>
                    </div>

                    <!-- Messaggi -->
                    <div v-if="giornateError" class="text-sm text-red-600">
                        {{ giornateError }}
                    </div>

                    <!-- Griglia Tabulator -->
                    <div ref="giornateTableEl" class="w-full"></div>

                    <!-- Empty state (se Tabulator è vuoto e non sta caricando) -->
                    <div
                        v-if="!giornateLoading && !giornate.length"
                        class="text-center text-gray-500 text-sm py-3 border rounded"
                    >
                        Nessuna giornata trovata per l’IdProg selezionato.
                    </div>
                </div>

                <!-- TAB: ELENCO ALLEGATI -->
                <div v-show="activeTab === 'allegati'" class="mt-3 space-y-3">
                    <div class="flex items-center gap-3">
                        <input type="file" @change="onUpload" accept=".pdf,.jpg,.jpeg" />
                        <span v-if="uploading" class="text-sm text-gray-500">Caricamento…</span>
                    </div>

                    <div class="overflow-auto border rounded">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="p-2 border">Nome File</th>
                                    <th class="p-2 border w-40 text-center">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="a in allegati" :key="a.id">
                                    <td class="p-2 border">{{ a.nome }}</td>
                                    <td class="p-2 border text-center">
                                        <button type="button" class="px-2 py-1 border rounded" @click="apriAllegato(a)">
                                            Apri
                                        </button>
                                        <button
                                            type="button"
                                            class="ml-2 px-2 py-1 bg-red-600 text-white rounded"
                                            @click="eliminaAllegato(a)"
                                        >
                                            Cancella
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!allegati.length">
                                    <td colspan="2" class="text-center text-gray-500 py-3">Nessun allegato</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
