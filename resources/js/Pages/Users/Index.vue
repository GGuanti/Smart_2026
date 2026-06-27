<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import SmartGrid from "@/Components/SmartGrid.vue";
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";

/* -------------------- Stile campi (riuso) -------------------- */
const labelClass =
    "mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500";
const fieldClass =
    "w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none";

const showPwd = ref(false);

/* -------------------- Logo upload -------------------- */
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

function removeLogoNow() {
    logoFile.value = null;
    logoPreview.value = null;
    removeLogo.value = true;
}

/* -------------------- Props -------------------- */
const props = defineProps({
    users: { type: Array, default: () => [] },
    regioniTrasporto: { type: Array, default: () => [] },
    tipiDoc: { type: Array, default: () => [] },
    preventiviPivot: { type: Array, default: () => [] },
    savedLayout: { type: [Array, Object], default: null },
});

/* -------------------- KPIs -------------------- */
const totalUsers = computed(() => props.users?.length ?? 0);
const totalAdmin = computed(
    () =>
        (props.users ?? []).filter(
            (u) => String(u.profilo).toLowerCase() === "admin",
        ).length,
);
const totalIsomax = computed(
    () =>
        (props.users ?? []).filter(
            (u) => String(u.profilo).toLowerCase() === "isomax",
        ).length,
);
const totalNurith = computed(
    () =>
        (props.users ?? []).filter(
            (u) => String(u.profilo).toLowerCase() === "nurith",
        ).length,
);

/* -------------------- SmartGrid utenti -------------------- */
const regioneById = computed(() =>
    Object.fromEntries(
        (props.regioniTrasporto ?? []).map((r) => [Number(r.id), r.regione]),
    ),
);

const gridRows = computed(() =>
    (props.users ?? []).map((u) => ({
        id: u.id,
        logo_path: u.logo_path ?? "",
        name: u.name ?? "",
        email: u.email ?? "",
        profilo: u.profilo ?? "",
        azienda: u.azienda ?? "",
        listino: u.listino ?? "",
        regione: regioneById.value[Number(u.IdCostoTrasporto)] ?? "-",
        azioni: u.id,
    })),
);

const gridLabels = {
    id: "ID",
    logo_path: "Logo",
    name: "Nome",
    email: "Email",
    profilo: "Profilo",
    azienda: "Azienda",
    listino: "Listino",
    regione: "Regione",
    azioni: "Azioni",
};

function editRowById(id) {
    const full = (props.users ?? []).find((u) => Number(u.id) === Number(id));
    if (full) editRow(full);
}

function logoSrc(p) {
    if (!p) return "";
    p = String(p).trim().replaceAll("\\", "/");
    if (/^https?:\/\//i.test(p)) return p;
    return p.startsWith("/") ? p : "/" + p;
}
function onLogoError(e) {
    e.target.onerror = null;
    e.target.src = "/Foto/placeholder.jpg";
}

/* -------------------- SmartGrid pivot preventivi -------------------- */
const pivotLabels = computed(() => {
    const m = { name: "Utente", Totale: "Totale" };
    for (const td of props.tipiDoc ?? []) m[String(td)] = String(td);
    return m;
});
const pivotSumColumns = computed(() => [
    ...(props.tipiDoc ?? []).map(String),
    "Totale",
]);

/* -------------------- Form (Inertia useForm) -------------------- */
const DEFAULTS = {
    id: null,
    name: "",
    email: "",
    password: "",
    profilo: "Isomax",
    listino: "1",
    azienda: "Isomax",
    IdCostoTrasporto: null,
    datiazienda: "",
};

const form = useForm({ ...DEFAULTS });

function resetForm() {
    form.reset();
    form.clearErrors();
    form.defaults({
        ...DEFAULTS,
        IdCostoTrasporto: props.regioniTrasporto?.[0]?.id ?? null,
    });
    form.reset();

    logoFile.value = null;
    logoPreview.value = null;
    removeLogo.value = false;
    showPwd.value = false;
}

function editRow(row) {
    form.clearErrors();
    form.id = row.id ?? null;
    form.name = row.name ?? "";
    form.email = row.email ?? "";
    form.password = "";
    form.profilo = row.profilo ?? "Isomax";
    form.listino = row.listino ?? "1";
    form.azienda = row.azienda ?? "Isomax";
    form.IdCostoTrasporto = row.IdCostoTrasporto ?? null;
    form.datiazienda = row.datiazienda ?? "";

    window.scrollTo({ top: 0, behavior: "smooth" });

    logoFile.value = null;

    let p = row.logo_path
        ? String(row.logo_path).trim().replaceAll("\\", "/")
        : "";

    if (!p) {
        logoPreview.value = null;
    } else if (/^https?:\/\//i.test(p)) {
        logoPreview.value = p;
    } else if (p.startsWith("/")) {
        logoPreview.value = p;
    } else if (p.startsWith("storage/") || p.startsWith("public/")) {
        logoPreview.value = "/" + p.replace(/^public\//, "storage/");
    } else {
        logoPreview.value = "/" + p;
    }

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
    fd.append("name", form.name ?? "");
    fd.append("email", form.email ?? "");
    fd.append("profilo", form.profilo ?? "");
    fd.append("password", form.password ?? "");
    fd.append("azienda", form.azienda ?? "");
    fd.append("listino", form.listino ?? "");
    fd.append("IdCostoTrasporto", form.IdCostoTrasporto ?? "");
    fd.append("datiazienda", form.datiazienda ?? "");

    if (logoFile.value) fd.append("logo", logoFile.value);
    if (removeLogo.value) fd.append("remove_logo", "1");

    const url = isEdit ? `/users/${form.id}` : "/users";

    if (isEdit) {
        fd.append("_method", "PUT");
        router.post(url, fd, { preserveScroll: true, onSuccess: () => resetForm() });
    } else {
        router.post(url, fd, { preserveScroll: true, onSuccess: () => resetForm() });
    }
}

function delRow(id) {
    if (!id) return;
    if (confirm("Sicuro di voler eliminare questo utente?")) {
        router.delete(`/users/${id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-4 md:p-5 space-y-4">
            <!-- Header -->


            <!-- ============ RIGA: SmartGrid (sx) + Form (dx) ============ -->
            <div class="grid grid-cols-12 gap-4 items-start">
                <!-- SINISTRA: tabella utenti -->
                <div class="col-span-12 xl:col-span-7 rounded-2xl border bg-white shadow-sm p-2">
                    <div class="h-[680px]">
                        <SmartGrid
                            query-name="users"
                            :rows="gridRows"
                            :column-labels="gridLabels"
                            :saved-layout="savedLayout"
                        >
                            <template #cell-logo_path="{ value }">
                                <img
                                    v-if="value"
                                    :src="logoSrc(value)"
                                    class="h-20 w-40 rounded-lg object-cover border border-slate-200"
                                    @error="onLogoError"
                                    alt=""
                                />
                                <span v-else class="text-slate-300">—</span>
                            </template>

                            <template #cell-azioni="{ row }">
                                <div class="flex justify-center gap-2">
                                    <button type="button" @click="editRowById(row.id)" title="Modifica" class="rounded-lg border border-slate-200 bg-white px-2 py-1 leading-none hover:bg-slate-50">✏️</button>
                                    <button type="button" @click="delRow(row.id)" title="Elimina" class="rounded-lg border border-slate-200 bg-white px-2 py-1 leading-none hover:bg-red-50">🗑️</button>
                                </div>
                            </template>
                        </SmartGrid>
                    </div>
                </div>

                <!-- DESTRA: form nuovo utente (compatto) -->
                <form
                    @submit.prevent="submit"
                    class="col-span-12 xl:col-span-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <!-- Header form -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">{{ form.id ? "Modifica utente" : "Nuovo utente" }}</div>
                                <div class="text-[11px] text-slate-500">{{ form.id ? `ID #${form.id}` : "Compila i campi e salva" }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button v-if="form.id" type="button" @click="resetForm" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Annulla</button>
                            <button type="submit" class="rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-1.5 text-sm font-semibold text-white shadow-md shadow-blue-600/20 transition hover:shadow-lg hover:brightness-110">
                                {{ form.id ? "Salva" : "Aggiungi" }}
                            </button>
                        </div>
                    </div>

                    <!-- Corpo form compatto -->
                    <div class="space-y-5 p-4">
                        <!-- Account -->
                        <section>
                            <div class="mb-2 flex items-center gap-3">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Account</span>
                                <span class="h-px flex-1 bg-slate-100"></span>
                            </div>
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-12 sm:col-span-6">
                                    <label :class="labelClass">Nome</label>
                                    <input v-model="form.name" :class="fieldClass" placeholder="Mario Rossi" />
                                    <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                                </div>
                                <div class="col-span-12 sm:col-span-6">
                                    <label :class="labelClass">Email</label>
                                    <input type="email" v-model="form.email" :class="fieldClass" placeholder="nome@azienda.com" />
                                    <div v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</div>
                                </div>
                                <div class="col-span-12">
                                    <label :class="labelClass">Password</label>
                                    <div class="relative">
                                        <input
                                            :type="showPwd ? 'text' : 'password'"
                                            v-model="form.password"
                                            autocomplete="new-password"
                                            :class="fieldClass"
                                            class="pr-10"
                                            :placeholder="form.id ? '••••••• (invariata)' : 'Imposta una password'"
                                        />
                                        <button type="button" tabindex="-1" @click="showPwd = !showPwd" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-700">
                                            <svg v-if="!showPwd" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <svg v-else width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                        </button>
                                    </div>
                                    <div class="mt-1 text-[11px] text-slate-400">{{ form.id ? "Lascia vuoto per non cambiarla" : "Obbligatoria in creazione" }}</div>
                                    <div v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</div>
                                </div>
                            </div>
                        </section>

                        <!-- Configurazione -->
                        <section>
                            <div class="mb-2 flex items-center gap-3">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Configurazione</span>
                                <span class="h-px flex-1 bg-slate-100"></span>
                            </div>
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-12 sm:col-span-6">
                                    <label :class="labelClass">Azienda</label>
                                    <input v-model="form.azienda" :class="fieldClass" placeholder="Isomax" />
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label :class="labelClass">Listino</label>
                                    <input v-model="form.listino" :class="fieldClass" class="text-center" inputmode="numeric" />
                                </div>
                                <div class="col-span-6 sm:col-span-3">
                                    <label :class="labelClass">Profilo</label>
                                    <select v-model="form.profilo" :class="fieldClass">
                                        <option value="admin">Admin</option>
                                        <option value="Isomax">Isomax</option>
                                        <option value="Nurith">Nurith</option>
                                        <option value="user">User</option>
                                    </select>
                                    <div v-if="form.errors.profilo" class="mt-1 text-xs text-red-600">{{ form.errors.profilo }}</div>
                                </div>
                                <div class="col-span-12">
                                    <label :class="labelClass">Regione trasporto</label>
                                    <select v-model.number="form.IdCostoTrasporto" :class="fieldClass">
                                        <option :value="null">— Seleziona —</option>
                                        <option v-for="r in regioniTrasporto" :key="r.id" :value="r.id">{{ r.regione }}</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <!-- Logo compatto + Note -->
                        <section>
                            <div class="mb-2 flex items-center gap-3">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Logo &amp; Note</span>
                                <span class="h-px flex-1 bg-slate-100"></span>
                            </div>

                            <!-- Logo orizzontale compatto -->
                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                                <div class="relative shrink-0">
                                    <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-200 bg-white">
                                        <img v-if="logoPreview" :src="logoPreview" class="h-full w-full object-contain" alt="logo" @error="logoPreview = null" />
                                        <svg v-else width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/></svg>
                                    </div>
                                    <button v-if="logoPreview" type="button" @click="removeLogoNow" title="Rimuovi" class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-white text-slate-400 shadow ring-1 ring-slate-200 transition hover:text-red-500">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <label class="block cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-center text-sm font-medium text-slate-600 transition hover:border-blue-400 hover:text-blue-600">
                                        {{ logoPreview ? "Cambia immagine" : "Scegli immagine" }}
                                        <input type="file" accept="image/png,image/jpeg,image/webp" @change="onPickLogo" class="hidden" />
                                    </label>
                                    <div class="mt-1.5 text-[11px] text-slate-400">PNG / JPG / WEBP • max 2MB</div>
                                </div>
                            </div>

                            <!-- Note -->
                            <div class="mt-3">
                                <label :class="labelClass">Dati azienda</label>
                                <textarea v-model="form.datiazienda" rows="3" :class="fieldClass" placeholder="Note, intestazione, riferimenti..."></textarea>
                            </div>
                        </section>
                    </div>
                </form>
            </div>
             <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-xl font-bold text-slate-900">Utenti</div>
                    <div class="text-sm text-slate-500">Gestione profili, listini e trasporto</div>
                </div>

                <!-- KPI compatti inline -->
                <div class="flex flex-wrap gap-2">
                    <div class="flex items-center gap-2 rounded-xl border bg-white px-3 py-1.5 shadow-sm">
                        <span class="text-xs text-slate-500">Totale</span>
                        <span class="text-base font-bold text-slate-900">{{ totalUsers }}</span>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl border bg-white px-3 py-1.5 shadow-sm">
                        <span class="text-xs text-slate-500">Admin</span>
                        <span class="text-base font-bold text-red-600">{{ totalAdmin }}</span>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl border bg-white px-3 py-1.5 shadow-sm">
                        <span class="text-xs text-slate-500">Isomax</span>
                        <span class="text-base font-bold text-indigo-600">{{ totalIsomax }}</span>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl border bg-white px-3 py-1.5 shadow-sm">
                        <span class="text-xs text-slate-500">Nurith</span>
                        <span class="text-base font-bold text-emerald-600">{{ totalNurith }}</span>
                    </div>
                </div>
            </div>

            <!-- Pivot preventivi (full width) -->
            <div class="rounded-2xl border bg-white shadow-sm p-4">
                <div class="text-base font-semibold text-slate-900">Preventivi / Documenti per utente</div>
                <div class="text-xs text-slate-500 mb-2">Conteggio per TipoDoc (pivot)</div>
                <div class="h-[440px]">
                    <SmartGrid
                        query-name="preventivi-pivot"
                        :rows="preventiviPivot"
                        :column-labels="pivotLabels"
                        :sum-columns="pivotSumColumns"
                        totals-label="TOTALE"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.logoBox {
    width: 100%;
    height: 220px;
}
</style>
