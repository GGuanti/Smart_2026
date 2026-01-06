<!-- resources/js/Pages/Ordini/Form.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { computed, ref, onMounted, watch } from "vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import {
    User,
    MapPin,
    Home,
    Phone,
    Smartphone,
    Mail,
    FileText,
    Hash,
    StickyNote
} from "lucide-vue-next";
const toast = useToast();
const today = new Date().toISOString().slice(0, 10);
const confirmDelete = ref(false);

const props = defineProps({
    ordine: { type: Object, default: null }, // per edit
    elementi: { type: Array, default: () => [] },
    nextNordine: { type: Number, default: null }, // per create
    mode: { type: String, default: "create" }, // "create" | "edit"
    ivaList: { type: Array, default: () => [] },
    trasportiList: { type: Array, default: () => [] },
    QtaTotRighe: { type: Number, default: 0 },
    regioneUtente: { type: String, default: "" },
    tariffeTrasporto: { type: Array, default: () => [] },
});
const ivaPerc = computed(() => {
    const sel = props.ivaList.find((i) => Number(i.id) === Number(form.IdIva));
    return Number(sel?.valore ?? 0);
});
const totaleIva = computed(() => totaleScontato.value * (ivaPerc.value / 100));
const totaleConIva = computed(() => totaleScontato.value + totaleIva.value);
const isEdit = computed(() => props.mode === "edit");

const form = useForm({
    ID: props.ordine?.ID ?? null,
    righe: props.elementi ?? [],
    Nordine: props.ordine?.Nordine ?? props.nextNordine ?? "",
    TipoStampa: "Prev",
    CognomeNome: props.ordine?.CognomeNome ?? "",
    Telefono: props.ordine?.Telefono ?? "",
    Cellulare: props.ordine?.Cellulare ?? "",
    Indirizzo: props.ordine?.Indirizzo ?? "",
    IdCitta: props.ordine?.IdCitta ?? "",
    Provincia: props.ordine?.Provincia ?? "",
    CAP: props.ordine?.CAP ?? "",

    CodFiscale: props.ordine?.CodFiscale ?? "",
    PIva: props.ordine?.PIva ?? "",
    Email: props.ordine?.Email ?? "",

    Progettista: props.ordine?.Progettista ?? "",

    DataOrdine: props.ordine?.DataOrdine
        ? String(props.ordine.DataOrdine).slice(0, 10)
        : today,

    DataCons: props.ordine?.DataCons
        ? String(props.ordine.DataCons).slice(0, 10)
        : today,

    DataConferma: props.ordine?.DataConferma
        ? String(props.ordine.DataConferma).slice(0, 10)
        : "",

    TipoDoc: props.ordine?.TipoDoc ?? "Preventivo",
    IdIva: props.ordine?.IdIva ?? null,
    IdTrasporto: props.ordine?.IdTrasporto ?? null,
    CstTrasporto: props.ordine?.CstTrasporto ?? 0,

    Sconto1: props.ordine?.Sconto1 ?? 0,
    Sconto2: props.ordine?.Sconto2 ?? 0,

    Annotazioni: props.ordine?.Annotazioni ?? "",
    regioneUtente: props.regioneUtente || props.ordine?.regioneUtente || "",
});
console.log("Regione", props.regioneUtente);
const totaleProdotti = computed(() => {
    const righe = Array.isArray(form.righe) ? form.righe : [];
    return righe.reduce((sum, r) => {
        const qta = Number(r?.Qta ?? 0);
        const prezzo = Number(r?.PrezzoCad ?? 0);
        const prezzom = Number(r?.PrezzoMan ?? 0);
        return sum + qta * (prezzo + prezzom);
    }, 0);
});
const isSendingEmail = ref(false);

async function generaEInviaEmail() {
    const id = props.ordine?.ID ?? form.ID;
    if (!id) return toast.error("Salva l'ordine prima di inviare la email.");

    const to = String(form.Email || "").trim();
    if (!to)
        return toast.error("Inserisci l'email del cliente prima di inviare.");

    if (isSendingEmail.value) return; // anti doppio click
    isSendingEmail.value = true;

    try {
        const res = await axios.post(route("ordini.email.conferma", id), {
            to, // puoi anche non mandarlo e usare quello del DB, ma così prendi quello del form
        });

        toast.success("✅ Email inviata con PDF allegato!", {
            position: "top-center",
            timeout: 2500,
        });

        // opzionale: se vuoi aprire anche il PDF dopo l'invio
        // window.open(route("ordini.report.conferma", id), "_blank");
    } catch (e) {
        const msg =
            e?.response?.data?.message ||
            "❌ Errore invio email (controlla configurazione mail/log).";
        toast.error(msg, { position: "top-left", timeout: 3500 });
    } finally {
        isSendingEmail.value = false;
    }
}
const totaleScontato = computed(() => {
    let totale = totaleProdotti.value;
    if (form.Sconto1) totale *= 1 - form.Sconto1 / 100;
    if (form.Sconto2) totale *= 1 - form.Sconto2 / 100;
    return totale;
});
const scontoTotale = computed(() => {
    const s1 = Number(form.Sconto1 ?? 0);
    const s2 = Number(form.Sconto2 ?? 0);
    const a = Math.min(100, Math.max(0, s1)) / 100;
    const b = Math.min(100, Math.max(0, s2)) / 100;
    const tot = 1 - (1 - a) * (1 - b);
    return +(tot * 100).toFixed(2);
});

function focusNext(e) {
    const formEl = e.target.closest("form");
    if (!formEl) return;

    const tag = (e.target.tagName || "").toLowerCase();
    if (tag === "textarea") return;

    const elements = Array.from(
        formEl.querySelectorAll("input, select, textarea, button")
    ).filter((el) => {
        const type = (el.type || "").toLowerCase();
        return (
            !el.disabled &&
            type !== "hidden" &&
            el.tabIndex !== -1 &&
            el.offsetParent !== null
        );
    });

    const index = elements.indexOf(e.target);
    if (index > -1 && index < elements.length - 1) {
        elements[index + 1].focus();
    }
}

function submit() {
    const opts = {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                isEdit.value
                    ? "✅ Ordine aggiornato con successo!"
                    : "✅ Ordine creato con successo!",
                { position: "top-center", timeout: 2500 }
            );
        },
        onError: () => {
            toast.error("❌ Controlla i campi: ci sono errori.", {
                position: "top-left",
                timeout: 3000,
            });
        },
    };

    if (isEdit.value) {
        form.put(route("ordini.update", form.ID ?? props.ordine?.ID), opts);
    } else {
        form.post(route("ordini.store"), opts);
    }
}

function destroy() {
    if (!confirmDelete.value) {
        confirmDelete.value = true;

        toast.warning("⚠️ Confermi eliminazione ordine?", {
            position: "top-left",
            timeout: 8000,
            closeOnClick: false,
            draggable: false,
            hideProgressBar: false,
            toastClassName: "toast-confirm",
            onClose: () => {
                confirmDelete.value = false;
            },
        });

        return;
    }

    form.delete(route("ordini.destroy", form.ID ?? props.ordine?.ID), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("🗑️ Ordine eliminato con successo", {
                position: "top-left",
                timeout: 2500,
            });

            window.location.href = route("ordini.index");
        },
        onError: () => {
            toast.error("❌ Errore durante l’eliminazione dell’ordine", {
                position: "top-left",
                timeout: 3000,
            });
            confirmDelete.value = false;
        },
    });
}
function generaReport() {
    const id = props.ordine?.ID ?? form.ID;
    const tipo = form.TipoStampa;
    if (!id)
        return toast.error("Salva l'ordine prima di generare la conferma.");
    window.open(
        route("ordini.report.conferma", {
            id: id,
            tipo: tipo,
        }),
        "_blank"
    );
}
function generaReport1() {
    const id = props.ordine?.ID ?? form.ID;
    const tipo = form.TipoStampa;
    console.log("Tipo", tipo);
    if (!id)
        return toast.error("Salva l'ordine prima di generare la conferma.");

    const url = `/report/genera?ordine=${id}&tipo=${encodeURIComponent(tipo)}`;
    console.log("URL REPORT:", url);
    window.open(url, "_blank");
}

onMounted(() => {
    // se NON è edit e l'IVA non è impostata → default 22%
    if (!isEdit.value && !form.IdIva && props.ivaList?.length) {
        const iva22 = props.ivaList.find((i) => Number(i.valore) === 22);
        if (iva22) {
            form.IdIva = iva22.id;
        }
    }
    if (form.IdTrasporto == null || form.IdTrasporto === "") {
    form.IdTrasporto = 2;
  }
});
watch(
    () => [
        form.regioneUtente,
        props.QtaTotRighe,
        form.IdTrasporto,
        props.tariffeTrasporto,
    ],
    () => {
        // Trasporto escluso -> 0
        if (Number(form.IdTrasporto) === 1 || Number(form.IdTrasporto) === 4) {
            form.CstTrasporto = 0;
            return;
        }

        const regione = String(form.regioneUtente || "")
            .toUpperCase()
            .trim();
        const qta = Number(props.QtaTotRighe) || 0;

        // cerca tariffa
        const t = (props.tariffeTrasporto || []).find(
            (x) =>
                String(x.regione || "")
                    .toUpperCase()
                    .trim() === regione
        );

        if (!t) {
            // se non trovo tariffa: NON forzo (lascia valore attuale)
            return;
        }

        const costo = Number(t.costo) || 0;
        const minTass = Number(t.min_tass) || 0;

        form.CstTrasporto = Math.max(qta * costo, minTass);
    },
    { immediate: true }
);
function copiaOrdine() {
    const id = props.ordine?.ID ?? form.ID;

    if (!id) {
        toast.error("Salva l'ordine prima di copiarlo.");
        return;
    }

    router.post(
        route("ordini.copia", id),
        {},
        {
            preserveScroll: true,
            onStart: () => {
                toast.info("📄 Copia ordine in corso…");
            },
            onSuccess: () => {
                toast.success("✅ Ordine copiato");
            },
            onError: () => {
                toast.error("❌ Errore copia ordine");
            },
        }
    );
}

</script>

<template>
    <AuthenticatedLayout>
        <Head :title="isEdit ? 'Modifica Ordine' : 'Nuovo Ordine'" />
        <div
            class="min-h-screen bg-[radial-gradient(1200px_circle_at_20%_0%,rgba(59,130,246,.10),transparent_60%),radial-gradient(900px_circle_at_90%_10%,rgba(99,102,241,.10),transparent_55%),linear-gradient(to_bottom,rgba(248,250,252,1),rgba(255,255,255,1))]"
        >
            <div class="sticky top-0 z-10 border-b bg-white/75 backdrop-blur">
                <div
                    class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-3"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-4">
                                <img src="/Logo1.png" alt="Logo" class="h-10" />
                            </div>
                            <h1 class="text-2xl font-extrabold text-slate-900">
                                Ordini • {{ isEdit ? "Modifica" : "Nuovo" }}
                            </h1>

                            <span class="badge badge-slate">
                                {{ isEdit ? "Edit" : "Inserimento" }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('ordini.index')"
                            class="btn btn-ghost"
                        >
                            ⬅️ Elenco
                        </Link>

                        <!-- ✅ fix: in create ordine è null, quindi uso props.ordine?.ID -->
                        <Link
                            v-if="isEdit && (props.ordine?.ID || form.ID)"
                            :href="
                                route(
                                    'preventivi.create',
                                    props.ordine?.ID ?? form.ID
                                )
                            "
                            class="px-3 py-2 rounded-lg bg-blue-600 text-white font-bold"
                        >
                            ➕ Apri Elenco Prodotti
                        </Link>
                        <button
                            v-if="isEdit && (props.ordine?.ID || form.ID)"
                            type="button"
                            class="px-3 py-2 rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 shadow-sm hover:shadow transition font-extrabold flex items-center gap-1"
                            :disabled="form.processing"
                            @click="copiaOrdine"
                        >
                            📄 Copia Ordine
                        </button>

                        <button
                            type="submit"
                            form="ordineForm"
                            class="btn btn-primary"
                            :disabled="form.processing"
                        >
                            💾 {{ isEdit ? "Salva" : "Crea" }}
                        </button>

                        <button
                            v-if="isEdit"
                            type="button"
                            class="btn btn-danger"
                            @click="destroy"
                            :disabled="form.processing"
                        >
                            {{
                                confirmDelete
                                    ? "⚠️ Conferma elimina"
                                    : "🗑️ Elimina"
                            }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto p-4">
                <!-- KPI -->
                <div class="grid grid-cols-12 gap-3 mb-4">
                    <div class="col-span-12 md:col-span-3 card cardKpi">
                        <div class="kpiLabel">Numero Ordine</div>
                        <div class="kpiValue">{{ form.Nordine || "—" }}</div>
                    </div>

                    <div class="col-span-12 md:col-span-4 card cardKpi">
                        <div class="kpiLabel">Tipo Documento</div>
                        <div class="kpiValue">{{ form.TipoDoc || "—" }}</div>
                    </div>
                </div>

                <form
                    id="ordineForm"
                    class="grid grid-cols-12 gap-4"
                    @submit.prevent="submit"
                >
                    <!-- LEFT -->
                    <div class="col-span-12 lg:col-span-5 space-y-4">
                        <div class="card overflow-hidden">
                            <div
                                class="cardHead bg-gradient-to-r from-blue-600 to-indigo-600 text-white"
                            >
                            <div class="font-extrabold flex items-center gap-2">
    <User class="w-5 h-5" />
    Cliente
</div>

                                <div class="text-xs opacity-90">
                                    Dati anagrafici e contatti
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="grid grid-cols-12 gap-3">
                                    <div class="col-span-12">
    <label class="label">Cognome Nome</label>
    <div class="input-icon">
        <User class="w-4 h-4" />
        <input
            v-model="form.CognomeNome"
            class="input"
            placeholder="Es. Rossi Mario"
            @keydown.enter.prevent="focusNext"
        />
    </div>
    <div v-if="form.errors.CognomeNome" class="err">
        {{ form.errors.CognomeNome }}
    </div>
</div>

                                   <div class="col-span-12 md:col-span-7">
    <label class="label">Indirizzo</label>
    <div class="input-icon">
        <Home class="w-4 h-4" />
        <input
            v-model="form.Indirizzo"
            class="input"
            @keydown.enter.prevent="focusNext"
        />
    </div>
</div>


                                    <div class="col-span-12 md:col-span-5">
    <label class="label">Città</label>
    <div class="input-icon">
        <MapPin class="w-4 h-4" />
        <input
            v-model="form.IdCitta"
            class="input"
            @keydown.enter.prevent="focusNext"
        />
    </div>
</div>


                                    <div class="col-span-6 md:col-span-3">
                                        <label class="label">Provincia</label>
                                        <input
                                            v-model="form.Provincia"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        />
                                    </div>

                                    <div class="col-span-6 md:col-span-3">
                                        <label class="label">CAP</label>
                                        <input
                                            v-model="form.CAP"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        />
                                    </div>

                                    <div class="col-span-12 md:col-span-6">
    <label class="label">Telefono</label>
    <div class="input-icon">
        <Phone class="w-4 h-4" />
        <input v-model="form.Telefono" class="input" />
    </div>
</div>

<div class="col-span-12 md:col-span-6">
    <label class="label">Cellulare</label>
    <div class="input-icon">
        <Smartphone class="w-4 h-4" />
        <input v-model="form.Cellulare" class="input" />
    </div>
</div>


                                    <div class="col-span-12 md:col-span-6">
    <label class="label">Cod. Fiscale</label>
    <div class="input-icon">
        <FileText class="w-4 h-4" />
        <input v-model="form.CodFiscale" class="input" />
    </div>
</div>

<div class="col-span-12 md:col-span-6">
    <label class="label">P. IVA</label>
    <div class="input-icon">
        <Hash class="w-4 h-4" />
        <input v-model="form.PIva" class="input" />
    </div>
</div>


                                    <div class="col-span-12">
    <label class="label">Email</label>
    <div class="input-icon">
        <Mail class="w-4 h-4" />
        <input v-model="form.Email" class="input" />
    </div>
</div>

                                    <div class="col-span-12">
    <label class="label flex items-center gap-1">
        <StickyNote class="w-4 h-4" />
        Note (Invio = a capo)
    </label>
    <div class="input-icon">
        <textarea
            v-model="form.Annotazioni"
            rows="2"
            class="input"
        ></textarea>
    </div>
</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT -->
                    <div class="col-span-12 lg:col-span-7 space-y-4">
                        <div class="card overflow-hidden">
                            <div
                                class="cardHead bg-gradient-to-r from-blue-600 to-indigo-600 text-white"
                            >
                                <div class="font-extrabold">📄 Documento</div>
                                <div class="text-xs opacity-90">
                                    Testata ordine/preventivo
                                </div>
                            </div>

                            <div class="p-2.5">
                                <div class="grid grid-cols-12 gap-2">
                                    <div class="col-span-6 md:col-span-3">
                                        <label class="label">Nordine</label>
                                        <input
                                            v-model="form.Nordine"
                                            type="number"
                                            class="input bg-amber-50 font-extrabold"
                                            readonly
                                        />
                                    </div>

                                    <div class="col-span-6 md:col-span-4">
                                        <label class="label">Data Ordine</label>
                                        <input
                                            v-model="form.DataOrdine"
                                            type="date"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        />
                                    </div>

                                    <div class="col-span-12 md:col-span-5">
                                        <label class="label"
                                            >Data Consegna</label
                                        >
                                        <input
                                            v-model="form.DataCons"
                                            type="date"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        />
                                    </div>

                                    <div class="col-span-12 md:col-span-6">
                                        <label class="label">Tipo Doc</label>
                                        <select
                                            v-model="form.TipoDoc"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        >
                                            <option value="Preventivo">
                                                Preventivo
                                            </option>
                                            <option value="Ordine">
                                                Ordine
                                            </option>
                                            <option value="Ordine inviato">
                                                Ordine inviato
                                            </option>
                                            <option value="Consegnato">
                                                Consegnato
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-6">
                                        <label class="label">IVA</label>
                                        <select
                                            v-model.number="form.IdIva"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        >
                                            <option
                                                v-for="i in props.ivaList"
                                                :key="i.id"
                                                :value="i.id"
                                            >
                                                {{ i.des }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-6">
                                        <label class="label">Trasporto</label>
                                        <select
                                            v-model.number="form.IdTrasporto"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        >
                                            <option
                                                v-for="t in props.trasportiList"
                                                :key="t.id"
                                                :value="t.id"
                                            >
                                                {{ t.des }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-5">
                                        <label
                                            class="text-xs font-semibold text-gray-600"
                                            >Costo Trasporto
                                            <span class="ml-1 text-gray-500">
                                                ({{
                                                    form.regioneUtente || "—"
                                                }})
                                            </span></label
                                        >

                                        <input
                                            v-model.number="form.CstTrasporto"
                                            type="number"
                                            step="0.01"
                                            class="input bg-green-50 font-extrabold text-lg disabled:bg-gray-100 disabled:text-gray-400"
                                            disabled
                                            @keydown.enter.prevent="focusNext"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card overflow-hidden">
                            <div
                                class="cardHead bg-gradient-to-r from-blue-600 to-indigo-600 text-white"
                            >        <div  class="font-extrabold">
                                    💸 Sconti
                                </div>
                                <div class="text-xs opacity-90">
                                    Calcolo composto
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="grid grid-cols-12 gap-3">
                                    <div class="col-span-12 md:col-span-3">
                                        <label class="label"
                                            >Totale Iva Esclusa</label
                                        >
                                        <input
                                            type="text"
                                            class="input bg-slate-100 font-bold text-lg"
                                            :value="`€ ${totaleProdotti.toFixed(
                                                2
                                            )}`"
                                            disabled
                                        />
                                    </div>
                                    <div class="col-span-12 md:col-span-2">
                                        <label class="label"
                                            >Sconto 1 (%)</label
                                        >
                                        <input
                                            v-model.number="form.Sconto1"
                                            type="number"
                                            step="0.01"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        />
                                    </div>

                                    <div class="col-span-12 md:col-span-2">
                                        <label class="label"
                                            >Sconto 2 (%)</label
                                        >
                                        <input
                                            v-model.number="form.Sconto2"
                                            type="number"
                                            step="0.01"
                                            class="input"
                                            @keydown.enter.prevent="focusNext"
                                        />
                                    </div>

                                    <div class="col-span-12 md:col-span-2">
                                        <label class="label"
                                            >Sconto Totale</label
                                        >
                                        <input
                                            :value="
                                                scontoTotale.toFixed(2) + '%'
                                            "
                                            class="input bg-amber-50 font-extrabold"
                                            readonly
                                        />
                                    </div>
                                    <div class="col-span-12 md:col-span-3">
                                        <label class="label"
                                            >Tot. Scontato Iva E.</label
                                        >
                                        <input
                                            type="text"
                                            class="input bg-green-50 font-extrabold text-lg"
                                            :value="`€ ${totaleScontato.toFixed(
                                                2
                                            )}`"
                                            disabled
                                        />
                                    </div>
                                    <div class="col-span-12 md:col-span-5">
                                        <label class="label text-indigo-700">
                                            Totale Ivato
                                        </label>

                                        <div
                                            class="mt-1 rounded-2xl border-2 border-indigo-500 bg-gradient-to-br from-indigo-50 to-white px-4 py-3 shadow-md"
                                        >
                                            <div
                                                class="text-xs uppercase font-bold text-indigo-600 tracking-wide"
                                            >
                                                Importo finale
                                            </div>

                                            <div
                                                class="text-2xl md:text-3xl font-extrabold text-indigo-900 mt-1"
                                            >
                                                €
                                                {{ totaleConIva.toFixed(2) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="col-span-12 flex flex-wrap items-end gap-3"
                                    >
                                        <!-- Stampa -->
                                        <div class="flex flex-col">
                                            <label class="label">Stampa</label>
                                            <select
                                                v-model="form.TipoStampa"
                                                class="input min-w-[220px]"
                                            >
                                                <option value="Prev">
                                                    Preventivo
                                                </option>
                                                <option value="Ord">
                                                    Ordine
                                                </option>
                                                <option value="ConfOrd">
                                                    Conferma Ordine
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Genera Report -->
                                        <button
                                            type="button"
                                            @click="generaReport"
                                            class="h-[42px] bg-green-600 hover:bg-green-700 text-white px-4 rounded flex items-center gap-1"
                                        >
                                            🖨️ Genera Report
                                        </button>

                                        <button
                                            type="button"
                                            @click="generaEInviaEmail"
                                            class="h-[42px] bg-blue-600 hover:bg-blue-700 text-white px-4 rounded flex items-center gap-1 disabled:opacity-60 disabled:cursor-not-allowed"
                                            :disabled="
                                                isSendingEmail ||
                                                form.processing
                                            "
                                        >
                                            <span v-if="!isSendingEmail"
                                                >✉️ Invia Email</span
                                            >
                                            <span v-else>📨 Invio...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div
                            v-if="Object.keys(form.errors).length"
                            class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm"
                        >
                            <div class="font-extrabold text-red-900 mb-2">
                                ⚠️ Errori
                            </div>
                            <div
                                v-for="(msg, key) in form.errors"
                                :key="key"
                                class="text-sm text-red-800"
                            >
                                <strong>{{ key }}:</strong> {{ msg }}
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.btn {
    @apply px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 transition;
}
.btn-ghost {
    @apply px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 transition;
}
.btn-primary {
    @apply px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold shadow hover:bg-blue-700 transition disabled:opacity-60 disabled:cursor-not-allowed;
}

.badge {
    @apply text-xs font-extrabold px-2.5 py-1 rounded-full border;
}
.badge-blue {
    @apply bg-blue-50 text-blue-700 border-blue-100;
}
.badge-slate {
    @apply bg-slate-50 text-slate-700 border-slate-200;
}

.card {
    @apply rounded-2xl border bg-white shadow-sm;
}
.cardHead {
    @apply px-4 py-3 border-b flex items-center justify-between;
}

.cardKpi {
    @apply flex flex-col items-center justify-center text-center min-h-[72px] px-4 py-3;
}
.kpiLabel {
    @apply text-xs text-slate-500 font-semibold leading-none;
}
.kpiValue {
    @apply text-2xl font-extrabold text-slate-900 leading-none;
}

.label {
    @apply text-xs font-bold text-slate-600;
}
.input {
    @apply mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2
  text-slate-900 outline-none shadow-sm
  focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition;
}
.err {
    @apply text-xs text-red-600 mt-1 font-semibold;
}
.toast-confirm {
    @apply border-l-4 border-red-500 bg-red-50 text-red-900;
}
.btn-danger {
    @apply px-4 py-2 rounded-xl bg-red-600 text-white font-semibold
  shadow hover:bg-red-700 transition disabled:opacity-60 disabled:cursor-not-allowed;
}
.input-icon {
    @apply relative;
}

.input-icon svg {
    @apply absolute left-3 top-1/2 -translate-y-1/2 text-slate-400;
}

.input-icon input,
.input-icon textarea {
    @apply pl-10;
}
</style>
