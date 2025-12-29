<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, onMounted, ref, watch } from "vue";
import { useToast } from "vue-toastification";
import { Save, Trash2, ArrowLeft, Plus } from "lucide-vue-next";

const toast = useToast();
const modelli = computed(() =>
    Array.isArray(props.modelli) ? props.modelli : []
);

const props = defineProps({
    ordine: { type: Object, required: true }, // padre (serve Nordine + permessi server)
    // se vuoi in futuro caricare righe già esistenti:
    elementi: { type: Array, default: () => [] }, // array di TabElementiOrdine (opzionale)
    // lookup select
    modelli: { type: Array, default: () => [] },
    colAnta: { type: Array, default: () => [] },
    colTelaio: { type: Array, default: () => [] },
    soluzioni: { type: Array, default: () => [] },
    maniglie: { type: Array, default: () => [] },
    aperture: { type: Array, default: () => [] },
    tipiTelaio: { type: Array, default: () => [] },
    vetri: { type: Array, default: () => [] },
    colFerr: { type: Array, default: () => [] },
    serrature: { type: Array, default: () => [] },
    imbotte: { type: Array, default: () => [] },
});

function normalizeOpt(x) {
    const id =
        x?.id ??
        x?.ID ??
        x?.Id ??
        x?.value ??
        x?.Value ??
        x?.key ??
        x?.Key ??
        x?.Cod ??
        x?.Codice ??
        "";
    const label =
        x?.label ??
        x?.Label ??
        x?.nome ??
        x?.Nome ??
        x?.descrizione ??
        x?.Descrizione ??
        x?.testo ??
        x?.Testo ??
        x?.value ??
        String(id ?? "");
    return { id, label };
}

function newRigaFromElemento(el = null) {
    return {
        // uid solo client
        uid: crypto?.randomUUID
            ? crypto.randomUUID()
            : String(Date.now() + Math.random()),
        // id DB (se esiste)
        Id: el?.Id ?? el?.ID ?? null,
        DimL: el?.DimL ?? "",
        DimA: el?.DimA ?? "",
        DimSp: el?.DimSp ?? "",
        Qta: el?.Qta ?? 1,
        PrezzoCad: el?.PrezzoCad ?? 0,
        PrezzoMan: el?.PrezzoMan ?? 0,

        NoteMan: el?.NoteMan ?? "",
        PercFile: el?.PercFile ?? "",
        TxtCassMet: el?.TxtCassMet ?? "",

        IdModello: el?.IdModello ?? "",
        IdSoluzione: el?.IdSoluzione ?? "",
        IdColAnta: el?.IdColAnta ?? "",
        IdColTelaio: el?.IdColTelaio ?? "",
        IdManiglia: el?.IdManiglia ?? "",
        IdApertura: el?.IdApertura ?? "",
        IdTipTelaio: el?.IdTipTelaio ?? "",
        IdVetro: el?.IdVetro ?? "",
        IdColFerr: el?.IdColFerr ?? "",
        IdSerratura: el?.IdSerratura ?? "",
        CkTaglioObl: el?.CkTaglioObl ?? "No",
        IdImbotte: el?.IdImbotte ?? "",
        _imgKey: 0,
    };
}

// Enter = campo successivo (NO textarea)
const focusNext = (event) => {
    const tag = (event.target?.tagName || "").toLowerCase();
    if (tag === "textarea") return; // invio = a capo
    event.preventDefault();

    const formEl = event.target.form;
    if (!formEl) return;

    const focusable = Array.from(
        formEl.querySelectorAll(
            'input:not([disabled]):not([type="hidden"]), select:not([disabled]), button:not([disabled])'
        )
    ).filter((el) => el.offsetParent !== null);

    const index = focusable.indexOf(event.target);
    if (index > -1 && focusable[index + 1]) focusable[index + 1].focus();
};

const form = useForm({
    Nordine: props.ordine?.Nordine ?? null,
    righe: props.elementi?.length
        ? props.elementi.map((e) => newRigaFromElemento(e))
        : [newRigaFromElemento()],
});

// totali
const totalePreventivo = computed(() =>
    form.righe.reduce((sum, r) => {
        const q = Number(r.Qta ?? 0);
        const p = Number(r.PrezzoCad ?? 0);
        const m = Number(r.PrezzoMan ?? 0);
        return sum + q * (p + m);
    }, 0)
);

function totaleRiga(r) {
    const q = Number(r.Qta ?? 0);
    const p = Number(r.PrezzoCad ?? 0);
    const m = Number(r.PrezzoMan ?? 0);
    return +(q * (p + m)).toFixed(2);
}

function addRiga() {
    form.righe.push(newRigaFromElemento());
    toast.success("➕ Riga aggiunta", { position: "top-left", timeout: 1200 });
}

function removeRigaLocal(i) {
    form.righe.splice(i, 1);
    toast.info("Riga rimossa (non ancora salvata)", {
        position: "top-left",
        timeout: 1200,
    });
}

// conferma eliminazione (toast double-click)
const askDelete = ref({}); // { [uid]: true }

function requestDelete(uid) {
    askDelete.value[uid] = true;
    toast.info("🗑️ Confermi eliminazione? Premi di nuovo Elimina.", {
        position: "top-left",
        timeout: 2200,
    });
    setTimeout(() => (askDelete.value[uid] = false), 2200);
}

function deleteRigaDb(riga) {
    // se non ha Id DB -> elimino solo local
    if (!riga.Id) return;

    if (!askDelete.value[riga.uid]) return requestDelete(riga.uid);

    router.delete(
        route("ordini.preventivi.destroy", [props.ordine.ID, riga.Id]),
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success("✅ Riga eliminata", { position: "top-left" }),
            onError: () =>
                toast.error("❌ Errore eliminazione", { position: "top-left" }),
        }
    );
}

function submitAll() {
    // Nordine forzato
    form.Nordine = props.ordine?.Nordine ?? form.Nordine;

    // salva tutte le righe
    form.post(route("preventivi.store", props.ordine.ID), {
        preserveScroll: true,
        onSuccess: () =>
            toast.success("✅ Preventivo salvato (righe registrate)", {
                position: "top-left",
            }),
        onError: () =>
            toast.error("❌ Errore nel salvataggio", { position: "top-left" }),
    });
}
function modelloId(m) {
    return (
        m?.id_listino ??
        m?.ID ??
        m?.Id ??
        m?.IdModello ??
        m?.IDModello ??
        m?.value ??
        null
    );
}


function modelloNome(m) {
  return (
    m?.nome_modello ??
    m?.NomeModello ??
    m?.nome ??
    m?.Nome ??
    m?.modello ??
    m?.Modello ??
    ""
  ).toString().trim();
}
function modelloById(id) { return ( props.modelli.find((m) => Number(modelloId(m)) === Number(id)) || null ); }
function fotoUrlForRiga(riga) {
    const m = modelloById(riga.IdModello);
    const nome = modelloNome(m);
    if (!nome) return "/foto/placeholder.jpg";
    return `/foto/${encodeURIComponent(nome)}.jpg?v=${riga._imgKey ?? 0}`;
}

// 🔁 key per forzare reload immagine quando cambia modello
function bumpImgKey(riga) {
    riga._imgKey = (riga._imgKey ?? 0) + 1;
}

// quando cambia IdModello su QUALSIASI riga, aggiorna la key immagine

watch(
    () => form.righe.map((r) => r.IdModello),
    (newIds, oldIds) => {
        form.righe.forEach((r, i) => {
            // bump solo sulla riga che è cambiata
            if (newIds?.[i] !== oldIds?.[i]) bumpImgKey(r);
        });
    }
);
onMounted(() => {
    console.table(
        props.modelli.map((m) => ({ id: m.id_listino, nome: m.nome_modello }))
    );
    if (!form.Nordine && props.ordine?.Nordine)
        form.Nordine = props.ordine.Nordine;
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Preventivo" />

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- HEADER -->
                <div
                    class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <img
                            src="/Logo.jpg"
                            alt="Logo"
                            class="h-10 w-auto hidden md:block"
                        />
                        <div>
                            <div class="flex items-center gap-2">
                                <h1
                                    class="text-2xl font-extrabold tracking-tight"
                                >
                                    Preventivi · Nuovo
                                </h1>
                                <span
                                    class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-semibold"
                                    >ERP</span
                                >
                            </div>
                            <div class="text-sm text-gray-500">
                                Nordine
                                <span class="font-semibold text-gray-700"
                                    >#{{ props.ordine?.Nordine }}</span
                                >
                            </div>
                        </div>
                        <div
                            class="col-span-12 md:col-span-5 rounded-2xl border bg-white shadow-sm p-4"
                        >
                            <div class="text-xs font-semibold text-gray-500">
                                Totale preventivo
                            </div>
                            <div
                                class="mt-1 text-2xl font-extrabold text-gray-900"
                            >
                                € {{ totalePreventivo.toFixed(2) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <Link
                            :href="route('ordini.edit', props.ordine.ID)"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border bg-white hover:bg-gray-50 shadow-sm"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            Torna ordine
                        </Link>

                        <button
                            type="button"
                            @click="addRiga"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-900 shadow-sm"
                        >
                            <Plus class="w-4 h-4" />
                            Aggiungi riga
                        </button>

                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="submitAll"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-sm disabled:opacity-50"
                        >
                            <Save class="w-4 h-4" />
                            Salva preventivo
                        </button>
                    </div>
                </div>

                <!-- FORM -->
                <form @submit.prevent="submitAll">
                    <div
                        v-for="(riga, i) in form.righe"
                        :key="riga.uid"
                        class="mb-5 rounded-2xl border bg-white shadow-sm overflow-hidden"
                    >
                        <!-- Riga header -->
                        <div
                            class="px-5 py-3 bg-gradient-to-r from-slate-900 to-slate-700 text-white flex items-center justify-between"
                        >
                            <div class="font-semibold">Riga #{{ i + 1 }}</div>
                            <div class="flex items-center gap-2">
                                <div class="text-xs opacity-90">
                                    Tot riga:
                                    <span class="font-bold"
                                        >€
                                        {{ totaleRiga(riga).toFixed(2) }}</span
                                    >
                                </div>

                                <!-- Elimina -->
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm"
                                    @click="
                                        riga.Id
                                            ? deleteRigaDb(riga)
                                            : removeRigaLocal(i)
                                    "
                                >
                                    <Trash2 class="w-4 h-4" />
                                    Elimina
                                </button>
                            </div>
                        </div>

                        <div class="p-5 grid grid-cols-12 gap-4">
                            <!-- CONFIG -->
                            <div class="col-span-12 lg:col-span-7">
                                <div
                                    class="rounded-2xl border bg-white overflow-hidden"
                                >
                                    <div
                                        class="px-4 py-2 bg-blue-50 border-b text-blue-900 font-semibold flex justify-between"
                                    >
                                        <span>🧩 Configurazione</span>
                                        <span class="text-xs text-blue-700"
                                            >Modello, colori, accessori</span
                                        >
                                    </div>

                                    <div class="p-4 grid grid-cols-12 gap-4">
                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                            >
                                                Modello
                                            </label>

                                            <select
                                                v-model.number="riga.IdModello"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                @change="bumpImgKey(riga)"
                                            >
                                                <option
                                                    v-for="m in props.modelli"
                                                    :key="m.id_listino"
                                                    :value="m.id_listino"
                                                >
                                                    {{ m.nome_modello }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Soluzione</label
                                            >
                                            <select
                                                v-model="riga.IdSoluzione"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm focus:ring focus:ring-blue-200"
                                                @keydown.enter.prevent="
                                                    focusNext
                                                "
                                            >
                                                <option
                                                    v-for="m in props.soluzioni"
                                                    :key="m.id"
                                                    :value="m.id"
                                                >
                                                    {{ m.soluzione }}
                                                </option>

                                                <option
                                                    v-for="m in props.soluzioni"
                                                    :key="m.id"
                                                    :value="m.id"
                                                >
                                                    {{ m.soluzione }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Colore Anta</label
                                            >
                                            <select
                                                v-model="riga.IdColAnta"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                @keydown.enter.prevent="
                                                    focusNext
                                                "
                                            >
                                                <option
                                                    v-for="x in props.colAnta"
                                                    :key="x.IdFinAnta"
                                                    :value="x.IdFinAnta"
                                                >
                                                    {{ x.Tipologia }} -
                                                    {{ x.Colore }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Colore Telaio</label
                                            >
                                            <select
                                                v-model="riga.IdColTelaio"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                @keydown.enter="focusNext"
                                            >
                                                <option
                                                    v-for="x in props.colTelaio"
                                                    :key="x.IdFinTelaio"
                                                    :value="x.IdFinTelaio"
                                                >
                                                    {{ x.Tipologia }} -
                                                    {{ x.Colore }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-4">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Maniglia</label
                                            >
                                            <select
                                                v-model="riga.IdManiglia"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                @keydown.enter="focusNext"
                                            >
                                                <option value="">
                                                    — Seleziona —
                                                </option>
                                                <option
                                                    v-for="x in props.maniglie"
                                                    :key="normalizeOpt(x).id"
                                                    :value="normalizeOpt(x).id"
                                                >
                                                    {{ normalizeOpt(x).label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-4">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Apertura</label
                                            >
                                            <select
                                                v-model="riga.IdApertura"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                @keydown.enter="focusNext"
                                            >
                                                <option value="">
                                                    — Seleziona —
                                                </option>
                                                <option
                                                    v-for="x in props.aperture"
                                                    :key="normalizeOpt(x).id"
                                                    :value="normalizeOpt(x).id"
                                                >
                                                    {{ normalizeOpt(x).label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12 md:col-span-4">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Tipo Telaio</label
                                            >
                                            <select
                                                v-model="riga.IdTipTelaio"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                @keydown.enter="focusNext"
                                            >
                                                <option value="">
                                                    — Seleziona —
                                                </option>
                                                <option
                                                    v-for="x in props.tipiTelaio"
                                                    :key="normalizeOpt(x).id"
                                                    :value="normalizeOpt(x).id"
                                                >
                                                    {{ normalizeOpt(x).label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-span-12">
                                            <label
                                                class="text-sm font-semibold text-gray-800"
                                                >Note</label
                                            >
                                            <textarea
                                                v-model="riga.NoteMan"
                                                rows="1"
                                                class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                                placeholder="Annotazioni lavorazioni..."
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- MISURE & PREZZI -->
                            <div class="col-span-12 lg:col-span-5">
                                <div
                                    class="rounded-2xl border bg-white overflow-hidden"
                                >
                                    <div
                                        class="px-4 py-2 bg-slate-50 border-b text-slate-900 font-semibold flex justify-between"
                                    >
                                        <span>📐 Misure & Prezzi</span>
                                        <span class="text-xs text-slate-500"
                                            >Qta + prezzi sotto immagine</span
                                        >
                                    </div>

                                    <div class="p-4">
                                        <!-- immagine più stretta -->
                                        <div
                                            class="rounded-lg border bg-white p-2 flex items-center justify-center w-36 mx-auto shadow-sm"
                                        >
                                            <div
                                                class="rounded-lg border bg-white p-2 flex items-center justify-center w-44 mx-auto shadow-sm"
                                            >
                                                <img
                                                    :key="`${riga.IdModello}-${riga._imgKey}`"
                                                    :src="fotoUrlForRiga(riga)"
                                                    class="h-24 w-auto object-contain"
                                                    @error="
                                                        (e) => {
                                                            const src =
                                                                e.target.src;
                                                            if (
                                                                src.includes(
                                                                    '.jpg'
                                                                )
                                                            )
                                                                e.target.src =
                                                                    src.replace(
                                                                        '.jpg',
                                                                        '.png'
                                                                    );
                                                            else if (
                                                                src.includes(
                                                                    '.png'
                                                                )
                                                            )
                                                                e.target.src =
                                                                    src.replace(
                                                                        '.png',
                                                                        '.webp'
                                                                    );
                                                            else
                                                                e.target.src =
                                                                    '/foto/placeholder.jpg';
                                                        }
                                                    "
                                                />
                                            </div>
                                        </div>

                                        <!-- misure -->
                                        <div
                                            class="mt-4 grid grid-cols-3 gap-3"
                                        >
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >DimL</label
                                                >
                                                <input
                                                    v-model.number="riga.DimL"
                                                    type="number"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >DimA</label
                                                >
                                                <input
                                                    v-model.number="riga.DimA"
                                                    type="number"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >DimSp</label
                                                >
                                                <input
                                                    v-model.number="riga.DimSp"
                                                    type="number"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                        </div>

                                        <!-- qta e prezzi sotto immagine -->
                                        <div
                                            class="mt-4 grid grid-cols-3 gap-3"
                                        >
                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >Qta</label
                                                >
                                                <input
                                                    v-model.number="riga.Qta"
                                                    type="number"
                                                    min="1"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >Prezzo Cad.</label
                                                >
                                                <input
                                                    v-model.number="
                                                        riga.PrezzoCad
                                                    "
                                                    type="number"
                                                    step="0.01"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs font-semibold text-gray-600"
                                                    >Prezzo Man.</label
                                                >
                                                <input
                                                    v-model.number="
                                                        riga.PrezzoMan
                                                    "
                                                    type="number"
                                                    step="0.01"
                                                    class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"
                                                    @keydown.enter="focusNext"
                                                />
                                            </div>
                                        </div>

                                        <div
                                            class="mt-4 rounded-xl border bg-slate-50 p-3 flex items-center justify-between"
                                        >
                                            <div class="text-xs text-slate-600">
                                                Totale riga
                                            </div>
                                            <div
                                                class="text-lg font-extrabold text-slate-900"
                                            >
                                                €
                                                {{
                                                    totaleRiga(riga).toFixed(2)
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- errori -->
                            <div
                                v-if="Object.keys(form.errors).length"
                                class="col-span-12 rounded-2xl border border-red-200 bg-red-50 p-4"
                            >
                                <div class="font-semibold text-red-800 mb-2">
                                    Errori:
                                </div>
                                <div
                                    v-for="(msg, key) in form.errors"
                                    :key="key"
                                    class="text-sm text-red-700"
                                >
                                    <strong>{{ key }}:</strong> {{ msg }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- footer -->
                    <div class="flex justify-end gap-2 mt-4">
                        <button
                            type="button"
                            @click="addRiga"
                            class="px-4 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-900"
                        >
                            + Aggiungi riga
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            Salva preventivo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
