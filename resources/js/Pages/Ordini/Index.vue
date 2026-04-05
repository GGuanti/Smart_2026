<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useToast } from "vue-toastification";

const toast = useToast();
const confirmDeleteId = ref(null);
const isCopyingId = ref(null);
const props = defineProps({
    ordini: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    isAdmin: { type: Boolean, default: false },
    usersList: { type: Array, default: () => [] },
});
const stato = ref(props.filters?.stato ?? "");
const statiOrdine = ["Preventivo", "Ordine", "Ordine inviato", "Consegnato"];

const q = ref(props.filters?.q ?? "");
const user_id = ref("");
const perPage = ref(props.filters?.per_page ?? 15);

function search(page = 1) {
    router.get(
        route("ordini.index"),
        {
            q: q.value,
            stato: stato.value,
            user_id: user_id.value || null,
            per_page: perPage.value,
            page: page,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        },
    );
}

function clearSearch() {
    q.value = "";
    stato.value = "";
    user_id.value = ""; // ✅ torna a “Tutti gli utenti”
    search();
}

const stats = computed(() => ({
    total: props.ordini?.total ?? 0,
    from: props.ordini?.from ?? 0,
    to: props.ordini?.to ?? 0,
}));

function fmtDate(v) {
    if (!v) return "";
    return String(v).slice(0, 10);
}

function destroy(id) {
    if (confirmDeleteId.value !== id) {
        confirmDeleteId.value = id;
        toast.warning("⚠️ Confermi eliminazione ordine?", {
            timeout: 8000,
            closeOnClick: false,
            onClose: () => (confirmDeleteId.value = null),
        });
        return;
    }

    router.delete(route("ordini.destroy", id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("🗑️ Ordine eliminato");
            confirmDeleteId.value = null;
        },
        onError: () => {
            toast.error("❌ Errore eliminazione ordine");
            confirmDeleteId.value = null;
        },
    });
}

function copiaOrdine(id) {
    if (!id) return toast.error("ID non valido");
    if (isCopyingId.value) return;

    isCopyingId.value = id;
    router.post(
        route("ordini.copia", id),
        {},
        {
            preserveScroll: true,
            onStart: () => toast.info("📄 Copia in corso…"),
            onSuccess: () => toast.success("✅ Ordine copiato"),
            onError: () => toast.error("❌ Errore copia"),
            onFinish: () => (isCopyingId.value = null),
        },
    );
}
function fmtDateIT(value) {
    if (!value) return "";
    const d = new Date(value);
    if (isNaN(d)) return "";
    return d.toLocaleDateString("it-IT");
}
function docPillClass(tipo) {
    const t = String(tipo || "").trim();

    switch (t) {
        case "Ordine":
            return "pill-blue";
        case "Ordine inviato":
            return "pill-amber";
        case "Consegnato":
            return "pill-green";
        default:
            return "pill-slate"; // Preventivo
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Ordini" />

        <div class="min-h-screen bg-slate-50">
            <!-- ================= TOP BAR ================= -->
            <div class="sticky top-0 z-20 bg-white/80 backdrop-blur border-b">
                <div class="max-w-6xl mx-auto px-4 py-3 space-y-3">
                    <!-- Title row -->
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-3">
                            <img src="/Logo1.png" class="h-10" />
                            <div>
                                <div class="flex items-center gap-2">
                                    <h1
                                        class="text-2xl font-extrabold text-slate-900"
                                    >
                                        Ordini
                                    </h1>
                                    <span class="badge badge-slate">Lista</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ stats.from }}–{{ stats.to }} su
                                    {{ stats.total }}
                                </div>
                            </div>
                        </div>

                        <Link
                            :href="route('ordini.create')"
                            class="btn btn-primary"
                        >
                            ➕ Nuovo ordine
                        </Link>
                    </div>

                    <!-- 🔎 SEARCH ROW -->
                    <div class="flex flex-wrap gap-2">
                        <input
                            v-model="q"
                            @keyup.enter="search"
                            class="input flex-1 min-w-[240px]"
                            placeholder="Cerca: Nordine, cliente, telefono, città…"
                        />
                        <!-- ✅ FILTRO STATO -->
                        <select
                            v-model="stato"
                            class="input w-[220px]"
                            @change="search"
                        >
                            <option value="">Tutti gli stati</option>
                            <option
                                v-for="s in statiOrdine"
                                :key="s"
                                :value="s"
                            >
                                {{ s }}
                            </option>
                        </select>
                        <!-- ✅ FILTRO UTENTE SOLO ADMIN -->
                        <select
                            v-if="props.isAdmin"
                            v-model="user_id"
                            class="input w-[220px]"
                            @change="search"
                        >
                            <option value="">Tutti gli utenti</option>

                            <option
                                v-for="u in props.usersList"
                                :key="u.id"
                                :value="String(u.id)"
                            >
                                {{ u.name }}
                            </option>
                        </select>

                        <button class="btn btn-ghost" @click="clearSearch">
                            Azzera
                        </button>

                        <button class="btn btn-primary" @click="search">
                            Cerca
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= CONTENT ================= -->
            <div class="max-w-6xl mx-auto p-4">
                <div class="card overflow-hidden">
                    <div class="px-4 py-3 border-b bg-slate-50">
                        <div class="font-extrabold text-slate-900">
                            📄 Elenco ordini
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-white border-b">
                                <tr class="text-slate-600">
                                    <th class="px-4 py-3 text-left">Nordine</th>
                                    <th class="px-4 py-3 text-left">
                                        Stato Ordine
                                    </th>
                                    <th class="px-4 py-3 text-left">Cliente</th>
                                    <th class="px-4 py-3 text-left">
                                        Telefono
                                    </th>
                                    <th class="px-4 py-3 text-left">Città</th>
                                    <th class="px-4 py-3 text-center">
                                        Data Ordine
                                    </th>
                                    <th class="px-4 py-3 text-center">
                                        Data Consegna
                                    </th>
                                    <th class="px-4 py-3 text-center">
                                        Utente
                                    </th>
                                    <th class="px-4 py-3 text-center">
                                        Azioni
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="o in ordini.data"
                                    :key="o.ID"
                                    class="border-t hover:bg-slate-50"
                                >
                                    <td class="px-4 py-3 font-extrabold">
                                        {{ o.Nordine }}
                                    </td>
                                    <!-- Tipo Documento -->
                                    <td class="px-4 py-4">
                                        <span
                                            class="pill"
                                            :class="docPillClass(o.TipoDoc)"
                                        >
                                            {{ o.TipoDoc || "—" }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold">
                                            {{ o.CognomeNome }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ o.Indirizzo }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ o.Telefono || o.Cellulare || "—" }}
                                    </td>

                                    <td class="px-4 py-3">{{ o.IdCitta }}</td>

                                    <td class="px-4 py-3 text-center">
                                        {{ fmtDateIT(o.DataOrdine) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ fmtDateIT(o.DataCons) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ o.Utente }}
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <div
                                            class="inline-flex rounded-xl border bg-white shadow-sm overflow-hidden"
                                        >
                                            <Link
                                                :href="
                                                    route('ordini.edit', o.ID)
                                                "
                                                class="px-3 py-2 hover:bg-blue-50 text-blue-700"
                                                title="Modifca Ordine"
                                                >✏️</Link
                                            >

                                            <button
                                                class="px-3 py-2 hover:bg-emerald-50 text-emerald-700"
                                                @click="copiaOrdine(o.ID)"
                                                :disabled="isCopyingId === o.ID"
                                                title="Copia Ordine"
                                            >
                                                {{
                                                    isCopyingId === o.ID
                                                        ? "⏳"
                                                        : "📄"
                                                }}
                                            </button>

                                            <button
                                                class="px-3 py-2 hover:bg-red-50 text-red-700"
                                                @click="destroy(o.ID)"
                                                title="Cancella Ordine"
                                            >
                                                {{
                                                    confirmDeleteId === o.ID
                                                        ? "⚠️"
                                                        : "🗑️"
                                                }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!ordini.data.length">
                                    <td
                                        colspan="6"
                                        class="py-10 text-center text-slate-500"
                                    >
                                        Nessun ordine trovato
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div
                            class="flex flex-wrap items-center justify-between gap-4 mt-4 p-3 bg-white border rounded-xl shadow-sm"
                        >
                            <!-- ⬅️ PAGINAZIONE -->
                            <div class="flex items-center gap-2">
                                <button
                                    class="px-3 py-2 rounded-lg border bg-white hover:bg-slate-50 disabled:opacity-40"
                                    :disabled="!ordini.prev_page_url"
                                    @click="search(ordini.current_page - 1)"
                                >
                                    ←
                                </button>

                                <div
                                    class="px-3 py-2 text-sm font-semibold text-slate-700"
                                >
                                    Pagina {{ ordini.current_page }}
                                    <span class="text-slate-400"
                                        >/ {{ ordini.last_page }}</span
                                    >
                                </div>

                                <button
                                    class="px-3 py-2 rounded-lg border bg-white hover:bg-slate-50 disabled:opacity-40"
                                    :disabled="!ordini.next_page_url"
                                    @click="search(ordini.current_page + 1)"
                                >
                                    →
                                </button>
                            </div>

                            <!-- 📊 INFO RECORD -->
                            <div class="text-sm text-slate-500">
                                Mostrati
                                <span class="font-semibold">{{
                                    stats.from
                                }}</span
                                >–
                                <span class="font-semibold">{{
                                    stats.to
                                }}</span>
                                di
                                <span class="font-semibold">{{
                                    stats.total
                                }}</span>
                            </div>

                            <!-- 🔢 RECORD PER PAGINA -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-500"
                                    >Record:</span
                                >

                                <select
                                    v-model="perPage"
                                    @change="search(1)"
                                    class="rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-blue-100"
                                >
                                    <option :value="10">10</option>
                                    <option :value="15">15</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.card {
    @apply rounded-2xl border bg-white shadow-sm;
}
.badge {
    @apply text-xs font-extrabold px-2 py-1 rounded-full border;
}
.badge-slate {
    @apply bg-slate-50 text-slate-700 border-slate-200;
}
.btn {
    @apply px-4 py-2 rounded-xl border bg-white hover:bg-slate-50;
}
.btn-primary {
    @apply bg-blue-600 text-white font-semibold hover:bg-blue-700;
}
.btn-ghost {
    @apply border;
}
.input {
    @apply rounded-xl border px-3 py-2 shadow-sm focus:ring-2 focus:ring-blue-100;
}
.pill {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full
    text-[11px] font-extrabold border tracking-wide;
}

.pill-slate {
    @apply bg-slate-50 text-slate-700 border-slate-200;
}

.pill-blue {
    @apply bg-blue-50 text-blue-700 border-blue-200;
}

.pill-amber {
    @apply bg-amber-50 text-amber-800 border-amber-200;
}

.pill-green {
    @apply bg-emerald-50 text-emerald-700 border-emerald-200;
}
</style>
