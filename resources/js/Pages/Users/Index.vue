<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, onMounted, watch, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { TabulatorFull as Tabulator } from "tabulator-tables";
const logoFile = ref(null);
const logoPreview = ref(null);
const removeLogo = ref(false);

function onPickLogo(e) {
    const file = e.target.files?.[0] ?? null;
    logoFile.value = file;
    removeLogo.value = false;

    if (!file) {
        logoPreview.value = null;
        return;
    }
    logoPreview.value = URL.createObjectURL(file);
}

const props = defineProps({
    users: { type: Array, default: () => [] },
    regioniTrasporto: { type: Array, default: () => [] },
});

/* -------------------- UI state -------------------- */
const showAdvanced = ref(false);
const showFilters = ref(false);
const quickSearch = ref("");

/* -------------------- KPIs -------------------- */
const totalUsers = computed(() => props.users?.length ?? 0);
const totalAdmin = computed(
    () =>
        (props.users ?? []).filter(
            (u) => String(u.profilo).toLowerCase() === "admin"
        ).length
);
const totalIsomax = computed(
    () =>
        (props.users ?? []).filter(
            (u) => String(u.profilo).toLowerCase() === "isomax"
        ).length
);
const totalNurith = computed(
    () =>
        (props.users ?? []).filter(
            (u) => String(u.profilo).toLowerCase() === "nurith"
        ).length
);

/* -------------------- Form (Inertia useForm) -------------------- */
const DEFAULTS = {
    id: null,
    name: "",
    email: "",
    password: "",
    profilo: "Isomax",
    listino: "1",
    azienda: "Isomax",
    trasporto: props.regioniTrasporto?.[0] ?? "",
    datiazienda: "",
};

const form = useForm({ ...DEFAULTS });

function resetForm() {
    form.reset();
    form.clearErrors();
    form.defaults({
        ...DEFAULTS,
        trasporto: props.regioniTrasporto?.[0] ?? "",
    });
    form.reset();
    showAdvanced.value = false;
    logoFile.value = null;
    logoPreview.value = null;
    removeLogo.value = false;
}

function editRow(row) {
    // password SEMPRE vuota in edit (si cambia solo se digitata)
    form.clearErrors();
    form.id = row.id ?? null;
    form.name = row.name ?? "";
    form.email = row.email ?? "";
    form.password = "";
    form.profilo = row.profilo ?? "Isomax";
    form.listino = row.listino ?? "1";
    form.azienda = row.azienda ?? "Isomax";
    form.trasporto = row.trasporto ?? props.regioniTrasporto?.[0] ?? "";
    form.datiazienda = row.datiazienda ?? "";
    showAdvanced.value = Boolean(form.datiazienda);
    window.scrollTo({ top: 0, behavior: "smooth" });
    logoFile.value = null;
    const p = row.logo_path
        ? String(row.logo_path).trim().replaceAll("\\", "/")
        : "";
    logoPreview.value = !p
        ? null
        : p.startsWith("/storage/") || /^https?:\/\//i.test(p)
        ? p
        : `/storage/${p.replace(/^public\//, "")}`;

    removeLogo.value = false;
}

function submit() {
    const isEdit = !!form.id;

    if (!form.name?.trim()) return alert("Il nome è obbligatorio");
    if (!form.email?.trim()) return alert("L'email è obbligatoria");
    if (!form.profilo?.trim()) return alert("Il profilo è obbligatorio");
    if (!isEdit && !form.password)
        return alert("La password è obbligatoria in creazione");

    const fd = new FormData();

    // campi testo
    fd.append("name", form.name ?? "");
    fd.append("email", form.email ?? "");
    fd.append("profilo", form.profilo ?? "");
    fd.append("password", form.password ?? "");
    fd.append("azienda", form.azienda ?? "");
    fd.append("listino", form.listino ?? "");
    fd.append("trasporto", form.trasporto ?? "");
    fd.append("datiazienda", form.datiazienda ?? "");

    // logo
    if (logoFile.value) fd.append("logo", logoFile.value);
    if (removeLogo.value) fd.append("remove_logo", "1");

    const url = isEdit ? `/users/${form.id}` : "/users";

    if (isEdit) {
        fd.append("_method", "PUT");
        router.post(url, fd, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    } else {
        router.post(url, fd, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    }
}

function delRow(id) {
    if (!id) return;
    if (confirm("Sicuro di voler eliminare questo utente?")) {
        router.delete(`/users/${id}`, { preserveScroll: true });
    }
}

/* -------------------- Tabulator -------------------- */
const tableRef = ref(null);
let tableInstance = null;

const dynamicFilters = ref([{ field: "name", type: "like", value: "" }]);

const availableFields = [
    { label: "Nome", value: "name", type: "string" },
    { label: "Email", value: "email", type: "string" },
    { label: "Profilo", value: "profilo", type: "string" },
    { label: "Azienda", value: "azienda", type: "string" },
    { label: "Regione", value: "trasporto", type: "string" },
    { label: "Listino", value: "listino", type: "string" },
];

const operatorsByType = {
    string: ["like", "="],
};

function getFieldType(field) {
    return availableFields.find((f) => f.value === field)?.type ?? "string";
}

function addFilterRow() {
    dynamicFilters.value.push({ field: "name", type: "like", value: "" });
}

function removeFilterRow(index) {
    dynamicFilters.value.splice(index, 1);
    applyDynamicFilters();
}

function applyDynamicFilters() {
    if (!tableInstance) return;

    const filters = dynamicFilters.value
        .filter((f) => String(f.value ?? "").trim() !== "")
        .map((f) => ({
            field: f.field,
            type: f.type,
            value: String(f.value),
        }));

    tableInstance.clearFilter(true);
    if (filters.length) tableInstance.setFilter(filters);
}

function resetColumnLayout() {
    localStorage.removeItem("TBL-users");
    window.location.reload();
}

function applyQuickSearch() {
    if (!tableInstance) return;
    const q = String(quickSearch.value ?? "").trim();
    tableInstance.setFilter((data) => {
        if (!q) return true;
        const hay = `${data.name ?? ""} ${data.email ?? ""} ${
            data.profilo ?? ""
        } ${data.azienda ?? ""} ${data.trasporto ?? ""}`.toLowerCase();
        return hay.includes(q.toLowerCase());
    });
}

onMounted(() => {
    tableInstance = new Tabulator(tableRef.value, {
        data: props.users,
        layout: "fitColumns",
        reactiveData: true,
        movableColumns: true,
        resizableColumns: true,
        pagination: true,
        paginationSize: 10,
        paginationSizeSelector: [10, 20, 50, 100],
        placeholder: "Nessun utente trovato",
        persistence: { columns: true },
        persistenceID: "TBL-users",
        persistenceMode: "local",

        columns: [
            { title: "ID", field: "id", width: 70, hozAlign: "center" },

            {
                title: "Logo",
                field: "logo_path",
                width: 80,
                headerSort: false,
                hozAlign: "center",
                formatter: (cell) => {
                    let p = cell.getValue();
                    if (!p) return "";

                    p = String(p).trim().replaceAll("\\", "/"); // ✅ Windows backslash -> slash
                    p = p.replace(/^public\//, ""); // ✅ se per caso salva "public/user-logos/.."

                    let src = p;

                    // ✅ se NON è già URL assoluto e non è già /storage/...
                    if (
                        !/^https?:\/\//i.test(src) &&
                        !src.startsWith("/storage/")
                    ) {
                        src = `/storage/${src}`;
                    }

                    // mini placeholder se 404
                    return `
      <img src="${src}"
           style="width:34px;height:34px;border-radius:10px;object-fit:cover;border:1px solid #e2e8f0"
           onerror="this.onerror=null; this.src='/Foto/placeholder.jpg';"
      />

    `;
                },
            },
            {
                title: "Logo",
                field: "logo_url",
                width: 80,
                headerSort: false,
                hozAlign: "center",
                formatter: (cell) => {
                    const src = cell.getValue();
                    if (!src) return "";
                    return `
      <img src="${src}"
           style="width:34px;height:34px;border-radius:10px;object-fit:cover;border:1px solid #e2e8f0"
           onerror="this.style.display='none'"
      />
    `;
                },
            },

            { title: "Nome", field: "name", minWidth: 180, sorter: "string" },
            { title: "Email", field: "email", minWidth: 220, sorter: "string" },
            {
                title: "Profilo",
                field: "profilo",
                width: 120,
                sorter: "string",
            },
            {
                title: "Azienda",
                field: "azienda",
                width: 120,
                sorter: "string",
            },
            {
                title: "Listino",
                field: "listino",
                width: 90,
                hozAlign: "center",
            },
            {
                title: "Trasporto",
                field: "trasporto",
                width: 150,
                sorter: "string",
            },

            {
                title: "Azioni",
                field: "azioni",
                width: 140,
                headerSort: false,
                hozAlign: "center",
                formatter: () => `
      <div class="flex justify-center gap-2">
        <button type="button" data-action="edit" class="tb-btn tb-edit" title="Modifica">✏️</button>
        <button type="button" data-action="del" class="tb-btn tb-del" title="Elimina">🗑️</button>
      </div>
    `,
                cellClick: (e, cell) => {
                    const action = e.target?.getAttribute?.("data-action");
                    const row = cell.getRow().getData();
                    if (action === "edit") editRow(row);
                    if (action === "del") delRow(row.id);
                },
            },
        ],
    });

    applyDynamicFilters();
    applyQuickSearch();
});

/* -------------------- React to props changes -------------------- */
watch(
    () => props.users,
    (newData) => {
        if (!tableInstance) return;
        tableInstance.replaceData(newData ?? []);
        applyDynamicFilters();
        applyQuickSearch();
    }
);

watch(quickSearch, () => applyQuickSearch());
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-4 md:p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-2xl font-bold text-slate-900">Utenti</div>
                    <div class="text-sm text-slate-500">
                        Gestione profili, listini e trasporto
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50"
                        @click="showFilters = !showFilters"
                    >
                        {{ showFilters ? "Nascondi filtri" : "Filtri" }}
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50"
                        @click="resetColumnLayout"
                    >
                        Reset colonne
                    </button>
                </div>
            </div>

            <!-- KPI -->
            <div class="grid grid-cols-12 gap-4">
                <div
                    class="col-span-12 md:col-span-3 rounded-2xl border bg-white p-4 shadow-sm"
                >
                    <div class="text-sm text-slate-500">Totale</div>
                    <div class="text-2xl font-bold text-slate-900">
                        {{ totalUsers }}
                    </div>
                </div>
                <div
                    class="col-span-12 md:col-span-3 rounded-2xl border bg-white p-4 shadow-sm"
                >
                    <div class="text-sm text-slate-500">Admin</div>
                    <div class="text-2xl font-bold text-slate-900">
                        {{ totalAdmin }}
                    </div>
                </div>
                <div
                    class="col-span-12 md:col-span-3 rounded-2xl border bg-white p-4 shadow-sm"
                >
                    <div class="text-sm text-slate-500">Isomax</div>
                    <div class="text-2xl font-bold text-slate-900">
                        {{ totalIsomax }}
                    </div>
                </div>
                <div
                    class="col-span-12 md:col-span-3 rounded-2xl border bg-white p-4 shadow-sm"
                >
                    <div class="text-sm text-slate-500">Nurith</div>
                    <div class="text-2xl font-bold text-slate-900">
                        {{ totalNurith }}
                    </div>
                </div>
            </div>

            <!-- Filtri + Quick search -->
            <div
                v-if="showFilters"
                class="rounded-2xl border bg-white shadow-sm p-4 md:p-5"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 mb-4"
                >
                    <div>
                        <div class="text-lg font-semibold text-slate-900">
                            Filtri
                        </div>
                        <div class="text-sm text-slate-500">
                            Ricerca rapida + filtri combinati
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            v-model="quickSearch"
                            placeholder="Cerca (nome, email, profilo, azienda, trasporto)…"
                            class="w-full md:w-[420px] rounded-xl border px-3 py-2 text-sm shadow-sm"
                        />
                        <button
                            type="button"
                            class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50"
                            @click="
                                () => {
                                    quickSearch = '';
                                    dynamicFilters = [
                                        {
                                            field: 'name',
                                            type: 'like',
                                            value: '',
                                        },
                                    ];
                                    applyDynamicFilters();
                                }
                            "
                        >
                            Pulisci
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(filter, index) in dynamicFilters"
                        :key="index"
                        class="grid grid-cols-12 gap-2 items-center"
                    >
                        <div class="col-span-12 md:col-span-4">
                            <select
                                v-model="filter.field"
                                class="w-full rounded-xl border px-3 py-2 text-sm"
                                @change="
                                    filter.type =
                                        operatorsByType[
                                            getFieldType(filter.field)
                                        ][0]
                                "
                            >
                                <option
                                    v-for="f in availableFields"
                                    :key="f.value"
                                    :value="f.value"
                                >
                                    {{ f.label }}
                                </option>
                            </select>
                        </div>

                        <div class="col-span-12 md:col-span-2">
                            <select
                                v-model="filter.type"
                                class="w-full rounded-xl border px-3 py-2 text-sm"
                            >
                                <option
                                    v-for="op in operatorsByType[
                                        getFieldType(filter.field)
                                    ]"
                                    :key="op"
                                    :value="op"
                                >
                                    {{ op }}
                                </option>
                            </select>
                        </div>

                        <div class="col-span-12 md:col-span-5">
                            <input
                                v-model="filter.value"
                                class="w-full rounded-xl border px-3 py-2 text-sm"
                                type="text"
                                placeholder="valore…"
                                @keyup.enter="applyDynamicFilters"
                            />
                        </div>

                        <div class="col-span-12 md:col-span-1 flex justify-end">
                            <button
                                type="button"
                                @click="removeFilterRow(index)"
                                class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50"
                                title="Rimuovi"
                            >
                                ✖
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="addFilterRow"
                            class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50"
                        >
                            ➕ Filtro
                        </button>
                        <button
                            type="button"
                            @click="applyDynamicFilters"
                            class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 text-sm"
                        >
                            Applica
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form
                @submit.prevent="submit"
                class="rounded-2xl border bg-white shadow-sm p-4 md:p-5"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 mb-4"
                >
                    <div>
                        <div class="text-lg font-semibold text-slate-900">
                            {{ form.id ? "Modifica utente" : "Nuovo utente" }}
                        </div>
                        <div class="text-sm text-slate-500">
                            {{
                                form.id
                                    ? `ID #${form.id}`
                                    : "Compila i campi e salva"
                            }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50"
                            @click="showAdvanced = !showAdvanced"
                        >
                            {{
                                showAdvanced ? "Nascondi avanzati" : "Avanzati"
                            }}
                        </button>

                        <button
                            v-if="form.id"
                            type="button"
                            class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50"
                            @click="resetForm"
                        >
                            Annulla
                        </button>

                        <button
                            type="submit"
                            class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 text-sm font-semibold shadow"
                        >
                            {{ form.id ? "Salva" : "Aggiungi" }}
                        </button>
                    </div>
                </div>

                <!-- 2 righe pulite -->
                <div class="grid grid-cols-12 gap-4">
                    <!-- Riga 1 -->
                    <div class="col-span-12 md:col-span-4">
                        <label class="text-sm font-semibold text-slate-800"
                            >Nome</label
                        >
                        <input
                            v-model="form.name"
                            class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                        />
                        <div
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="text-sm font-semibold text-slate-800"
                            >Email</label
                        >
                        <input
                            type="email"
                            v-model="form.email"
                            class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                        />
                        <div
                            v-if="form.errors.email"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="text-sm font-semibold text-slate-800"
                            >Password</label
                        >
                        <input
                            type="password"
                            v-model="form.password"
                            autocomplete="new-password"
                            class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                        />
                        <div class="mt-1 text-xs text-slate-500">
                            {{
                                form.id
                                    ? "Lascia vuoto per non cambiarla"
                                    : "Obbligatoria in creazione"
                            }}
                        </div>
                        <div
                            v-if="form.errors.password"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <!-- Riga 2 -->
                    <div class="col-span-12 md:col-span-2">
                        <label class="text-sm font-semibold text-slate-800"
                            >Azienda</label
                        >
                        <input
                            v-model="form.azienda"
                            class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                        />
                    </div>

                    <div class="col-span-12 md:col-span-1">
                        <label class="text-sm font-semibold text-slate-800"
                            >Listino</label
                        >
                        <input
                            v-model="form.listino"
                            class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm text-center"
                        />
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="text-sm font-semibold text-slate-800"
                            >Regione trasporto</label
                        >
                        <select
                            v-model="form.trasporto"
                            class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                        >
                            <option value="">— Seleziona —</option>
                            <option
                                v-for="r in regioniTrasporto"
                                :key="r"
                                :value="r"
                            >
                                {{ r }}
                            </option>
                        </select>
                    </div>

                    <div class="col-span-12 md:col-span-2">
                        <label class="text-sm font-semibold text-slate-800"
                            >Profilo</label
                        >
                        <select
                            v-model="form.profilo"
                            class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                        >
                            <option value="admin">Admin</option>
                            <option value="Isomax">Isomax</option>
                            <option value="Nurith">Nurith</option>
                            <option value="user">User</option>
                        </select>
                        <div
                            v-if="form.errors.profilo"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.profilo }}
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-3 flex items-end">
                        <div class="text-xs text-slate-500">
                            Tip: puoi trascinare le colonne, ridimensionarle e
                            la vista si salva.
                        </div>
                    </div>

                    <!-- Avanzati -->
                    <div v-if="showAdvanced" class="col-span-12">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-8">
                                <label
                                    class="text-sm font-semibold text-slate-800"
                                    >Dati azienda</label
                                >
                                <textarea
                                    v-model="form.datiazienda"
                                    rows="3"
                                    class="mt-1 w-full rounded-xl border px-3 py-2 shadow-sm"
                                ></textarea>
                            </div>

                            <div class="col-span-12 md:col-span-4">
                                <label
                                    class="text-sm font-semibold text-slate-800"
                                    >Logo utente</label
                                >

                                <div class="mt-1 flex items-center gap-3">
                                    <div
                                        class="h-12 w-12 rounded-xl border bg-white overflow-hidden flex items-center justify-center"
                                    >
                                        <img
                                            v-if="logoPreview"
                                            :src="logoPreview"
                                            class="h-full w-full object-cover"
                                            alt="logo"
                                        />
                                        <span
                                            v-else
                                            class="text-xs text-slate-400"
                                            >—</span
                                        >
                                    </div>

                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        @change="onPickLogo"
                                        class="block w-full text-sm"
                                    />

                                    <button
                                        v-if="logoPreview"
                                        type="button"
                                        class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50"
                                        @click="
                                            () => {
                                                logoFile.value = null;
                                                logoPreview.value = null;
                                                removeLogo.value = true;
                                            }
                                        "
                                        title="Rimuovi logo"
                                    >
                                        Rimuovi
                                    </button>
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    PNG/JPG/WEBP, max 2MB
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Tabella -->
            <div class="rounded-2xl border bg-white shadow-sm">
                <div
                    class="px-4 py-3 border-b flex flex-wrap items-center justify-between gap-2"
                >
                    <div class="font-semibold text-slate-900">Elenco</div>
                    <div class="text-sm text-slate-500">
                        {{ totalUsers }} utenti
                    </div>
                </div>

                <div class="w-full overflow-x-auto">
                    <div class="min-w-[900px] p-2">
                        <div ref="tableRef"></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "tabulator-tables/dist/css/tabulator.min.css";

/* Tabulator “più moderno” senza stravolgere */
.tabulator {
    border: 0 !important;
    background: transparent !important;
    font-size: 13px;
}
.tabulator .tabulator-header {
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
.tabulator .tabulator-row {
    border-bottom: 1px solid #eef2f7 !important;
}
.tabulator .tabulator-row.tabulator-row-even {
    background: #fcfdff !important;
}
.tabulator .tabulator-cell {
    padding: 10px 10px !important;
}
.tb-btn {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 6px 8px;
    line-height: 1;
    background: white;
}
.tb-btn:hover {
    background: #f8fafc;
}
.tb-edit {
}
.tb-del {
}
</style>
