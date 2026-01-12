<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import ModalProjectForm from "./ModalProjectForm.vue";

const props = defineProps({
    codCliente: { type: String, required: true },
    clienti: { type: Object, default: () => ({}) },
    attivita: { type: Array, default: () => [] },
});

const page = usePage();
const isAdmin = computed(() => page.props?.auth?.user?.profilo === "admin");

// stato pannelli attività
const open = ref(null);
const toggle = (id) => (open.value = open.value === id ? null : id);

// filtro attività per cliente
const attivitaCliente = computed(() =>
    (props.attivita || []).filter((a) => a.CodCliente === props.codCliente)
);

// modal state
const modalOpen = ref(false);
const editMode = ref(false);
const selectedActivity = ref(null);
const selectedProject = ref(null);

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

const reloadProjects = () => router.reload({ only: ["attivita"] });

const destroyProject = (p) => {
    if (!isAdmin.value) return;
    if (confirm(`Eliminare definitivamente il progetto #${p.IdProgetto}?`)) {
        router.delete(route("progetti.destroy", p.IdProgetto), {
            preserveScroll: true,
            onSuccess: reloadProjects,
        });
    }
};

const fmtDate = (d) => {
    if (!d) return "—";
    const date = typeof d === "string" ? new Date(d) : d;
    return isNaN(date)
        ? d
        : new Intl.DateTimeFormat("it-IT", { dateStyle: "medium" }).format(date);
};

const short = (s) => String(s ?? "").trim();
</script>

<template>
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <!-- Header -->
        <div
            class="flex items-start justify-between gap-4 p-5 border-b border-slate-200"
        >
            <div>
                <div class="flex items-center gap-2">
                    <div
                        class="h-9 w-9 rounded-xl bg-indigo-50 ring-1 ring-indigo-100 flex items-center justify-center"
                    >
                        <span class="text-indigo-700">📌</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900">
                            Attività
                        </h2>
                        <p class="text-sm text-slate-500">
                            Elenco attività e progetti collegati al cliente
                            <span class="font-semibold text-slate-700">
                                {{ codCliente }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 ring-1 ring-slate-200"
            >
                <span class="text-sm text-slate-600">Totale:</span>
                <span
                    class="text-sm font-bold text-slate-900 tabular-nums"
                >
                    {{ attivitaCliente.length }}
                </span>
            </div>
        </div>

        <!-- Body -->
        <div class="p-5">
            <!-- Empty state -->
            <div
                v-if="attivitaCliente.length === 0"
                class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"
            >
                <div class="text-3xl mb-2">🗂️</div>
                <div class="text-slate-900 font-semibold">
                    Nessuna attività per il cliente selezionato
                </div>
                <div class="text-sm text-slate-500 mt-1">
                    Quando saranno presenti attività, le vedrai qui con i progetti collegati.
                </div>
            </div>

            <!-- Accordion cards -->
            <div v-else class="space-y-4">
                <div
                    v-for="a in attivitaCliente"
                    :key="a.IDAttivita"
                    class="rounded-2xl border border-slate-200 overflow-hidden bg-white"
                >
                    <!-- Header attività -->
                    <button
                        type="button"
                        @click="toggle(a.IDAttivita)"
                        class="w-full text-left px-4 py-3 md:px-5 md:py-4 bg-gradient-to-r from-slate-50 to-white hover:from-slate-100 hover:to-white transition flex items-center justify-between gap-4"
                        :aria-expanded="open === a.IDAttivita"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"
                                >
                                    #{{ a.IDAttivita }}
                                </span>

                                <span class="font-semibold text-slate-900 truncate">
                                    {{ a.NomeAttivita }}
                                </span>

                                <span
                                    v-if="short(a.DesProgetto)"
                                    class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100"
                                >
                                    {{ a.DesProgetto }}
                                </span>
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                Progetti: <span class="font-semibold text-slate-700">{{ (a.progetti ?? []).length }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1"
                                :class="[
                                    open === a.IDAttivita
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-100'
                                        : 'bg-slate-50 text-slate-600 ring-slate-200',
                                ]"
                            >
                                {{ open === a.IDAttivita ? "Aperto" : "Chiuso" }}
                            </span>

                            <span
                                class="text-slate-600 text-lg"
                                aria-hidden="true"
                            >
                                {{ open === a.IDAttivita ? "▾" : "▸" }}
                            </span>
                        </div>
                    </button>

                    <!-- Corpo attività -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div v-show="open === a.IDAttivita" class="border-t border-slate-200">
                            <!-- Action bar -->
                            <div class="px-4 md:px-5 pt-4 flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm text-slate-600">
                                    Progetti collegati all’attività
                                    <span class="font-semibold text-slate-800">#{{ a.IDAttivita }}</span>
                                </div>

                                <button
                                    v-if="isAdmin"
                                    type="button"
                                    @click="openNewProject(a)"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.99] transition"
                                >
                                    <span>＋</span>
                                    <span>Aggiungi progetto</span>
                                </button>
                            </div>

                            <!-- Projects table -->
                            <div class="p-4 md:p-5 overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0">
                                        <tr class="bg-slate-50 text-slate-700">
                                            <th class="p-3 text-left font-bold border-b border-slate-200 whitespace-nowrap">
                                                Id
                                            </th>
                                            <th class="p-3 text-left font-bold border-b border-slate-200">
                                                Committente
                                            </th>
                                            <th class="p-3 text-left font-bold border-b border-slate-200 whitespace-nowrap">
                                                Inizio
                                            </th>
                                            <th class="p-3 text-left font-bold border-b border-slate-200 whitespace-nowrap">
                                                Fine
                                            </th>
                                            <th class="p-3 text-left font-bold border-b border-slate-200 whitespace-nowrap">
                                                Pagamento
                                            </th>
                                            <th class="p-3 text-left font-bold border-b border-slate-200">
                                                Note
                                            </th>
                                            <th class="p-3 text-center font-bold border-b border-slate-200 whitespace-nowrap">
                                                Azioni
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100">
                                        <tr
                                            v-for="p in a.progetti ?? []"
                                            :key="p.IdProgetto"
                                            class="hover:bg-slate-50/70 transition"
                                        >
                                            <td class="p-3 align-top whitespace-nowrap">
                                                <span class="inline-flex items-center rounded-lg bg-slate-900 px-2 py-1 text-xs font-bold text-white">
                                                    #{{ p.IdProgetto }}
                                                </span>
                                            </td>

                                            <!-- Committente: Cod + Ragione Sociale -->
                                            <td class="p-3 align-top">
                                                <div class="leading-tight">
                                                    <div class="font-semibold text-slate-900">
                                                        {{ p.CodCommittente ?? "—" }}
                                                    </div>
                                                    <div class="text-xs text-slate-500">
                                                        {{ p.RagioneSocialeCommittenti ?? "—" }}
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="p-3 align-top whitespace-nowrap text-slate-700">
                                                {{ fmtDate(p.DataInzProgetto) }}
                                            </td>
                                            <td class="p-3 align-top whitespace-nowrap text-slate-700">
                                                {{ fmtDate(p.DataFineProgetto) }}
                                            </td>
                                            <td class="p-3 align-top whitespace-nowrap text-slate-700">
                                                {{ fmtDate(p.DataPagamento) }}
                                            </td>

                                            <td class="p-3 align-top">
                                                <span class="text-slate-700 line-clamp-3">
                                                    {{ p.Note ?? "—" }}
                                                </span>
                                            </td>

                                            <td class="p-3 align-top text-center whitespace-nowrap">
                                                <div class="inline-flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        @click="openEditProject(a, p)"
                                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 active:scale-[0.99] transition"
                                                        title="Modifica"
                                                    >
                                                        ✏️ <span class="hidden sm:inline">Modifica</span>
                                                    </button>

                                                    <button
                                                        v-if="isAdmin"
                                                        type="button"
                                                        @click="destroyProject(p)"
                                                        class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 active:scale-[0.99] transition"
                                                        title="Elimina"
                                                    >
                                                        🗑️ <span class="hidden sm:inline">Elimina</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr v-if="(a.progetti ?? []).length === 0">
                                            <td colspan="7" class="py-10 text-center">
                                                <div class="inline-flex flex-col items-center gap-2">
                                                    <div class="text-3xl">📭</div>
                                                    <div class="font-semibold text-slate-800">
                                                        Nessun progetto collegato
                                                    </div>
                                                    <div class="text-sm text-slate-500">
                                                        Aggiungi un progetto per iniziare.
                                                    </div>

                                                    <button
                                                        v-if="isAdmin"
                                                        type="button"
                                                        @click="openNewProject(a)"
                                                        class="mt-2 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition"
                                                    >
                                                        ＋ Aggiungi progetto
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
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
