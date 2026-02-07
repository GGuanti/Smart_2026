<script setup>
import { ref, computed } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link, usePage } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);
const page = usePage();

const profilo = computed(() => page.props?.auth?.user?.profilo ?? "");
const userName = computed(() => page.props?.auth?.user?.name ?? "");

const isAdmin = computed(() => profilo.value === "admin");
const isUser = computed(() => profilo.value === "user" || isAdmin.value);
const isIsomax = computed(() => profilo.value === "Isomax" || isAdmin.value);
const isNurith = computed(() => profilo.value === "Nurith" || isAdmin.value);

const badgeClass = computed(() => {
    const p = profilo.value;
    if (p === "admin") return "bg-red-50 text-red-700 ring-red-200";
    if (p === "Isomax") return "bg-indigo-50 text-indigo-700 ring-indigo-200";
    if (p === "Nurith")
        return "bg-emerald-50 text-emerald-700 ring-emerald-200";
    return "bg-slate-50 text-slate-700 ring-slate-200";
});

const pillClass =
    "inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold " +
    "transition shadow-sm ring-1 ring-transparent " +
    "hover:shadow-md hover:-translate-y-[1px] active:translate-y-0 " +
    "focus:outline-none focus:ring-4 focus:ring-indigo-100";

const triggerBase =
    "inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold " +
    "transition shadow-sm ring-1 ring-slate-200 bg-white text-slate-700 " +
    "hover:bg-slate-50 hover:shadow-md";

const caretSvg = `<svg class="h-4 w-4 opacity-70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4" />
     </svg>`;
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100"
    >
        <!-- NAV -->
        <nav class="sticky top-0 z-40 w-full">
            <!-- bordo + blur -->
            <div class="border-b border-slate-200/70 bg-white/70 backdrop-blur">
                <div class="w-full px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">
                        <!-- LEFT -->
                        <div class="flex items-center gap-3">
                            <!-- Logo + Brand -->
                            <Link
                                :href="route('dashboard')"
                                class="flex items-center gap-3 group"
                            >
                                <div
                                    class="h-10 w-10 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 p-2 shadow-md"
                                >
                                    <ApplicationLogo
                                        class="h-6 w-6 text-white"
                                    />
                                </div>
                                <div
                                    v-if="
                                        $page.props.auth.user.profilo ===
                                        'admin'
                                    "
                                    class="hidden sm:block leading-tight"
                                >
                                    <div
                                        class="font-extrabold text-slate-900 group-hover:text-indigo-700 transition"
                                    >
                                        Smart 2026
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Gestionale • Ordini • Calendario •
                                        Report
                                    </div>
                                </div>
                            </Link>

                            <!-- MENU DESKTOP -->
                            <div
                                class="hidden sm:flex items-center gap-2 sm:ms-6"
                            >
                                <!-- ADMIN -->
                                <template v-if="isAdmin">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="vbtn"
                                                :class="triggerBase"
                                                v-html="`📊 Report ${caretSvg}`"
                                            ></button>
                                        </template>
                                        <template #content>
                                            <DropdownLink
                                                :href="route('report.index')"
                                                >📑 Report</DropdownLink
                                            >
                                        </template>
                                    </Dropdown>

                                    <Dropdown align="left" width="64">
                                        <template #trigger>
                                            <button
                                                class="vbtn"
                                                :class="triggerBase"
                                                v-html="
                                                    `🛠️ Amministrazione ${caretSvg}`
                                                "
                                            ></button>
                                        </template>
                                        <template #content>
                                            <DropdownLink
                                                :href="route('users.index')"
                                                >👥 Utenti</DropdownLink
                                            >

                                            <DropdownLink
                                                :href="
                                                    route('anagrafica.index', {
                                                        tipoU: 'U',
                                                    })
                                                "
                                                >👤 User</DropdownLink
                                            >

                                            <DropdownLink
                                                :href="
                                                    route('anagrafica.index', {
                                                        tipoU: 'C',
                                                    })
                                                "
                                                >🏢 Committenti</DropdownLink
                                            >

                                            <DropdownLink
                                                :href="
                                                    route('anagrafica.index', {
                                                        tipoU: 'F',
                                                    })
                                                "
                                                >🏭 Fornitori</DropdownLink
                                            >

                                            <DropdownLink
                                                :href="route('articoli.index')"
                                                >📄 Elenco
                                                Articoli</DropdownLink
                                            >
                                            <DropdownLink
                                                :href="
                                                    route(
                                                        'appointments.calendar',
                                                    )
                                                "
                                                >📅 Calendario</DropdownLink
                                            >

                                            <!-- NB: avevi Giornate che puntava al calendario: lascio uguale -->
                                            <DropdownLink
                                                :href="
                                                    route(
                                                        'appointments.calendar',
                                                    )
                                                "
                                                >🗓️ Giornate</DropdownLink
                                            >
                                        </template>
                                    </Dropdown>
                                </template>

                                <!-- USER -->
                                <template v-if="isUser">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="vbtn"
                                                :class="triggerBase"
                                                v-html="`👤 Utente ${caretSvg}`"
                                            ></button>
                                        </template>
                                        <template #content>
                                            <DropdownLink
                                                :href="
                                                    route(
                                                        'appointments.calendar',
                                                    )
                                                "
                                                >📅 Calendario</DropdownLink
                                            >
                                        </template>
                                    </Dropdown>
                                </template>

                                <!-- ISOMAX -->
                                <template v-if="isIsomax">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="vbtn inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:shadow-md"
                                                :class="'bg-gradient-to-r from-indigo-600 to-blue-600'"
                                                v-html="`🧩 Isomax ${caretSvg}`"
                                            ></button>
                                        </template>
                                        <template #content>
                                            <DropdownLink
                                                :href="route('ordini.index')"
                                                >📄 Ordini</DropdownLink
                                            >
                                            <DropdownLink
                                                v-if="
                                                    $page.props.auth.user
                                                        .profilo === 'admin'
                                                "
                                                :href="route('users.index')"
                                                >👥 Utenti</DropdownLink
                                            >
                                            <DropdownLink
                                                v-if="
                                                    $page.props.auth.user
                                                        .email ===
                                                    'info@isomaxporte.com'
                                                "
                                                :href="route('users.index')"
                                            >
                                                👥 Utenti
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </template>

                                <!-- NURITH -->
                                <template v-if="isNurith">
                                    <Dropdown align="left" width="56">
                                        <template #trigger>
                                            <button
                                                class="vbtn inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:shadow-md"
                                                :class="'bg-gradient-to-r from-emerald-600 to-teal-600'"
                                                v-html="`🌿 Nurith ${caretSvg}`"
                                            ></button>
                                        </template>
                                        <template #content>
                                            <DropdownLink
                                                :href="
                                                    route(
                                                        'appointments.calendar',
                                                    )
                                                "
                                                >📅 Calendario</DropdownLink
                                            >
                                        </template>
                                    </Dropdown>
                                </template>
                            </div>
                        </div>

                        <!-- RIGHT -->
                        <div class="hidden sm:flex items-center gap-3">
                            <!-- Badge profilo -->
                            <span
                                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold ring-1"
                                :class="badgeClass"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-current opacity-60"
                                ></span>
                                {{ profilo || "Profilo" }}
                            </span>

                            <!-- Dropdown profilo -->
                            <Dropdown align="right" width="56">
                                <template #trigger>
                                    <button
                                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold bg-white text-slate-700 ring-1 ring-slate-200 shadow-sm hover:bg-slate-50 hover:shadow-md transition"
                                    >
                                        <span class="truncate max-w-[180px]">{{
                                            userName
                                        }}</span>
                                        <svg
                                            class="h-4 w-4 opacity-70"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink
                                        v-if="isAdmin"
                                        :href="route('profile.edit')"
                                        >⚙️ Profilo</DropdownLink
                                    >

                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        >🚪 Logout</DropdownLink
                                    >
                                </template>
                            </Dropdown>
                        </div>

                        <!-- MOBILE HAMBURGER -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-xl p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-800 transition"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MOBILE MENU -->
                <div
                    v-show="showingNavigationDropdown"
                    class="sm:hidden border-t border-slate-200/70 bg-white/80 backdrop-blur"
                >
                    <div class="px-4 py-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <div
                                class="text-sm font-bold text-slate-800 truncate"
                            >
                                {{ userName }}
                            </div>
                            <span
                                class="text-xs font-bold rounded-full px-2 py-1 ring-1"
                                :class="badgeClass"
                            >
                                {{ profilo || "Profilo" }}
                            </span>
                        </div>

                        <!-- Link rapidi mobile (essenziali) -->
                        <div class="grid grid-cols-2 gap-2">
                            <ResponsiveNavLink
                                v-if="isIsomax"
                                :href="route('ordini.index')"
                                class="rounded-xl bg-slate-50"
                            >
                                📄 Ordini
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('appointments.calendar')"
                                class="rounded-xl bg-slate-50"
                            >
                                📅 Calendario
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="isAdmin"
                                :href="route('users.index')"
                                class="rounded-xl bg-slate-50"
                            >
                                👥 Utenti
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="isAdmin"
                                :href="route('report.index')"
                                class="rounded-xl bg-slate-50"
                            >
                                📑 Report
                            </ResponsiveNavLink>
                        </div>

                        <div class="pt-2 border-t border-slate-200/70">
                            <ResponsiveNavLink
                                v-if="isAdmin"
                                :href="route('profile.edit')"
                            >
                                ⚙️ Profilo
                            </ResponsiveNavLink>

                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                🚪 Logout
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Header -->
        <header
            class="bg-white/70 backdrop-blur shadow-sm border-b border-slate-200/70"
            v-if="$slots.header"
        >
            <div class="w-full py-5 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Main -->
        <main class="w-full px-0 py-0">
            <slot />
        </main>
    </div>
</template>
