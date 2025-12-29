<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    listino: { type: Object, default: null },
    modelli: { type: Array, default: () => [] },
    prezziBT: { type: Object, default: () => ({}) }, // ✅ mappa: modello -> BT
});

// sicurezza extra
const modelli = computed(() => (Array.isArray(props.modelli) ? props.modelli : []));

const form = useForm({
    // campi listino
    nome_modello: props.listino?.nome_modello ?? "",
    soluzioni: props.listino?.soluzioni ?? "",
    finiture_anta: props.listino?.finiture_anta ?? "",
    finiture_telaio: props.listino?.finiture_telaio ?? "",
    maniglie: props.listino?.maniglie ?? "",
    vetro: props.listino?.vetro ?? "",
    note: props.listino?.note ?? "",
    nascondi: props.listino?.nascondi ?? false,

    // campi "preventivo" (li stai usando a destra)
    magg_lrg: props.listino?.magg_lrg ?? null,
    Alt: props.listino?.Alt ?? null,
    Prf: props.listino?.Prf ?? null,
    Qta: props.listino?.Qta ?? 1,
    PrezzoMan: props.listino?.PrezzoMan ?? 0,   // ✅ come hai chiesto
    PrezzoList: props.listino?.PrezzoList ?? null,
});

const imgKey = ref(0);

const fotoUrl = computed(() => {
    const nome = (form.nome_modello ?? "").toString().trim();
    if (!nome) return null;
    return `/foto/${encodeURIComponent(nome)}.jpg`;
});

function onImgError(e) {
    e.target.src = "/foto/placeholder.jpg";
}

// ✅ ricava BT in base al modello
const prezzoListinoDaBT = computed(() => {
    const key = (form.nome_modello ?? "").toString().trim();
    if (!key) return null;

    const bt = props.prezziBT[key]; // valore BT per quel modello
    if (bt === undefined || bt === null || bt === "") return null;

    const n = Number(bt);
    return Number.isFinite(n) ? n : null;
});

// ✅ quando cambia il modello aggiorna immagine + PrezzoList
function onModelloChange() {
    imgKey.value++;
    form.PrezzoList = prezzoListinoDaBT.value;
}

// ✅ aggiorna anche all’apertura (edit o create)
watch(
    () => form.nome_modello,
    () => {
        form.PrezzoList = prezzoListinoDaBT.value;
    },
    { immediate: true }
);

function submit() {
    if (props.listino && props.listino.id_listino) {
        form.put(route("listini.update", props.listino.id_listino));
    } else {
        form.post(route("listini.store"));
    }
}
</script>

<template>
    <div class="wrap">
        <div class="header">
            <h1 class="title">
                {{ props.listino ? "Modifica Listino" : "Nuovo Preventivo" }}
            </h1>
            <div class="actions">
                <button class="btn primary" @click="submit" :disabled="form.processing">
                    {{ props.listino ? "Salva modifiche" : "Aggiungi" }}
                </button>
            </div>
        </div>

        <div class="grid">
            <!-- SINISTRA -->
            <div class="left">
                <div class="row">
                    <label class="label">Modello</label>
                    <select v-model="form.nome_modello" class="input" @change="onModelloChange">
                        <option value="">— Seleziona modello —</option>
                        <option v-for="m in modelli" :key="m" :value="m">
                            {{ m }}
                        </option>
                    </select>
                </div>

                <div class="row">
                    <label class="label">Soluzione</label>
                    <input v-model="form.soluzioni" class="input" />
                </div>

                <div class="row">
                    <label class="label">Colore Anta</label>
                    <input v-model="form.finiture_anta" class="input" />
                </div>

                <div class="row">
                    <label class="label">Colore Telaio</label>
                    <input v-model="form.finiture_telaio" class="input" />
                </div>

                <div class="row">
                    <label class="label">Maniglia</label>
                    <input v-model="form.maniglie" class="input" />
                </div>

                <div class="row">
                    <label class="label">Vetro</label>
                    <input v-model="form.vetro" class="input" />
                </div>

                <div class="row">
                    <label class="label">Note</label>
                    <textarea v-model="form.note" class="input" rows="3"></textarea>
                </div>

                <div class="check">
                    <input id="nascondi" type="checkbox" v-model="form.nascondi" />
                    <label for="nascondi">Nascondi dal listino</label>
                </div>

                <div v-if="Object.keys(form.errors).length" class="errors">
                    <div v-for="(msg, key) in form.errors" :key="key" class="err">
                        <strong>{{ key }}:</strong> {{ msg }}
                    </div>
                </div>
            </div>

            <!-- DESTRA -->
            <div class="right">
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

                <div class="row">
                    <label class="label">Lrg</label>
                    <input type="number" v-model.number="form.magg_lrg" class="input" />
                </div>

                <div class="row">
                    <label class="label">Alt</label>
                    <input type="number" v-model.number="form.Alt" class="input" />
                </div>

                <div class="row">
                    <label class="label">Spess. Muro</label>
                    <input type="number" v-model.number="form.Prf" class="input" />
                </div>

                <div class="row">
                    <label class="label">Qta</label>
                    <input type="number" v-model.number="form.Qta" class="input" />
                </div>

                <div class="row">
                    <label class="label">Prezzo Man.</label>
                    <input type="number" v-model.number="form.PrezzoMan" class="input" />
                </div>

                <div class="row">
                    <label class="label">Prezzo Listino (BT)</label>
                    <input
                        type="number"
                        v-model.number="form.PrezzoList"
                        class="input"
                        readonly
                        style="background:#fff7cc; font-weight:700;"
                    />
                </div>

                <div class="hint">
                    <div class="hintTitle">Percorso immagini</div>
                    <div class="hintText">
                        Metti i file in <code>public/foto/</code> con nome:
                        <code>&lt;NomeModello&gt;.jpg</code><br />
                        E crea anche <code>public/foto/placeholder.jpg</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.wrap {
    max-width: 1100px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
}

.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.title {
    font-size: 18px;
    font-weight: 800;
    margin: 0;
}

.actions {
    display: flex;
    gap: 10px;
}

.btn {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
}
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.btn.primary {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 16px;
}

.left {
    display: grid;
    gap: 10px;
}

.row {
    display: grid;
    gap: 6px;
}

.label {
    font-size: 12px;
    font-weight: 700;
    color: #374151;
}

.input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 10px;
    outline: none;
}
.input:focus {
    border-color: #2563eb;
}

.check {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
    color: #111827;
}

.right {
    display: grid;
    gap: 10px;
    align-content: start;
}

.preview {
    height: 280px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
}

.previewImg {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.previewEmpty {
    font-size: 12px;
    color: #6b7280;
    text-align: center;
    padding: 0 10px;
}

.hint {
    border: 1px dashed #d1d5db;
    border-radius: 10px;
    padding: 10px;
    background: #fff;
}
.hintTitle {
    font-weight: 800;
    font-size: 12px;
    margin-bottom: 6px;
}
.hintText {
    font-size: 12px;
    color: #374151;
}
code {
    background: #f3f4f6;
    padding: 1px 5px;
    border-radius: 6px;
}

.errors {
    margin-top: 8px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 10px;
}
.err {
    font-size: 12px;
    color: #991b1b;
}

@media (max-width: 900px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>
