<script setup>
import { ref, onMounted } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Tabs from "@/Components/Tabs.vue";
import { useForm } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import TabSicurezza from "./Tabs/TabSicurezza.vue";
import TabAttivita from "@/Pages/Anagrafica/Tabs/TabAttivita.vue";
import TabProgetti from "@/Pages/Anagrafica/Tabs/TabProgetti.vue";
import TabNoteSpese from "@/Pages/Anagrafica/Tabs/TabNoteSpese.vue";
import TabGiornate from "@/Pages/Anagrafica/Tabs/TabGiornate.vue";
import TabContratti from "@/Pages/Anagrafica/Tabs/TabContratti.vue";
import FlatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Italian } from "flatpickr/dist/l10n/it.js";
const toast = useToast();

// Su successo
//toast.success("Record aggiornato");

// Su errore
// toast.error("Errore durante il salvataggio");
// Calcola oggi e un mese fa
const oggi = new Date();
const unMeseFa = new Date();
unMeseFa.setMonth(unMeseFa.getMonth() - 1);
const FormatoData = {
    altInput: true,
    altFormat: "d/m/Y",
    dateFormat: "Y-m-d",
    locale: Italian,
    allowInput: true,
};
// Imposta valori predefiniti nel formato Y-m-d
const formatDate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
};

const filtroDataInizio = ref(formatDate(unMeseFa));
const filtroDataFine = ref(formatDate(oggi));

const props = defineProps({
    anagrafica: Object,
    corsi: Array,
    corsiDisponibili: Array,
    progetti: Array,
    progettiDisponibili: Array,
    NoteSpese: Array,
    NoteSpeseDisponibili: Array,
    Giornate: Array,
    GiornateDisponibili: Array,
    Contratti: Array,
    ContrattiDisponibili: Array,
    tipiContratto: Array,
    Professione: Array,
    attivita: Array,
    clienti: Array,
    newCode: String,
});

const form = useForm({
    // Generali
    A_NomeVisualizzato: props.anagrafica?.A_NomeVisualizzato ?? '',
    B_TipoU: props.anagrafica?.B_TipoU ?? "U",
    C_Sede: props.anagrafica?.C_Sede ?? "",
    I_Cognome: props.anagrafica?.I_Cognome ?? "",
    H_Nome: props.anagrafica?.H_Nome ?? "",
    J_Genere: props.anagrafica?.J_Genere ?? "",
    O_DataNascita: props.anagrafica?.O_DataNascita ?? "",
    CodCliente: props.anagrafica?.CodCliente ?? props.newCode ?? "",
    L_K_LuogoNascita: props.anagrafica?.L_K_LuogoNascita ?? "",
    M_SiglaNascita: props.anagrafica?.M_SiglaNascita ?? "",
    N_StatoNascita: props.anagrafica?.N_StatoNascita ?? "",
    AL_Cittadinanza: props.anagrafica?.AL_Cittadinanza ?? "",
    AE_IndirizzoEmail: props.anagrafica?.AE_IndirizzoEmail ?? "",
    AD_Cellulare: props.anagrafica?.AD_Cellulare ?? "",
    AF_Tel: props.anagrafica?.AF_Tel ?? "",
    BA_CodiceUser: props.anagrafica?.BA_CodiceUser ?? "",

    // Residenza
    P_IndirizzoResidenza: props.anagrafica?.P_IndirizzoResidenza ?? "",
    R_ComuneResidenza: props.anagrafica?.R_ComuneResidenza ?? "",
    Q_CAP_Residenza: props.anagrafica?.Q_CAP_Residenza ?? "",
    T_SiglaResidenza: props.anagrafica?.T_SiglaResidenza ?? "",
    U_StatoResidenza: props.anagrafica?.U_StatoResidenza ?? "",

    // Domicilio
    V_IndirizzoDomicilio: props.anagrafica?.V_IndirizzoDomicilio ?? "",
    X_ComuneDomicilio: props.anagrafica?.X_ComuneDomicilio ?? "",
    W_CAP_Domicilio: props.anagrafica?.W_CAP_Domicilio ?? "",
    Z_SiglaDomicilio: props.anagrafica?.Z_SiglaDomicilio ?? "",
    AA_StatoDomicilio: props.anagrafica?.AA_StatoDomicilio ?? "",

    // Permesso di soggiorno
    TitSogg: props.anagrafica?.TitSogg ?? "",
    NTitSogg: props.anagrafica?.NTitSogg ?? "",
    MotTitSogg: props.anagrafica?.MotTitSogg ?? "",
    ScaTitSogg: props.anagrafica?.ScaTitSogg ?? "",
    QueTitSogg: props.anagrafica?.QueTitSogg ?? "",

    // Dati Fiscali
    AG_CodiceFiscalePF: props.anagrafica?.AG_CodiceFiscalePF ?? "",
    AI_PartitaIVA: props.anagrafica?.AI_PartitaIVA ?? "",
    AJ_RegimeAgevolato: props.anagrafica?.AJ_RegimeAgevolato ?? "",
    AssegniFam: props.anagrafica?.AssegniFam ?? "",
    INPSridotta: props.anagrafica?.INPSridotta ?? "",
    DetrLav: props.anagrafica?.DetrLav ?? "",
    BonusRenzi: props.anagrafica?.BonusRenzi ?? "",
    AW_IBAN: props.anagrafica?.AW_IBAN ?? "",
    AX_BIC_SWIFT: props.anagrafica?.AX_BIC_SWIFT ?? "",
    Banca: props.anagrafica?.Banca ?? "",
    Pensionato: props.anagrafica?.Pensionato ?? "",
    RapPublAmm: props.anagrafica?.RapPublAmm ?? "",

    // Professione
    AM_Professione1: props.anagrafica?.AM_Professione1 ?? "",
    AO_UniLav: props.anagrafica?.AO_UniLav ?? "",
    LivelloCCNL: props.anagrafica?.LivelloCCNL ?? "",
    AN_Professione2: props.anagrafica?.AN_Professione2 ?? "",
    AN_Professione3: props.anagrafica?.AN_Professione3 ?? "",

    // Quota sociale
    AP_NumeroQuote: props.anagrafica?.AP_NumeroQuote ?? "",
    AR_DataDomanda: props.anagrafica?.AR_DataDomanda ?? "",
    AS_DataApprovazioneCDA: props.anagrafica?.AS_DataApprovazioneCDA ?? "",
    AT_DataVersamento: props.anagrafica?.AT_DataVersamento ?? "",
    NoQuotaSociale: props.anagrafica?.NoQuotaSociale ?? "",
    AU_DataRatifica: props.anagrafica?.AU_DataRatifica ?? "",
    BE_DataLettera: props.anagrafica?.BE_DataLettera ?? "",
    BB_Note: props.anagrafica?.BB_Note ?? "",
});

const submit = () => {
    form.A_NomeVisualizzato = `${form.H_Nome} ${form.I_Cognome}`.trim();
    const id = props.anagrafica?.IDAnagrafica;

    const onSuccess = () => toast.success("✅ Dati salvati con successo!");
    const onError = () => toast.error("❌ Errore durante il salvataggio!");

    if (id) {
        form.put(`/anagrafica/${id}`, {
            onSuccess,
            onError,
        });
    } else {
        form.post("/anagrafica", {
            onSuccess,
            onError,
        });
    }
};

const focusNext = (e) => {
    e.preventDefault();
    const formEls = [...e.target.form.elements].filter((el) =>
        ["INPUT", "SELECT", "TEXTAREA"].includes(el.tagName)
    );
    const i = formEls.indexOf(e.target);
    if (i >= 0 && i < formEls.length - 1) {
        formEls[i + 1].focus();
    }
};

const generaReport = () => {
    if (!form.CodCliente) {
        toast.error("⚠️ Codice cliente mancante.");
        return;
    }

    if (
        filtroDataInizio.value &&
        filtroDataFine.value &&
        filtroDataInizio.value > filtroDataFine.value
    ) {
        toast.error(
            "❌ La data di inizio non può essere successiva alla data di fine."
        );
        return;
    }

    const params = new URLSearchParams();
    params.append("codCliente", form.CodCliente);

    if (filtroDataInizio.value) {
        params.append("dataInizio", filtroDataInizio.value);
    }

    if (filtroDataFine.value) {
        params.append("dataFine", filtroDataFine.value);
    }

    const url = `/report/giornate/preview?${params.toString()}`;
    window.open(url, "_blank");
};
const generaEInviaEmail = () => {
    if (!form.CodCliente) {
        toast.error("⚠️ Codice cliente mancante.");
        return;
    }

    if (!form.AE_IndirizzoEmail) {
        toast.error("⚠️ Nessun indirizzo email specificato.");
        return;
    }

    const params = new URLSearchParams();
    params.append("codCliente", form.CodCliente);
    params.append("email", form.AE_IndirizzoEmail);

    if (filtroDataInizio.value)
        params.append("dataInizio", filtroDataInizio.value);
    if (filtroDataFine.value) params.append("dataFine", filtroDataFine.value);

    toast.info("📨 Invio email in corso...");

    axios
        .post(`/report/giornate/email`, params)
        .then(() => {
            toast.success("✅ Email inviata con successo!");
        })
        .catch(() => {
            toast.error("❌ Errore durante l’invio dell’email.");
        });
};
</script>

<template>
    <Head title="Anagrafica" />
    <AuthenticatedLayout>
        <div
            class="w-full h-[80vh] overflow-y-auto mt-6 bg-white shadow rounded-lg p-6"
        >
            <h2 class="text-2xl font-semibold mb-6">
                {{ props.anagrafica ? "Modifica Utente" : "Nuovo Utente" }}
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Cognome e Nome</label
                    >
                    <input
                        v-model.trim="form.A_NomeVisualizzato"
                        type="text"
                        readonly
                        class="w-full border rounded-md p-2"
                        :class="{
                            'border-red-500': form.errors.A_NomeVisualizzato,
                        }"
                    />
                </div>
            </h2>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="flex gap-4 mb-6">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                        Salva
                    </button>
                    <button
                        type="button"
                        @click="$inertia.visit('/anagrafica')"
                        class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
                    >
                        Annulla
                    </button>
                </div>

                <Tabs :tipoU="form.B_TipoU">
                    <template #generali>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Cognome</label
                                >
                                <input
                                    v-model="form.I_Cognome"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.I_Cognome">
                                    {{ form.errors.I_Cognome }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Nome</label
                                >
                                <input
                                    v-model="form.H_Nome"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.H_Nome">
                                    {{ form.errors.H_Nome }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Genere</label
                                >
                                <select
                                    v-model="form.J_Genere"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                >
                                    <option value="">-- Seleziona --</option>
                                    <option value="M">Maschio</option>
                                    <option value="F">Femmina</option>
                                    <option value="T">
                                        Altro / Transgender
                                    </option>
                                </select>
                                <p class="error" v-if="form.errors.J_Genere">
                                    {{ form.errors.J_Genere }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Data di Nascita</label
                                >

                                <FlatPickr
                                    v-model="form.O_DataNascita"
                                    :config="FormatoData"
                                    class="w-full border rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.O_DataNascita"
                                >
                                    {{ form.errors.O_DataNascita }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Luogo di Nascita</label
                                >
                                <input
                                    v-model="form.L_K_LuogoNascita"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.L_K_LuogoNascita"
                                >
                                    {{ form.errors.L_K_LuogoNascita }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Provincia di Nascita</label
                                >
                                <input
                                    v-model="form.M_SiglaNascita"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.M_SiglaNascita"
                                >
                                    {{ form.errors.M_SiglaNascita }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Stato di Nascita</label
                                >
                                <input
                                    v-model="form.N_StatoNascita"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.N_StatoNascita"
                                >
                                    {{ form.errors.N_StatoNascita }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Cittadinanza</label
                                >
                                <input
                                    v-model="form.AL_Cittadinanza"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AL_Cittadinanza"
                                >
                                    {{ form.errors.AL_Cittadinanza }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Email</label
                                >
                                <input
                                    v-model="form.AE_IndirizzoEmail"
                                    type="email"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AE_IndirizzoEmail"
                                >
                                    {{ form.errors.AE_IndirizzoEmail }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Cellulare</label
                                >
                                <input
                                    v-model="form.AD_Cellulare"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AD_Cellulare"
                                >
                                    {{ form.errors.AD_Cellulare }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Telefono</label
                                >
                                <input
                                    v-model="form.AF_Tel"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.AF_Tel">
                                    {{ form.errors.AF_Tel }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Codice Utente</label
                                >
                                <input
                                    v-model="form.CodCliente"
                                    type="text"
                                    readonly
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.BA_CodiceUser"
                                >
                                    {{ form.errors.BA_CodiceUser }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Sede</label
                                >
                                <select
                                    v-model="form.C_Sede"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                >
                                    <option value="">-- Seleziona --</option>
                                    <option value="MILANO">MILANO</option>
                                    <option value="ROMA">ROMA</option>
                                </select>
                                <p class="error" v-if="form.errors.C_Sede">
                                    {{ form.errors.C_Sede }}
                                </p>
                            </div>
                        </div>
                    </template>

                    <template #residenza>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Residenza -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Indirizzo Residenza</label
                                >
                                <input
                                    v-model="form.P_IndirizzoResidenza"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.P_IndirizzoResidenza"
                                >
                                    {{ form.errors.P_IndirizzoResidenza }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Comune Residenza</label
                                >
                                <input
                                    v-model="form.R_ComuneResidenza"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.R_ComuneResidenza"
                                >
                                    {{ form.errors.R_ComuneResidenza }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >CAP Residenza</label
                                >
                                <input
                                    v-model="form.Q_CAP_Residenza"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.Q_CAP_Residenza"
                                >
                                    {{ form.errors.Q_CAP_Residenza }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Sigla Provincia Residenza</label
                                >
                                <input
                                    v-model="form.T_SiglaResidenza"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.T_SiglaResidenza"
                                >
                                    {{ form.errors.T_SiglaResidenza }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Stato Residenza</label
                                >
                                <input
                                    v-model="form.U_StatoResidenza"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.U_StatoResidenza"
                                >
                                    {{ form.errors.U_StatoResidenza }}
                                </p>
                            </div>

                            <!-- Domicilio -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Indirizzo Domicilio</label
                                >
                                <input
                                    v-model="form.V_IndirizzoDomicilio"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.V_IndirizzoDomicilio"
                                >
                                    {{ form.errors.V_IndirizzoDomicilio }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Comune Domicilio</label
                                >
                                <input
                                    v-model="form.X_ComuneDomicilio"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.X_ComuneDomicilio"
                                >
                                    {{ form.errors.X_ComuneDomicilio }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >CAP Domicilio</label
                                >
                                <input
                                    v-model="form.W_CAP_Domicilio"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.W_CAP_Domicilio"
                                >
                                    {{ form.errors.W_CAP_Domicilio }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Sigla Provincia Domicilio</label
                                >
                                <input
                                    v-model="form.Z_SiglaDomicilio"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.Z_SiglaDomicilio"
                                >
                                    {{ form.errors.Z_SiglaDomicilio }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Stato Domicilio</label
                                >
                                <input
                                    v-model="form.AA_StatoDomicilio"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AA_StatoDomicilio"
                                >
                                    {{ form.errors.AA_StatoDomicilio }}
                                </p>
                            </div>

                            <!-- Permesso di soggiorno -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Titolo di soggiorno</label
                                >
                                <input
                                    v-model="form.TitSogg"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.TitSogg">
                                    {{ form.errors.TitSogg }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Numero titolo soggiorno</label
                                >
                                <input
                                    v-model="form.NTitSogg"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.NTitSogg">
                                    {{ form.errors.NTitSogg }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Motivo titolo soggiorno</label
                                >
                                <input
                                    v-model="form.MotTitSogg"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.MotTitSogg">
                                    {{ form.errors.MotTitSogg }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Scadenza titolo soggiorno</label
                                >
                                <input
                                    v-model="form.ScaTitSogg"
                                    type="date"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.ScaTitSogg">
                                    {{ form.errors.ScaTitSogg }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Questura rilascio titolo</label
                                >
                                <input
                                    v-model="form.QueTitSogg"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.QueTitSogg">
                                    {{ form.errors.QueTitSogg }}
                                </p>
                            </div>
                        </div>
                    </template>
                    <template #fiscali>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Codice Fiscale</label
                                >
                                <input
                                    v-model="form.AG_CodiceFiscalePF"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AG_CodiceFiscalePF"
                                >
                                    {{ form.errors.AG_CodiceFiscalePF }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Partita IVA</label
                                >
                                <input
                                    v-model="form.AI_PartitaIVA"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AI_PartitaIVA"
                                >
                                    {{ form.errors.AI_PartitaIVA }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Regime Agevolato</label
                                >
                                <input
                                    v-model="form.AJ_RegimeAgevolato"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AJ_RegimeAgevolato"
                                >
                                    {{ form.errors.AJ_RegimeAgevolato }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Assegni Familiari</label
                                >
                                <input
                                    v-model="form.AssegniFam"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.AssegniFam">
                                    {{ form.errors.AssegniFam }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >INPS Ridotta</label
                                >
                                <input
                                    v-model="form.INPSridotta"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.INPSridotta">
                                    {{ form.errors.INPSridotta }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Detrazione Lavoratore Dipendente</label
                                >
                                <input
                                    v-model="form.DetrLav"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.DetrLav">
                                    {{ form.errors.DetrLav }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Trattamento Integrativo 100€</label
                                >
                                <input
                                    v-model="form.BonusRenzi"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.BonusRenzi">
                                    {{ form.errors.BonusRenzi }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Codice IBAN</label
                                >
                                <input
                                    v-model="form.AW_IBAN"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.AW_IBAN">
                                    {{ form.errors.AW_IBAN }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >BIC/SWIFT</label
                                >
                                <input
                                    v-model="form.AX_BIC_SWIFT"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AX_BIC_SWIFT"
                                >
                                    {{ form.errors.AX_BIC_SWIFT }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Banca</label
                                >
                                <input
                                    v-model="form.Banca"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.Banca">
                                    {{ form.errors.Banca }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Pensionato</label
                                >
                                <input
                                    v-model="form.Pensionato"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.Pensionato">
                                    {{ form.errors.Pensionato }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Lavoro Pubblica Amm.</label
                                >
                                <input
                                    v-model="form.RapPublAmm"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.RapPublAmm">
                                    {{ form.errors.RapPublAmm }}
                                </p>
                            </div>
                        </div>
                    </template>
                    <template #professione>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Professione 1</label
                                >
                                <input
                                    v-model="form.AM_Professione1"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AM_Professione1"
                                >
                                    {{ form.errors.AM_Professione1 }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >UniLav</label
                                >
                                <input
                                    v-model="form.AO_UniLav"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.AO_UniLav">
                                    {{ form.errors.AO_UniLav }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Livello CCNL</label
                                >
                                <input
                                    v-model="form.LivelloCCNL"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.LivelloCCNL">
                                    {{ form.errors.LivelloCCNL }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Professione 2</label
                                >
                                <input
                                    v-model="form.AN_Professione2"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AN_Professione2"
                                >
                                    {{ form.errors.AN_Professione2 }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Professione 3</label
                                >
                                <input
                                    v-model="form.AN_Professione3"
                                    type="text"
                                    class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AN_Professione3"
                                >
                                    {{ form.errors.AN_Professione3 }}
                                </p>
                            </div>
                        </div>
                    </template>
                    <template #librosoci>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Numero quote</label
                                >
                                <input
                                    v-model="form.AP_NumeroQuote"
                                    type="number"
                                    class="input mt-1 w-full"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AP_NumeroQuote"
                                >
                                    {{ form.errors.AP_NumeroQuote }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Data domanda ammissione</label
                                >
                                <FlatPickr
                                    v-model="form.AR_DataDomanda"
                                    :config="FormatoData"
                                    class="w-full border rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                />

                                <p
                                    class="error"
                                    v-if="form.errors.AR_DataDomanda"
                                >
                                    {{ form.errors.AR_DataDomanda }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Data approvazione CDA</label
                                >
                                <FlatPickr
                                    v-model="form.AS_DataApprovazioneCDA"
                                    :config="FormatoData"
                                    class="w-full border rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                />

                                <p
                                    class="error"
                                    v-if="form.errors.AS_DataApprovazioneCDA"
                                >
                                    {{ form.errors.AS_DataApprovazioneCDA }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Data versamento</label
                                >
                                <FlatPickr
                                    v-model="form.AT_DataVersamento"
                                    :config="FormatoData"
                                    class="w-full border rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AT_DataVersamento"
                                >
                                    {{ form.errors.AT_DataVersamento }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Quota sociale</label
                                >
                                <input
                                    v-model="form.NoQuotaSociale"
                                    type="text"
                                    class="input mt-1 w-full"
                                    @keydown.enter="focusNext"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.NoQuotaSociale"
                                >
                                    {{ form.errors.NoQuotaSociale }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Data ratifica</label
                                >
                                <FlatPickr
                                    v-model="form.AU_DataRatifica"
                                    :config="FormatoData"
                                    class="w-full border rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.AU_DataRatifica"
                                >
                                    {{ form.errors.AU_DataRatifica }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Data lettera</label
                                >
                                <FlatPickr
                                    v-model="form.BE_DataLettera"
                                    :config="FormatoData"
                                    class="w-full border rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                />
                                <p
                                    class="error"
                                    v-if="form.errors.BE_DataLettera"
                                >
                                    {{ form.errors.BE_DataLettera }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Note</label
                                >
                                <textarea
                                    v-model="form.BB_Note"
                                    rows="3"
                                    class="input mt-1 w-full resize-y rounded-md border-gray-300 shadow-sm focus:ring-blue-200 focus:border-blue-500"
                                    @keydown.enter="focusNext"
                                />
                                <p class="error" v-if="form.errors.BB_Note">
                                    {{ form.errors.BB_Note }}
                                </p>
                            </div>
                        </div>
                    </template>
                    <template #sicurezza>
                        <TabSicurezza
                            :codCliente="form.CodCliente"
                            :corsi="props.corsi"
                            :corsiDisponibili="props.corsiDisponibili"
                        />
                    </template>
                    <template #progetti>
                        <TabProgetti
                            :codCliente="form.CodCliente"
                            :progetti="props.progetti"
                            :progettiDisponibili="props.progettiDisponibili"
                        />
                    </template>

                    <template #attivita>
                        <TabAttivita
                            :codCliente="form.CodCliente"
                            :attivita="props.attivita"
                            :clienti="props.clienti"
                        />
                    </template>
                    <template #notespese>
                        <TabNoteSpese
                            :codCliente="form.CodCliente"
                            :noteSpese="props.NoteSpese"
                            :nomeUtente="`${form.H_Nome} ${form.I_Cognome}`"
                        />
                    </template>
                    <template #giornate>
                        <div>
                            <div class="flex items-center gap-4 mb-4">
                                <label class="text-sm font-medium text-gray-700"
                                    >Data Inizio:</label
                                >
                                <FlatPickr
                                    v-model="filtroDataInizio"
                                    :config="FormatoData"
                                    class="w-full border rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                />

                                <label class="text-sm font-medium text-gray-700"
                                    >Data Fine:</label
                                >
                                <FlatPickr
                                    v-model="filtroDataFine"
                                    :config="FormatoData"
                                    class="w-full border rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500"
                                />

                                <button
                                    type="button"
                                    @click="generaReport"
                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded"
                                >
                                    🖨️ Genera Report
                                </button>

                                <button
                                    type="button"
                                    @click="generaEInviaEmail"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded"
                                >
                                    ✉️ Invia Email
                                </button>
                            </div>

                            <TabGiornate
                                :codCliente="form.CodCliente"
                                :giornate="props.Giornate"
                            />
                        </div>
                    </template>

                    <template #contratti>
                        <TabContratti
                            :codCliente="form.CodCliente"
                            :contratti="props.Contratti"
                            :tipiContratto="props.tipiContratto"
                            :Professione="props.Professione"
                            :nomeUtente="`${form.H_Nome} ${form.I_Cognome}`"
                            :CodFiscale="form.AG_CodiceFiscalePF || ''"
                        />
                    </template>
                </Tabs>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
