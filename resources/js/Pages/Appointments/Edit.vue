<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Italian } from "flatpickr/dist/l10n/it.js";
import { Trash2, Save, X } from "lucide-vue-next";
import { watch, computed, ref, onBeforeUnmount } from "vue";
import { useToast } from "vue-toastification";
const toast = useToast();

const confirmDelete = ref(false);
const isDeleting = ref(false);
let confirmTimer = null;

const focusNext = (event) => {
    event.preventDefault();

    const form = event.target.form;
    if (!form) return;

    const focusable = Array.from(
        form.querySelectorAll(
            'input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])'
        )
    ).filter((el) => el.offsetParent !== null);

    const index = focusable.indexOf(event.target);
    if (index > -1 && focusable[index + 1]) {
        focusable[index + 1].focus();
    }
};

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
        Ferramenta: false,
        Vetratura: false,
        Accessori: false,
        Coprifili: false,
        Fermavetri: false,
        OrdineVetri: false,

        _confirmDelete: false,
        _isDeleting: false,
        _confirmTimer: null,
    };
}

function addItem() {
    form.items.push(newItem());
}

function removeItem(uid) {
    const idx = form.items.findIndex((x) => x.uid === uid);
    if (idx === -1) return;

    const it = form.items[idx];

    // 1️⃣ primo click → conferma
    if (!it._confirmDelete) {
        it._confirmDelete = true;

        it._confirmTimer = setTimeout(() => {
            it._confirmDelete = false;
        }, 3000);

        return;
    }

    // 2️⃣ secondo click → rimuove
    clearTimeout(it._confirmTimer);
    it._isDeleting = true;

    // rimozione immediata (solo frontend)
    form.items.splice(idx, 1);
}

const magazzinoIcon = computed(() => {
    switch (form.StatoMagazzino) {
        case "Ordinato":
            return "🚚";
        case "Arrivato":
            return "✅";
        case "In ritardo":
            return "❌";
        default:
            return "✅"; // Magazzino
    }
});
const props = defineProps({
    appointment: Object,
    clients: Array,
});

const toLocalInput = (value) => {
    if (!value) return "";
    const d = new Date(value);
    // yyyy-mm-ddThh:mm (HTML datetime-local style)
    return d.toISOString().slice(0, 16);
};

const formatDateForMySQL = (value) => {
    if (!value) return null;
    const date = new Date(value);
    return date.toISOString().slice(0, 19).replace("T", " ");
};

const form = useForm({
    // Campi esistenti
    title: props.appointment.title ?? "",
    description: props.appointment.description ?? "",
    DataInizio: props.appointment.DataInizio
        ? String(props.appointment.DataInizio).substring(0, 10)
        : "",
    DataFine: props.appointment.DataFine
        ? String(props.appointment.DataFine).substring(0, 10)
        : "",
    DataConferma: props.appointment.DataConferma
        ? String(props.appointment.DataConferma).substring(0, 10)
        : "",
    DataConsegna: props.appointment.DataConsegna
        ? String(props.appointment.DataConsegna).substring(0, 10)
        : "",

    status: props.appointment.status ?? "Da Pianificare",
    StatoMagazzino: props.appointment.StatoMagazzino ?? "Magazzino",

    // Nuovi campi
    Nordine: props.appointment.Nordine
        ? Number(props.appointment.Nordine)
        : null,

    Colore: props.appointment.Colore ?? "",
    Riferimento: props.appointment.Riferimento ?? "",
    Pezzi: props.appointment.Pezzi ?? "",

    Annotazioni: props.appointment.Annotazioni ?? "",

    items:
        Array.isArray(props.appointment.items) && props.appointment.items.length
            ? props.appointment.items.map((x) => ({
                  uid: crypto?.randomUUID
                      ? crypto.randomUUID()
                      : String(Date.now() + Math.random()),
                  prodotto: x.Prodotto ?? "",
                  colore: x.Colore ?? "",
                  descrizione: x.Descrizione ?? "",
                  Lotto: x.Lotto ?? "",

                  pezzi: Number(x.Pezzi ?? 0),
                  taglio: !!x.Taglio,
                  assemblaggio: !!x.Assemblaggio,
                  comandi: !!x.Comandi,
                  taglio_zoccolo: !!x.TaglioZoccolo,
                  taglio_lamelle: !!x.TaglioLamelle,
                  montaggio_lamelle: !!x.MontaggioLamelle,
                  Ferramenta: !!x.Ferramenta,
                  Fermavetri: !!x.Fermavetri,
                  Vetratura: !!x.Vetratura,
                  Coprifili: !!x.Coprifili,
                  Accessori: !!x.Accessori,
                  OrdineVetri: !!x.OrdineVetri,

                  _confirmDelete: false,
                  _isDeleting: false,
                  _confirmTimer: null,
              }))
            : [newItem()],
});

const submit = () => {
    form.put(route("appointments.update", props.appointment.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success("✅ Dati salvati con successo!", {
                position: "top-left",
            });
        },
        onError: () =>
            toast.error("Errore nel salvataggio", { position: "top-left" }),
    });
};
const deleteAppointment = () => {
    // 1° click: attiva conferma (senza popup browser)
    if (!confirmDelete.value) {
        confirmDelete.value = true;

        // reset automatico dopo 3 secondi
        confirmTimer = setTimeout(() => {
            confirmDelete.value = false;
        }, 3000);

        return;
    }

    // 2° click: elimina davvero
    clearTimeout(confirmTimer);
    isDeleting.value = true;

    router.delete(route("appointments.destroy", props.appointment.id), {
        onSuccess: () => {
            window.location.href = route("appointments.calendar");
        },
        onError: () => {
            toast?.error?.("Errore durante l'eliminazione.", {
                position: "top-left",
            });
        },
        onFinish: () => {
            isDeleting.value = false;
            confirmDelete.value = false;
        },
    });
};

onBeforeUnmount(() => {
    if (confirmTimer) clearTimeout(confirmTimer);
});
function todayYmd() {
    const d = new Date();
    return d.toISOString().slice(0, 10);
}

function setAllItemChecks(value = true) {
    (form.items || []).forEach((it) => {
        it.taglio = value;
        it.assemblaggio = value;
        it.comandi = value;

        it.taglio_zoccolo = value;
        it.taglio_lamelle = value;
        it.montaggio_lamelle = value;

        it.Ferramenta = value;
        it.Vetratura = value;
        it.Accessori = value;
        it.Coprifili = value;
        it.Fermavetri = value;
        it.OrdineVetri = value;
    });
}

watch(
    () => form.status,
    (newVal, oldVal) => {

        // 🔥 SOLO cambio manuale
        if (newVal === "Completato" && oldVal !== "Completato") {

            // Data Fine Produzione = oggi
            form.DataFine = todayYmd();

            // tutti i check = true
            setAllItemChecks(true);
        }
    }
);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Appuntamento" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6"
                >
                    <form @submit.prevent="submit">
                        <!-- Titolo -->
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6"
                        >
                            <!-- Titolo -->

                            <h2
                                class="text-2xl font-semibold flex items-center gap-2"
                            >
                                <span class="text-2xl">{{
                                    magazzinoIcon
                                }}</span>
                                Dettaglio Ordine: {{ appointment.title }}
                            </h2>

                            <div class="w-full md:w-56">
                                <div
                                    class="flex flex-col md:flex-row justify-end items-stretch md:items-center gap-4 mt-6"
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

                                    <button
                                        type="button"
                                        @click="deleteAppointment"
                                        :disabled="isDeleting"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg text-white disabled:opacity-50"
                                        :class="[
                                            isDeleting
                                                ? 'bg-red-400 cursor-not-allowed'
                                                : confirmDelete
                                                ? 'bg-red-700 hover:bg-red-800'
                                                : 'bg-red-600 hover:bg-red-700',
                                        ]"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                        {{
                                            isDeleting
                                                ? "Eliminazione..."
                                                : confirmDelete
                                                ? "⚠️ Conferma"
                                                : "Elimina"
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Dati ordine / riferimento -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Avanzamento Produzione
                                </label>
                                <select
                                    v-model="form.status"
                                    @keydown.enter.prevent="focusNext"
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    class="block text-xs font-semibold text-gray-600 mb-1"
                                >
                                    Stato Merce Magazzino
                                </label>

                                <select
                                    v-model="form.StatoMagazzino"
                                    @keydown.enter.prevent="focusNext"
                                    class="block w-full rounded-lg border border-gray-300 shadow-sm focus:ring focus:ring-blue-300 focus:border-blue-500 text-sm"
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
                                    class="mt-1 text-xs text-red-600"
                                >
                                    {{ form.errors.StatoMagazzino }}
                                </p>
                            </div>
                        </div>

                        <!-- Date principali + stato -->
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
                                        minDate: form.DataConferma,
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
                                        minDate: form.DataInizio,
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

                        <!-- Date conferma / consegna + pezzi -->

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
                            :key="i"
                            class="mt-4 p-4 rounded-lg border bg-gray-50"
                        >
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-6 md:col-span-4">
                                    <label class="text-sm font-semibold"
                                        >Prodotto</label
                                    >
                                    <select
                                        v-model="it.prodotto"
                                        @keydown.enter.prevent="focusNext"
                                        class="mt-1 w-full rounded-lg border"
                                    >
                                        <option value="">— Seleziona —</option>
                                        <option value="AR">Archi</option>
                                        <option value="CP">Coprifili</option>
                                        <option value="CA">
                                            Cover Alluminio
                                        </option>
                                        <option value="IA">
                                            Infissi Alluminio
                                        </option>
                                        <option value="PAF">
                                            Persiane Fisse
                                        </option>
                                        <option value="PA">Persiane</option>
                                        <option value="SG">Sghembi</option>
                                        <option value="SC">Scuroni</option>
                                        <option value="VA">Varie</option>
                                    </select>
                                </div>

                                <div class="col-span-6 md:col-span-4">
                                    <label class="text-sm font-semibold"
                                        >Colore</label
                                    >
                                    <input
                                        v-model="it.colore"
                                        @keydown.enter.prevent="focusNext"
                                        class="mt-1 w-full rounded-lg border"
                                    />
                                </div>

                                <div class="col-span-6 md:col-span-1">
                                    <label class="text-sm font-semibold"
                                        >N. Pezzi</label
                                    >
                                    <input
                                        v-model.number="it.pezzi"
                                        @keydown.enter.prevent="focusNext"
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
                                        @keydown.enter.prevent="focusNext"
                                        class="mt-1 w-full rounded-lg border"
                                    />
                                </div>
                                <div
                                    class="col-span-12 md:col-span-2 flex md:justify-end items-end"
                                >
                                    <button
                                        type="button"
                                        @click="removeItem(it.uid)"
                                        :disabled="it._isDeleting"
                                        class="px-3 py-2 rounded-lg text-white text-sm"
                                        :class="[
                                            it._isDeleting
                                                ? 'bg-red-400 cursor-not-allowed'
                                                : it._confirmDelete
                                                ? 'bg-red-700 hover:bg-red-800'
                                                : 'bg-red-600 hover:bg-red-700',
                                        ]"
                                    >
                                        {{
                                            it._isDeleting
                                                ? "Eliminazione..."
                                                : it._confirmDelete
                                                ? "⚠️ Conferma"
                                                : "Elimina"
                                        }}
                                    </button>
                                </div>
                            </div>

                            <!-- Opzioni riga -->
                            <div
                                class="mt-3 grid grid-cols-2 md:grid-cols-6 gap-3 text-sm"
                            >
                                <!-- PA: solo Taglio -->
                                <template v-if="it.prodotto === 'CP'">
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio"
                                        />
                                        Taglio
                                    </label>
                                </template>
                                <template v-if="it.prodotto === 'VA'">
                                    <label
                                        class="inline-flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="it.taglio"
                                        />
                                        Taglio
                                    </label>
                                </template>
                                <template v-if="it.prodotto === 'SC'">
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
                                </template>
                                <template v-if="it.prodotto === 'CA'">
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
                                </template>
                                <template v-if="it.prodotto === 'PA'">
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
                                </template>
                                <template v-if="it.prodotto === 'AR'">
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
                                </template>

                                <template v-if="it.prodotto === 'SG'">
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
                                </template>

                                <template v-if="it.prodotto === 'PAF'">
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
                                            v-model="it.montaggio_lamelle"
                                        />
                                        Montaggio Lamelle
                                    </label>
                                </template>

                                <!-- IA: Taglio + Assemblaggio + Ferramenta + Vetratura -->
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
                                            v-model="it.Ferramenta"
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
                                            v-model="it.Vetratura"
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

                        <div class="mb-6">
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
