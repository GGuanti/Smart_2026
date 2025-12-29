<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useToast } from "vue-toastification";
const toast = useToast();
const confirmDeleteId = ref(null);

const props = defineProps({
    ordini: Object, // paginator
    filters: Object, // { q }
});

const q = ref(props.filters?.q ?? "");

function search() {
    router.get(
        route("ordini.index"),
        { q: q.value },
        { preserveState: true, replace: true, preserveScroll: true }
    );
}

function clearSearch() {
    q.value = "";
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

function fmtNum(v) {
    return Number(v ?? 0).toFixed(2);
}
function destroy(id) {
    // primo click → chiedi conferma
    if (confirmDeleteId.value !== id) {
        confirmDeleteId.value = id;

        toast.warning("⚠️ Confermi eliminazione ordine?", {
            position: "top-left",
            timeout: 8000,
            closeOnClick: false,
            draggable: false,
            onClose: () => {
                confirmDeleteId.value = null;
            },
        });

        return;
    }

    // secondo click → elimina davvero
    router.delete(route("ordini.destroy", id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("🗑️ Ordine eliminato con successo", {
                position: "top-left",
                timeout: 2500,
            });
            confirmDeleteId.value = null;
        },
        onError: () => {
            toast.error("❌ Errore durante l’eliminazione dell’ordine", {
                position: "top-left",
                timeout: 3000,
            });
            confirmDeleteId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Ordini" />

    <div
        class="min-h-screen bg-[radial-gradient(1200px_circle_at_15%_0%,rgba(59,130,246,.10),transparent_60%),radial-gradient(900px_circle_at_90%_10%,rgba(99,102,241,.10),transparent_55%),linear-gradient(to_bottom,rgba(248,250,252,1),rgba(255,255,255,1))]"
    >
        <!-- Top bar -->
        <div class="sticky top-0 z-10 border-b bg-white/75 backdrop-blur">
            <div
                class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-3"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <img
                            src="/logo.jpg"
                            alt="Logo"
                            class="h-12 object-contain"
                        />
                        <h1
                            class="text-xl md:text-2xl font-extrabold text-slate-900"
                        >
                            Ordini
                        </h1>

                        <span class="badge badge-slate">Lista</span>
                    </div>
                    <div class="text-xs text-slate-500">
                        Visualizzati
                        <span class="font-semibold"
                            >{{ stats.from }}–{{ stats.to }}</span
                        >
                        su <span class="font-semibold">{{ stats.total }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('ordini.create')"
                        class="btn btn-primary"
                    >
                        ➕ Nuovo ordine
                    </Link>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto p-4">
            <!-- Search card -->
            <div class="card p-4 mb-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="font-extrabold text-slate-900">🔎 Ricerca</div>
                    <div class="text-xs text-slate-500">Invio = cerca</div>
                </div>

                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-8">
                        <div class="relative">
                            <input
                                v-model="q"
                                @keyup.enter="search"
                                class="input pl-9"
                                placeholder="   Cerca: Nordine, cliente, telefono, città…"
                            />
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-4 flex gap-2">
                        <button
                            class="btn btn-ghost w-full"
                            @click="clearSearch"
                        >
                            Azzera
                        </button>
                        <button class="btn btn-primary w-full" @click="search">
                            Cerca
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table card -->
            <div class="card overflow-hidden">
                <div
                    class="px-4 py-3 border-b bg-slate-50 flex items-center justify-between"
                >
                    <div class="font-extrabold text-slate-900">
                        📄 Elenco ordini
                    </div>
                    <div class="text-xs text-slate-500">
                        Ordina e filtra dalla ricerca (step successivo: sort
                        colonne)
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-white border-b">
                            <tr class="text-slate-600">
                                <th class="text-left px-4 py-3">Nordine</th>
                                <th class="text-left px-4 py-3">Cliente</th>
                                <th class="text-left px-4 py-3">Contatto</th>
                                <th class="text-left px-4 py-3">Città</th>
                                <th class="text-left px-4 py-3">Data</th>
                                <th class="text-right px-4 py-3">Azioni</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="o in ordini.data"
                                :key="o.ID"
                                class="border-t hover:bg-slate-50/70"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-extrabold text-slate-900">
                                        {{ o.Nordine }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <span
                                            class="pill"
                                            :class="
                                                o.TipoDoc === 'Ordine'
                                                    ? 'pill-green'
                                                    : 'pill-blue'
                                            "
                                        >
                                            {{ o.TipoDoc || "—" }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">
                                        {{ o.CognomeNome || "—" }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ o.Indirizzo || "" }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="text-slate-900">
                                        {{ o.Telefono || o.Cellulare || "—" }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ o.Email || "" }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-slate-900">
                                    {{ o.IdCitta || "—" }}
                                </td>

                                <td class="px-4 py-3 text-slate-900">
                                    {{ fmtDate(o.DataOrdine) }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="route('ordini.edit', o.ID)"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-sm hover:shadow-md hover:from-blue-700 hover:to-blue-600 transition text-sm font-semibold"
                                        >
                                            ✏️ Apri
                                        </Link>

                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            @click="destroy(o.ID)"
                                            :title="
                                                confirmDeleteId === o.ID
                                                    ? 'Conferma eliminazione'
                                                    : 'Elimina ordine'
                                            "
                                        >
                                            {{
                                                confirmDeleteId === o.ID
                                                    ? "⚠️ Conferma"
                                                    : "🗑️ Elimina"
                                            }}
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!ordini.data.length">
                                <td
                                    colspan="8"
                                    class="px-4 py-8 text-center text-slate-500"
                                >
                                    Nessun ordine trovato.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex flex-wrap gap-2 mt-4">
                <Link
                    v-for="l in ordini.links"
                    :key="l.label + (l.url ?? '')"
                    :href="l.url || ''"
                    class="btn btn-ghost btn-sm"
                    :class="{
                        'bg-blue-600 text-white border-blue-600 hover:bg-blue-700':
                            l.active,
                        'opacity-50 pointer-events-none': !l.url,
                    }"
                    v-html="l.label"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ERP mini kit */
.card {
    @apply rounded-2xl border bg-white shadow-sm;
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

.btn {
    @apply px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 transition;
}
.btn-primary {
    @apply px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold shadow
    hover:bg-blue-700 transition disabled:opacity-60 disabled:cursor-not-allowed;
}
.btn-ghost {
    @apply px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 transition;
}
.btn-sm {
    @apply px-3 py-1.5 rounded-lg text-sm;
}

.input {
    @apply w-full rounded-xl border border-slate-200 bg-white px-3 py-2
    text-slate-900 outline-none shadow-sm
    focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition;
}

.pill {
    @apply inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-extrabold border;
}
.pill-blue {
    @apply bg-blue-50 text-blue-700 border-blue-100;
}
.pill-green {
    @apply bg-emerald-50 text-emerald-700 border-emerald-100;
}
.btn-danger {
    @apply px-3 py-2 rounded-xl
    bg-red-600 text-white font-semibold
    shadow hover:bg-red-700 transition
    disabled:opacity-60 disabled:cursor-not-allowed;
}
</style>
