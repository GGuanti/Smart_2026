<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import FlatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { Italian } from "flatpickr/dist/l10n/it.js";

const toast = useToast();

const props = defineProps({
    codCliente: String,
    corsi: Array,
    corsiDisponibili: Array,
});

const editingId = ref(null);
const localCorsi = ref([]);
const isLoading = ref(false);

const newCorso = ref({
    IdCorso: "",
    DurataAttestato: "",
    DataAttestato: "",
    Note: "",
    Stato: "",
});

const inEditing = ref({});

const formatDate = (val) => {
    if (!val) return "";
    const date = new Date(val);
    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

const formatDateForMySQL = (value) => {
    if (!value) return null;
    const date = new Date(value);
    return date.toISOString().split("T")[0];
};

const fetchCorsi = async () => {
    try {
        isLoading.value = true;
        localCorsi.value = [];
        await nextTick();

        const { data } = await axios.get("/corsi", {
            params: { cod: props.codCliente },
        });

        localCorsi.value = [...data];
    } catch (err) {
        console.error("Errore fetch corsi:", err);
        toast.error("Errore aggiornamento elenco corsi");
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchCorsi);

const edit = (corso) => {
    if (!corso || !corso.IdTabCorso) return;
    editingId.value = corso.IdTabCorso;
    const corsoDisponibile = props.corsiDisponibili.find(
        (c) => c.TipoCorso === corso.TipoCorso
    );
    inEditing.value = {
        IdCorso: corsoDisponibile?.IdCorso ?? "",
        DurataAttestato: corso.DurataAttestato,
        DataAttestato: corso.DataAttestato,
        Note: corso.Note,
        Stato: corso.Stato,
        CodCliente: corso.CodCliente,
        UtenteMod: "",
        DataModifica: new Date().toISOString().split("T")[0],
    };
};

const cancelEdit = () => {
    editingId.value = null;
    inEditing.value = {};
};

const update = async (corsoId) => {
    const payload = {
        ...inEditing.value,
        DataAttestato: formatDateForMySQL(inEditing.value.DataAttestato),
    };

    try {
        await axios.put(`/corsi/${corsoId}`, payload);
        toast.success("Corso aggiornato");
        cancelEdit();
        await fetchCorsi();
    } catch (err) {
        console.error("Errore update:", err);
        toast.error("Errore durante il salvataggio");
    }
};

const destroy = (corsoId) => {
    if (confirm("Sei sicuro di voler eliminare questo corso?")) {
        axios
            .delete(`/corsi/${corsoId}`)
            .then(() => {
                toast.success("Corso eliminato");
                fetchCorsi();
            })
            .catch((err) => {
                console.error("Errore delete:", err);
                toast.error("Errore durante l'eliminazione");
            });
    }
};

const addCorso = () => {
    if (!newCorso.value.IdCorso || !newCorso.value.DataAttestato) {
        toast.error("Compila almeno tipo corso e data");
        return;
    }

    const payload = {
        ...newCorso.value,
        DataAttestato: formatDateForMySQL(newCorso.value.DataAttestato),
        CodCliente: props.codCliente,
        UtenteMod: "",
        DataModifica: new Date().toISOString().split("T")[0],
    };

    axios
        .post("/corsi", payload)
        .then(() => {
            toast.success("Corso creato");
            newCorso.value = {
                IdCorso: "",
                DurataAttestato: "",
                DataAttestato: "",
                Note: "",
                Stato: "",
            };
            fetchCorsi();
        })
        .catch((err) => {
            console.error("Errore insert:", err);
            toast.error("Errore durante la creazione del corso");
        });
};

const corsiValidi = computed(() =>
    localCorsi.value.filter((c) => c && c.IdTabCorso)
);
</script>

<template>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-extrabold flex items-center gap-2">
                🎓 Corsi di Formazione
            </h2>
            <p class="text-xs text-slate-500">
                Gestione attestati e corsi del lavoratore
            </p>
        </div>
    </div>
    <div class="overflow-x-auto rounded-2xl border bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-100 text-slate-600">
                <tr>
                    <th class="px-3 py-2 text-left">🎓 Corso</th>
                    <th class="px-3 py-2 text-center">⏱ Durata</th>
                    <th class="px-3 py-2 text-center">📅 Attestato</th>
                    <th class="px-3 py-2 text-left">📝 Note</th>
                    <th class="px-3 py-2 text-center">📌 Stato</th>
                    <th class="px-3 py-2 text-right">⚙️</th>
                </tr>
            </thead>

            <tbody>
                <!-- loading -->
                <tr v-if="isLoading">
                    <td colspan="6" class="text-center py-6 text-slate-500">
                        ⏳ Caricamento corsi…
                    </td>
                </tr>

                <!-- righe corsi -->
                <tr
                    v-for="corso in corsiValidi"
                    :key="corso.IdTabCorso"
                    class="border-t hover:bg-slate-50 transition"
                >
                    <!-- corso -->
                    <td class="px-3 py-2 font-semibold text-slate-800">
                        {{
                            props.corsiDisponibili.find(
                                (c) => c.IdCorso === corso.IdCorso
                            )?.TipoCorso ?? corso.TipoCorso
                        }}
                    </td>

                    <!-- durata -->
                    <td class="px-3 py-2 text-center">
                        {{ corso.DurataAttestato }} h
                    </td>

                    <!-- data -->
                    <td class="px-3 py-2 text-center">
                        {{ formatDate(corso.DataAttestato) }}
                    </td>

                    <!-- note -->
                    <td class="px-3 py-2 text-slate-600 truncate max-w-[220px]">
                        {{ corso.Note || "—" }}
                    </td>

                    <!-- stato -->
                    <td class="px-3 py-2 text-center">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border"
                            :class="
                                corso.Stato
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : 'bg-amber-50 text-amber-700 border-amber-200'
                            "
                        >
                            {{ corso.Stato || "Da completare" }}
                        </span>
                    </td>

                    <!-- azioni -->
                    <td class="px-3 py-2 text-right whitespace-nowrap">
                        <button
                            class="text-blue-600 hover:text-blue-800 mr-2"
                            @click="edit(corso)"
                            title="Modifica"
                        >
                            ✏️
                        </button>
                        <button
                            class="text-red-600 hover:text-red-800"
                            @click="destroy(corso.IdTabCorso)"
                            title="Elimina"
                        >
                            🗑️
                        </button>
                    </td>
                </tr>

                <!-- nessun corso -->
                <tr v-if="!isLoading && !corsiValidi.length">
                    <td colspan="6" class="text-center py-6 text-slate-500">
                        Nessun corso presente
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-6 rounded-2xl border bg-slate-50 p-4">
        <div class="font-extrabold mb-3 flex items-center gap-2">
            ➕ Aggiungi nuovo corso
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <select v-model="newCorso.IdCorso" class="input">
                <option value="">Tipo corso</option>
                <option
                    v-for="op in props.corsiDisponibili"
                    :key="op.IdCorso"
                    :value="op.IdCorso"
                >
                    {{ op.TipoCorso }}
                </option>
            </select>

            <input
                type="number"
                v-model="newCorso.DurataAttestato"
                class="input"
                placeholder="Durata (h)"
            />

            <FlatPickr
                v-model="newCorso.DataAttestato"
                :config="{
                    altInput: true,
                    altFormat: 'd/m/Y',
                    dateFormat: 'Y-m-d',
                    locale: Italian,
                }"
                class="input"
            />

            <input v-model="newCorso.Note" class="input" placeholder="Note" />
            <input v-model="newCorso.Stato" class="input" placeholder="Stato" />
        </div>

        <div class="mt-4 text-right">
            <button class="btn btn-primary" @click="addCorso">
                💾 Salva corso
            </button>
        </div>
    </div>

</template>
