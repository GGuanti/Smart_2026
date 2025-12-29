<script setup>
import { Head, Link, useForm, router, usePage } from "@inertiajs/vue3";
import { computed, nextTick } from "vue";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
    ordine: { type: Object, required: true },
    elemento: { type: Object, default: null },
});

const isEdit = computed(() => !!props.elemento?.Id);

const form = useForm({
    // 👇 NON lo facciamo editabile: lo ereditiamo sempre
    Nordine: props.ordine?.Nordine ?? null,

    DimL: props.elemento?.DimL ?? null,
    DimA: props.elemento?.DimA ?? null,
    DimSp: props.elemento?.DimSp ?? null,
    Qta: props.elemento?.Qta ?? 1,

    PrezzoCad: props.elemento?.PrezzoCad ?? 0,
    PrezzoMan: props.elemento?.PrezzoMan ?? 0,

    NoteMan: props.elemento?.NoteMan ?? "",
    PercFile: props.elemento?.PercFile ?? "",

    IdModello: props.elemento?.IdModello ?? null,
    IdColAnta: props.elemento?.IdColAnta ?? null,
    IdColTelaio: props.elemento?.IdColTelaio ?? null,
    IdSoluzione: props.elemento?.IdSoluzione ?? null,
    IdManiglia: props.elemento?.IdManiglia ?? null,
    IdApertura: props.elemento?.IdApertura ?? null,
    IdTipTelaio: props.elemento?.IdTipTelaio ?? null,
    IdVetro: props.elemento?.IdVetro ?? null,
    IdColFerr: props.elemento?.IdColFerr ?? null,
    IdSerratura: props.elemento?.IdSerratura ?? null,
    IdImbotte: props.elemento?.IdImbotte ?? null,

    CkTaglioObl: props.elemento?.CkTaglioObl ?? "No",
    TxtCassMet: props.elemento?.TxtCassMet ?? "",
});

// Totali utili (facoltativi)
const totaleRiga = computed(() => {
    const q = Number(form.Qta ?? 0);
    const cad = Number(form.PrezzoCad ?? 0);
    const man = Number(form.PrezzoMan ?? 0);
    return +(q * (cad + man)).toFixed(2);
});

// invio => campo successivo (tranne textarea note)
function focusNext(e) {
    const tag = (e.target?.tagName ?? "").toLowerCase();
    if (tag === "textarea") return; // ✅ NoteMan: invio = a capo

    const f = e.target.form;
    if (!f) return;

    const els = Array.from(f.elements).filter(
        (el) =>
            !el.disabled &&
            el.type !== "hidden" &&
            el.tabIndex !== -1 &&
            el.offsetParent !== null
    );

    const i = els.indexOf(e.target);
    if (i > -1 && i < els.length - 1) {
        nextTick(() => els[i + 1].focus());
    }
}

function submit() {
    if (isEdit.value) {
        form.put(
            route("preventivi.update", {
                ordine: props.ordine.ID,
                elemento: props.elemento.Id,
            }),
            {
                onSuccess: () =>
                    toast.success("✅ Riga preventivo aggiornata!", {
                        position: "top-left",
                    }),
                onError: () =>
                    toast.error("❌ Controlla i campi obbligatori", {
                        position: "top-left",
                    }),
            }
        );
    } else {
        form.post(route("preventivi.store", { ordine: props.ordine.ID }), {
            onSuccess: () =>
                toast.success("✅ Riga preventivo aggiunta!", {
                    position: "top-left",
                }),
            onError: () =>
                toast.error("❌ Controlla i campi obbligatori", {
                    position: "top-left",
                }),
        });
    }
}

function destroyRow() {
    if (!isEdit.value) return;
    if (!confirm("Eliminare questa riga preventivo?")) return;

    router.delete(
        route("preventivi.destroy", {
            ordine: props.ordine.ID,
            elemento: props.elemento.Id,
        }),
        {
            onSuccess: () =>
                toast.success("🗑️ Riga eliminata", { position: "top-left" }),
            onError: () =>
                toast.error("❌ Errore eliminazione", { position: "top-left" }),
        }
    );
}
</script>

<template>
    <Head
        :title="isEdit ? 'Modifica Riga Preventivo' : 'Nuova Riga Preventivo'"
    />

    <div class="page">
        <!-- TOP BAR -->
        <div class="topbar">
            <div class="leftTitle">
                <div class="titleRow">
                    <h1 class="title">
                        Preventivi · {{ isEdit ? "Modifica" : "Nuovo" }}
                    </h1>
                    <span class="badge">ERP</span>
                </div>
                <div class="sub">
                    Nordine ereditato · Invio = campo successivo · Note: invio =
                    a capo
                </div>
            </div>

            <div class="rightActions">
                <img src="/images/logo.png" class="logo" alt="Logo" />

                <Link
                    :href="route('ordini.edit', props.ordine.ID)"
                    class="btn ghost"
                    >↩ Torna ordine</Link
                >

                <button
                    class="btn primary"
                    @click="submit"
                    :disabled="form.processing"
                >
                    💾 {{ isEdit ? "Salva" : "Aggiungi" }}
                </button>

                <button
                    v-if="isEdit"
                    class="btn danger"
                    @click="destroyRow"
                    :disabled="form.processing"
                >
                    🗑️ Elimina
                </button>
            </div>
        </div>

        <!-- KPI -->
        <div class="kpis">
            <div class="kpiCard">
                <div class="kpiLabel">Nordine</div>
                <div class="kpiValue">{{ form.Nordine }}</div>
            </div>

            <div class="kpiCard">
                <div class="kpiLabel">Totale riga</div>
                <div class="kpiValue">€ {{ totaleRiga.toFixed(2) }}</div>
            </div>
        </div>

        <!-- FORM -->
        <form class="grid">
            <div class="card">
                <div class="cardHeader">
                    <div class="cardTitle">📐 Dimensioni & Quantità</div>
                </div>
                <div class="preview">
                    <img
                        v-if="fotoUrl"
                        :key="imgKey"
                        :src="fotoUrl"
                        alt="Anteprima modello"
                        class="previewImg"
                        @error="onImgError"
                    />
                    <div v-else class="previewEmpty">
                        Seleziona un modello per vedere l’immagine
                    </div>
                </div>
                <div class="fields">
                    <div class="field">
                        <label>DimL</label>
                        <input
                            v-model.number="form.DimL"
                            type="number"
                            step="0.01"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>

                    <div class="field">
                        <label>DimA</label>
                        <input
                            v-model.number="form.DimA"
                            type="number"
                            step="0.01"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>

                    <div class="field">
                        <label>DimSp</label>
                        <input
                            v-model.number="form.DimSp"
                            type="number"
                            step="0.01"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>

                    <div class="field">
                        <label>Qta</label>
                        <input
                            v-model.number="form.Qta"
                            type="number"
                            step="1"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                </div>

                <div class="fields">
                    <div class="field">
                        <label>PrezzoCad</label>
                        <input
                            v-model.number="form.PrezzoCad"
                            type="number"
                            step="0.01"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>

                    <div class="field">
                        <label>PrezzoMan</label>
                        <input
                            v-model.number="form.PrezzoMan"
                            type="number"
                            step="0.01"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>


                </div>
            </div>

            <div class="card wide">
                <div class="cardHeader">
                    <div class="cardTitle">🧩 Selezioni (ID)</div>
                    <div class="cardHint">
                        Per ora sono campi numerici. Dopo li trasformiamo in
                        select da Listino/Soluzioni.
                    </div>
                </div>

                <div class="fields cols3">
                    <div class="field">
                        <label>IdModello</label
                        ><input
                            v-model.number="form.IdModello"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                    <div class="field">
                        <label>IdSoluzione</label
                        ><input
                            v-model.number="form.IdSoluzione"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                    <div class="field">
                        <label>IdTipTelaio</label
                        ><input
                            v-model.number="form.IdTipTelaio"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>

                    <div class="field">
                        <label>IdColAnta</label
                        ><input
                            v-model.number="form.IdColAnta"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                    <div class="field">
                        <label>IdColTelaio</label
                        ><input
                            v-model.number="form.IdColTelaio"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                    <div class="field">
                        <label>IdImbotte</label
                        ><input
                            v-model.number="form.IdImbotte"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>

                    <div class="field">
                        <label>IdManiglia</label
                        ><input
                            v-model.number="form.IdManiglia"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                    <div class="field">
                        <label>IdApertura</label
                        ><input
                            v-model.number="form.IdApertura"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                    <div class="field">
                        <label>IdVetro</label
                        ><input
                            v-model.number="form.IdVetro"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>

                    <div class="field">
                        <label>IdColFerr</label
                        ><input
                            v-model.number="form.IdColFerr"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                    <div class="field">
                        <label>IdSerratura</label
                        ><input
                            v-model.number="form.IdSerratura"
                            type="number"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                    <div class="field">
                        <label>PercFile</label
                        ><input
                            v-model="form.PercFile"
                            @keydown.enter.prevent="focusNext"
                        />
                    </div>
                </div>
            </div>

            <div class="card wide">
                <div class="cardHeader">
                    <div class="cardTitle">📝 Note</div>
                    <div class="cardHint">Invio = a capo</div>
                </div>

                <div class="field">
                    <label>NoteMan</label>
                    <textarea v-model="form.NoteMan" rows="3"></textarea>
                </div>

                <div v-if="Object.keys(form.errors).length" class="errors">
                    <div class="errTitle">Errori</div>
                    <div
                        v-for="(msg, key) in form.errors"
                        :key="key"
                        class="errRow"
                    >
                        <b>{{ key }}:</b> {{ msg }}
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
.page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 16px;
    background: radial-gradient(
        1200px 500px at 50% 0%,
        rgba(37, 99, 235, 0.12),
        transparent 60%
    );
}
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}
.leftTitle .titleRow {
    display: flex;
    align-items: center;
    gap: 10px;
}
.title {
    font-size: 22px;
    font-weight: 900;
    margin: 0;
}
.badge {
    font-size: 12px;
    font-weight: 800;
    padding: 4px 8px;
    border-radius: 999px;
    background: #e0f2fe;
    color: #075985;
}
.sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}
.rightActions {
    display: flex;
    align-items: center;
    gap: 10px;
}
.logo {
    height: 52px;
    width: auto;
    object-fit: contain;
}

.kpis {
    display: grid;
    grid-template-columns: 220px 220px;
    gap: 10px;
    margin: 10px 0 14px;
}
.kpiCard {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 10px 12px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
}
.kpiLabel {
    font-size: 12px;
    color: #64748b;
    font-weight: 700;
}
.kpiValue {
    font-size: 20px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.2;
}

.btn {
    border-radius: 12px;
    padding: 10px 14px;
    font-weight: 800;
    border: 1px solid #e5e7eb;
    background: white;
    cursor: pointer;
}
.btn.ghost {
    background: #fff;
}
.btn.primary {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}
.btn.primary:hover {
    background: #1d4ed8;
}
.btn.danger {
    background: #dc2626;
    border-color: #dc2626;
    color: #fff;
}
.btn.danger:hover {
    background: #b91c1c;
}
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px; /* ✅ spazio ridotto tra card */
}
.card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}
.cardHeader {
    padding: 10px 12px;
    background: linear-gradient(180deg, #0f172a, #111827);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.cardTitle {
    font-weight: 900;
    font-size: 14px;
}
.cardHint {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.75);
}

.fields {
    padding: 12px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.fields.cols3 {
    grid-template-columns: 1fr 1fr 1fr;
}
.field {
    display: grid;
    gap: 6px;
}
label {
    font-size: 12px;
    font-weight: 800;
    color: #334155;
}
input,
select,
textarea {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 10px 12px;
    outline: none;
    background: #fff;
}
input:focus,
select:focus,
textarea:focus {
    border-color: #93c5fd;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.wide {
    grid-column: 1 / -1;
}

.errors {
    margin: 10px 12px 12px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 14px;
    padding: 10px;
}
.errTitle {
    font-weight: 900;
    color: #991b1b;
    margin-bottom: 6px;
}
.errRow {
    font-size: 12px;
    color: #991b1b;
}

@media (max-width: 900px) {
    .grid {
        grid-template-columns: 1fr;
    }
    .kpis {
        grid-template-columns: 1fr;
    }
}
</style>
