<script setup>
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Italian } from "flatpickr/dist/l10n/it.js";
import { Save, X } from "lucide-vue-next";
import { watch, computed, ref, onMounted, nextTick } from "vue";
import { useToast } from "vue-toastification";

const toast = useToast();
const page = usePage();
const titleInput = ref(null);
onMounted(async () => {
  await nextTick(); // aspetta che il DOM sia pronto
  titleInput.value?.focus();
});
const props = defineProps({
    clients: { type: Array, default: () => [] }, // [{id, name}]
    prefill: { type: Object, default: () => ({}) }, // { DataInizio, DataConsegna, DataFine }
});

// -----------------------------
// Focus Next (Enter)
// -----------------------------
const focusNext = (event) => {
    event.preventDefault();

    const formEl = event.target.form;
    if (!formEl) return;

    const focusable = Array.from(
        formEl.querySelectorAll(
            'input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])'
        )
    ).filter((el) => el.offsetParent !== null);

    const index = focusable.indexOf(event.target);
    if (index > -1 && focusable[index + 1]) {
        focusable[index + 1].focus();
    }
};

// -----------------------------
// Items helpers
// -----------------------------
function newItem() {
    return {
        uid: crypto?.randomUUID
            ? crypto.randomUUID()
            : String(Date.now() + Math.random()),
        prodotto: "",
        colore: "",
        descrizione: "",
        Lotto: "",

        pezzi: 0,

        taglio: false,
        assemblaggio: false,
        comandi: false,

        taglio_zoccolo: false,
        taglio_lamelle: false,
        montaggio_lamelle: false,
        Accessori: false,
        Coprifili: false,
        Fermavetri: false,
        Ferramenta: false,
        Vetratura: false,
        OrdineVetri: false,
    };
}

function addItem() {
    form.items.push(newItem());
}

function removeItem(uid) {
    const idx = form.items.findIndex((x) => x.uid === uid);
    if (idx === -1) return;
    form.items.splice(idx, 1);
}

// -----------------------------
// Default DataInizio da query string (?DataInizio=YYYY-MM-DD)
// -----------------------------
const prefillDataInizio = computed(() => {
    const q = page?.props?.ziggy?.query || {};
    // se non usi Ziggy query, prova anche:
    // const url = new URL(window.location.href); url.searchParams.get("DataInizio")
    return q.DataInizio || "";
});

// -----------------------------
// Icona magazzino (header)
// -----------------------------
const magazzinoIcon = computed(() => {
    switch (form.StatoMagazzino) {
        case "Ordinato":
            return "🚚";
        case "Arrivato":
            return "✅";
        case "In ritardo":
            return "❌";
        case "Magazzino":
        default:
            return "📦";
    }
});

// -----------------------------
// Form
// -----------------------------
const form = useForm({
    title: "",
    description: "",
    DataInizio: props.prefill?.DataInizio ?? "",
    DataConsegna:
        props.prefill?.DataConsegna ?? props.prefill?.DataInizio ?? "",
    DataFine: props.prefill?.DataFine ?? props.prefill?.DataInizio ?? "",
    DataConferma:
        props.prefill?.DataConferma ?? props.prefill?.DataConferma ?? "",

    status: "Da Pianificare",
    StatoMagazzino: "Magazzino",

    Nordine: null,
    Colore: "",
    Riferimento: "",
    Pezzi: "",

    Annotazioni: "",

    items: [newItem()],
});
function isValidDateYmd(val) {
    // accetta "YYYY-MM-DD" o "YYYY-MM-DD HH:mm:ss"
    if (!val) return false;
    const s = String(val).trim();
    return /^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}:\d{2})?$/.test(s);
}

const submit = () => {
    // ✅ REGOLA IN SALVATAGGIO:
    // se DataInizio valida e status è Da Pianificare -> Pianificato
    if (isValidDateYmd(form.DataInizio) && form.status === "Da Pianificare") {
        form.status = "Pianificato";
    }
    form.post(route("appointments.store"), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success("✅ Ordine creato con successo!", {
                position: "top-left",
            });
            // se vuoi restare in edit dopo creazione, lo fai nel controller con redirect
            // qui lasciamo com’è
        },
        onError: () => {
            toast.error("Errore nella creazione dell'ordine", {
                position: "top-left",
            });
        },
    });
};
watch(
    () => form.DataInizio,
    (newVal) => {
        if (newVal && String(form.status).toLowerCase() === "da pianificare") {
            form.status = "Pianificato";
        }
    }
);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Nuovo Ordine" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6"
                >
                    <form @submit.prevent="submit">
                        <!-- Header -->
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6"
                        >
                            <h2
                                class="text-2xl font-semibold flex items-center gap-2"
                            >
                                <span class="text-2xl">{{
                                    magazzinoIcon
                                }}</span>
                                Nuovo Ordine
                            </h2>

                            <div
                                class="flex flex-col md:flex-row justify-end items-stretch md:items-center gap-3"
                            >
                                <Link
                                    :href="route('appointments.calendar')"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
                                >
                                    <X class="w-4 h-4" />
                                    Chiudi
                                </Link>

                                <Link
                                    :href="route('appointments.calendar')"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
                                >
                                    <X class="w-4 h-4" />
                                    Annulla
                                </Link>

                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                >
                                    <Save class="w-4 h-4" />
                                    Salva
                                </button>
                            </div>
                        </div>

                        <!-- Dati ordine / riferimento -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                            <div class="md:col-span-2">
                                <label
                                    class="block text-sm font-semibold text-gray-800"
                                    >Titolo</label
                                >
                                <input
                                ref="titleInput"
                                    v-model="form.title"
                                    @keydown.enter.prevent="focusNext"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                    placeholder="Es. ORD 1234 - Rossi"
                                />
                                <p
                                    v-if="form.errors.title"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.title }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-800"
                                    >N° Ordine</label
                                >
                                <input
                                    v-model="form.Nordine"
                                    @keydown.enter.prevent="focusNext"
                                    type="number"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                    placeholder="Es. 12345"
                                />
                                <p
                                    v-if="form.errors.Nordine"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.Nordine }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-800"
                                    >Riferimento</label
                                >
                                <input
                                    v-model="form.Riferimento"
                                    @keydown.enter.prevent="focusNext"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                    placeholder="Es. Commessa / Referente"
                                />
                                <p
                                    v-if="form.errors.Riferimento"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.Riferimento }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="status"
                                    class="block text-sm font-semibold text-gray-800"
                                >
                                    Avanzamento Produzione
                                </label>
                                <select
                                    v-model="form.status"
                                    id="status"
                                    @keydown.enter.prevent="focusNext"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                >
                                    <option value="Da Pianificare">
                                        Da Pianificare
                                    </option>
                                    <option value="Pianificato">
                                        Pianificato
                                    </option>
                                    <option value="Completato">
                                        Completato
                                    </option>
                                    <option value="Sospeso">Sospeso</option>
                                    <option value="Cancellato">
                                        Cancellato
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.status"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-800"
                                    >Stato Merce Magazzino</label
                                >
                                <select
                                    v-model="form.StatoMagazzino"
                                    @keydown.enter.prevent="focusNext"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                >
                                    <option value="Magazzino">Magazzino</option>
                                    <option value="Ordinato">Ordinato</option>
                                    <option value="Arrivato">Arrivato</option>
                                    <option value="In ritardo">
                                        In ritardo
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.StatoMagazzino"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.StatoMagazzino }}
                                </p>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                            <div @keydown.enter.prevent="focusNext">
                                <label
                                    class="block text-sm font-semibold text-gray-800"
                                    >Data Conferma</label
                                >
                                <flat-pickr
                                    v-model="form.DataConferma"
                                    :config="{
                                        enableTime: false,
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        dateFormat: 'Y-m-d',
                                        locale: Italian,
                                        allowInput: true,
                                    }"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                />
                                <p
                                    v-if="form.errors.DataConferma"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.DataConferma }}
                                </p>
                            </div>

                            <div @keydown.enter.prevent="focusNext">
                                <label
                                    class="block text-sm font-semibold text-gray-800"
                                    >Data Consegna</label
                                >
                                <flat-pickr
                                    v-model="form.DataConsegna"
                                    :config="{
                                        enableTime: false,
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        dateFormat: 'Y-m-d',
                                        locale: Italian,
                                        allowInput: true,
                                        minDate: form.DataConferma || null,
                                    }"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                />
                                <p
                                    v-if="form.errors.DataConsegna"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.DataConsegna }}
                                </p>
                            </div>

                            <div @keydown.enter.prevent="focusNext">
                                <label
                                    class="block text-sm font-semibold text-gray-800"
                                    >Data Inizio Produzione</label
                                >
                                <flat-pickr
                                    v-model="form.DataInizio"
                                    :config="{
                                        enableTime: false,
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        dateFormat: 'Y-m-d',
                                        locale: Italian,
                                        allowInput: true,
                                    }"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                />
                                <p
                                    v-if="form.errors.DataInizio"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.DataInizio }}
                                </p>
                            </div>

                            <div @keydown.enter.prevent="focusNext">
                                <label
                                    class="block text-sm font-semibold text-gray-800"
                                    >Data Fine Produzione</label
                                >
                                <flat-pickr
                                    v-model="form.DataFine"
                                    :config="{
                                        enableTime: false,
                                        altInput: true,
                                        altFormat: 'd/m/Y',
                                        dateFormat: 'Y-m-d',
                                        locale: Italian,
                                        allowInput: true,
                                        minDate: form.DataInizio || null,
                                    }"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                />
                                <p
                                    v-if="form.errors.DataFine"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.DataFine }}
                                </p>
                            </div>
                        </div>

                        <!-- Prodotti -->
                        <div class="flex items-center justify-between mt-6">
                            <h3 class="font-semibold">Prodotti</h3>

                            <button
                                type="button"
                                @click="addItem"
                                class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm"
                            >
                                + Aggiungi prodotto
                            </button>
                        </div>

                        <div
                            v-for="(it, i) in form.items"
                            :key="it.uid"
                            class="mt-4 p-4 rounded-lg border bg-gray-50"
                        >
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-4">
                                    <label class="text-sm font-semibold"
                                        >Prodotto</label
                                    >
                                    <select
                                        v-model="it.prodotto"
                                        class="mt-1 w-full rounded-lg border"
                                    >
                                        <option value="">— Seleziona —</option>
                                        <option value="PAF">
                                            Persiane Fisse
                                        </option>
                                        <option value="PA">Persiane</option>
                                        <option value="SG">Sghembi</option>
                                        <option value="AR">Archi</option>
                                        <option value="SC">Scuroni</option>
                                        <option value="CA">
                                            Cover Alluminio
                                        </option>
                                        <option value="IA">
                                            Infissi Alluminio
                                        </option>
                                    </select>
                                </div>

                                <div class="col-span-6 md:col-span-3">
                                    <label class="text-sm font-semibold"
                                        >Colore</label
                                    >
                                    <input
                                        v-model="it.colore"
                                        class="mt-1 w-full rounded-lg border"
                                    />
                                </div>

                                <div class="col-span-6 md:col-span-2">
                                    <label class="text-sm font-semibold"
                                        >N. Pezzi</label
                                    >
                                    <input
                                        v-model.number="it.pezzi"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="mt-1 w-full rounded-lg border"
                                    />
                                </div>
<div class="col-span-6 md:col-span-1">
                                    <label class="text-sm font-semibold"
                                        >Lotto</label
                                    >
                                    <input
                                        v-model="it.Lotto"
                                        class="mt-1 w-full rounded-lg border"
                                    />
                                </div>
                                <div
                                    class="col-span-12 md:col-span-2 flex md:justify-end items-end"
                                >
                                    <button
                                        type="button"
                                        @click="removeItem(it.uid)"
                                        class="px-3 py-2 rounded-lg bg-red-600 text-white text-sm"
                                    >
                                        Elimina
                                    </button>
                                </div>
                            </div>

                            <!-- Opzioni riga -->
                            <div
                                class="mt-3 grid grid-cols-2 md:grid-cols-6 gap-3 text-sm"
                            >
                                <template
                                    v-if="
                                        it.prodotto === 'SC' ||
                                        it.prodotto === 'CA'
                                    "
                                >
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio"
                                        />
                                        Taglio
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.assemblaggio"
                                        />
                                        Assemblaggio
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Coprifili"
                                        />
                                        Coprifili
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Accessori"
                                        />
                                        Accessori
                                    </label>
                                </template>
                                <template v-else-if="it.prodotto === 'PAF'">
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio"
                                        />
                                        Taglio
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio_zoccolo"
                                        />
                                        Taglio Zoccolo
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio_lamelle"
                                        />
                                        Taglio Lamelle
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.assemblaggio"
                                        />
                                        Assemblaggio
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.comandi"
                                        />
                                        Montaggio Comandi
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.montaggio_lamelle"
                                        />
                                        Montaggio Lamelle
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Coprifili"
                                        />
                                        Coprifili
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Accessori"
                                        />
                                        Accessori
                                    </label>
                                </template>

                                <template
                                    v-else-if="
                                        it.prodotto === 'AR' ||
                                        it.prodotto === 'SG' ||
                                        it.prodotto === 'PA'
                                    "
                                >
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio"
                                        />
                                        Taglio
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio_zoccolo"
                                        />
                                        Taglio Zoccolo
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio_lamelle"
                                        />
                                        Taglio Lamelle
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.assemblaggio"
                                        />
                                        Assemblaggio
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.comandi"
                                        />
                                        Montaggio Comandi
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.montaggio_lamelle"
                                        />
                                        Montaggio Lamelle
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Coprifili"
                                        />
                                        Coprifili
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Accessori"
                                        />
                                        Accessori
                                    </label>
                                </template>

                                <template v-else-if="it.prodotto === 'IA'">
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio"
                                        />
                                        Taglio
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.assemblaggio"
                                        />
                                        Assemblaggio
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.ferramenta"
                                        />
                                        Ferramenta
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Fermavetri"
                                        />
                                        Fermavetri
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.vetratura"
                                        />
                                        Vetratura
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Coprifili"
                                        />
                                        Coprifili
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.Accessori"
                                        />
                                        Accessori
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.OrdineVetri"
                                        />
                                        Ordine Vetri
                                    </label>
                                </template>
                            </div>

                            <div class="mt-3">
                                <label class="text-sm font-semibold"
                                    >Descrizione riga</label
                                >
                                <textarea
                                    v-model="it.descrizione"
                                    class="mt-1 w-full rounded-lg border"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Annotazioni -->
                        <div class="mb-6 mt-6">
                            <label
                                class="block text-sm font-semibold text-gray-800"
                                >Annotazioni</label
                            >
                            <textarea
                                v-model="form.Annotazioni"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
                                placeholder="Note interne..."
                            ></textarea>
                            <p
                                v-if="form.errors.Annotazioni"
                                class="mt-2 text-sm text-red-600"
                            >
                                {{ form.errors.Annotazioni }}
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
