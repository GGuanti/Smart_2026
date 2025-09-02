<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import ModalProjectForm from "./ModalProjectForm.vue"; // oppure '@/Components/ModalProjectForm.vue'

const props = defineProps({
    codCliente: { type: String, required: true },
    clienti: { type: Object, default: () => ({}) },
    attivita: { type: Array, default: () => [] },
});

const page = usePage();
const isAdmin = computed(() => page.props?.auth?.user?.profilo === "admin");

// stato pannelli attività
const open = ref(null);
const toggle = (id) => {
    open.value = open.value === id ? null : id;
};

// filtro attività per cliente
const attivitaCliente = computed(() =>
    (props.attivita || []).filter((a) => a.CodCliente === props.codCliente)
);

// modal state
const modalOpen = ref(false);
const editMode = ref(false); // false=create, true=edit
const selectedActivity = ref(null); // attività corrente
const selectedProject = ref(null); // progetto in modifica

const openNewProject = (a) => {
    selectedActivity.value = a;
    selectedProject.value = null;
    editMode.value = false;
    modalOpen.value = true;
};

const openEditProject = (a, p) => {
    selectedActivity.value = a;
    selectedProject.value = p;
    editMode.value = true;
    modalOpen.value = true;
};

const reloadProjects = () => {
    router.reload({ only: ["attivita"] });
};

const destroyProject = (p) => {
    if (!isAdmin.value) return;
    if (confirm(`Eliminare definitivamente il progetto #${p.IdProgetto}?`)) {
        router.delete(route("progetti.destroy", p.IdProgetto), {
            preserveScroll: true,
            onSuccess: reloadProjects,
        });
    }
};

// util formattazione
const fmtDate = (d) => {
    if (!d) return "";
    const date = typeof d === "string" ? new Date(d) : d;
    return isNaN(date)
        ? d
        : new Intl.DateTimeFormat("it-IT", { dateStyle: "medium" }).format(
              date
          );
};
</script>

<template>
    <div class="p-4 bg-white rounded shadow">
        <h2 class="text-lg font-semibold mb-4">Attività</h2>

        <div
            v-for="a in attivitaCliente"
            :key="a.IDAttivita"
            class="mb-4 border rounded overflow-hidden"
        >
            <!-- Header attività -->
            <button
                type="button"
                @click="toggle(a.IDAttivita)"
                class="w-full text-left px-4 py-2 bg-gray-100 hover:bg-gray-200 flex items-center justify-between"
                :aria-expanded="open === a.IDAttivita"
            >
                <div class="truncate">
                    <strong>#{{ a.IDAttivita }}</strong> — {{ a.NomeAttivita }}
                    <span class="text-sm text-gray-500 ml-2"
                        >({{ a.DesProgetto }})</span
                    >
                </div>
                <span class="ml-3 text-gray-600" aria-hidden="true">
                    {{ open === a.IDAttivita ? "▾" : "▸" }}
                </span>
            </button>

            <!-- Corpo attività -->
            <div v-show="open === a.IDAttivita" class="border-t">
                <!-- Barra azioni -->
                <div class="px-4 pt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Progetti collegati all’attività #{{ a.IDAttivita }}
                    </div>
                    <button
                        v-if="isAdmin"
                        type="button"
                        @click="openNewProject(a)"
                        class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        + Aggiungi progetto
                    </button>
                </div>

                <!-- Tabella progetti -->
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="p-2 border whitespace-nowrap">
                                    IdProgetto
                                </th>
                                <th class="p-2 border">Ragione Sociale</th>
                                <th class="p-2 border whitespace-nowrap">
                                    Data Inizio
                                </th>
                                <th class="p-2 border whitespace-nowrap">
                                    Data Fine
                                </th>
                                <th class="p-2 border whitespace-nowrap">
                                    Data Pagamento
                                </th>
                                <th class="p-2 border">Note</th>
                                <th class="p-2 border text-center">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="p in a.progetti ?? []"
                                :key="p.IdProgetto"
                            >
                                <td class="p-2 border align-top">
                                    {{ p.IdProgetto }}
                                </td>
                                <td class="p-2 border">
                                    {{ p.RagioneSocialeCommittenti }}
                                </td>
                                <td class="p-2 border">
                                    {{ fmtDate(p.DataInzProgetto) }}
                                </td>
                                <td class="p-2 border">
                                    {{ fmtDate(p.DataFineProgetto) }}
                                </td>
                                <td class="p-2 border">
                                    {{ fmtDate(p.DataPagamento) }}
                                </td>
                                <td class="p-2 border">
                                    <span class="line-clamp-3">{{
                                        p.Note
                                    }}</span>
                                </td>
                                <td
                                    class="p-2 border text-center whitespace-nowrap"
                                >
                                    <button
                                        type="button"
                                        @click="openEditProject(a, p)"
                                        class="px-2 py-1 border rounded hover:bg-gray-50"
                                        title="Modifica"
                                    >
                                        ✏️
                                    </button>
                                    <button
                                        v-if="isAdmin"
                                        type="button"
                                        @click="destroyProject(p)"
                                        class="px-2 py-1 border rounded hover:bg-gray-50"
                                        title="Elimina"
                                    >
                                        🗑️
                                    </button>

                                </td>
                            </tr>

                            <tr v-if="(a.progetti ?? []).length === 0">
                                <td
                                    colspan="7"
                                    class="text-center text-gray-500 py-2"
                                >
                                    Nessun progetto collegato
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-if="attivitaCliente.length === 0"
            class="text-sm text-gray-500 px-1 pb-2"
        >
            Nessuna attività per il cliente selezionato.
        </div>

        <!-- MODALE -->
        <ModalProjectForm
            :show="modalOpen"
            :editMode="editMode"
            :project="selectedProject"
            :codCliente="codCliente"
            :idAttivita="selectedActivity?.IDAttivita"
            :clienti="props.clienti"
            @close="modalOpen = false"
            @saved="reloadProjects"
        />
    </div>
</template>
