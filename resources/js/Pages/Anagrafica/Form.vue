<script setup>
import { watch, nextTick, ref, onMounted } from "vue";
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
import TabVisiteMediche from "@/Pages/Anagrafica/Tabs/TabVisiteMediche.vue";
import FlatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Italian } from "flatpickr/dist/l10n/it.js";
import {
    User,
    UserCircle2,
    Users,
    Calendar,
    Flag,
    Mail,
    Smartphone,
    Phone,
    Hash,
    Home,
    MapPin,
    Building2,
    Map,
    Globe,
    Navigation,
    BadgeCheck,
    IdCard,
    FileText,
    Building,
    Briefcase,
    BadgePercent,
    ShieldCheck,
    MinusCircle,
    Euro,
    CreditCard,
    KeyRound,
    Landmark,
} from "lucide-vue-next";
const toast = useToast();
const submitError = ref(null);
const submitSuccess = ref(false);
function scrollToFirstError() {
    nextTick(() => {
        const root = document.querySelector("#anagraficaForm");
        if (!root) return;

        // 1) Cerca un input/select/textarea marcato con classe errore
        const el =
            root.querySelector(".input-danger") ||
            root.querySelector(".border-red-500") ||
            root.querySelector("[aria-invalid='true']") ||
            root.querySelector(
                ".has-error input, .has-error select, .has-error textarea"
            );

        if (!el) return;

        el.scrollIntoView({ behavior: "smooth", block: "center" });
        setTimeout(() => el.focus?.(), 250);
    });
}

function toastFirstError(errors) {
    if (!errors) return;
    const firstKey = Object.keys(errors)[0];
    const firstMsg = errors[firstKey];
    toast.error(`❌ ${firstMsg}`, { timeout: 8000 });
}

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
    wrap: true,
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
    Visite: Array,
    VisiteDisponibili: Array,
    tipiContratto: Array,
    Professione: Array,
    attivita: Array,
    clienti: { type: [Array, Object], default: () => [] },
    newCode: String,
    tipoU: { type: String, default: "U" },
    errors: { type: Object, default: () => ({}) },
    tabErrorMap: { type: Object, default: () => ({}) },
});

const form = useForm({
    // Generali
    A_NomeVisualizzato: props.anagrafica?.A_NomeVisualizzato ?? "",
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
    submitError.value = null;
    submitSuccess.value = false;

    form.A_NomeVisualizzato = `${form.H_Nome} ${form.I_Cognome}`.trim();
    const id = props.anagrafica?.IDAnagrafica;

    const onSuccess = () => {
        submitSuccess.value = true;
    };

    const onError = (errors) => {
        const firstKey = Object.keys(errors)[0];
        submitError.value = errors[firstKey];
        submitSuccess.value = false;

        scrollToFirstError(errors);
    };

    if (id) {
        form.put(`/anagrafica/${id}`, {
            preserveScroll: true,
            onSuccess,
            onError,
        });
    } else {
        form.post("/anagrafica", {
            preserveScroll: true,
            onSuccess,
            onError,
        });
    }
};
function tabHasErrors(key) {
    const fields = props.tabErrorMap?.[key] ?? [];
    return fields.some((f) => props.errors && props.errors[f]);
}

function tabErrorCount(key) {
    const fields = props.tabErrorMap?.[key] ?? [];
    return fields.reduce((acc, f) => acc + (props.errors?.[f] ? 1 : 0), 0);
}

const tabErrorMap = {
    generali: [
        "I_Cognome",
        "H_Nome",
        "J_Genere",
        "O_DataNascita",
        "L_K_LuogoNascita",
        "M_SiglaNascita",
        "N_StatoNascita",
        "AL_Cittadinanza",
        "AE_IndirizzoEmail",
        "AD_Cellulare",
        "AF_Tel",
        "CodCliente",
        "C_Sede",
    ],
    residenza: [
        "P_IndirizzoResidenza",
        "R_ComuneResidenza",
        "Q_CAP_Residenza",
        "T_SiglaResidenza",
        "U_StatoResidenza",
        "V_IndirizzoDomicilio",
        "X_ComuneDomicilio",
        "W_CAP_Domicilio",
        "Z_SiglaDomicilio",
        "AA_StatoDomicilio",
        "TitSogg",
        "NTitSogg",
        "MotTitSogg",
        "ScaTitSogg",
        "QueTitSogg",
    ],
    fiscali: [
        "AG_CodiceFiscalePF",
        "AI_PartitaIVA",
        "AJ_RegimeAgevolato",
        "AssegniFam",
        "INPSridotta",
        "DetrLav",
        "BonusRenzi",
        "AW_IBAN",
        "AX_BIC_SWIFT",
        "Banca",
        "Pensionato",
        "RapPublAmm",
    ],
    professione: [
        "AM_Professione1",
        "AO_UniLav",
        "LivelloCCNL",
        "AN_Professione2",
        "AN_Professione3",
    ],
    librosoci: [
        "AP_NumeroQuote",
        "AR_DataDomanda",
        "AS_DataApprovazioneCDA",
        "AT_DataVersamento",
        "NoQuotaSociale",
        "AU_DataRatifica",
        "BE_DataLettera",
        "BB_Note",
    ],
    // tabs “componenti” (se validi lato backend)
    sicurezza: [],
    attivita: [],
    progetti: [],
    notespese: [],
    giornate: [],
    contratti: [],
    sorveglianza: [],
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
watch(
    () => JSON.stringify(form.data ? form.data() : form),
    () => {
        // appena l’utente cambia qualcosa, tolgo il messaggio “salvato”
        if (submitSuccess.value) submitSuccess.value = false;
        // opzionale: se cambi qualcosa, tolgo anche l’errore inline
        // if (submitError.value) submitError.value = null;
    }
);
</script>

<template>
    <Head title="Anagrafica" />
    <AuthenticatedLayout>
        <div class="page">
            <!-- ===== HERO / HEADER ===== -->
            <div class="hero">
                <div class="heroInner">
                    <div class="flex items-center gap-3">
                        <div class="logoPill">👤</div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="heroTitle">
                                    {{
                                        props.anagrafica
                                            ? "Modifica Utente"
                                            : "Nuovo Utente"
                                    }}
                                </h1>
                                <span
                                    v-if="form.CodCliente"
                                    class="badge badge-blue px-3 py-1"
                                >
                                    <span
                                        class="text-2xl md:text-3xl font-extrabold tracking-tight opacity-80"
                                    >
                                    </span>

                                    <span
                                        class="text-2xl md:text-3xl font-extrabold tracking-tight"
                                    >
                                        {{ form.A_NomeVisualizzato }}
                                    </span>
                                </span>
                            </div>
                            <p class="heroSub">
                                Dati anagrafici, residenza, fiscali, attività e
                                report giornate.
                            </p>
                        </div>
                    </div>

                    <!-- Nome visualizzato (readonly) -->
                    <!--     <div class="heroRight">
                        <label class="label">Cognome e Nome</label>
                        <input
                            v-model.trim="form.A_NomeVisualizzato"
                            type="text"
                            readonly
                            class="input input-readonly"
                            :class="{
                                'input-danger': form.errors.A_NomeVisualizzato,
                            }"
                        />
                        <p v-if="form.errors.A_NomeVisualizzato" class="err">
                            {{ form.errors.A_NomeVisualizzato }}
                        </p>
                    </div> -->
                </div>
            </div>

            <!-- ===== CARD CONTENUTO ===== -->
            <div class="contentCard">
                <!-- toolbar sticky -->

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="toolbar">
                        <div class="flex items-center gap-2">
                            <span class="chip">
                                {{
                                    props.anagrafica
                                        ? "Stai modificando"
                                        : "Stai creando"
                                }}
                            </span>

                            <!-- SUCCESS -->
                            <span
                                v-if="submitSuccess"
                                class="chip chip-green flex items-center gap-1"
                            >
                                ✅ Dati salvati
                            </span>

                            <!-- ERROR -->
                            <span
                                v-if="submitError"
                                class="chip chip-red flex items-center gap-1"
                            >
                                ⚠️ {{ submitError }}
                            </span>

                            <span
                                v-if="form.processing"
                                class="chip chip-amber"
                            >
                                ⏳ Salvataggio...
                            </span>

                            <span
                                v-else-if="!submitSuccess && !submitError"
                                class="chip chip-green"
                            >
                                ✅ Pronto
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="$inertia.visit('/anagrafica')"
                                class="btn btn-ghost"
                            >
                                ← Annulla
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="btn btn-primary"
                            >
                                💾 Salva
                            </button>
                        </div>
                    </div>

                    <Tabs
                        :tipoU="form.B_TipoU"
                        :errors="form.errors"
                        :tabErrorMap="tabErrorMap"
                        :jumpToFirstErrorTab="true"
                        :stickyTop="72"
                    >
                        <template #generali>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <!-- Cognome -->
                                <div class="field">
                                    <label class="label">Cognome</label>
                                    <div class="iconInputWrap">
                                        <User class="iconLeft w-4 h-4" />
                                        <input
                                            name="I_Cognome"
                                            v-model="form.I_Cognome"
                                            type="text"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr: form.errors.I_Cognome,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p class="err" v-if="form.errors.I_Cognome">
                                        {{ form.errors.I_Cognome }}
                                    </p>
                                </div>

                                <!-- Nome -->
                                <div class="field">
                                    <label class="label">Nome</label>
                                    <div class="iconInputWrap">
                                        <UserCircle2 class="iconLeft w-4 h-4" />
                                        <input
                                            name="H_Nome"
                                            v-model="form.H_Nome"
                                            type="text"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr: form.errors.H_Nome,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p class="err" v-if="form.errors.H_Nome">
                                        {{ form.errors.H_Nome }}
                                    </p>
                                </div>

                                <!-- Genere -->
                                <div class="field">
                                    <label class="label">Genere</label>
                                    <div class="iconInputWrap">
                                        <Users class="iconLeft w-4 h-4" />
                                        <select
                                            name="J_Genere"
                                            v-model="form.J_Genere"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr: form.errors.J_Genere,
                                            }"
                                            @keydown.enter="focusNext"
                                        >
                                            <option value="">
                                                -- Seleziona --
                                            </option>
                                            <option value="M">Maschio</option>
                                            <option value="F">Femmina</option>
                                            <option value="T">
                                                Altro / Transgender
                                            </option>
                                        </select>
                                    </div>
                                    <p class="err" v-if="form.errors.J_Genere">
                                        {{ form.errors.J_Genere }}
                                    </p>
                                </div>

                                <!-- Data di nascita -->
                                <div class="field">
                                    <label class="label">Data di Nascita</label>
                                    <FlatPickr
                                        name="O_DataNascita"
                                        v-model="form.O_DataNascita"
                                        :config="FormatoData"
                                        class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                        :class="{
                                            inputErr: form.errors.O_DataNascita,
                                        }"
                                    />
                                    <p
                                        class="err"
                                        v-if="form.errors.O_DataNascita"
                                    >
                                        {{ form.errors.O_DataNascita }}
                                    </p>
                                </div>

                                <!-- Luogo di nascita -->
                                <div class="field">
                                    <label class="label"
                                        >Luogo di Nascita</label
                                    >
                                    <div class="iconInputWrap">
                                        <MapPin
                                            class="iconLeft icon-residenza"
                                        />

                                        <input
                                            name="L_K_LuogoNascita"
                                            v-model="form.L_K_LuogoNascita"
                                            type="text"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr:
                                                    form.errors
                                                        .L_K_LuogoNascita,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p
                                        class="err"
                                        v-if="form.errors.L_K_LuogoNascita"
                                    >
                                        {{ form.errors.L_K_LuogoNascita }}
                                    </p>
                                </div>

                                <!-- Provincia di nascita -->
                                <div class="field">
                                    <label class="label"
                                        >Provincia di Nascita</label
                                    >
                                    <div class="iconInputWrap">
                                        <BadgeCheck
                                            class="iconLeft icon-soggiorno"
                                        />
                                        <input
                                            name="M_SiglaNascita"
                                            v-model="form.M_SiglaNascita"
                                            type="text"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr:
                                                    form.errors.M_SiglaNascita,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p
                                        class="err"
                                        v-if="form.errors.M_SiglaNascita"
                                    >
                                        {{ form.errors.M_SiglaNascita }}
                                    </p>
                                </div>

                                <!-- Stato di nascita -->
                                <div class="field">
                                    <label class="label"
                                        >Stato di Nascita</label
                                    >
                                    <div class="iconInputWrap">
                                        <Flag class="iconLeft w-4 h-4" />
                                        <input
                                            name="N_StatoNascita"
                                            v-model="form.N_StatoNascita"
                                            type="text"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr:
                                                    form.errors.N_StatoNascita,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p
                                        class="err"
                                        v-if="form.errors.N_StatoNascita"
                                    >
                                        {{ form.errors.N_StatoNascita }}
                                    </p>
                                </div>

                                <!-- Cittadinanza -->
                                <div class="field">
                                    <label class="label">Cittadinanza</label>
                                    <div class="iconInputWrap">
                                        <Flag class="iconLeft w-4 h-4" />
                                        <input
                                            name="AL_Cittadinanza"
                                            v-model="form.AL_Cittadinanza"
                                            type="text"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr:
                                                    form.errors.AL_Cittadinanza,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p
                                        class="err"
                                        v-if="form.errors.AL_Cittadinanza"
                                    >
                                        {{ form.errors.AL_Cittadinanza }}
                                    </p>
                                </div>

                                <!-- Email -->
                                <div class="field">
                                    <label class="label">Email</label>
                                    <div class="iconInputWrap">
                                        <Mail class="iconLeft w-4 h-4" />
                                        <input
                                            name="AE_IndirizzoEmail"
                                            v-model="form.AE_IndirizzoEmail"
                                            type="email"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr:
                                                    form.errors
                                                        .AE_IndirizzoEmail,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p
                                        class="err"
                                        v-if="form.errors.AE_IndirizzoEmail"
                                    >
                                        {{ form.errors.AE_IndirizzoEmail }}
                                    </p>
                                </div>

                                <!-- Cellulare -->
                                <div class="field">
                                    <label class="label">Cellulare</label>
                                    <div class="iconInputWrap">
                                        <Smartphone class="iconLeft w-4 h-4" />
                                        <input
                                            name="AD_Cellulare"
                                            v-model="form.AD_Cellulare"
                                            type="text"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr:
                                                    form.errors.AD_Cellulare,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p
                                        class="err"
                                        v-if="form.errors.AD_Cellulare"
                                    >
                                        {{ form.errors.AD_Cellulare }}
                                    </p>
                                </div>

                                <!-- Telefono -->
                                <div class="field">
                                    <label class="label">Telefono</label>
                                    <div class="iconInputWrap">
                                        <Phone class="iconLeft w-4 h-4" />
                                        <input
                                            name="AF_Tel"
                                            v-model="form.AF_Tel"
                                            type="text"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr: form.errors.AF_Tel,
                                            }"
                                            @keydown.enter="focusNext"
                                        />
                                    </div>
                                    <p class="err" v-if="form.errors.AF_Tel">
                                        {{ form.errors.AF_Tel }}
                                    </p>
                                </div>

                                <!-- Codice utente -->
                                <div class="field">
                                    <label class="label">Codice Utente</label>
                                    <div class="iconInputWrap">
                                        <Hash class="iconLeft w-4 h-4" />
                                        <input
                                            name="CodCliente"
                                            v-model="form.CodCliente"
                                            type="text"
                                            readonly
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                        />
                                    </div>
                                    <p
                                        class="err"
                                        v-if="form.errors.BA_CodiceUser"
                                    >
                                        {{ form.errors.BA_CodiceUser }}
                                    </p>
                                </div>

                                <!-- Sede -->
                                <div class="field">
                                    <label class="label">Sede</label>
                                    <div class="iconInputWrap">
                                        <Building2 class="iconLeft w-4 h-4" />
                                        <select
                                            name="C_Sede"
                                            v-model="form.C_Sede"
                                            class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                            :class="{
                                                inputErr: form.errors.C_Sede,
                                            }"
                                            @keydown.enter="focusNext"
                                        >
                                            <option value="">
                                                -- Seleziona --
                                            </option>
                                            <option value="MILANO">
                                                MILANO
                                            </option>
                                            <option value="ROMA">ROMA</option>
                                        </select>
                                    </div>
                                    <p class="err" v-if="form.errors.C_Sede">
                                        {{ form.errors.C_Sede }}
                                    </p>
                                </div>
                            </div>
                        </template>

                        <template #residenza>
                            <div class="space-y-6">
                                <!-- ================= RESIDENZA ================= -->
                                <div class="sectionCard">
                                    <div class="sectionHead">
                                        <div class="sectionTitle">
                                            <Home class="w-5 h-5" />
                                            <div>
                                                <div class="font-extrabold">
                                                    Residenza
                                                </div>
                                                <div
                                                    class="text-xs text-slate-500"
                                                >
                                                    Indirizzo e riferimenti
                                                    ufficiali
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-2 p-4"
                                    >
                                        <!-- Indirizzo Residenza -->
                                        <div class="field">
                                            <label class="label"
                                                >Indirizzo Residenza</label
                                            >
                                            <div class="iconInputWrap">
                                                <MapPin
                                                    class="iconLeft icon-residenza"
                                                />
                                                <input
                                                    name="P_IndirizzoResidenza"
                                                    v-model="
                                                        form.P_IndirizzoResidenza
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .P_IndirizzoResidenza,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                    placeholder="Via, numero civico…"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors
                                                        .P_IndirizzoResidenza
                                                "
                                            >
                                                {{
                                                    form.errors
                                                        .P_IndirizzoResidenza
                                                }}
                                            </p>
                                        </div>

                                        <!-- Comune Residenza -->
                                        <div class="field">
                                            <label class="label"
                                                >Comune Residenza</label
                                            >
                                            <div class="iconInputWrap">
                                                <Building2 class="iconLeft" />
                                                <input
                                                    name="R_ComuneResidenza"
                                                    v-model="
                                                        form.R_ComuneResidenza
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .R_ComuneResidenza,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                    placeholder="Comune…"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors
                                                        .R_ComuneResidenza
                                                "
                                            >
                                                {{
                                                    form.errors
                                                        .R_ComuneResidenza
                                                }}
                                            </p>
                                        </div>

                                        <!-- CAP Residenza -->
                                        <div class="field">
                                            <label class="label"
                                                >CAP Residenza</label
                                            >
                                            <div class="iconInputWrap">
                                                <Hash class="iconLeft" />
                                                <input
                                                    name="Q_CAP_Residenza"
                                                    v-model="
                                                        form.Q_CAP_Residenza
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .Q_CAP_Residenza,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                    placeholder="Es. 00100"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors.Q_CAP_Residenza
                                                "
                                            >
                                                {{
                                                    form.errors.Q_CAP_Residenza
                                                }}
                                            </p>
                                        </div>

                                        <!-- Sigla Provincia Residenza -->
                                        <div class="field">
                                            <label class="label"
                                                >Sigla Provincia</label
                                            >
                                            <div class="iconInputWrap">
                                                <Map class="iconLeft" />
                                                <input
                                                    name="T_SiglaResidenza"
                                                    v-model="
                                                        form.T_SiglaResidenza
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .T_SiglaResidenza,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                    placeholder="Es. RM"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors.T_SiglaResidenza
                                                "
                                            >
                                                {{
                                                    form.errors.T_SiglaResidenza
                                                }}
                                            </p>
                                        </div>

                                        <!-- Stato Residenza -->
                                        <div class="field md:col-span-2">
                                            <label class="label">Stato</label>
                                            <div class="iconInputWrap">
                                                <Globe class="iconLeft" />
                                                <input
                                                    name="U_StatoResidenza"
                                                    v-model="
                                                        form.U_StatoResidenza
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .U_StatoResidenza,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                    placeholder="Italia"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors.U_StatoResidenza
                                                "
                                            >
                                                {{
                                                    form.errors.U_StatoResidenza
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= DOMICILIO ================= -->
                                <div class="sectionCard">
                                    <div class="sectionHead">
                                        <div class="sectionTitle">
                                            <Navigation
                                                class="iconLeft icon-domicilio"
                                            />
                                            <div>
                                                <div class="font-extrabold">
                                                    Domicilio
                                                </div>
                                                <div
                                                    class="text-xs text-slate-500"
                                                >
                                                    Indirizzo attuale (se
                                                    diverso)
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-2 p-4"
                                    >
                                        <div class="field">
                                            <label class="label"
                                                >Indirizzo Domicilio</label
                                            >
                                            <div class="iconInputWrap">
                                                <MapPin
                                                    class="iconLeft icon-residenza"
                                                />
                                                <input
                                                    name="V_IndirizzoDomicilio"
                                                    v-model="
                                                        form.V_IndirizzoDomicilio
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .V_IndirizzoDomicilio,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors
                                                        .V_IndirizzoDomicilio
                                                "
                                            >
                                                {{
                                                    form.errors
                                                        .V_IndirizzoDomicilio
                                                }}
                                            </p>
                                        </div>

                                        <div class="field">
                                            <label class="label"
                                                >Comune Domicilio</label
                                            >
                                            <div class="iconInputWrap">
                                                <Building2 class="iconLeft" />
                                                <input
                                                    name="X_ComuneDomicilio"
                                                    v-model="
                                                        form.X_ComuneDomicilio
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .X_ComuneDomicilio,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors
                                                        .X_ComuneDomicilio
                                                "
                                            >
                                                {{
                                                    form.errors
                                                        .X_ComuneDomicilio
                                                }}
                                            </p>
                                        </div>

                                        <div class="field">
                                            <label class="label"
                                                >CAP Domicilio</label
                                            >
                                            <div class="iconInputWrap">
                                                <Hash class="iconLeft" />
                                                <input
                                                    name="W_CAP_Domicilio"
                                                    v-model="
                                                        form.W_CAP_Domicilio
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .W_CAP_Domicilio,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors.W_CAP_Domicilio
                                                "
                                            >
                                                {{
                                                    form.errors.W_CAP_Domicilio
                                                }}
                                            </p>
                                        </div>

                                        <div class="field">
                                            <label class="label"
                                                >Sigla Provincia</label
                                            >
                                            <div class="iconInputWrap">
                                                <Map class="iconLeft" />
                                                <input
                                                    name="Z_SiglaDomicilio"
                                                    v-model="
                                                        form.Z_SiglaDomicilio
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .Z_SiglaDomicilio,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors.Z_SiglaDomicilio
                                                "
                                            >
                                                {{
                                                    form.errors.Z_SiglaDomicilio
                                                }}
                                            </p>
                                        </div>

                                        <div class="field md:col-span-2">
                                            <label class="label">Stato</label>
                                            <div class="iconInputWrap">
                                                <Globe class="iconLeft" />
                                                <input
                                                    name="AA_StatoDomicilio"
                                                    v-model="
                                                        form.AA_StatoDomicilio
                                                    "
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .AA_StatoDomicilio,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors
                                                        .AA_StatoDomicilio
                                                "
                                            >
                                                {{
                                                    form.errors
                                                        .AA_StatoDomicilio
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= PERMESSO SOGGIORNO ================= -->
                                <div class="sectionCard">
                                    <div class="sectionHead">
                                        <div class="sectionTitle">
                                            <BadgeCheck
                                                class="iconLeft icon-soggiorno"
                                            />
                                            <div>
                                                <div class="font-extrabold">
                                                    Permesso di soggiorno
                                                </div>
                                                <div
                                                    class="text-xs text-slate-500"
                                                >
                                                    Dati titolo (se applicabile)
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-2 p-4"
                                    >
                                        <div class="field">
                                            <label class="label"
                                                >Titolo di soggiorno</label
                                            >
                                            <div class="iconInputWrap">
                                                <IdCard class="iconLeft" />
                                                <input
                                                    name="TitSogg"
                                                    v-model="form.TitSogg"
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors.TitSogg,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="form.errors.TitSogg"
                                            >
                                                {{ form.errors.TitSogg }}
                                            </p>
                                        </div>

                                        <div class="field">
                                            <label class="label"
                                                >Numero titolo</label
                                            >
                                            <div class="iconInputWrap">
                                                <Hash class="iconLeft" />
                                                <input
                                                    name="NTitSogg"
                                                    v-model="form.NTitSogg"
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .NTitSogg,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="form.errors.NTitSogg"
                                            >
                                                {{ form.errors.NTitSogg }}
                                            </p>
                                        </div>

                                        <div class="field">
                                            <label class="label">Motivo</label>
                                            <div class="iconInputWrap">
                                                <FileText class="iconLeft" />
                                                <input
                                                    name="MotTitSogg"
                                                    v-model="form.MotTitSogg"
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .MotTitSogg,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="form.errors.MotTitSogg"
                                            >
                                                {{ form.errors.MotTitSogg }}
                                            </p>
                                        </div>
                                        <div class="field">
                                            <label class="label"
                                                >Scadenza</label
                                            >
                                            <FlatPickr
                                                name="ScaTitSogg"
                                                v-model="form.ScaTitSogg"
                                                :config="FormatoData"
                                                class="input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-blue-200 focus:border-blue-500 inputIcon"
                                                :class="{
                                                    inputErr:
                                                        form.errors.ScaTitSogg,
                                                }"
                                            />
                                            <p
                                                class="err"
                                                v-if="form.errors.ScaTitSogg"
                                            >
                                                {{ form.errors.ScaTitSogg }}
                                            </p>
                                        </div>

                                        <div class="field md:col-span-2">
                                            <label class="label"
                                                >Questura rilascio</label
                                            >
                                            <div class="iconInputWrap">
                                                <Building class="iconLeft" />
                                                <input
                                                    name="QueTitSogg"
                                                    v-model="form.QueTitSogg"
                                                    type="text"
                                                    class="input inputIcon"
                                                    :class="{
                                                        inputErr:
                                                            form.errors
                                                                .QueTitSogg,
                                                    }"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <p
                                                class="err"
                                                v-if="form.errors.QueTitSogg"
                                            >
                                                {{ form.errors.QueTitSogg }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template #fiscali>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- 🧾 IDENTITÀ FISCALE -->
                                <div class="md:col-span-2 fieldGroup">
                                    <div class="groupTitle">
                                        🧾 Identità fiscale
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                    >
                                        <div class="field iconWrap">
                                            <label class="label"
                                                >Codice Fiscale</label
                                            >
                                            <Hash
                                                class="iconLeft icon-fiscale"
                                            />
                                            <input
                                                v-model="
                                                    form.AG_CodiceFiscalePF
                                                "
                                                class="input inputIcon"
                                                @keydown.enter="focusNext"
                                            />
                                            <p
                                                class="err"
                                                v-if="
                                                    form.errors
                                                        .AG_CodiceFiscalePF
                                                "
                                            >
                                                {{
                                                    form.errors
                                                        .AG_CodiceFiscalePF
                                                }}
                                            </p>
                                        </div>

                                        <div class="field iconWrap">
                                            <label class="label"
                                                >Partita IVA</label
                                            >
                                            <Briefcase
                                                class="iconLeft icon-fiscale"
                                            />
                                            <input
                                                v-model="form.AI_PartitaIVA"
                                                class="input inputIcon"
                                                @keydown.enter="focusNext"
                                            />
                                            <p
                                                class="err"
                                                v-if="form.errors.AI_PartitaIVA"
                                            >
                                                {{ form.errors.AI_PartitaIVA }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- 💶 AGEvolazioni -->
                                <div class="md:col-span-2 fieldGroup">
                                    <div class="groupTitle">
                                        💶 Agevolazioni e contributi
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                    >
                                        <div class="field iconWrap">
                                            <label class="label"
                                                >Regime Agevolato</label
                                            >
                                            <BadgePercent
                                                class="iconLeft icon-money"
                                            />
                                            <input
                                                v-model="
                                                    form.AJ_RegimeAgevolato
                                                "
                                                class="input inputIcon"
                                            />
                                        </div>

                                        <div class="field iconWrap">
                                            <label class="label"
                                                >Assegni Familiari</label
                                            >
                                            <Users
                                                class="iconLeft icon-money"
                                            />
                                            <input
                                                v-model="form.AssegniFam"
                                                class="input inputIcon"
                                            />
                                        </div>

                                        <div class="field iconWrap">
                                            <label class="label"
                                                >INPS Ridotta</label
                                            >
                                            <ShieldCheck
                                                class="iconLeft icon-money"
                                            />
                                            <input
                                                v-model="form.INPSridotta"
                                                class="input inputIcon"
                                            />
                                        </div>

                                        <div class="field iconWrap">
                                            <label class="label"
                                                >Detrazione Lav. Dip.</label
                                            >
                                            <MinusCircle
                                                class="iconLeft icon-money"
                                            />
                                            <input
                                                v-model="form.DetrLav"
                                                class="input inputIcon"
                                            />
                                        </div>

                                        <div class="field iconWrap">
                                            <label class="label"
                                                >Bonus 100€</label
                                            >
                                            <Euro class="iconLeft icon-money" />
                                            <input
                                                v-model="form.BonusRenzi"
                                                class="input inputIcon"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- 🏦 BANCA / LAVORO -->
                                <div class="md:col-span-2 fieldGroup">
                                    <div class="groupTitle">
                                        🏦 Banca e lavoro
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                    >
                                        <div class="field iconWrap">
                                            <label class="label">IBAN</label>
                                            <CreditCard
                                                class="iconLeft icon-bank"
                                            />
                                            <input
                                                v-model="form.AW_IBAN"
                                                class="input inputIcon"
                                            />
                                        </div>

                                        <div class="field iconWrap">
                                            <label class="label"
                                                >BIC / SWIFT</label
                                            >
                                            <KeyRound
                                                class="iconLeft icon-bank"
                                            />
                                            <input
                                                v-model="form.AX_BIC_SWIFT"
                                                class="input inputIcon"
                                            />
                                        </div>

                                        <div class="field iconWrap">
                                            <label class="label">Banca</label>
                                            <Landmark
                                                class="iconLeft icon-bank"
                                            />
                                            <input
                                                v-model="form.Banca"
                                                class="input inputIcon"
                                            />
                                        </div>

                                        <div class="field iconWrap">
                                            <label class="label"
                                                >Rapporto P.A.</label
                                            >
                                            <Building2
                                                class="iconLeft icon-bank"
                                            />
                                            <input
                                                v-model="form.RapPublAmm"
                                                class="input inputIcon"
                                            />
                                        </div>
                                    </div>
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
                                    <p
                                        class="error"
                                        v-if="form.errors.AO_UniLav"
                                    >
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
                                    <p
                                        class="error"
                                        v-if="form.errors.LivelloCCNL"
                                    >
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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
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
                                        v-if="
                                            form.errors.AS_DataApprovazioneCDA
                                        "
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

                        <!--     <template #attivita>
                            <TabAttivita
                                :codCliente="form.CodCliente"
                                :attivita="props.attivita"
                                :clienti="props.clienti"
                            />
                        </template> -->
                        <template #notespese>
                            <TabNoteSpese
                                :codCliente="form.CodCliente"
                                :noteSpese="props.NoteSpese"
                                :nomeUtente="`${form.H_Nome} ${form.I_Cognome}`"
                            />
                        </template>
                        <template #giornate>
                            <div class="space-y-4">
                                <div class="cardLite">
                                    <div class="cardLiteHead">
                                        <div>
                                            <div
                                                class="font-extrabold text-slate-900"
                                            >
                                                📅 Report Giornate
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                Seleziona un intervallo e genera
                                                l’anteprima o inviala via email.
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                @click="generaReport"
                                                class="btn btn-green"
                                            >
                                                🖨️ Genera
                                            </button>

                                            <button
                                                type="button"
                                                @click="generaEInviaEmail"
                                                class="btn btn-primary"
                                            >
                                                ✉️ Invia Email
                                            </button>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4"
                                    >
                                        <div>
                                            <label class="label"
                                                >Data Inizio</label
                                            >
                                            <FlatPickr
                                                v-model="filtroDataInizio"
                                                :config="FormatoData"
                                                class="input w-full"
                                            />
                                        </div>

                                        <div>
                                            <label class="label"
                                                >Data Fine</label
                                            >
                                            <FlatPickr
                                                v-model="filtroDataFine"
                                                :config="FormatoData"
                                                class="input w-full"
                                            />
                                        </div>
                                    </div>
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
                        <template #sorveglianza>
                            <TabVisiteMediche
                                :codCliente="form.CodCliente"
                                :Visite="props.Visite"
                            />
                        </template>
                    </Tabs>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
.page {
    @apply w-full min-h-[80vh] px-4 md:px-8 xl:px-12 py-6 bg-slate-50;
}

/* HERO */
.hero {
    @apply rounded-3xl border bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-lg;
}
.heroInner {
    @apply w-full px-6 py-5 flex flex-col lg:flex-row gap-4
           lg:items-center lg:justify-between;
}
.heroTitle {
    @apply text-2xl md:text-3xl font-extrabold tracking-tight;
}
.heroSub {
    @apply text-xs md:text-sm text-white/80 mt-1;
}
.heroRight {
    @apply bg-white/10 backdrop-blur rounded-2xl border border-white/20 p-4 w-full lg:w-[420px];
}
.logoPill {
    @apply w-11 h-11 grid place-items-center rounded-2xl bg-white/15 border border-white/20 text-xl;
}

/* CONTENT */
@media (min-width: 1920px) {
    .contentCard {
        max-width: 1800px;
        margin-left: auto;
        margin-right: auto;
    }
}

.toolbar {
    @apply sticky top-0 z-20 flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-white/90 backdrop-blur border-b;
}
.cardLite {
    @apply rounded-2xl border bg-white shadow-sm overflow-hidden;
}
.cardLiteHead {
    @apply flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b bg-slate-50;
}

/* BADGE / CHIP */
.badge {
    @apply text-[11px] font-extrabold px-2 py-1 rounded-full border;
}
.badge-slate {
    @apply bg-white/15 text-white border-white/25;
}
.badge-blue {
    @apply bg-white text-indigo-700 border-white/40;
}
.chip {
    @apply text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200;
}
.chip-amber {
    @apply bg-amber-50 text-amber-800 border-amber-200;
}
.chip-green {
    @apply bg-emerald-50 text-emerald-800 border-emerald-200;
}

/* INPUT / LABEL / ERROR */
.label {
    @apply block text-xs font-bold text-slate-600;
}
.input {
    @apply mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 shadow-sm
    focus:ring-2 focus:ring-blue-100 focus:border-blue-500;
}
.input-readonly {
    @apply bg-white/80;
}
.input-danger {
    @apply border-red-500;
}
.err {
    @apply mt-1 text-xs text-red-600 font-semibold;
}

/* BUTTONS */
.btn {
    @apply inline-flex items-center gap-2 px-4 py-2 rounded-xl border font-semibold shadow-sm
    hover:shadow transition disabled:opacity-50 disabled:cursor-not-allowed;
}
.btn-ghost {
    @apply bg-white text-slate-700 border-slate-200 hover:bg-slate-50;
}
.btn-primary {
    @apply bg-blue-600 text-white border-blue-600 hover:bg-blue-700;
}
.btn-green {
    @apply bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700;
}
.field {
    @apply space-y-1;
}
.label {
    @apply block text-sm font-medium text-gray-700;
}

.iconInputWrap {
    @apply relative;
}
.iconLeft {
    @apply absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none;
}
.iconInputWrap :deep(input.flatpickr-input) {
    padding-left: 2.75rem; /* ~pl-11 */
}
/* ✅ più spazio reale */
.inputIcon {
    @apply pl-11;
}

/* Flatpickr wrap: fa sì che il contenuto riempia la riga */
.fpWrap :deep(.flatpickr-wrapper) {
    width: 100%;
}
.iconInputWrap :deep(input.flatpickr-input),
.iconInputWrap :deep(input.flatpickr-input.form-control),
.iconInputWrap :deep(.flatpickr-wrapper input) {
    padding-left: 2.75rem !important; /* ~pl-11 */
}
.err {
    @apply text-xs text-red-600 font-semibold;
}
.inputErr {
    @apply border-red-500 focus:border-red-500 focus:ring-red-200;
}
.chip-red {
    @apply bg-red-50 text-red-700 border border-red-200 font-semibold;
}
.sectionCard {
    @apply rounded-2xl border bg-white shadow-sm overflow-hidden;
}
.sectionHead {
    @apply px-4 py-3 border-b bg-slate-50;
}
.sectionTitle {
    @apply flex items-center gap-3 text-slate-900;
}
.field {
    @apply space-y-1;
}
.iconInputWrap {
    @apply relative;
}
.iconLeft {
    @apply absolute left-3 top-1/2 -translate-y-1/2 text-slate-400;
    width: 16px;
    height: 16px;
}
.inputIcon {
    @apply pl-10; /* spazio per icona */
}
.inputErr {
    @apply border-red-500;
}
.err {
    @apply text-xs font-semibold text-red-600;
}
.label {
    @apply block text-xs font-bold text-slate-600;
}
.iconLeft {
    @apply absolute left-3 top-1/2 -translate-y-1/2;
    width: 16px;
    height: 16px;
    transition: color 0.15s ease;
}

/* categorie */
.icon-residenza {
    color: #2563eb;
} /* blue-600 */
.icon-domicilio {
    color: #059669;
} /* emerald-600 */
.icon-soggiorno {
    color: #d97706;
} /* amber-600 */

/* focus input → icona più scura */
.iconInputWrap:focus-within .iconLeft {
    filter: brightness(0.85);
}

/* errore → rosso */
.inputErr + .iconLeft,
.iconInputWrap .inputErr ~ .iconLeft {
    color: #dc2626;
}
.field {
    @apply space-y-1;
}

.fieldGroup {
    @apply rounded-2xl border bg-slate-50 p-4 space-y-4;
}

.groupTitle {
    @apply flex items-center gap-2 text-sm font-extrabold text-slate-700;
}

.iconWrap {
    @apply relative;
}

.iconLeft {
    @apply absolute left-3 top-1/2 -translate-y-1/2;
    width: 16px;
    height: 16px;
}

.inputIcon {
    padding-left: 2.25rem;
}

/* colori */
.icon-fiscale {
    color: #2563eb;
} /* blue */
.icon-money {
    color: #059669;
} /* emerald */
.icon-bank {
    color: #7c3aed;
} /* violet */
.courseCard {
    @apply flex flex-col md:flex-row md:items-center justify-between
         gap-4 p-4 rounded-2xl border bg-white shadow-sm
         hover:shadow-md transition;
}

.courseLeft {
    @apply flex items-start gap-4;
}

.courseIcon {
    @apply w-10 h-10 rounded-xl flex items-center justify-center
         bg-indigo-100 text-indigo-600 text-lg;
}

.courseTitle {
    @apply font-extrabold text-slate-800;
}

.courseMeta {
    @apply text-xs text-slate-500 mt-1 space-x-2;
}

.badgeStatus {
    @apply inline-flex items-center px-2 py-0.5 rounded-full
         text-xs font-bold border;
}

.badge-ok {
    @apply bg-emerald-50 text-emerald-700 border-emerald-200;
}

.badge-warn {
    @apply bg-amber-50 text-amber-700 border-amber-200;
}

.courseActions {
    @apply flex gap-2 items-center;
}

.btnIcon {
    @apply w-9 h-9 flex items-center justify-center
         rounded-xl border shadow-sm transition
         hover:scale-105;
}
</style>
